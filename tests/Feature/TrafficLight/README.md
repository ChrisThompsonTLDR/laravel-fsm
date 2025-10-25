# Traffic Light FSM Example

This directory contains a simple and universally understood example of a Finite State Machine (FSM) using a traffic light system. This example replaces the previous RPG example with something more relatable and professional.

## Overview

A traffic light is an excellent example of a state machine because:
- It has clear, distinct states (Red, Yellow, Green)
- Transitions follow a predictable pattern
- Everyone understands how it works
- It's simple enough to be clear, yet complex enough to demonstrate FSM features

## Structure

### Models
- **TrafficLight** (`Models/TrafficLight.php`): Eloquent model representing a traffic light with a state column

### Enums
- **TrafficLightState** (`Enums/TrafficLightState.php`): Enum defining the three states:
  - `Red` 🔴 - Stop
  - `Yellow` 🟡 - Prepare to stop
  - `Green` 🟢 - Go

### Definitions
- **TrafficLightFsmDefinition** (`Definitions/TrafficLightFsmDefinition.php`): FSM definition with transitions:
  - Red → Yellow (via 'cycle' event)
  - Yellow → Green (via 'cycle' event)
  - Green → Yellow (via 'cycle' event)
  - Yellow → Red (via 'cycle' event)

### Factories
- **TrafficLightFactory** (`Database/Factories/TrafficLightFactory.php`): Factory for creating test traffic lights

### Tests
- **TrafficLightFsmTest** (`TrafficLightFsmTest.php`): Basic test demonstrating the FSM in action

## Usage Example

```php
use Tests\Feature\TrafficLight\Models\TrafficLight;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;

// Create a new traffic light starting at Red
$light = TrafficLight::factory()->create(['state' => TrafficLightState::Red]);

// Transition through the full cycle
$light->transitionFsm('state', TrafficLightState::Yellow); // Red → Yellow
$light->transitionFsm('state', TrafficLightState::Green);  // Yellow → Green
$light->transitionFsm('state', TrafficLightState::Yellow); // Green → Yellow
$light->transitionFsm('state', TrafficLightState::Red);    // Yellow → Red

```

## Benefits of This Example

1. **Universal Understanding**: Everyone knows how traffic lights work
2. **Simple but Complete**: Demonstrates states, transitions, and events
3. **Professional**: More appropriate for business contexts than gaming examples
4. **Extensible**: Easy to add features like:
   - Pedestrian crossing states
   - Emergency vehicle override
   - Time-based auto-transitions
   - Guard conditions (e.g., minimum green time)
   - Actions (e.g., logging, notifications)

## Extending the Example

This basic example can be extended to demonstrate more advanced FSM features:

```php
// Add guards
->from(TrafficLightState::Green)->to(TrafficLightState::Yellow)
->event('change')
->guard(function($light) {
    // Must be green for at least 30 seconds
    return $light->updated_at->diffInSeconds() >= 30;
});

// Add actions
->action(function($light) {
    Log::info("Light changed to {$light->state->value}");
});

// Add state callbacks
->state(TrafficLightState::Red, function($builder) {
    $builder->onEntry(function($light) {
        // Notify pedestrians they can cross
        PedestrianCrossing::allowCrossing();
    });
});
```

## Migration

The traffic lights table is created in `tests/database/migrations/2024_01_01_000002_create_traffic_lights_table.php`:

```php
Schema::create('traffic_lights', function (Blueprint $table) {
    $table->id();
    $table->string('name');  // e.g., "Main St & 5th Ave"
    $table->string('state'); // Current state
    $table->timestamps();
});
```

## Why This Replaces the RPG Example

The RPG (Role-Playing Game) character example was complex and gaming-specific, which made it:
- Less relatable for business applications
- More complex than necessary for a basic example
- Domain-specific to gaming

The traffic light example is:
- Universally understood across cultures and industries
- Simple and clear
- Easily extensible for advanced features
- More professional for business contexts
- Still demonstrates all FSM capabilities (states, transitions, events, guards, actions)
