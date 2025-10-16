# Documentation: StateDefinition.php

Original file: `src/Fsm/Data/StateDefinition.php`

# StateDefinition Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Constants](#class-constants)
- [Properties](#properties)
- [Constructor](#constructor)
- [Methods](#methods)
  - [isOfType](#isOfType)
  - [isInitial](#isInitial)
  - [isFinal](#isFinal)
  - [isError](#isError)
  - [isTerminal](#isTerminal)
  - [isTransient](#isTransient)
  - [getStateName](#getStateName)
  - [getMetadata](#getMetadata)
  - [hasMetadata](#hasMetadata)
  - [getDisplayName](#getDisplayName)
  - [getIcon](#getIcon)
  - [getCallbacksForTiming](#getCallbacksForTiming)

## Introduction
The `StateDefinition` class encapsulates the representation of a single state within a finite state machine (FSM) in the Laravel framework. This class provides a structured way to define states by incorporating immutable properties, typed constants for type safety, and callbacks for state transitions. It enhances static analysis, ensuring type safety and clarity in FSM implementation.

The `StateDefinition` relies on a strong design that allows behaviors to be ascribed to states, thus providing a comprehensive mechanism for managing state transitions within the machine.

## Class Constants
The class includes several typed constants that define the types, categories, and behaviors of states:

| Constant Name        | Value         | Description                                |
|----------------------|---------------|--------------------------------------------|
| `TYPE_INITIAL`       | `'initial'`   | Represents the initial state of the FSM.  |
| `TYPE_INTERMEDIATE`  | `'intermediate'` | Represents a state that is neither initial nor final. |
| `TYPE_FINAL`         | `'final'`     | Represents the concluding state of the FSM. |
| `TYPE_ERROR`         | `'error'`     | Indicates an error state within the FSM.  |
| `CATEGORY_PENDING`    | `'pending'`  | Designates the state as pending.          |
| `CATEGORY_ACTIVE`    | `'active'`    | Designates the state as actively processing. |
| `CATEGORY_COMPLETED` | `'completed'` | Indicates that the processing for this state is complete. |
| `CATEGORY_CANCELLED` | `'cancelled'` | Denotes that the state has been cancelled. |
| `CATEGORY_FAILED`    | `'failed'`    | Indicates a failed state in the processing flow. |
| `BEHAVIOR_TRANSIENT` | `'transient'` | Represents a state that does not persist. |
| `BEHAVIOR_PERSISTENT`| `'persistent'` | Indicates that the state persists.       |
| `BEHAVIOR_TERMINAL`  | `'terminal'`   | Indicates that this state is terminal.   |

## Properties
The class has various properties that define its state characteristics:

- **`onEntryCallbacks`**: `Collection<int, TransitionCallback>` - A collection of callbacks executed when entering the state.
- **`onExitCallbacks`**: `Collection<int, TransitionCallback>` - A collection of callbacks executed when exiting the state.
- **`name`**: `FsmStateEnum|string` - The name of the state, which can be an enum or a string.
- **`description`**: `string|null` - An optional human-readable description of the state.
- **`type`**: `string` - Type of the state as defined by the class constants, defaulting to `TYPE_INTERMEDIATE`.
- **`category`**: `string|null` - An optional category for the state.
- **`behavior`**: `string` - The behavior type for the state, defaulting to `BEHAVIOR_PERSISTENT`.
- **`metadata`**: `array<string, mixed>` - Additional metadata about the state.
- **`isTerminal`**: `bool` - A flag indicating whether this state is terminal, defaulting to `false`.
- **`priority`**: `int` - The priority assigned to this state, defaulting to `50`.

## Constructor
```php
public function __construct(
    array|FsmStateEnum|string $name,
    array|Collection $onEntryCallbacks = [],
    array|Collection $onExitCallbacks = [],
    ?string $description = null,
    string $type = self::TYPE_INTERMEDIATE,
    ?string $category = null,
    string $behavior = self::BEHAVIOR_PERSISTENT,
    array $metadata = [],
    bool $isTerminal = false,
    int $priority = 50,
)
```
### Purpose
The constructor initializes a new `StateDefinition` object. It can be called with either a direct name or an associative array for ease of use.

### Parameters
- **`$name`**: The name of the state, either as a `FsmStateEnum`, a string, or an associative array.
- **`$onEntryCallbacks`**: Callbacks to be executed when entering the state (optional).
- **`$onExitCallbacks`**: Callbacks to be executed when exiting the state (optional).
- **`$description`**: A human-readable description of the state (optional).
- **`$type`**: The state type (optional, defaults to `TYPE_INTERMEDIATE`).
- **`$category`**: The state category (optional).
- **`$behavior`**: The state behavior (optional, defaults to `BEHAVIOR_PERSISTENT`).
- **`$metadata`**: Additional metadata about the state (optional).
- **`$isTerminal`**: Indicates if the state is terminal (optional, defaults to `false`).
- **`$priority`**: The priority for state processing (optional, defaults to `50`).

### Functionality
- The constructor checks if the provided name is an associative array; if so, it initializes the state using the array values.
- It throws an `InvalidArgumentException` if it receives a non-associative array.
- The constructor invokes the parent constructor with the prepared attributes.

## Methods

### `isOfType`
```php
public function isOfType(string $type): bool
```
#### Purpose
Determines if the current state is of a specified type.

#### Parameters
- **`$type`**: The type to check against the state.

#### Return Value
- Returns `true` if the state is of the specified type; otherwise, `false`.

### `isInitial`
```php
public function isInitial(): bool
```
#### Purpose
Checks if the current state is the initial state.

#### Return Value
- Returns `true` if this is the initial state; otherwise, `false`.

### `isFinal`
```php
public function isFinal(): bool
```
#### Purpose
Checks if the current state is the final state.

#### Return Value
- Returns `true` if this is the final state; otherwise, `false`.

### `isError`
```php
public function isError(): bool
```
#### Purpose
Checks if the current state is