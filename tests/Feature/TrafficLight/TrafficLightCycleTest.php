<?php

declare(strict_types=1);

namespace Tests\Feature\TrafficLight;

use Fsm\Models\FsmLog;
use Tests\Feature\Fsm\Behavioral\BehavioralTestCase;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;

class TrafficLightCycleTest extends BehavioralTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->defineTrafficLightFsm(function ($builder) {
            $builder->from(TrafficLightState::Red)
                ->to(TrafficLightState::Yellow)
                ->event('cycle');

            $builder->from(TrafficLightState::Yellow)
                ->to(TrafficLightState::Green)
                ->event('cycle');

            $builder->from(TrafficLightState::Green)
                ->to(TrafficLightState::Yellow)
                ->event('cycle');

            $builder->from(TrafficLightState::Yellow)
                ->to(TrafficLightState::Red)
                ->event('cycle');
        });
    }

    public function test_traffic_light_completes_full_cycle(): void
    {
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        $expectedStates = [
            TrafficLightState::Yellow,
            TrafficLightState::Green,
            TrafficLightState::Yellow,
            TrafficLightState::Red,
        ];

        foreach ($expectedStates as $expectedState) {
            $light = $light->transitionFsm('state', $expectedState);
            $this->assertSame($expectedState, $light->state);
            $this->assertSame($expectedState, $light->fresh()->state);
        }
    }

    public function test_full_cycle_records_log_for_each_transition(): void
    {
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        $transitions = [
            [TrafficLightState::Red, TrafficLightState::Yellow],
            [TrafficLightState::Yellow, TrafficLightState::Green],
            [TrafficLightState::Green, TrafficLightState::Yellow],
            [TrafficLightState::Yellow, TrafficLightState::Red],
        ];

        foreach ($transitions as [$from, $to]) {
            $light = $this->engine->performTransition($light->fresh(), 'state', $to);
            $this->assertSame($to, $light->state);
        }

        $logs = FsmLog::query()
            ->where('model_id', (string) $light->id)
            ->where('model_type', $light->getMorphClass())
            ->orderBy('happened_at')
            ->get();

        $this->assertCount(4, $logs);
        $this->assertSame(
            array_map(fn ($transition) => $transition[1]->value, $transitions),
            $logs->pluck('to_state')->all()
        );
    }
}
