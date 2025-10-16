# Documentation: FsmReplayService.php

Original file: `src/Fsm/Services/FsmReplayService.php`

# FsmReplayService Documentation

## Table of Contents
- [Introduction](#introduction)
- [Methods](#methods)
  - [getTransitionHistory](#gettransitionhistory)
  - [replayTransitions](#replaytransitions)
  - [validateTransitionHistory](#validatetransitionhistory)
  - [getTransitionStatistics](#gettransitionstatistics)

## Introduction

The `FsmReplayService` class is part of the FSM (Finite State Machine) suite within the Laravel application. Its primary role is to provide functionalities for replaying state transitions based on event logs. This enables developers to reconstruct state history, validate transitions, generate analytics, and debug state changes effectively.

The service is particularly beneficial in scenarios such as:
- Auditing state changes to comply with regulatory standards.
- Analyzing the performance and usage patterns in applications utilizing state machines.
- Recovering the correct states in case of data corruption or to validate the robustness of state transition implementations.

## Methods

### getTransitionHistory

```php
public function getTransitionHistory(string $modelClass, string $modelId, string $columnName): EloquentCollection
```

#### Purpose
Retrieves the history of state transitions for a specified model and column.

#### Parameters
| Parameter   | Type              | Description                               |
|-------------|-------------------|-------------------------------------------|
| `$modelClass` | `class-string<Model>` | The fully qualified class name of the model. |
| `$modelId`   | `string`          | The unique identifier of the model instance. |
| `$columnName`| `string`          | The name of the column representing the state. |

#### Returns
- **EloquentCollection**: A collection of `FsmEventLog` records representing the state transition history.

#### Functionality
The method checks that both `modelId` and `columnName` are provided (non-empty strings) and queries the `FsmEventLog` based on the specified model and state column. If any of the parameters are invalid, an `InvalidArgumentException` is thrown.

### replayTransitions

```php
public function replayTransitions(string $modelClass, string $modelId, string $columnName): array
```

#### Purpose
Replays the state transitions for a model to determine its current state, providing a comprehensive overview of the transition flow.

#### Parameters
| Parameter   | Type              | Description                               |
|-------------|-------------------|-------------------------------------------|
| `$modelClass` | `class-string<Model>` | The fully qualified class name of the model. |
| `$modelId`   | `string`          | The unique identifier of the model instance. |
| `$columnName`| `string`          | The name of the column representing the state. |

#### Returns
- **array**: An associative array with the following keys:
  - `initial_state`: The state before the first transition (or `null` if none).
  - `final_state`: The state after the last transition (or `null` if none).
  - `transition_count`: The total number of transitions.
  - `transitions`: An array of transition records detailing each transition.

#### Functionality
This method retrieves the transition history using `getTransitionHistory`. If no transitions exist, it returns a default structure indicating no state changes. Otherwise, it captures the initial and final states while mapping each transition's relevant data to create a detailed replay report.

### validateTransitionHistory

```php
public function validateTransitionHistory(string $modelClass, string $modelId, string $columnName): array
```

#### Purpose
Validates the continuity and consistency of the transition history to detect anomalies caused by data corruption or erroneous state transitions.

#### Parameters
| Parameter   | Type              | Description                               |
|-------------|-------------------|-------------------------------------------|
| `$modelClass` | `class-string<Model>` | The fully qualified class name of the model. |
| `$modelId`   | `string`          | The unique identifier of the model instance. |
| `$columnName`| `string`          | The name of the column representing the state. |

#### Returns
- **array**: An associative array containing:
  - `valid`: A boolean indicating whether the transition history is valid.
  - `errors`: An array of validation error messages (empty if valid).

#### Functionality
After obtaining the transitions, the method iterates through them, checking that the `from_state` of each transition matches the `to_state` of the previous transition. If inconsistencies are found, error messages are collected and returned alongside a validity flag.

### getTransitionStatistics

```php
public function getTransitionStatistics(string $modelClass, string $modelId, string $columnName): array
```

#### Purpose
Generates statistics regarding the transitions for a specific model and column, providing insights into state machine usage patterns.

#### Parameters
| Parameter   | Type              | Description                               |
|-------------|-------------------|-------------------------------------------|
| `$modelClass` | `class-string<Model>` | The fully qualified class name of the model. |
| `$modelId`   | `string`          | The unique identifier of the model instance. |
| `$columnName`| `string`          | The name of the column representing the state. |

#### Returns
- **array**: An associative array containing:
  - `total_transitions`: The total number of transitions recorded.
  - `unique_states`: The count of unique states encountered during transitions.
  - `state_frequency`: An associative array where states are keys, and their counts are values.
  - `transition_frequency`: An associative array where transition keys are strings representing state changes, and their counts are values.

#### Functionality
The method processes the retrieved transition history to compile frequency counts for both states and discrete transitions. This data is useful for analyzing trends, optimizing performance, and understanding user behavior in relation to the state machine.

---

This documentation provides a comprehensive overview of the `FsmReplayService` and its methods, offering both detailed technical data and practical usage information for developers working with or extending this functionality within a Laravel-based application.