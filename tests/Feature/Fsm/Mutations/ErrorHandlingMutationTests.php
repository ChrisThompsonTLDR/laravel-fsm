<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Mutations;

use Fsm\Events\TransitionFailed;
use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\FsmBuilder;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class ErrorHandlingMutationTests extends FsmTestCase
{
    public function test_concurrent_modification_detected_and_reports_failure(): void
    {
        Event::fake();

        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);

        TestModel::query()
            ->whereKey($model->getKey())
            ->update(['secondary_status' => TestFeatureState::Completed->value]);

        try {
            $model->transitionFsm('secondary_status', TestFeatureState::Processing);
            $this->fail('Concurrent modification should cause the transition to fail.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertStringContainsString(
                'Concurrent modification detected',
                $exception->getMessage(),
                'Exception message should highlight the concurrency issue.'
            );
        }

        Event::assertDispatched(TransitionFailed::class);
    }

    public function test_invalid_transition_surfaces_descriptive_error(): void
    {
        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Processing->value]);

        try {
            $model->transitionFsm('secondary_status', TestFeatureState::Completed);
            $this->fail('Transition without definition should throw an invalid transition exception.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertStringContainsString(
                "No defined transition from 'processing' to 'completed'",
                $exception->getMessage()
            );
        }
    }

    public function test_multiple_guard_failures_are_collated_in_exception_message(): void
    {
        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->guard(
                function (): bool {
                    return false;
                },
                description: 'first guard'
            )
            ->guard(
                function (): bool {
                    return false;
                },
                description: 'second guard'
            )
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);

        try {
            $model->transitionFsm('secondary_status', TestFeatureState::Processing);
            $this->fail('Transition should fail when multiple guards are unsatisfied.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertStringContainsString(
                'Multiple guards failed',
                $exception->getMessage()
            );
            $this->assertStringContainsString('first guard', $exception->getMessage());
            $this->assertStringContainsString('second guard', $exception->getMessage());
        }
    }
}
