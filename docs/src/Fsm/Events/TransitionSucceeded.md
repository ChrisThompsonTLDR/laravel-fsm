# Documentation: TransitionSucceeded.php

Original file: `src/Fsm/Events/TransitionSucceeded.php`

# TransitionSucceeded Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Definition](#class-definition)
- [Constructor](#constructor)

## Introduction
The `TransitionSucceeded` class in the `/home/chris/laravel-fsm/src/Fsm/Events/` directory is an event class in a Laravel application that is fired when a state transition has successfully completed. This class encapsulates the necessary information about the state transition that has happened, enabling other parts of the application to respond accordingly. The primary purpose of this class is to provide a structured way to handle and broadcast successful state transitions without making direct changes to the application’s general flow.

## Class Definition

This class is defined in the namespace `Fsm\Events` and it leverages Laravel's event handling capabilities. 

### Constructor

The constructor method is used to initialize a new instance of the `TransitionSucceeded` class with specific details about the transition event.

```php
public function __construct(
    public Model $model,
    public string $columnName,
    public ?string $fromState,
    public string $toState,
)
```

#### Purpose
The constructor is responsible for setting up the properties of the `TransitionSucceeded` event, which allows for the necessary information regarding the state's change to be passed along when the event is fired.

#### Parameters
| Parameter    | Type                                   | Description                                              |
|--------------|----------------------------------------|----------------------------------------------------------|
| `$model`     | `Model`                                | The Eloquent model that is undergoing the state transition. |
| `$columnName`| `string`                               | The name of the column in the database that holds the state. |
| `$fromState` | `?string`                              | The state from which the model is transitioning. This can be `null` if there is no prior state. |
| `$toState`   | `string`                               | The target state to which the model is transitioning.    |

#### Functionality
The constructor initializes the properties of the `TransitionSucceeded` instance. Here's a detailed explanation of how it works:
- **Model Binding:** The `$model` property represents the specific Eloquent model whose state is within context and is a standard practice for event-based programming in Laravel.
- **State Information:** The `$columnName`, `$fromState`, and `$toState` properties provide crucial context around the state change, allowing listeners of the event to handle this change appropriately in various contexts, such as logging, notifications, or further processing.

This class does not include any methods beyond the constructor, as it primarily serves as a simple data carrier for the event's data.

### Important Notes
- The event is specifically fired only after all necessary conditions have been met, including passing all guards and executing any pre-transition actions. 
- Notably, this event is **not** fired during dry-run validations (methods like `canTransition` or `dryRunTransition`), which are used to check if a transition can occur without executing the transition itself.

This comprehensive documentation should enable developers to understand the purpose and function of the `TransitionSucceeded` class, facilitating effective use within the Laravel framework and enabling straightforward integration as part of the event-handling practice.