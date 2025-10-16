# Documentation: TransitionFailed.php

Original file: `src/Fsm/Events/TransitionFailed.php`

# TransitionFailed Documentation

## Table of Contents
- [Introduction](#introduction)
- [Constructor](#constructor)

## Introduction

The `TransitionFailed` class in the file `/home/chris/laravel-fsm/src/Fsm/Events/TransitionFailed.php` is part of the Finite State Machine (FSM) implementation utilized within the Laravel framework. This class encapsulates the event that is triggered when a transition between states fails. It serves as a way to handle exceptions and understand the context surrounding the transition failure, allowing developers to implement appropriate error handling and logging mechanisms.

This class captures the necessary information related to the failed transition, such as the model instance involved, the state values, context data, and any exception that was thrown, making it crucial for debugging and monitoring FSM behavior in applications.

## Constructor

The `TransitionFailed` class contains a constructor designed to initialize the properties of the class. 

### Purpose
The constructor facilitates the creation of a `TransitionFailed` event instance, which carries essential details about the failed state transition.

### Parameters

| Parameter             | Type                             | Description                                                           |
|-----------------------|----------------------------------|-----------------------------------------------------------------------|
| `$model`              | `Model`                          | The model instance for which the transition has failed.              |
| `$columnName`         | `string`                         | The name of the column in the model that represents the FSM state.   |
| `$fromState`          | `FsmStateEnum|string|null`      | The state that the transition was initiated from (nullable).         |
| `$toState`            | `FsmStateEnum|string`           | The state that the transition was attempted to reach.                |
| `$context`            | `ArgonautDTOContract|null`      | Context data pertaining to the transition attempt (nullable).        |
| `$exception`          | `Throwable|null`                | The exception that caused the transition to fail (nullable).         |

### Return Values
The constructor does not return any value. It initializes an instance of the `TransitionFailed` class with the provided parameters.

### Functionality
The constructor is defined as follows:

```php
public function __construct(
    public readonly Model $model,
    public readonly string $columnName,
    public readonly FsmStateEnum|string|null $fromState,
    public readonly FsmStateEnum|string $toState,
    public readonly ?ArgonautDTOContract $context,
    public readonly ?Throwable $exception
) {}
```

Upon instantiation, the following actions take place:
- The parameters passed to the constructor are assigned to readonly properties of the instance.
- The `Model` type ensures that the first parameter is always an Eloquent model, thus retaining proper type safety within Laravel applications.
- The `$fromState` parameter is accepted as either a `FsmStateEnum` or a string, and it can be null, allowing for flexibility in handling dynamic state representations.
- The `$toState` parameter must always be provided, as it identifies the target state in the transition attempt.
- The inclusion of a context DTO allows additional data to be carried along with the event, which can be useful for more complex FSM logic.
- The optional `$exception` parameter enables developers to diagnose the reason for the failure, fostering a better understanding of critical issues that may arise during state transitions.

Through the encapsulation of this information within the `TransitionFailed` class, developers can effectively respond to errors and maintain robust state management throughout their applications.