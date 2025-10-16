# Documentation: TransitionAction.php

Original file: `src/Fsm/Data/TransitionAction.php`

# TransitionAction Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class TransitionAction](#class-transitionaction)
  - [Constants](#constants)
  - [Properties](#properties)
  - [Constructor](#constructor)
  - [Methods](#methods)
    - [getType](#gettype)
    - [shouldExecuteAt](#shouldexecuteat)
    - [getDisplayName](#getdisplayname)

## Introduction

The `TransitionAction` class is part of a Finite State Machine (FSM) system implemented in PHP. This class represents an action that occurs during a transition between states within the FSM framework. By allowing for both callable logic defined as strings or Closures, the class enables a flexible mechanism for defining behavior that can be triggered in various states of the system.

## Class TransitionAction

### Constants

The following constants are defined for the `TransitionAction` class. These constants help enforce strict typing and improve static analysis.

| Constant              | Value        | Description                                                   |
|----------------------|--------------|---------------------------------------------------------------|
| `TIMING_BEFORE`      | `'before'`   | Action to be executed before the transition occurs.          |
| `TIMING_AFTER`       | `'after'`    | Action to be executed after the transition occurs.           |
| `TIMING_ON_SUCCESS`  | `'on_success'` | Action to be executed when the transition succeeds.         |
| `TIMING_ON_FAILURE`  | `'on_failure'` | Action to be executed when the transition fails.           |
| `TYPE_VERB`          | `'verb'`     | Indicates that the action is a class string or verb.        |
| `TYPE_CLOSURE`       | `'closure'`  | Indicates that the action is a Closure.                      |
| `TYPE_CALLABLE`      | `'callable'` | Indicates that the action is a callable array.              |
| `TYPE_SERVICE`       | `'service'`  | Indicates that the action is a service.                      |
| `PRIORITY_CRITICAL`  | `100`        | Highest action priority.                                     |
| `PRIORITY_HIGH`      | `75`         | High action priority.                                        |
| `PRIORITY_NORMAL`    | `50`         | Normal action priority.                                      |
| `PRIORITY_LOW`       | `25`         | Lowest action priority.                                      |

### Properties

The class defines the following properties:

| Property              | Type                           | Default                  | Description                                                                          |
|----------------------|--------------------------------|-------------------------|--------------------------------------------------------------------------------------|
| `callable`           | `string|Closure|array`         | Required                 | The action logic represented as a callable.                                         |
| `parameters`         | `array<string, mixed>`         | `[]`                   | Static parameters to pass to the callable action.                                    |
| `runAfterTransition`  | `bool`                        | `true`                 | Flag to indicate if the action should run after the model transition is completed.   |
| `timing`             | `string`                      | `self::TIMING_AFTER`  | Timing of when the action should be executed relative to the transition.            |
| `priority`           | `int`                         | `self::PRIORITY_NORMAL`| Execution priority determining the order of action execution.                       |
| `name`               | `string|null`                | `null`                 | Optional name for action useful for debugging and logging.                           |
| `queued`             | `bool`                        | `false`                | Whether the action is queued for execution.                                         |

### Constructor

```php
public function __construct(
    string|Closure|array $callable,
    array $parameters = [],
    bool $runAfterTransition = true,
    string $timing = self::TIMING_AFTER,
    int $priority = self::PRIORITY_NORMAL,
    ?string $name = null,
    bool $queued = false,
)
```

#### Parameters

- `callable`: The action logic, which can be a string, a Closure, or an array indicating a callable.
- `parameters`: An array of static parameters to be passed to the callable.
- `runAfterTransition`: A boolean flag indicating if the action should run after the model is saved.
- `timing`: A string indicating when the action will be executed relative to the transition.
- `priority`: An integer representing the action's execution priority. Higher values are executed first.
- `name`: An optional string for the action's name, useful for debugging or logging.
- `queued`: A boolean indicating if the action is queued.

#### Functionality

The constructor initializes the action with the provided parameters. It features improved logic for constructing the object from an array if the first argument is an array, checking against expected keys.

### Methods

#### getType

```php
public function getType(): string
```

#### Purpose

Determines the type of action based on the `callable` property.

#### Return Value

- Returns a string representing the type of action, which can be one of the constants: `TYPE_VERB`, `TYPE_CLOSURE`, `TYPE_CALLABLE`, or `TYPE_SERVICE`.

#### Functionality

This method assesses the `callable` property's value and utilizes a match expression to determine its type based on the following classifications:

- If `callable` is a string and represents an existing class, it returns `TYPE_VERB`.
- If `callable` is an instance of `Closure`, it returns `TYPE_CLOSURE`.
- If `callable` is an array, it returns `TYPE_CALLABLE`.
- Otherwise, it defaults to `TYPE_SERVICE`.

#### shouldExecuteAt

```php
public function shouldExecuteAt(string $timing): bool
```

#### Purpose

Determines if the action should be executed at the provided timing.

#### Parameters

- `timing`: A string representing the timing to check against, such as `TIMING_BEFORE`, `TIMING_AFTER`, `TIMING_ON_SUCCESS`, or `TIMING_ON_FAILURE`.

#### Return Value

- Returns a boolean indicating whether the action timings match.

#### Functionality

This method compares the `timing` property with the provided timing argument and returns true if they match, enabling condition checks for action execution based on the transition lifecycle.

#### getDisplayName

```php
public function getDisplayName(): string
```

#### Purpose

Retrieves a human-readable name for the action.

#### Return Value

- Returns a string representing the display name of the action.

#### Functionality

The method checks if a `name` has been provided. If not, it derives a name based on the type of action:

- For `TYPE_VERB`, it returns the class basename if it is a string.
- For `TYPE_CLOSURE`, it returns 'Closure'.
- For `TYPE_CALLABLE`, it returns a combination of the class name and method name, if applicable.
- Otherwise, it returns 'Unknown Action'.

This aids in debugging and logging by providing meaningful information about the action being executed. 

--- 

This detailed documentation serves as a resource for developers working with the `TransitionAction` class in the FSM system, providing