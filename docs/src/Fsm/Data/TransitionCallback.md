# Documentation: TransitionCallback.php

Original file: `src/Fsm/Data/TransitionCallback.php`

# TransitionCallback Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Structure](#class-structure)
- [Constructor](#constructor)
- [Methods](#methods)
  - [getType](#gettype)
  - [shouldExecuteAt](#shouldexecuteat)
  - [getDisplayName](#getdisplayname)
  - [shouldExecuteAtPriority](#shouldexecuteatpriority)

## Introduction
The `TransitionCallback` class is part of a finite state machine (FSM) implementation in PHP, providing the means to define and manage callbacks associated with various states and transitions within the FSM. This class encapsulates the behavior of callbacks that can be executed at specific times during state transitions, enhancing the modularity and maintainability of the FSM logic. It supports various types of callable definitions, priority execution, and detailed configuration parameters for greater flexibility.

## Class Structure
```php
class TransitionCallback extends Dto
```
- **Namespace**: `Fsm\Data`
- **Extends**: `Dto`, which is likely another class representing a Data Transfer Object.

### Constants
The class defines a series of type and timing constants for improved type safety:

| Constant Name                    | Description                                         |
|----------------------------------|-----------------------------------------------------|
| `TYPE_CLOSURE`                   | Indicates a closure type callback                    |
| `TYPE_INVOKABLE`                 | Indicates an invokable class type callback          |
| `TYPE_CALLABLE`                  | Indicates a general callable type                    |
| `TYPE_SERVICE`                   | Indicates a service-based callback                   |
| `TIMING_ON_ENTRY`                | Callback execution timing when entering a state     |
| `TIMING_ON_EXIT`                 | Callback execution timing when exiting a state      |
| `TIMING_ON_TRANSITION`           | Callback execution timing during a transition       |
| `TIMING_BEFORE_SAVE`             | Callback execution timing before saving             |
| `TIMING_AFTER_SAVE`              | Callback execution timing after saving              |

### Properties
- `callable`: The callback logic, supports closures, arrays, and strings.
- `parameters`: An associative array of static parameters to pass to the callback.
- `runAfterTransition`: Boolean indicating if the callback should run after state transition.
- `timing`: Specifies when the callback should execute.
- `priority`: An integer denoting the execution priority.
- `name`: An optional callback name for identification.
- `continueOnFailure`: Indicates whether to continue executing other callbacks on failure.
- `queued`: Indicates if the callback is queued for execution.

## Constructor
```php
public function __construct(
    string|Closure|array $callable,
    array $parameters = [],
    bool $runAfterTransition = false,
    string $timing = self::TIMING_AFTER_SAVE,
    int $priority = self::PRIORITY_NORMAL,
    ?string $name = null,
    bool $continueOnFailure = true,
    bool $queued = false,
)
```

### Purpose
The constructor initializes a new instance of `TransitionCallback` and can accept both direct values and an associative array for flexible construction.

### Parameters
- **`$callable`**: Accepts a callable type (closure, string, or array) to define the callback logic.
- **`$parameters`**: Additional parameters to be passed to the callable (default is an empty array).
- **`$runAfterTransition`**: Indicates if the callback should run after the model is saved.
- **`$timing`**: Determines the execution timing of the callback (default is `TIMING_AFTER_SAVE`).
- **`$priority`**: Execution priority of the callback (default is `PRIORITY_NORMAL`).
- **`$name`**: Optional name for logging/debugging.
- **`$continueOnFailure`**: Whether to proceed with subsequent callbacks if this one fails.
- **`$queued`**: Boolean indicating if the callback is queued for execution.

## Methods

### getType
```php
public function getType(): string
```

#### Purpose
Determines the type of the callback based on its definition.

#### Return Value
Returns a string representing the type of the callable:
- `TYPE_CLOSURE`
- `TYPE_INVOKABLE`
- `TYPE_CALLABLE`
- `TYPE_SERVICE`

#### Functionality
Uses a `match` expression to ascertain the type of the callable. It checks if the callable is an instance of `Closure`, a string representing a class name, or an array representing a callable structure.

### shouldExecuteAt
```php
public function shouldExecuteAt(string $timing): bool
```

#### Purpose
Checks if the callback is set to execute at a specified timing.

#### Parameters
- **`$timing`**: A string representing the timing to check against.

#### Return Value
Returns a boolean indicating if the callback's timing matches the given timing.

#### Functionality
Simply compares the internal `timing` property with the provided argument.

### getDisplayName
```php
public function getDisplayName(): string
```

#### Purpose
Generates a human-readable name for the callback.

#### Return Value
Returns a string that represents the display name of the callback.

#### Functionality
Examines the `name` property first; if it is `null`, it derives a name based on the callback type:
- If it is an invokable, it retrieves the class name.
- If it is a closure, it returns that it's a closure callback.
- For arrays, it constructs a name from the class and method names.

### shouldExecuteAtPriority
```php
public function shouldExecuteAtPriority(int $minPriority): bool
```

#### Purpose
Checks if the callback should be executed based on its set priority.

#### Parameters
- **`$minPriority`**: An integer that represents the minimum priority threshold.

#### Return Value
Returns a boolean indicating if this callback's priority meets or exceeds the given threshold.

#### Functionality
Compares the current `priority` property with the provided minimum priority.

## Conclusion
The `TransitionCallback` class is an essential component in managing state transition callbacks within the FSM architecture. Its flexible constructor, well-structured callable handling, and robust methods for evaluating execution timing and priority make it a powerful tool for developers implementing FSMs in their applications.