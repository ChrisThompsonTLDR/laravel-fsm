<?php

declare(strict_types=1);

namespace Tests\Feature\TrafficLight;

use Fsm\FsmBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;
use Tests\FsmTestCase;

class TrafficLightFsmTest extends FsmTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        FsmBuilder::for(TrafficLight::class, 'state')
            ->initialState(TrafficLightState::Red)
            ->from(TrafficLightState::Red)->to(TrafficLightState::Green)
            ->build();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_it_moves_from_red_to_green(): void
    {
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        $light->transitionFsm('state', TrafficLightState::Green);

        $this->assertSame(TrafficLightState::Green->value, $light->state->value);
    }
}
