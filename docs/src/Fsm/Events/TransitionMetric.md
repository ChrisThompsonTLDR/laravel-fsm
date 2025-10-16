# Documentation: TransitionMetric.php

Original file: `src/Fsm/Events/TransitionMetric.php`

# TransitionMetric Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Constructor](#constructor)

## Introduction
The `TransitionMetric` class is part of the `Fsm\Events` namespace within a Laravel application that implements a finite state machine (FSM). This class is designed to encapsulate transition metrics that are recorded when transitioning between different states within the FSM. It is primarily used for tracking and analyzing state changes in a way that can be integrated with other parts of the system, such as logging, metrics gathering, or triggering additional business logic in response to state transitions.

## Class Overview
### TransitionMetric
The `TransitionMetric` class serves as a data transfer object (DTO) to facilitate the structured passing of transition state data within the finite state machine context. Below is the detailed documentation of its constructor used to initialize the metrics.

### Constructor
```php
public function __construct(
    public readonly Model $model,
    public readonly string $columnName,
    public readonly FsmStateEnum|string|null $fromState,
    public readonly FsmStateEnum|string $toState,
    public readonly bool $successful,
    public readonly ?ArgonautDTOContract $context = null,
)
```

#### Purpose
The constructor is responsible for creating an instance of the `TransitionMetric` class with the necessary attributes that define a transition in the FSM.

#### Parameters
| Parameter                  | Type                          | Description                                                                                                                                                         |
|----------------------------|-------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `$model`                   | `Model`                       | An instance of an Eloquent model that represents the entity undergoing the state transition.                                                                       |
| `$columnName`              | `string`                      | The name of the database column that corresponds to the state being transitioned.                                                                                  |
| `$fromState`               | `FsmStateEnum|string|null`    | The state that the model is transitioning from; can be an instance of `FsmStateEnum`, a string representation of the state, or null if there is no previous state. |
| `$toState`                 | `FsmStateEnum|string`         | The state that the model is transitioning to; must be an instance of `FsmStateEnum` or a string representation.                                                      |
| `$successful`              | `bool`                        | A boolean indicating whether the state transition was successful.                                                                                                  |
| `$context`                 | `ArgonautDTOContract|null`    | Optional parameter providing additional context information related to the transition, implementing the `ArgonautDTOContract`.                                     |

#### Functionality
The `TransitionMetric` constructor initializes the object with the provided parameters. It ensures encapsulation of the transition's relevant data, promoting immutability through the use of `readonly` properties. This design choice encourages safer programming practices by ensuring that the state metrics remain unchanged after instantiation.

All parameters are type-hinted, enforcing type safety and improving code reliability. The `context` parameter is optional, which allows flexibility in scenarios where no additional context is necessary.

### Example Usage
Here’s an example of how to create a new instance of the `TransitionMetric` class:

```php
use Fsm\Events\TransitionMetric;
use App\Models\User; // Example model
use Fsm\Contracts\FsmStateEnum;

$user = new User(); // Assume this is a model instance
$columnName = 'current_state';
$fromState = FsmStateEnum::INACTIVE; // Previously defined state
$toState = FsmStateEnum::ACTIVE;      // Transitioning to a new state
$successful = true;
$context = null; // No additional context

$transitionMetric = new TransitionMetric($user, $columnName, $fromState, $toState, $successful, $context);
```

This example illustrates how the `TransitionMetric` can be used to track state transitions for a user model within the FSM, conveying critical information about the transition process. 

The `TransitionMetric` class plays a vital role in ensuring that information regarding state changes is recorded consistently, allowing for easier auditing, logging, and handling of business logic associated with state management in this application's context.