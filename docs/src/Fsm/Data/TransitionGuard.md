# Documentation: TransitionGuard.php

Original file: `src/Fsm/Data/TransitionGuard.php`

# TransitionGuard Documentation

## Table of Contents

- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Constants](#constants)
  - [Guard Type Constants](#guard-type-constants)
  - [Guard Result Constants](#guard-result-constants)
  - [Priority Levels](#priority-levels)
- [Constructor](#constructor)
- [Methods](#methods)
  - [getType()](#gettype)
  - [getDisplayName()](#getdisplayname)
  - [getServiceDisplayName()](#getservicedisplayname)
  - [shouldExecuteAtPriority()](#shouldexecuteatpriority)

## Introduction

The `TransitionGuard` class represents a guard condition for a state transition in the Finite State Machine (FSM) context. This class is designed to encapsulate the logic that determines whether a transition is permitted based on specified guard conditions, providing a flexible architecture to control transitions based on various callable types.

## Class Overview

`TransitionGuard` extends the `Dto` class and is equipped with properties that are marked as readonly for better immutability. It offers typed constants which help in enhancing type safety and support static analysis, ensuring that issues can be identified at compile time rather than runtime.

## Constants

### Guard Type Constants

The following constants define different guard types:
| Constant              | Value     | Description                      |
|----------------------|-----------|----------------------------------|
| `TYPE_CLOSURE`       | `'closure'` | Represents a closure-based guard. |
| `TYPE_INVOKABLE`     | `'invokable'` | Represents a class that can be invoked. |
| `TYPE_CALLABLE`      | `'callable'` | Represents callable arrays. |
| `TYPE_SERVICE`       | `'service'` | Represents service-based guards in Laravel. |

### Guard Result Constants

These constants define possible results of guard evaluations:
| Constant              | Value     | Description                      |
|----------------------|-----------|----------------------------------|
| `RESULT_ALLOW`       | `true`    | Indicates that the transition is allowed. |
| `RESULT_DENY`        | `false`   | Indicates that the transition is denied. |

### Priority Levels

The priority levels for guard execution specify the order in which guards are evaluated:
| Constant          | Value | Description             |
|-------------------|-------|-------------------------|
| `PRIORITY_CRITICAL` | `100` | Critical guard, highest priority. |
| `PRIORITY_HIGH`     | `75`  | High guard priority.   |
| `PRIORITY_NORMAL`   | `50`  | Normal guard priority. |
| `PRIORITY_LOW`      | `25`  | Low guard priority.    |

## Constructor

```php
public function __construct(
    array|string|Closure $callable,
    array $parameters = [],
    ?string $description = null,
    int $priority = self::PRIORITY_NORMAL,
    bool $stopOnFailure = false,
    ?string $name = null,
)
```

### Parameters

- **`$callable`**: (array|string|Closure) The guard logic, which can be a DTO attributes array, class string, Closure, or callable array.
- **`$parameters`**: (array)mixed Optional. Static parameters to pass to the guard in addition to `TransitionInput`.
- **`$description`**: (string|null) Optional. Description of the guard for logging/debugging.
- **`$priority`**: (int) Optional. Executes guards in order from higher to lower priority.
- **`$stopOnFailure`**: (bool) Optional. Indicates whether to halt the execution of other guards if this guard fails.
- **`$name`**: (string|null) Optional. Name for the guard for debugging/logging.

### Functionality

The constructor handles multiple types of `$callable`, implementing validation that verifies it either conforms to an array of callable structure or a DTO attributes structure. If neither structure is detected, it raises an `InvalidArgumentException`.

## Methods

### getType()

```php
public function getType(): string
```

#### Purpose

Determines the type of the guard based on the callable provided.

#### Returns

- **string**: The type of the guard.

#### Functionality

The method uses a match expression to return the appropriate type constant based on the type of callable (`Closure`, class name, or array).

### getDisplayName()

```php
public function getDisplayName(): string
```

#### Purpose

Provides a human-readable name for the guard instance.

#### Returns

- **string**: A display name representing the guard, tailored based on its type.

#### Functionality

The method checks if a `name` has been set and returns it. If not, it derives a display name based on the type of guard using another match expression, accommodating closures, classes, or service callables.

### getServiceDisplayName()

```php
private function getServiceDisplayName(): string
```

#### Purpose

Generates a display name for service-type callables.

#### Returns

- **string**: A display name for the service guard.

#### Functionality

This private method checks if the callable is a string and formats it accordingly to represent the Laravel service callable format, replacing the `@` symbol with `::`.

### shouldExecuteAtPriority()

```php
public function shouldExecuteAtPriority(int $minPriority): bool
```

#### Purpose

Checks if this guard should be executed based on the provided priority threshold.

#### Returns

- **bool**: Returns true if the guard's priority is equal to or greater than the specified minimum priority.

#### Functionality

This method simply compares the guard's priority against the provided minimum value and returns a boolean indicating whether the guard meets the execution threshold.

## Conclusion

The `TransitionGuard` class is a pivotal component of the FSM implementation in this codebase. By leveraging guard logical constructs, it provides structured decision-making for transition evolution based on specified logical conditions, ensuring a powerful and flexible state management system. Through its thoughtfully designed constants and methods, this class aids in clarity and intention, making it easier for developers to implement state transitions effectively.