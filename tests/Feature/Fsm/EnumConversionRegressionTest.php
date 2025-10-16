<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm;

use Fsm\Contracts\FsmStateEnum;
use Fsm\FsmBuilder;
use Fsm\FsmRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Enums\WorkflowState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;
use Tests\FsmTestCase;

/**
 * Regression test suite to prevent the hard-coded enum type bug from reoccurring.
 *
 * Previously, HasFsm::mapEvent() hard-coded a check for \Modules\Combat\Enums\UnitState,
 * which caused a fatal "Object could not be converted to string" error for any other
 * enum type. This test suite validates that the fix works with multiple different
 * enum types implementing FsmStateEnum.
 *
 * @see https://github.com/your-repo/issues/xxx (reference the issue if tracked)
 */
class EnumConversionRegressionTest extends FsmTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset builder and registry to allow tests to define FSMs inline
        FsmBuilder::reset();

        // Create a fresh FsmRegistry instance
        $this->app->singleton(FsmRegistry::class, function ($app) {
            return new FsmRegistry(
                $app->make(\Fsm\Services\BootstrapDetector::class),
                $app->make(\Illuminate\Contracts\Config\Repository::class)
            );
        });
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    /**
     * Test that fsm()->trigger() works with TestFeatureState enum.
     * This validates the generic enum conversion.
     */
    public function test_trigger_converts_test_feature_state_enum_correctly(): void
    {
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)->event('submit')
            ->build();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        // This should NOT throw "Object could not be converted to string" error
        $result = $model->fsm()->trigger('submit');

        $this->assertInstanceOf(TestModel::class, $result);
        $this->assertEquals(TestFeatureState::Pending->value, $result->status);
    }

    /**
     * Test that fsm()->trigger() works with TrafficLightState enum.
     * This validates the fix works with a different enum type.
     */
    public function test_trigger_converts_traffic_light_state_enum_correctly(): void
    {
        FsmBuilder::for(TrafficLight::class, 'state')
            ->initialState(TrafficLightState::Red)
            ->from(TrafficLightState::Red)->to(TrafficLightState::Green)->event('change')
            ->build();

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        // This should NOT throw "Object could not be converted to string" error
        $result = $light->fsm('state')->trigger('change');

        $this->assertInstanceOf(TrafficLight::class, $result);
        $this->assertEquals(TrafficLightState::Green, $result->state);
    }

    /**
     * Test that fsm()->trigger() works with WorkflowState enum.
     * This validates the fix works with yet another enum type.
     */
    public function test_trigger_converts_workflow_state_enum_correctly(): void
    {
        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(WorkflowState::Draft)
            ->from(WorkflowState::Draft)->to(WorkflowState::UnderReview)->event('submit_for_review')
            ->from(WorkflowState::UnderReview)->to(WorkflowState::Approved)->event('approve')
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => WorkflowState::Draft->value]);

        // This should NOT throw "Object could not be converted to string" error
        $result = $model->fsm('secondary_status')->trigger('submit_for_review');

        $this->assertInstanceOf(TestModel::class, $result);
        $this->assertEquals(WorkflowState::UnderReview->value, $result->secondary_status);

        // Continue the workflow
        $result = $result->fsm('secondary_status')->trigger('approve');
        $this->assertEquals(WorkflowState::Approved->value, $result->secondary_status);
    }

    /**
     * Test that the enum instanceof check uses the interface, not a specific class.
     */
    public function test_enum_type_checking_uses_interface_not_concrete_class(): void
    {
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)
            ->build();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        // Verify the enum is an instance of FsmStateEnum interface
        $this->assertInstanceOf(FsmStateEnum::class, TestFeatureState::Idle);
        $this->assertInstanceOf(FsmStateEnum::class, TestFeatureState::Pending);

        // Transition should work because we check instanceof FsmStateEnum, not a specific enum class
        $result = $model->transitionFsm('status', TestFeatureState::Pending);
        $this->assertEquals(TestFeatureState::Pending->value, $result->status);
    }

    /**
     * Test that different enum types can coexist in the same application.
     */
    public function test_multiple_enum_types_can_coexist_without_conflicts(): void
    {
        // Setup FSM for TestModel with TestFeatureState
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)->event('submit')
            ->build();

        // Setup FSM for TrafficLight with TrafficLightState
        FsmBuilder::for(TrafficLight::class, 'state')
            ->initialState(TrafficLightState::Red)
            ->from(TrafficLightState::Red)->to(TrafficLightState::Green)->event('change')
            ->build();

        // Create instances
        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        // Both should work independently without interfering
        $model->fsm()->trigger('submit');
        $light->fsm('state')->trigger('change');

        $this->assertEquals(TestFeatureState::Pending->value, $model->fresh()->status);
        $this->assertEquals(TrafficLightState::Green, $light->fresh()->state);
    }

    /**
     * Test that the can() method also properly converts enum types.
     */
    public function test_can_method_handles_multiple_enum_types(): void
    {
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)->event('submit')
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Processing)->event('process')
            ->build();

        FsmBuilder::for(TrafficLight::class, 'state')
            ->initialState(TrafficLightState::Red)
            ->from(TrafficLightState::Red)->to(TrafficLightState::Green)->event('change')
            ->build();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        // can() should work with both enum types
        $this->assertTrue($model->fsm()->can('submit'));
        $this->assertFalse($model->fsm()->can('process'));

        $this->assertTrue($light->fsm('state')->can('change'));
    }

    /**
     * Test that the dryRun() method also properly converts enum types.
     */
    public function test_dry_run_method_handles_multiple_enum_types(): void
    {
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)->event('submit')
            ->build();

        FsmBuilder::for(TrafficLight::class, 'state')
            ->initialState(TrafficLightState::Red)
            ->from(TrafficLightState::Red)->to(TrafficLightState::Green)->event('change')
            ->build();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        // dryRun() should work with both enum types
        $result1 = $model->fsm()->dryRun('submit');
        $this->assertTrue($result1['can_transition']);
        $this->assertEquals(TestFeatureState::Pending->value, $result1['to_state']);

        $result2 = $light->fsm('state')->dryRun('change');
        $this->assertTrue($result2['can_transition']);
        $this->assertEquals(TrafficLightState::Green->value, $result2['to_state']);
    }

    /**
     * Critical test: Ensure no enum type throws the conversion error.
     */
    public function test_no_enum_throws_object_to_string_conversion_error(): void
    {
        $enumTypes = [
            ['model' => TestModel::class, 'column' => 'status', 'from' => TestFeatureState::Idle, 'to' => TestFeatureState::Pending, 'event' => 'go'],
            ['model' => TrafficLight::class, 'column' => 'state', 'from' => TrafficLightState::Red, 'to' => TrafficLightState::Green, 'event' => 'go'],
            ['model' => TestModel::class, 'column' => 'secondary_status', 'from' => WorkflowState::Draft, 'to' => WorkflowState::UnderReview, 'event' => 'go'],
        ];

        foreach ($enumTypes as $config) {
            FsmBuilder::for($config['model'], $config['column'])
                ->initialState($config['from'])
                ->from($config['from'])->to($config['to'])->event($config['event'])
                ->build();
        }

        // Create test instances
        $testModel = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $workflowModel = TestModel::factory()->create(['secondary_status' => WorkflowState::Draft->value]);

        // None of these should throw "Object could not be converted to string" Error
        try {
            $testModel->fsm()->trigger('go');
            $this->assertEquals(TestFeatureState::Pending->value, $testModel->fresh()->status);

            $light->fsm('state')->trigger('go');
            $this->assertEquals(TrafficLightState::Green, $light->fresh()->state);

            $workflowModel->fsm('secondary_status')->trigger('go');
            $this->assertEquals(WorkflowState::UnderReview->value, $workflowModel->fresh()->secondary_status);

            $this->assertTrue(true, 'All enum types converted successfully');
        } catch (\Error $e) {
            if (str_contains($e->getMessage(), 'could not be converted to string')) {
                $this->fail('Enum conversion error occurred: '.$e->getMessage());
            }
            throw $e;
        }
    }
}
