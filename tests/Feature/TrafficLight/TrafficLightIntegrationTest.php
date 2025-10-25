<?php

declare(strict_types=1);

namespace Tests\Feature\TrafficLight;

use Fsm\Events\TransitionFailed;
use Fsm\Events\TransitionSucceeded;
use Fsm\FsmRegistry;
use Fsm\Models\FsmLog;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Fsm\Behavioral\BehavioralTestCase;
use Tests\Feature\TrafficLight\Definitions\TrafficLightFsmDefinition;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;

class TrafficLightIntegrationTest extends BehavioralTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new TrafficLightFsmDefinition)->define();
    }

    public function test_definition_is_registered_with_registry_and_contains_cycle_transitions(): void
    {
        /** @var FsmRegistry $registry */
        $registry = $this->app->make(FsmRegistry::class);
        $definition = $registry->getDefinition(TrafficLight::class, 'state');

        $this->assertNotNull($definition);
        $this->assertSame(TrafficLight::class, $definition->modelClass);
        $this->assertSame('state', $definition->columnName);
        $this->assertSame(TrafficLightState::Red, $definition->initialState);

        $transitions = array_map(
            static function ($transition) {
                $from = $transition->fromState;
                $to = $transition->toState;

                return [
                    'from' => $from instanceof TrafficLightState ? $from->value : $from,
                    'to' => $to instanceof TrafficLightState ? $to->value : $to,
                    'event' => $transition->event,
                ];
            },
            $definition->transitions
        );

        $this->assertCount(4, $transitions);
        $this->assertSame(
            [
                ['from' => TrafficLightState::Red->value, 'to' => TrafficLightState::Yellow->value, 'event' => 'cycle'],
                ['from' => TrafficLightState::Yellow->value, 'to' => TrafficLightState::Green->value, 'event' => 'cycle'],
                ['from' => TrafficLightState::Green->value, 'to' => TrafficLightState::Yellow->value, 'event' => 'cycle'],
                ['from' => TrafficLightState::Yellow->value, 'to' => TrafficLightState::Red->value, 'event' => 'cycle'],
            ],
            $transitions
        );
    }

    public function test_model_cycles_through_states_using_registered_definition(): void
    {
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        foreach ([
            TrafficLightState::Yellow,
            TrafficLightState::Green,
            TrafficLightState::Yellow,
            TrafficLightState::Red,
        ] as $expectedState) {
            $light = $light->transitionFsm('state', $expectedState);
            $this->assertSame($expectedState, $light->state);
            $this->assertSame($expectedState, $light->fresh()->state);
        }
    }

    public function test_definition_emits_events_and_logs_for_successful_transitions(): void
    {
        Event::fake();

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        $light->fsm('state')->trigger('cycle');

        Event::assertDispatched(TransitionSucceeded::class);
        Event::assertNotDispatched(TransitionFailed::class);

        $this->assertDatabaseHas('fsm_logs', [
            'model_id' => (string) $light->id,
            'model_type' => $light->getMorphClass(),
            'from_state' => TrafficLightState::Red->value,
            'to_state' => TrafficLightState::Yellow->value,
            'fsm_column' => 'state',
        ]);

        $this->assertSame(
            1,
            FsmLog::query()->where('model_id', (string) $light->id)->count()
        );
    }

    public function test_definition_blocks_invalid_transition_attempts(): void
    {
        Event::fake();

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Green->value]);

        try {
            $light->transitionFsm('state', TrafficLightState::Red);
            $this->fail('Expected invalid transition to throw.');
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\Fsm\Exceptions\FsmTransitionFailedException::class, $exception);
            $this->assertSame(TrafficLightState::Green, $light->fresh()->state);
            Event::assertDispatched(TransitionFailed::class);
        }
    }
}
