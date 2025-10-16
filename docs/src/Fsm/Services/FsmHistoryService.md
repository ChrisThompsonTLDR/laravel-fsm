# Documentation: FsmHistoryService.php

Original file: `src/Fsm/Services/FsmHistoryService.php`

# FsmHistoryService Documentation

## Table of Contents
- [Introduction](#introduction)
- [getStateTimeline](#getstatetimeline)
  - [Purpose](#purpose)
  - [Parameters](#parameters)
  - [Return Value](#return-value)
  - [Functionality](#functionality)
- [getStateTimeAnalysis](#getstatetimeanalysis)
  - [Purpose](#purpose-1)
  - [Parameters](#parameters-1)
  - [Return Value](#return-value-1)
  - [Functionality](#functionality-1)

## Introduction
The `FsmHistoryService` class is designed to interact with the history of state transitions for entities that utilize a Finite State Machine (FSM). This service provides methods to retrieve detailed information about state changes and analyze the durations of time that entities spend in each state. It is integral to applications utilizing FSM as it allows for comprehensive analysis and tracking of state transitions, useful for debugging, reporting, and overall state management.

## getStateTimeline
### Purpose
The `getStateTimeline` method retrieves a complete timeline of state transitions for a specified model instance, providing insight into how the FSM column has evolved over time.

### Parameters
| Parameter        | Type                      | Description                                                                                |
|------------------|---------------------------|--------------------------------------------------------------------------------------------|
| `$model`         | `Model`                  | The model instance for which the state transition history is to be fetched.                |
| `$columnName`    | `string`                 | The name of the FSM column to query against.                                              |
| `$from`          | `DateTimeInterface|null`  | Optional parameter to specify the start date for filtering state transitions.             |
| `$to`            | `DateTimeInterface|null`  | Optional parameter to specify the end date for filtering state transitions.               |

### Return Value
Returns a `Collection<int, StateTimelineEntryData>` containing entries that represent the timeline of state transitions.

### Functionality
The method constructs a query to the `FsmLog` model to fetch relevant state transition logs based on the supplied `$model` and `$columnName`. It filters results based on optional date constraints (`$from` and `$to`) and retrieves the logs in chronological order. Each log entry is transformed into a `StateTimelineEntryData` object to provide structured access to the state transition details, such as from/to states and timestamps.

Here is the implementation:
```php
public function getStateTimeline(
    Model $model,
    string $columnName,
    ?DateTimeInterface $from = null,
    ?DateTimeInterface $to = null
): Collection {
    $query = FsmLog::query()
        ->select([
            'id',
            'model_id',
            'model_type',
            'fsm_column',
            'from_state',
            'to_state',
            'transition_event',
            'context_snapshot',
            'exception_details',
            'duration_ms',
            'happened_at',
            'subject_id',
            'subject_type',
        ])
        ->where('model_id', $model->getKey())
        ->where('model_type', $model->getMorphClass())
        ->where('fsm_column', $columnName)
        ->orderBy('happened_at', 'asc');

    if ($from !== null) {
        $query->where('happened_at', '>=', $from);
    }

    if ($to !== null) {
        $query->where('happened_at', '<=', $to);
    }

    return $query->get()->map(function (FsmLog $log) {
        return StateTimelineEntryData::from([
            'id' => $log->id,
            'model_id' => $log->model_id,
            'model_type' => $log->model_type,
            'fsm_column' => $log->fsm_column,
            'from_state' => $log->from_state,
            'to_state' => $log->to_state,
            'transition_event' => $log->transition_event,
            'context_snapshot' => $log->context_snapshot,
            'exception_details' => $log->exception_details,
            'duration_ms' => $log->duration_ms,
            'happened_at' => $log->happened_at,
            'subject_id' => $log->subject_id,
            'subject_type' => $log->subject_type,
        ]);
    });
}
```

## getStateTimeAnalysis
### Purpose
The `getStateTimeAnalysis` method analyzes the time spent in each state of a specified model entity, generating detailed statistics for state durations.

### Parameters
| Parameter        | Type                      | Description                                                                                |
|------------------|---------------------------|--------------------------------------------------------------------------------------------|
| `$model`         | `Model`                  | The model instance to analyze the state durations for.                                    |
| `$columnName`    | `string`                 | The name of the FSM column whose state durations are to be analyzed.                      |

### Return Value
Returns a `Collection<int, StateTimeAnalysisData>` containing analysis results for state durations, including total times, average durations, and occurrence counts.

### Functionality
This method first calls `getStateTimeline` to fetch the timeline of state transitions for the provided model and column name. It checks if the timeline is empty before proceeding. The function pairs and analyzes each timeline entry to compute the duration each state was active.

The analysis includes:
- Calculating the duration for transitions between states.
- Tracking occurrences of each state based on available transitions.
- Returning statistical data as `StateTimeAnalysisData` instances.

The computed statistics encompass:
- Total duration spent in each state.
- Count of transitions for each state.
- Average, minimum, and maximum duration of those states.

Here’s how the method is implemented:
```php
public function getStateTimeAnalysis(Model $model, string $columnName): Collection
{
    $timeline = $this->getStateTimeline($model, $columnName);

    if ($timeline->isEmpty()) {
        return collect();
    }

    $stateAnalysis = [];
    $timelineEntries = $timeline->values();

    for ($i = 0; $i < $timelineEntries->count(); $i++) {
        /** @var StateTimelineEntryData $currentEntry */
        $currentEntry = $timelineEntries[$i];
        $nextEntry = $timelineEntries[$i + 1] ?? null;

        if ($i > 0) {
            /** @var StateTimelineEntryData $previousEntry */
            $previousEntry = $timelineEntries[$i - 1];

            $fromState = $currentEntry->fromState;
            $currentHappenedAt = CarbonImmutable::make($currentEntry->happenedAt);
            $previousHappenedAt = CarbonImmutable::make($previousEntry->happenedAt);

            if ($fromState !== null && $currentHappenedAt instanceof CarbonImmutable && $previousHappenedAt instanceof CarbonImmutable) {
                $durationMs = $previousHappenedAt->diffInMilliseconds($currentHappenedAt);

                if (! isset($stateAnalysis[$fromState])) {
                    $stateAnalysis[$fromState] = [
                        'durations' => [],
