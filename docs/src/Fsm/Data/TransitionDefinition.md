# Documentation: TransitionDefinition.php

Original file: `src/Fsm/Data/TransitionDefinition.php`

# TransitionDefinition Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
- [Constructor](#constructor)
- [Static Methods](#static-methods)
- [Instance Methods](#instance-methods)
    - [isOfType](#isofType)
    - [isAutomatic](#isautomatic)
    - [isTriggered](#istriggered)
    - [hasGuards](#hasguards)
    - [hasActions](#hasactions)
    - [hasCallbacks](#hascallbacks)
    - [getFromStateName](#getfromstatename)
    - [getToStateName](#gettostatename)
    - [getMetadata](#getmetadata)
    - [hasMetadata](#hasmetadata)
    - [getGuardsForPriority](#getguardsforpriority)
    - [getActionsForTiming](#getactionsfortiming)
    - [getCallbacksForTiming](#getcallbacksfortiming)
    - [shouldEvaluateAllGuards](#shouldevaluateallguards)
    - [shouldEvaluateAnyGuard](#shouldevaluateanyguard)
    - [getDisplayDescription](#getdisplaydescription)

## Introduction
The `TransitionDefinition` class is a crucial component of the Finite State Machine (FSM) implementation. It encapsulates the definition of a single transition between two states, incorporating various attributes and functionalities to ensure proper state transitions. This class employs immutable properties and strong typing for enhanced safety and maintainability, making it easier for developers to define complex state transitions with guards, actions, and callbacks.

## Class Properties

### Transition Type Constants
| Constant          | Value       |
|-------------------|-------------|
| `TYPE_AUTOMATIC`  | 'automatic' |
| `TYPE_MANUAL`     | 'manual'    |
| `TYPE_TRIGGERED`  | 'triggered' |
| `TYPE_CONDITIONAL`| 'conditional'|

### Transition Priority Constants
| Constant          | Value |
|-------------------|-------|
| `PRIORITY_CRITICAL` | 100   |
| `PRIORITY_HIGH`     | 75    |
| `PRIORITY_NORMAL`   | 50    |
| `PRIORITY_LOW`      | 25    |

### Transition Behavior Constants
| Constant              | Value      |
|-----------------------|------------|
| `BEHAVIOR_IMMEDIATE`  | 'immediate'|
| `BEHAVIOR_DEFERRED`   | 'deferred' |
| `BEHAVIOR_QUEUED`     | 'queued'   |

### Guard Evaluation Constants
| Constant              | Value     |
|-----------------------|-----------|
| `GUARD_EVALUATION_ALL`| 'all'     |
| `GUARD_EVALUATION_ANY`| 'any'     |
| `GUARD_EVALUATION_FIRST`| 'first'   |

### Typed Properties
- `Collection<int, TransitionGuard> $guards`
- `Collection<int, TransitionAction> $actions`
- `Collection<int, TransitionCallback> $onTransitionCallbacks`
  
## Constructor
```php
public function __construct(
    FsmStateEnum|string|null|array $fromState = null,
    FsmStateEnum|string|null $toState = null,
    ?string $event = null,
    array|Collection $guards = [],
    array|Collection $actions = [],
    array|Collection $onTransitionCallbacks = [],
    ?string $description = null,
    string $type = self::TYPE_MANUAL,
    int $priority = self::PRIORITY_NORMAL,
    string $behavior = self::BEHAVIOR_IMMEDIATE,
    string $guardEvaluation = self::GUARD_EVALUATION_ALL,
    array $metadata = [],
    bool $isReversible = false,
    int $timeout = 30
)
```
### Purpose
The constructor initializes a new instance of the `TransitionDefinition` class, allowing for the definition of state transitions with comprehensive parameters.

### Parameters
- **`$fromState`**: The state to transition from (can be null for wildcard).
- **`$toState`**: The state to transition to (string or `FsmStateEnum`).
- **`$event`**: (optional) An optional event that triggers this transition.
- **`$guards`**: Guard conditions that must pass for the transition.
- **`$actions`**: Actions to execute during the transition.
- **`$onTransitionCallbacks`**: Callbacks specific to this transition path.
- **`$description`**: A human-readable description of the transition.
- **`$type`**: Type of transition, defaults to `self::TYPE_MANUAL`.
- **`$priority`**: Execution priority for this transition, defaults to `self::PRIORITY_NORMAL`.
- **`$behavior`**: Behavior of this transition, defaults to `self::BEHAVIOR_IMMEDIATE`.
- **`$guardEvaluation`**: Method to evaluate multiple guards (default is `self::GUARD_EVALUATION_ALL`).
- **`$metadata`**: Additional information regarding the transition.
- **`$isReversible`**: A boolean indicating if this transition can be reversed.
- **`$timeout`**: Maximum time for transition completion in seconds.

### Functionality
- The constructor supports both array-based and named parameters for flexible initialization.
- It includes validations for all attributes, ensuring proper types are received.
  
## Static Methods

### fromArray
```php
public static function fromArray(array $data): self
```
#### Purpose
Creates a `TransitionDefinition` instance from an associative array.

#### Parameters
- **`$data`**: An associative array containing transition definitions.

#### Returns
- A new `TransitionDefinition` instance.

#### Functionality
- Validates array keys and their corresponding values.
- Ensures that `toState` exists and is of the correct type.

## Instance Methods

### isOfType
```php
public function isOfType(string $type): bool
```
#### Purpose
Determines if the transition is of a specific type.

#### Parameters
- **`$type`**: The type to check against the current transition type.

#### Returns
- `true` if the transition is of the specified type, `false` otherwise.

### isAutomatic
```php
public function isAutomatic(): bool
```
#### Purpose
Checks if the transition is automatic.

#### Returns
- `true` if the transition type is automatic, `false` otherwise.

### isTriggered
```php
public function isTriggered(): bool
```
#### Purpose
Checks if the transition is triggered by an event.

#### Returns
- `true` if triggered, `false` otherwise.

### hasGuards
```php
public function hasGuards(): bool
```
#### Purpose
Checks if the transition has any guard conditions.

#### Returns
- `true` if there are guard conditions, `false` otherwise.

### hasActions
```php
public function hasActions(): bool
```
#### Purpose
Checks if the transition has any actions to execute.

#### Returns
- `true` if there are actions, `