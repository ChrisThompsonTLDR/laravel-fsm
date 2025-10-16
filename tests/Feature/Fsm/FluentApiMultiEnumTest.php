<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm;

use Fsm\FsmBuilder;
use Fsm\FsmRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;
use Tests\FsmTestCase;

/**
 * This test suite specifically validates that the fsm()->trigger() helper
 * works correctly with multiple different enum types implementing FsmStateEnum.
 *
 * This prevents regressions like the hard-coded UnitState check that broke
 * generic enum support in the mapEvent() method.
 */
class FluentApiMultiEnumTest extends FsmTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset builder and registry to allow this test to define FSMs inline
        FsmBuilder::reset();

        // Create a fresh FsmRegistry instance
        $this->app->singleton(FsmRegistry::class, function ($app) {
            return new FsmRegistry(
                $app->make(\Fsm\Services\BootstrapDetector::class),
                $app->make(\Illuminate\Contracts\Config\Repository::class)
            );
        });

        // Setup FSM with TestFeatureState enum
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)->event('submit')
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Processing)->event('process')
            ->from(TestFeatureState::Processing)->to(TestFeatureState::Completed)->event('complete')
            ->from(TestFeatureState::Processing)->to(TestFeatureState::Failed)->event('fail')
            ->build();

        // Setup FSM with TrafficLightState enum (different enum type)
        FsmBuilder::for(TrafficLight::class, 'state')
            ->initialState(TrafficLightState::Red)
            ->from(TrafficLightState::Red)->to(TrafficLightState::Green)->event('change')
            ->from(TrafficLightState::Green)->to(TrafficLightState::Yellow)->event('change')
            ->from(TrafficLightState::Yellow)->to(TrafficLightState::Red)->event('change')
            ->build();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_fluent_trigger_works_with_test_feature_state_enum(): void
    {
        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        // Trigger event - should convert enum correctly
        $result = $model->fsm()->trigger('submit');

        $this->assertEquals(TestFeatureState::Pending->value, $result->status);
        $this->assertDatabaseHas('test_models', [
            'id' => $model->id,
            'status' => TestFeatureState::Pending->value,
        ]);
    }

    public function test_fluent_trigger_works_with_traffic_light_state_enum(): void
    {
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        // Trigger event - should convert enum correctly
        $result = $light->fsm('state')->trigger('change');

        $this->assertEquals(TrafficLightState::Green, $result->state);
        $this->assertDatabaseHas('traffic_lights', [
            'id' => $light->id,
            'state' => TrafficLightState::Green->value,
        ]);
    }

    public function test_fluent_can_works_with_multiple_enum_types(): void
    {
        // Test with TestFeatureState
        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);
        $this->assertTrue($model->fsm()->can('submit'));
        $this->assertFalse($model->fsm()->can('process'));

        // Test with TrafficLightState
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $this->assertTrue($light->fsm('state')->can('change'));
    }

    public function test_fluent_dry_run_works_with_multiple_enum_types(): void
    {
        // Test with TestFeatureState
        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);
        $result = $model->fsm()->dryRun('submit');

        $this->assertTrue($result['can_transition']);
        $this->assertEquals(TestFeatureState::Idle->value, $result['from_state']);
        $this->assertEquals(TestFeatureState::Pending->value, $result['to_state']);

        // Test with TrafficLightState
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $result = $light->fsm('state')->dryRun('change');

        $this->assertTrue($result['can_transition']);
        $this->assertEquals(TrafficLightState::Red->value, $result['from_state']);
        $this->assertEquals(TrafficLightState::Green->value, $result['to_state']);
    }

    public function test_enum_conversion_works_correctly_for_different_states(): void
    {
        // Test multiple transitions with TestFeatureState
        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);

        $model->fsm()->trigger('submit');
        $this->assertEquals(TestFeatureState::Pending->value, $model->fresh()->status);

        $model->fsm()->trigger('process');
        $this->assertEquals(TestFeatureState::Processing->value, $model->fresh()->status);

        $model->fsm()->trigger('complete');
        $this->assertEquals(TestFeatureState::Completed->value, $model->fresh()->status);

        // Test multiple transitions with TrafficLightState
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        $light->fsm('state')->trigger('change');
        $this->assertEquals(TrafficLightState::Green, $light->fresh()->state);

        $light->fsm('state')->trigger('change');
        $this->assertEquals(TrafficLightState::Yellow, $light->fresh()->state);
    }

    public function test_transition_with_same_event_from_different_states_works_for_multiple_enums(): void
    {
        // Traffic lights use the same "change" event from all states
        $lightRed = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $lightRed->fsm('state')->trigger('change');
        $this->assertEquals(TrafficLightState::Green, $lightRed->fresh()->state);

        $lightGreen = TrafficLight::factory()->create(['state' => TrafficLightState::Green->value]);
        $lightGreen->fsm('state')->trigger('change');
        $this->assertEquals(TrafficLightState::Yellow, $lightGreen->fresh()->state);
    }

    public function test_enum_value_extraction_does_not_throw_error(): void
    {
        // This test specifically validates that the enum conversion doesn't crash
        // with "Object could not be converted to string" error

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle->value]);
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        // These should not throw any errors about converting objects to strings
        try {
            $model->fsm()->trigger('submit');
            $light->fsm('state')->trigger('change');

            $this->assertTrue(true, 'Enum conversion worked without errors');
        } catch (\Error $e) {
            $this->fail('Enum conversion threw error: '.$e->getMessage());
        }
    }

    public function test_map_event_respects_current_state_for_multiple_enums(): void
    {
        // Test that mapEvent correctly matches transitions based on current state

        // For TestFeatureState
        $model = TestModel::factory()->create(['status' => TestFeatureState::Processing->value]);

        // From Processing, "complete" should work
        $this->assertTrue($model->fsm()->can('complete'));
        $model->fsm()->trigger('complete');
        $this->assertEquals(TestFeatureState::Completed->value, $model->fresh()->status);

        // For TrafficLightState
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Green->value]);

        // From Green, "change" should transition to Yellow
        $this->assertTrue($light->fsm('state')->can('change'));
        $light->fsm('state')->trigger('change');
        $this->assertEquals(TrafficLightState::Yellow, $light->fresh()->state);
    }
}
