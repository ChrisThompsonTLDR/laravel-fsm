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

class CallbackMutationTests extends FsmTestCase
{
    public function test_on_transition_callback_receives_context_and_parameters(): void
    {
        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->after(
                TestSpyService::class.'@onTransitionCallback',
                ['params' => ['message' => 'expected payload']]
            )
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);
        $context = new TestContextData('context payload');

        $model->transitionFsm('secondary_status', TestFeatureState::Processing, $context);

        $recorded = null;
        foreach (TestSpyService::$staticCalled as $call) {
            if (($call['method'] ?? null) === 'onTransitionCallback') {
                $recorded = $call;
                break;
            }
        }

        $this->assertNotNull($recorded, 'Callback should record invocation via TestSpyService.');
        $this->assertSame('expected payload', $recorded['params']['message'] ?? null);
        $this->assertSame('context payload', $recorded['context']->message ?? null);
    }

    public function test_queued_callbacks_reject_closures(): void
    {
        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->after(
                function (TransitionInput $input): void {
                    // no-op
                },
                queued: true
            )
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);

        try {
            $model->transitionFsm('secondary_status', TestFeatureState::Processing);
            $this->fail('Queued callbacks using closures should throw a wrapped exception.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertStringContainsString(
                'Queued callbacks cannot use closures',
                $exception->getMessage(),
                'Exception message should surface the queue restriction.'
            );
        }
    }

    public function test_callback_failures_are_reported_with_original_message(): void
    {
        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->after(
                TestSpyService::class.'@onTransitionCallback',
                ['params' => ['message' => 'fail_on_transition']]
            )
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);
        $context = new TestContextData('context', triggerFailure: true);

        try {
            $model->transitionFsm('secondary_status', TestFeatureState::Processing, $context);
            $this->fail('Transition should fail when on-transition callback throws.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertStringContainsString(
                "Exception during 'onTransition (after)'",
                $exception->getMessage()
            );
            $this->assertSame(
                'Simulated onTransition failure',
                $exception->getOriginalException()?->getMessage(),
                'Original callback exception should be preserved.'
            );
        }
    }
}
