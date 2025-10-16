# Documentation: PersistStateTransitionedEvent.php

Original file: `src/Fsm/Listeners/PersistStateTransitionedEvent.php`

# PersistStateTransitionedEvent Documentation

## Table of Contents

- [Introduction](#introduction)
- [Class Definition](#class-definition)
- [Constructor](#constructor)
- [handle Method](#handle-method)

## Introduction

The `PersistStateTransitionedEvent` class is part of the `Fsm\Listeners` namespace within the Laravel-based PHP application. Its primary role is to act as an event listener that listens for `StateTransitioned` events and persists related data to the database. This functionality is crucial in systems utilizing a Finite State Machine (FSM) to keep a log of state transitions, which can be valuable for auditing, debugging, and historical tracking of state changes in various models.

The event listener has been designed to optionally operate in a queued fashion to enhance performance in high-throughput scenarios, ensuring that event logging does not hinder the responsiveness of the application.

## Class Definition

```php
namespace Fsm\Listeners;

use Fsm\Events\StateTransitioned;
use Fsm\Models\FsmEventLog;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
```

### Key Dependencies
- `Fsm\Events\StateTransitioned`: This class represents the event that contains the details of the state transition.
- `Fsm\Models\FsmEventLog`: This model is responsible for interacting with the database table that logs events.
- `Illuminate\Contracts\Config\Repository`: This Laravel contract defines the methods needed for configuration management, allowing the listener to check if event logging is enabled.

## Constructor

```php
public function __construct(
    private readonly ConfigRepository $config
)
```

### Purpose
The constructor initializes the `PersistStateTransitionedEvent` class and injects the configuration repository.

### Parameters
- `ConfigRepository $config`: An instance of the configuration repository that provides access to application configuration values.

### Return Values
- The constructor does not return any values; its purpose is to set up the class.

## handle Method

```php
public function handle(StateTransitioned $event): void
```

### Purpose
The `handle` method is invoked when a `StateTransitioned` event is dispatched. It processes the event and persists the transition details into the `FsmEventLog` database table.

### Parameters
- `StateTransitioned $event`: The event object containing information about the state transition, including:
  - `model`: The instance of the model that transitioned states.
  - `columnName`: The name of the column that triggered the state transition.
  - `fromState`: The state the model transitioned from.
  - `toState`: The state the model transitioned to.
  - `transitionName`: A name for the transition, useful for identification.
  - `timestamp`: The time when the transition occurred.
  - `context`: Additional contextual information related to the transition.
  - `metadata`: Any associated metadata worth logging.

### Functionality
- The method begins by checking if event logging is enabled via the application configuration. It defaults to enabled if no setting is found.
- If logging is enabled, it attempts to create a new `FsmEventLog` entry based on the properties of the provided `StateTransitioned` event.
- Each aspect of the event is captured in the log, creating a comprehensive record of the transition.
- If an error occurs during the log creation process, it catches the exception, logs it for further investigation, but allows the rest of the application to continue functioning normally.

### Example Usage
When a state transition occurs in the application, the `handle` method is automatically invoked by the Laravel event system. For instance:

```php
event(new StateTransitioned($model, 'active', 'inactive', 'deactivate', now()));
```

In this example, the `handle` method will log the transition from 'active' to 'inactive'.

## Conclusion

The `PersistStateTransitionedEvent` class serves a vital role in maintaining an audit trail of state transitions within the application. By implementing this listener, the system can ensure that all transformations of state are recorded, supporting operational integrity and facilitating any needed retrospective analysis of state transitions. Understanding how this class operates and the information it captures is essential for developers working with finite state machines in this Laravel application.