# Documentation: Constants.php

Original file: `src/Fsm/Constants.php`

# Constants Documentation

## Table of Contents
- [Introduction](#introduction)
- [Constant Definitions](#constant-definitions)
  - [Skip Discovery Commands](#skip-discovery-commands)
  - [Wildcard Events and States](#wildcard-events-and-states)
  - [Transition Result Constants](#transition-result-constants)
  - [Event Sourcing Integration Constants](#event-sourcing-integration-constants)
  - [FSM Operation Types](#fsm-operation-types)
  - [Priority Levels](#priority-levels)
  - [Configuration Keys](#configuration-keys)
  - [State Metadata Keys](#state-metadata-keys)
  - [Validation Error Types](#validation-error-types)

## Introduction

The `Constants.php` file defines a collection of constants used throughout the Finite State Machine (FSM) component of the application. With the use of typed class constants introduced in PHP 8.3, these constants enhance type safety, promote static analysis, and improve IDE support with type inference. This file serves as a centralized location for defining key values and messages that the FSM relies upon, ensuring consistency and reducing the likelihood of hard-coded values scattered throughout the codebase.

## Constant Definitions

### Skip Discovery Commands

```php
public const array SKIP_DISCOVERY_COMMANDS = [
    'package:discover',
    'config:cache',
    'config:clear',
    'cache:clear',
    'optimize',
    'optimize:clear',
    'dump-autoload',
];
```

These constants define a list of commands that should be skipped during FSM discovery. This is important to avoid unnecessary database access during package discovery and bootstrap operations. When executing these commands, the FSM will not engage in discovery processes, allowing for more efficient execution.

### Wildcard Events and States

```php
public const string EVENT_WILDCARD = '*';
public const string STATE_WILDCARD = '__STATE_WILDCARD__';
```

- **EVENT_WILDCARD**: Represents a wildcard event that can be used when defining transitions. If a transition is configured without a specific event, this wildcard is utilized as the event key.
- **STATE_WILDCARD**: Allows transitions to accept any "from" state, enabling flexibility in state transitions by using this placeholder for state definitions.

### Transition Result Constants

```php
public const string TRANSITION_SUCCESS = 'success';
public const string TRANSITION_BLOCKED = 'blocked';
public const string TRANSITION_FAILED = 'failed';
```

These constants indicate the result of a state transition:
- **TRANSITION_SUCCESS**: Indicates that the transition was successful.
- **TRANSITION_BLOCKED**: Indicates the transition was blocked by some condition (typically a guard).
- **TRANSITION_FAILED**: Indicates a failure in executing the transition, possibly due to an action failure.

### Event Sourcing Integration Constants

```php
public const string VERBS_AGGREGATE_TYPE = 'fsm_aggregate';
public const string VERBS_EVENT_TYPE = 'fsm_transitioned';
public const int VERBS_DEFAULT_REPLAY_CHUNK_SIZE = 100;
```

These constants facilitate event sourcing related to the FSM:
- **VERBS_AGGREGATE_TYPE**: Specifies the aggregate type for event sourcing.
- **VERBS_EVENT_TYPE**: Defines the event type for transitioned states.
- **VERBS_DEFAULT_REPLAY_CHUNK_SIZE**: Sets the default chunk size for replaying events in event sourcing.

### FSM Operation Types

```php
public const string OPERATION_TRANSITION = 'transition';
public const string OPERATION_GUARD_CHECK = 'guard_check';
public const string OPERATION_ACTION_EXECUTE = 'action_execute';
public const string OPERATION_CALLBACK_EXECUTE = 'callback_execute';
```

These constants classify FSM operations:
- **OPERATION_TRANSITION**: Used when a transition is made between states.
- **OPERATION_GUARD_CHECK**: Represents the act of checking conditions (guards) before a transition.
- **OPERATION_ACTION_EXECUTE**: Indicates the execution of actions associated with transitions.
- **OPERATION_CALLBACK_EXECUTE**: Represents the execution of callbacks related to state transitions.

### Priority Levels

```php
public const int PRIORITY_HIGH = 100;
public const int PRIORITY_NORMAL = 50;
public const int PRIORITY_LOW = 10;
```

Priority constants indicate the processing priority of transitions:
- **PRIORITY_HIGH**: Highest priority for urgent transitions.
- **PRIORITY_NORMAL**: Standard priority for regular transitions.
- **PRIORITY_LOW**: Lowest priority for transitions that can wait for processing.

### Configuration Keys

```php
public const string CONFIG_LOGGING_ENABLED = 'fsm.logging.enabled';
public const string CONFIG_VERBS_DISPATCH = 'fsm.verbs.dispatch_transitioned_verb';
public const string CONFIG_USE_TRANSACTIONS = 'fsm.use_transactions';
public const string CONFIG_DISCOVERY_PATHS = 'fsm.discovery_paths';
```

These constants define keys used in the FSM configuration:
- **CONFIG_LOGGING_ENABLED**: Key for enabling/disabling logging in the FSM.
- **CONFIG_VERBS_DISPATCH**: Key for defining how transitioned verbs are dispatched.
- **CONFIG_USE_TRANSACTIONS**: Key to determine if transactions are being utilized.
- **CONFIG_DISCOVERY_PATHS**: Key to specify paths for FSM discovery processes.

### State Metadata Keys

```php
public const string META_DISPLAY_NAME = 'display_name';
public const string META_DESCRIPTION = 'description';
public const string META_ICON = 'icon';
public const string META_COLOR = 'color';
public const array META_ALLOWED_KEYS = [
    self::META_DISPLAY_NAME,
    self::META_DESCRIPTION,
    self::META_ICON,
    self::META_COLOR,
];
```

These constants are used for state metadata:
- **META_DISPLAY_NAME**: Display name for the state.
- **META_DESCRIPTION**: Description of the state.
- **META_ICON**: Icon associated with the state.
- **META_COLOR**: Color representation of the state.
- **META_ALLOWED_KEYS**: Contains allowed metadata keys for states to ensure only valid keys are used.

### Validation Error Types

```php
public const string ERROR_INVALID_STATE = 'invalid_state';
public const string ERROR_INVALID_TRANSITION = 'invalid_transition';
public const string ERROR_GUARD_FAILED = 'guard_failed';
public const string ERROR_ACTION_FAILED = 'action_failed';
public const string ERROR_CALLBACK_FAILED = 'callback_failed';
```

These constants define error types for validation within the FSM:
- **ERROR_INVALID_STATE**: Error indicating an invalid state was used in a transition.
- **ERROR_INVALID_TRANSITION**: Error raised for an invalid transition attempt.
- **ERROR_GUARD_FAILED**: Indicates a guard check has failed during transition.
- **ERROR_ACTION_FAILED**: Raised when an action associated with a transition fails to execute.
- **ERROR_CALLBACK_FAILED**: Indicates a failure in executing a callback related to a state change.

This documentation provides a comprehensive overview of the `Constants` class found in `Constants.php`, outlining the purpose and significance of each constant to aid developers in understanding and utilizing the FSM component effectively.