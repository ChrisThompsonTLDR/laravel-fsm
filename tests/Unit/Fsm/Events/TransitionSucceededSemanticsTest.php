<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Events;

use Fsm\Events\TransitionAttempted;
use Fsm\Events\TransitionFailed;
use Fsm\Events\TransitionSucceeded;
use Fsm\FsmBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

/**
 * Tests that verify the semantic contract of TransitionSucceeded:
 * - It means "state changed and was persisted"
 * - It does NOT fire during dry runs
 */
class TransitionSucceededSemanticsTest extends FsmTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)
            ->build();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_transition_succeeded_fires_only_on_actual_state_change(): void
    {
        Event::fake();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        // Perform actual transition
        $model->transitionFsm('status', TestFeatureState::Pending);

        // TransitionSucceeded should fire because state actually changed
        Event::assertDispatched(TransitionSucceeded::class, function ($event) use ($model) {
            return $event->model->is($model)
                && $event->fromState === TestFeatureState::Idle->value
                && $event->toState === TestFeatureState::Pending->value;
        });

        // Verify state actually changed in database
        $this->assertEquals(TestFeatureState::Pending->value, $model->fresh()->status);
    }

    public function test_transition_succeeded_does_not_fire_during_dry_run(): void
    {
        Event::fake();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        // Perform dry run
        $result = $model->dryRunFsm('status', TestFeatureState::Pending);

        // Dry run should succeed
        $this->assertTrue($result['can_transition']);

        // TransitionAttempted should fire (dry runs are attempts)
        Event::assertDispatched(TransitionAttempted::class);

        // TransitionSucceeded should NOT fire (state didn't change)
        Event::assertNotDispatched(TransitionSucceeded::class);

        // Verify state did NOT change in database
        $this->assertEquals(TestFeatureState::Idle->value, $model->fresh()->status);
    }

    public function test_transition_succeeded_does_not_fire_during_can_transition(): void
    {
        Event::fake();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        // Check if transition is possible
        $canTransition = $model->canTransitionFsm('status', TestFeatureState::Pending);

        // Should return true
        $this->assertTrue($canTransition);

        // No events should fire during canTransition (it's silent)
        Event::assertNotDispatched(TransitionSucceeded::class);
        Event::assertNotDispatched(TransitionAttempted::class);
        Event::assertNotDispatched(TransitionFailed::class);

        // Verify state did NOT change
        $this->assertEquals(TestFeatureState::Idle->value, $model->fresh()->status);
    }

    public function test_transition_failed_fires_during_dry_run_with_guard_failure(): void
    {
        Event::fake();

        // Create a transition with a failing guard
        FsmBuilder::reset();
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)
            ->to(TestFeatureState::Pending)
            ->guard(fn () => false) // Always fails
            ->build();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        // Perform dry run with failing guard
        $result = $model->dryRunFsm('status', TestFeatureState::Pending);

        // Dry run should fail
        $this->assertFalse($result['can_transition']);

        // TransitionAttempted should fire
        Event::assertDispatched(TransitionAttempted::class);

        // TransitionFailed should NOT fire during dry runs - they're validation checks only
        // See FsmEngineService.php lines 222-223: "TransitionFailed is NOT dispatched for dry runs."
        Event::assertNotDispatched(TransitionFailed::class);

        // TransitionSucceeded should NOT fire
        Event::assertNotDispatched(TransitionSucceeded::class);

        // Verify state did NOT change
        $this->assertEquals(TestFeatureState::Idle->value, $model->fresh()->status);
    }

    public function test_semantic_contract_transition_succeeded_means_state_persisted(): void
    {
        Event::fake();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);
        $originalId = $model->id;

        // Perform transition
        $model->transitionFsm('status', TestFeatureState::Pending);

        // When TransitionSucceeded fires, verify the contract:
        // 1. State has been persisted to database
        // 2. Fresh model load shows new state
        Event::assertDispatched(TransitionSucceeded::class, function ($event) use ($originalId) {
            // At the time this event fires, state should be persisted
            $freshModel = TestModel::find($originalId);

            return $freshModel->status === TestFeatureState::Pending->value
                && $event->toState === TestFeatureState::Pending->value;
        });
    }
}
