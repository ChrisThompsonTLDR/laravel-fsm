<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm;

use Fsm\Constants;
use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\FsmBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class FluentApiTest extends FsmTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset FSM definitions to avoid conflicts with parent setUp
        FsmBuilder::reset();

        // Setup FSM with multiple transitions sharing the same event name
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)->event('submit')
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Processing)->event('approve')
            ->from(TestFeatureState::Processing)->to(TestFeatureState::Completed)->event('complete')
            // This creates an ambiguity: "approve" can go to different states from different origins
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Cancelled)->event('cancel')
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Cancelled)->event('cancel')
            ->from(TestFeatureState::Processing)->to(TestFeatureState::Failed)->event('fail')
            ->transition()
            ->from(Constants::STATE_WILDCARD)
            ->to(TestFeatureState::Failed)
            ->event('force_fail')
            ->add()
            ->build();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_fluent_trigger_respects_from_state_for_same_event_name(): void
    {
        // Create model in Idle state
        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        // Trigger "cancel" from Idle state - should go to Cancelled
        $result = $model->fsm()->trigger('cancel');

        $this->assertEquals(TestFeatureState::Cancelled->value, $result->status);

        // Now create another model in Pending state
        $model2 = TestModel::factory()->create(['status' => TestFeatureState::Pending->value]);

        // Trigger "cancel" from Pending state - should also go to Cancelled
        $result2 = $model2->fsm()->trigger('cancel');

        $this->assertEquals(TestFeatureState::Cancelled->value, $result2->status);
    }

    public function test_fluent_can_respects_from_state_for_same_event_name(): void
    {
        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        // From Idle, "cancel" should be possible
        $this->assertTrue($model->fsm()->can('cancel'));

        // From Idle, "fail" should NOT be possible (only from Processing)
        $this->assertFalse($model->fsm()->can('fail'));

        // Transition to Pending
        $model->transitionFsm('status', TestFeatureState::Pending);

        // From Pending, "cancel" should still be possible
        $this->assertTrue($model->fsm()->can('cancel'));

        // Transition to Processing
        $model->transitionFsm('status', TestFeatureState::Processing);

        // From Processing, "fail" should now be possible
        $this->assertTrue($model->fsm()->can('fail'));

        // From Processing, "cancel" should NOT be possible
        $this->assertFalse($model->fsm()->can('cancel'));
    }

    public function test_fluent_dry_run_respects_from_state(): void
    {
        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        $result = $model->fsm()->dryRun('cancel');

        $this->assertTrue($result['can_transition']);
        $this->assertEquals(TestFeatureState::Idle->value, $result['from_state']);
        $this->assertEquals(TestFeatureState::Cancelled->value, $result['to_state']);

        // Try with an event that doesn't exist from this state
        $result = $model->fsm()->dryRun('fail');

        $this->assertFalse($result['can_transition']);
    }

    public function test_fluent_trigger_with_nonexistent_event_from_current_state_fails(): void
    {
        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        // "fail" event only exists from Processing state
        $this->expectException(FsmTransitionFailedException::class);
        $model->fsm()->trigger('fail');
    }

    public function test_fluent_trigger_with_state_name_fallback_still_works(): void
    {
        // When no event matches, it should fall back to treating the parameter as a state name
        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        // Use state name directly (no event with this name exists)
        $result = $model->fsm()->trigger(TestFeatureState::Pending->value);

        $this->assertEquals(TestFeatureState::Pending->value, $result->status);
    }

    public function test_fluent_trigger_uses_wildcard_transition_when_no_specific_match_exists(): void
    {
        $model = TestModel::factory()->create(['status' => TestFeatureState::Processing->value]);

        $result = $model->fsm()->trigger('force_fail');

        $this->assertEquals(TestFeatureState::Failed->value, $result->status);

        $modelStartingIdle = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);
        $modelStartingIdle->fsm()->trigger('force_fail');

        $this->assertEquals(TestFeatureState::Failed->value, $modelStartingIdle->status);
    }

    public function test_fluent_can_and_dry_run_respect_wildcard_transitions(): void
    {
        $model = TestModel::factory()->create(['status' => TestFeatureState::Cancelled->value]);

        $this->assertTrue($model->fsm()->can('force_fail'));

        $dryRun = $model->fsm()->dryRun('force_fail');

        $this->assertTrue($dryRun['can_transition']);
        $this->assertSame(TestFeatureState::Cancelled->value, $dryRun['from_state']);
        $this->assertSame(TestFeatureState::Failed->value, $dryRun['to_state']);
    }
}
