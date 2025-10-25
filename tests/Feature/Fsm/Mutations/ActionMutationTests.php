<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Mutations;

use Fsm\Data\TransitionInput;
use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\FsmBuilder;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\Feature\Fsm\Services\TestSpyService;
use Tests\FsmTestCase;

class ActionMutationTests extends FsmTestCase
{
    public function test_non_deferred_actions_execute_before_state_change(): void
    {
        $observedStates = [];

        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->action(
                function (TransitionInput $input) use (&$observedStates): void {
                    $observedStates[] = $input->model->getAttribute('secondary_status');
                }
            )
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);

        $model->transitionFsm('secondary_status', TestFeatureState::Processing);

        $this->assertSame(
            ['pending'],
            $observedStates,
            'Immediate actions should observe the original state before persistence.'
        );
        $this->assertSame(
            TestFeatureState::Processing->value,
            $model->fresh()->secondary_status,
            'Model should persist the new state after the transition completes.'
        );
    }

    public function test_deferred_actions_run_after_state_change(): void
    {
        $observedStates = [];

        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->action(
                function (TransitionInput $input) use (&$observedStates): void {
                    $observedStates[] = $input->model->getAttribute('secondary_status');
                },
                runAfterTransition: true
            )
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);

        $model->transitionFsm('secondary_status', TestFeatureState::Processing);

        $this->assertSame(
            ['processing'],
            $observedStates,
            'Deferred actions should run after the state has been updated.'
        );
    }

    public function test_action_exceptions_are_wrapped_and_surface_original_error(): void
    {
        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->action(
                TestSpyService::class.'@anAction',
                runAfterTransition: true
            )
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);
        $context = new TestContextData('trigger failure', triggerFailure: true);

        try {
            $model->transitionFsm('secondary_status', TestFeatureState::Processing, $context);
            $this->fail('Transition should fail when a deferred action throws an exception.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertStringContainsString(
                "Exception during 'action (after)'",
                $exception->getMessage(),
                'Exception message should describe which action stage failed.'
            );
            $this->assertSame(
                'Simulated action failure',
                $exception->getOriginalException()?->getMessage(),
                'Original action exception should be preserved for debugging.'
            );
        }
    }
}
