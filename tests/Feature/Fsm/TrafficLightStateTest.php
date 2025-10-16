<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm;

/**
 * Basic finite state machine implementation used solely for testing.
 * Uses a traffic light example that is simple and universally understood.
 */
enum TrafficLightState: string
{
    case Red = 'red';
    case Yellow = 'yellow';
    case Green = 'green';
}

final class TrafficLight
{
    public function __construct(public string $state = TrafficLightState::Red->value) {}
}

final class FakeFsm
{
    private TrafficLight $light;

    /** @var array<string, mixed> */
    public array $transitions = [];

    public function __construct(TrafficLight $light)
    {
        $this->light = $light;
        $this->transitions = [
            TrafficLightState::Red->value => [TrafficLightState::Green->value],
            TrafficLightState::Green->value => [TrafficLightState::Yellow->value],
            TrafficLightState::Yellow->value => [TrafficLightState::Red->value],
        ];
    }

    public function canTransitionTo(TrafficLightState $state): bool
    {
        return in_array($state->value, $this->transitions[$this->light->state], true);
    }

    public function transitionTo(TrafficLightState $state): void
    {
        if (! $this->canTransitionTo($state)) {
            throw new \RuntimeException('Invalid transition');
        }
        $this->light->state = $state->value;
    }
}

final class FsmRegistry
{
    public static function for(TrafficLight $light): FakeFsm
    {
        return new FakeFsm($light);
    }
}

it('light can transition from red to green', function () {
    $light = new TrafficLight;
    $fsm = FsmRegistry::for($light);

    expect($fsm->canTransitionTo(TrafficLightState::Green))->toBeTrue();
    $fsm->transitionTo(TrafficLightState::Green);
    expect($light->state)->toBe(TrafficLightState::Green->value);
});

it('light transitions through full cycle', function () {
    $light = new TrafficLight;
    $fsm = FsmRegistry::for($light);

    // Red -> Green
    $fsm->transitionTo(TrafficLightState::Green);
    expect($light->state)->toBe(TrafficLightState::Green->value);

    // Green -> Yellow
    expect($fsm->canTransitionTo(TrafficLightState::Yellow))->toBeTrue();
    $fsm->transitionTo(TrafficLightState::Yellow);
    expect($light->state)->toBe(TrafficLightState::Yellow->value);

    // Yellow -> Red
    expect($fsm->canTransitionTo(TrafficLightState::Red))->toBeTrue();
    $fsm->transitionTo(TrafficLightState::Red);
    expect($light->state)->toBe(TrafficLightState::Red->value);
});

it('light cannot skip states', function () {
    $light = new TrafficLight(TrafficLightState::Red->value);
    $fsm = FsmRegistry::for($light);

    expect($fsm->canTransitionTo(TrafficLightState::Yellow))->toBeFalse();
    expect(fn () => $fsm->transitionTo(TrafficLightState::Yellow))->toThrow(\RuntimeException::class);
    expect($light->state)->toBe(TrafficLightState::Red->value);
});
