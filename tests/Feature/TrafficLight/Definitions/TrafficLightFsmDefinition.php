<?php

namespace Tests\Feature\TrafficLight\Definitions;

use Fsm\Contracts\FsmDefinition;
use Fsm\FsmBuilder;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;

class TrafficLightFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        FsmBuilder::for(TrafficLight::class, 'state')
            ->initialState(TrafficLightState::Red)
            ->state(TrafficLightState::Red)
            ->state(TrafficLightState::Yellow)
            ->state(TrafficLightState::Green)
            ->from(TrafficLightState::Red)->to(TrafficLightState::Green)
            ->event('change')
            ->from(TrafficLightState::Green)->to(TrafficLightState::Yellow)
            ->event('change')
            ->from(TrafficLightState::Yellow)->to(TrafficLightState::Red)
            ->event('change')
            ->build();
    }
}
