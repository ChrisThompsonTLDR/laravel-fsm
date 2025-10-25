<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Behavioral;

use Fsm\Data\TransitionInput;
use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\FsmBuilder;
use Fsm\Models\FsmLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\Feature\Fsm\Data\OrderProcessContext;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class ImplementationVsBehaviorComparison extends FsmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->registerOrderStatusFsm();
    }

    /**
     * Legacy tests only asserted that logToChannel existed. This behavioral test verifies the
     * actual log payload, ensuring a future mutation that drops key fields fails loudly.
     */
    public function test_success_transition_emits_audit_message_with_key_fields(): void
    {
        Config::set('fsm.logging.channel', 'fsm-audit');
        Config::set('fsm.logging.structured', false);

        $logger = new class
        {
            /** @var array<int, array<string, mixed>> */
            public array $messages = [];

            public function error(string $message, array $context = []): void
            {
                $this->messages[] = ['message' => $message, 'context' => $context];
            }
        };

        Log::shouldReceive('channel')
            ->once()
            ->with('fsm-audit')
            ->andReturn($logger);

        $order = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        $order->transitionFsm('status', TestFeatureState::Pending);

        $this->assertNotEmpty($logger->messages, 'FSM transition should produce a log entry via configured channel.');
        $payload = $logger->messages[0]['message'] ?? '';

        $this->assertStringContainsString('FSM transition failed', $payload);
        $this->assertStringContainsString('model_type='.TestModel::class, $payload);
        $this->assertStringContainsString('fsm_column=status', $payload);
        $this->assertStringContainsString('from_state=idle', $payload);
        $this->assertStringContainsString('to_state=pending', $payload);
    }

    /**
     * Old tests used assertTrue(true) after triggering logFailure. This version
     * verifies that failure logs retain business diagnostics and respect the configured truncation.
     */
    public function test_failure_log_truncates_exception_details_but_keeps_root_message(): void
    {
        Config::set('fsm.logging.log_failures', true);
        Config::set('fsm.logging.exception_character_limit', 60);
        Config::set('fsm.logging.excluded_context_properties', ['metadata.notes']);

        $order = TestModel::factory()->create(['status' => TestFeatureState::Pending->value]);
        $veryLongReason = str_repeat('Guard rejected due to missing compliance approval. ', 3);

        try {
            $order->transitionFsm('status', TestFeatureState::Cancelled, new OrderProcessContext(
                message: 'Attempting manual cancellation',
                actorId: 77,
                metadata: ['notes' => $veryLongReason],
                approvalCode: null
            ));
            $this->fail('Transition should throw while the guard denies the move.');
        } catch (FsmTransitionFailedException $exception) {
            $log = FsmLog::query()
                ->where('model_id', $order->getKey())
                ->where('fsm_column', 'status')
                ->where('to_state', TestFeatureState::Cancelled->value)
                ->latest('happened_at')
                ->firstOrFail();

            $this->assertLessThanOrEqual(63, mb_strlen((string) $log->exception_details));
            $this->assertStringEndsWith('...', (string) $log->exception_details);
            $this->assertStringContainsString('Guard', (string) $log->exception_details);
            $this->assertSame(TestFeatureState::Pending->value, $log->from_state);
            $this->assertArrayNotHasKey('notes', $log->context_snapshot['metadata'] ?? [], 'Context should not stash verbose guard messages by default.');
        }
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
            ->guard(fn (TransitionInput $input): bool => true, description: 'Process order guard')
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
