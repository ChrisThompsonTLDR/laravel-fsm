<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Behavioral;

use Fsm\Data\TransitionInput;
use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\FsmBuilder;
use Fsm\Models\FsmLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\Feature\Fsm\Data\OrderProcessContext;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\Feature\Fsm\Models\TestUser;
use Tests\FsmTestCase;
use Thunk\Verbs\Contracts\BrokersEvents;
use Thunk\Verbs\Event as VerbsEvent;
use Thunk\Verbs\Facades\Verbs;

class ConvertedUnitTests extends FsmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->registerOrderStatusFsm();
    }

    public function test_transition_audit_record_persists_sanitized_context(): void
    {
        Config::set('fsm.logging.excluded_context_properties', ['triggerFailure', 'metadata.payment.token']);

        $order = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        $order->transitionFsm('status', TestFeatureState::Pending);

        $context = new OrderProcessContext(
            message: 'Processing purchase order',
            actorId: 4021,
            metadata: [
                'payment' => [
                    'card_last4' => '4242',
                    'token' => 'tok_live_123',
                ],
                'channel' => 'web',
            ],
            approvalCode: 'OK-2024'
        );

        $order->transitionFsm('status', TestFeatureState::Processing, $context);

        $order->refresh();
        $this->assertSame(TestFeatureState::Processing->value, $order->status);

        $log = FsmLog::query()
            ->where('model_id', $order->getKey())
            ->where('fsm_column', 'status')
            ->where('to_state', TestFeatureState::Processing->value)
            ->latest('happened_at')
            ->firstOrFail();

        $this->assertSame(TestFeatureState::Pending->value, $log->from_state);
        $this->assertSame(TestFeatureState::Processing->value, $log->to_state);
        $this->assertSame('status', $log->fsm_column);

        $this->assertSame('Processing purchase order', $log->context_snapshot['message'] ?? null);
        $this->assertSame(4021, $log->context_snapshot['actorId'] ?? null);
        $this->assertSame(
            [
                'payment' => ['card_last4' => '4242'],
                'channel' => 'web',
            ],
            $log->context_snapshot['metadata'] ?? []
        );
        $this->assertSame('OK-2024', $log->context_snapshot['approvalCode'] ?? null);
        $this->assertArrayNotHasKey('triggerFailure', $log->context_snapshot);
    }

    public function test_guard_failure_is_logged_and_leaves_state_unchanged(): void
    {
        Config::set('fsm.logging.log_failures', true);

        $order = TestModel::factory()->create(['status' => TestFeatureState::Pending->value]);

        try {
            $order->transitionFsm('status', TestFeatureState::Cancelled);
            $this->fail('Guard failure should throw a transition exception.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertSame(
                TestFeatureState::Pending->value,
                $order->fresh()->status,
                'Failed guard must not mutate the state column.'
            );
            $this->assertStringContainsString('Guard', $exception->getMessage());

            $log = FsmLog::query()
                ->where('model_id', $order->getKey())
                ->where('fsm_column', 'status')
                ->where('to_state', TestFeatureState::Cancelled->value)
                ->latest('happened_at')
                ->first();

            $this->assertNotNull($log, 'Guard failures must be captured in the audit log.');
            $this->assertNull($log->transition_event);
            $this->assertSame(TestFeatureState::Pending->value, $log->from_state);
            $this->assertStringContainsString('failed', (string) $log->exception_details);
            $this->assertSame(TestFeatureState::Cancelled->value, $log->to_state);
        }
    }

    public function test_verbs_state_with_private_subject_information_is_recorded(): void
    {
        $originalBroker = Verbs::getFacadeRoot();

        Config::set('fsm.verbs.log_user_subject', true);
        Config::set('fsm.logging.enabled', true);
        Config::set('auth.providers.users.model', TestUser::class);

        Config::set('fsm.verbs.log_user_subject', true);
        Config::set('auth.providers.users.model', TestUser::class);
        Config::set('fsm.logging.enabled', true);
        Config::set('fsm.logging.log_failures', true);
        Config::set('fsm.logging.channel', 'stack');
        Config::set('fsm.logging.structured', true);

        $subjectId = (string) Str::uuid();

        $broker = new class($subjectId) implements BrokersEvents
        {
            public function __construct(private readonly string $userId) {}

            public function fire(VerbsEvent $event): ?VerbsEvent
            {
                return $event;
            }

            public function commit(): bool
            {
                return true;
            }

            public function isAuthorized(VerbsEvent $event): bool
            {
                return true;
            }

            public function isValid(VerbsEvent $event): bool
            {
                return true;
            }

            public function replay(?callable $beforeEach = null, ?callable $afterEach = null): void {}

            public function state(): object
            {
                return new class($this->userId)
                {
                    private string $user_id;

                    public function __construct(string $userId)
                    {
                        $this->user_id = $userId;
                    }
                };
            }
        };

        $this->app->instance(BrokersEvents::class, $broker);
        Verbs::swap($broker);

        $channel = new class
        {
            /** @var array<int, array<string, mixed>> */
            public array $records = [];

            public function error(string $message, array $context = []): void
            {
                $this->records[] = $context;
            }
        };

        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturn($channel);

        $logger = $this->app->make(\Fsm\Services\FsmLogger::class);
        $subjectInspector = new \ReflectionMethod($logger, 'subjectFromVerbs');
        $subjectInspector->setAccessible(true);
        $this->assertSame(
            [
                'subject_id' => $subjectId,
                'subject_type' => TestUser::class,
            ],
            $subjectInspector->invoke($logger)
        );
        $order = TestModel::factory()->create(['status' => TestFeatureState::Pending->value]);

        $logger->logFailure(
            $order,
            'status',
            TestFeatureState::Pending,
            TestFeatureState::Cancelled,
            'manual_cancel',
            null,
            new \RuntimeException('guard failure')
        );

        $log = FsmLog::query()
            ->where('model_id', $order->getKey())
            ->where('fsm_column', 'status')
            ->where('to_state', TestFeatureState::Cancelled->value)
            ->latest('happened_at')
            ->firstOrFail();

        $this->assertSame($subjectId, $log->subject_id);
        $this->assertSame(TestUser::class, $log->subject_type);
        $this->assertSame($subjectId, $channel->records[0]['subject_id'] ?? null);
        $this->assertSame(TestUser::class, $channel->records[0]['subject_type'] ?? null);

        $this->app->instance(BrokersEvents::class, $originalBroker);
        Verbs::swap($originalBroker);
    }

    protected function registerOrderStatusFsm(): void
    {
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->state(TestFeatureState::Idle)
            ->state(TestFeatureState::Pending)
            ->state(TestFeatureState::Processing)
            ->state(TestFeatureState::Cancelled)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Processing)
            ->event('process_order')
            ->guard(fn (TransitionInput $input): bool => true, description: 'Automatic approval guard')
            ->action(function (TransitionInput $input): void {
                if ($input->context?->triggerFailure ?? false) {
                    throw new \RuntimeException('Simulated action failure');
                }
            })
            ->after(function (TransitionInput $input): void {
                if ($input->context?->metadata['should_fail_after'] ?? false) {
                    throw new \RuntimeException('Simulated after-transition failure');
                }
            })
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Cancelled)
            ->guard(fn (TransitionInput $input): bool => false, description: 'Manual cancellation guard')
            ->build();
    }
}
