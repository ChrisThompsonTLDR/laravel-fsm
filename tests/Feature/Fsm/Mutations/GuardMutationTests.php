<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Mutations;

use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\FsmBuilder;
use RuntimeException;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class GuardMutationTests extends FsmTestCase
{
    public function test_guard_condition_must_return_strict_true_to_pass(): void
    {
        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->guard(function (): string {
                return 'true';
            })
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);

        try {
            $model->transitionFsm('secondary_status', TestFeatureState::Processing);
            $this->fail('Transition should have been blocked when guard returns non-boolean truthy value.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertStringContainsString(
                'Guard [Closure Guard] failed',
                $exception->getMessage(),
                'Guard failure message should mention the closure guard.'
            );
        }
    }

    public function test_stop_on_failure_guard_short_circuits_follow_up_guards(): void
    {
        $secondaryGuardRan = false;

        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->criticalGuard(function (): bool {
                return false;
            }, description: 'critical short circuit')
            ->guard(function () use (&$secondaryGuardRan): bool {
                $secondaryGuardRan = true;

                return true;
            })
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);

        try {
            $model->transitionFsm('secondary_status', TestFeatureState::Processing);
            $this->fail('Transition should have failed due to critical guard failure.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertFalse(
                $secondaryGuardRan,
                'Guards after a stop-on-failure guard must not execute.'
            );
            $this->assertStringContainsString(
                'Guard [critical short circuit] failed',
                $exception->getMessage(),
                'Exception should reference the critical guard description.'
            );
        }
    }

    public function test_guard_exception_is_wrapped_with_original_exception_retained(): void
    {
        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->criticalGuard(function (): bool {
                throw new RuntimeException('boom guard');
            }, description: 'exceptional guard')
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);

        try {
            $model->transitionFsm('secondary_status', TestFeatureState::Processing);
            $this->fail('Critical guard exception should cause the transition to fail.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertInstanceOf(RuntimeException::class, $exception->getOriginalException());
            $this->assertSame(
                'boom guard',
                $exception->getOriginalException()?->getMessage(),
                'Original guard exception message should be preserved.'
            );
            $this->assertStringContainsString(
                "Exception during 'guard Guard [exceptional guard]'",
                $exception->getMessage(),
                'Wrapped exception message should describe the guard failure.'
            );
        }
    }
}
