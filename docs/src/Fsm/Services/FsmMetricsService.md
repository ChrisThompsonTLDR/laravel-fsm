# Documentation: FsmMetricsService.php

Original file: `src/Fsm/Services/FsmMetricsService.php`

# FsmMetricsService Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class: FsmMetricsService](#class-fsmmetricsservice)
  - [Method: record](#method-record)

## Introduction
The `FsmMetricsService` class is part of the `Fsm\Services` namespace in the Laravel application and is designed to facilitate the tracking of state transitions within a finite state machine (FSM). It leverages Laravel's event dispatching system and caching mechanism to record successful and failed transitions of models, sending relevant metrics for further analysis and monitoring.

It is particularly useful in contexts where state transitions are critical and need to be monitored for performance, error handling, or user behavior analysis.

## Class: FsmMetricsService

### Properties
| Name                | Type                | Description                                                   |
|---------------------|---------------------|---------------------------------------------------------------|
| CACHE_KEY_SUCCESS    | string              | The cache key used to track successful transitions.            |
| CACHE_KEY_FAILURE    | string              | The cache key used to track failed transitions.                |
| dispatcher           | Dispatcher          | An instance of Laravel's event dispatcher for dispatching events. |

### Constructor

```php
public function __construct(
    private readonly Dispatcher $dispatcher
)
```

#### Purpose
The constructor initializes the `FsmMetricsService` with a `Dispatcher` instance, which is essential for dispatching transition events.

#### Parameters
- `Dispatcher $dispatcher`: An instance of the Laravel event dispatcher that enables the service to send events related to state transitions.

#### Return Values
- None.

### Method: record

```php
public function record(
    Model $model,
    string $columnName,
    FsmStateEnum|string|null $fromState,
    FsmStateEnum|string $toState,
    bool $successful,
    ?ArgonautDTOContract $context = null
): void
```

#### Purpose
Records a state transition from one FSM state to another, tracking whether the transition was successful or not. It also increments the appropriate cache key for statistics.

#### Parameters
| Name           | Type                         | Description                                                       |
|----------------|------------------------------|-------------------------------------------------------------------|
| `$model`       | Model                        | The Eloquent model whose state is being recorded.                 |
| `$columnName`  | string                       | The name of the column representing the FSM state in the model.   |
| `$fromState`   | FsmStateEnum|string|null     | The state being transitioned from. Can be a state enum or null.   |
| `$toState`     | FsmStateEnum|string          | The state being transitioned to. This cannot be null.            |
| `$successful`   | bool                         | Indicates whether the transition was successful (true) or failed (false). |
| `$context`     | ?ArgonautDTOContract         | Optional context information related to the transition.           |

#### Functionality
1. **Caching**: 
   - Increments the cache count for successful or failed transitions based on the `$successful` parameter using predefined cache keys (`CACHE_KEY_SUCCESS` or `CACHE_KEY_FAILURE`).

2. **Event Dispatching**: 
   - Dispatches a new `TransitionMetric` event with the details of the transition including the model, column name, states, success status, and any optional context provided.
   
3. **Error Handling**: 
   - Not explicitly listed, but since the method parameters are strictly typed, passing invalid types will result in a `TypeError`.

4. **State Management**:
   - It allows developers to monitor FSMs effectively by providing metrics that can be used to identify patterns or issues in state transitions.

### Usage Example
Here's a brief example demonstrating how to use the `FsmMetricsService`:

```php
use Fsm\Services\FsmMetricsService;
use SomeModel; // An Eloquent model
use SomeStateEnum; // An enum representing FSM states

$fsmMetricsService = new FsmMetricsService($dispatcher);
$model = new SomeModel();
$fsmMetricsService->record($model, 'state', null, SomeStateEnum::NEW, true);
```

In this example, we record a successful transition from no state (null) to `SomeStateEnum::NEW` for an instance of `SomeModel`.

## Conclusion
The `FsmMetricsService` provides essential functionalities for recording and monitoring state transitions within a finite state machine in a Laravel application. By utilizing caching and event dispatching, it enhances the observability of state changes, thereby aiding developers in maintaining the health and performance of their FSM implementations.