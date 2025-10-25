<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Behavioral;

use Fsm\Data\TransitionInput;
use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\FsmBuilder;
use Fsm\Models\FsmLog;
use Illuminate\Support\Facades\Config;
use Tests\Feature\Fsm\Data\OrderProcessContext;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class BehavioralTestGuidelines extends FsmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->registerOrderStatusFsm();
    }

    public function test_focus_on_public_api_when_driving_transitions(): void
    {
        Config::set('fsm.logging.excluded_context_properties', ['triggerFailure']);

        $order = TestModel::factory()->create(['status' => TestFeatureState::Pending->value]);

        $updated = $order->fsm()->trigger('process_order', new OrderProcessContext(
            message: 'Gateway approved charge',
            actorId: 99,
            metadata: ['source' => 'checkout_widget'],
            approvalCode: 'OK-991'
        ));

        $this->assertSame(TestFeatureState::Processing->value, $updated->status);

        $log = FsmLog::query()
            ->where('model_id', $order->getKey())
            ->where('fsm_column', 'status')
            ->where('to_state', TestFeatureState::Processing->value)
            ->latest('happened_at')
            ->firstOrFail();

        $this->assertSame('checkout_widget', $log->context_snapshot['metadata']['source'] ?? null);
    }

    public function test_assert_on_observable_outcomes_for_failures(): void
    {
        Config::set('fsm.logging.log_failures', true);

        $order = TestModel::factory()->create(['status' => TestFeatureState::Pending->value]);

        try {
            $order->transitionFsm('status', TestFeatureState::Cancelled, new OrderProcessContext(
                message: 'Customer support override',
                actorId: 501,
                metadata: ['ticket' => 'CASE-445'], // ensures realistic payload
            ));
            $this->fail('Guard-protected transition must throw when conditions fail.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertSame(TestFeatureState::Pending->value, $order->fresh()->status);

            $log = FsmLog::query()
                ->where('model_id', $order->getKey())
                ->where('fsm_column', 'status')
                ->where('to_state', TestFeatureState::Cancelled->value)
                ->latest('happened_at')
                ->firstOrFail();

            $this->assertSame('Customer support override', $log->context_snapshot['message'] ?? null);
            $this->assertSame('CASE-445', $log->context_snapshot['metadata']['ticket'] ?? null);
            $this->assertStringContainsString('Guard', (string) $log->exception_details);

        }
    }

    public function test_realistic_context_enriches_cross_column_actions(): void
    {
        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->state(TestFeatureState::Pending)
            ->state(TestFeatureState::Shipped)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Shipped)
            ->event('ship_order')
            ->action(function (TransitionInput $input): void {
                $input->model->forceFill(['status' => TestFeatureState::Active->value])->save();
            })
            ->build();

        $order = TestModel::factory()->create([
            'status' => TestFeatureState::Pending->value,
            'secondary_status' => TestFeatureState::Pending->value,
        ]);

        $context = new OrderProcessContext(
            message: 'Handed to carrier',
            actorId: 16,
            metadata: [
                'tracking_number' => '1Z'.random_int(1000000, 9999999),
                'carrier' => 'UPS',
            ],
        );

        $order->fsm('secondary_status')->trigger('ship_order', $context);

        $fresh = $order->fresh();
        $this->assertSame(TestFeatureState::Shipped->value, $fresh->secondary_status);
        $this->assertSame(TestFeatureState::Active->value, $fresh->status);

        $log = FsmLog::query()
            ->where('model_id', $order->getKey())
            ->where('fsm_column', 'secondary_status')
            ->where('to_state', TestFeatureState::Shipped->value)
            ->latest('happened_at')
            ->firstOrFail();

        $this->assertSame('UPS', $log->context_snapshot['metadata']['carrier'] ?? null);
        $this->assertMatchesRegularExpression('/^1Z[0-9]{7}$/', (string) ($log->context_snapshot['metadata']['tracking_number'] ?? ''));
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
            ->guard(fn (TransitionInput $input): bool => true, description: 'Processing guard')
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
