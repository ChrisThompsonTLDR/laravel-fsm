# Documentation: StateTimeAnalysisData.php

Original file: `src/Fsm/Data/StateTimeAnalysisData.php`

# StateTimeAnalysisData Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
- [Constructor Method](#constructor-method)

## Introduction

The `StateTimeAnalysisData` class is a Data Transfer Object (DTO) that represents time analysis data for a specific state in a state machine or finite state machine (FSM) system. It encapsulates information regarding the total duration of time an entity has spent in a particular state, the total number of occurrences of that state, along with additional metrics such as average, minimum, and maximum durations.

This class is designed to facilitate the tracking and reporting of state performance within the larger context of state management. It provides a structured way to store and validate state duration data, ensuring that any analysis performed on state timings is based on reliable data.

## Class Properties

| Property                | Type     | Description                                                      |
|------------------------|----------|------------------------------------------------------------------|
| `$state`               | `string` | The name of the state the object is analyzing.                  |
| `$totalDurationMs`     | `int`    | Total duration (in milliseconds) that an entity has spent in the state. |
| `$occurrenceCount`     | `int`    | The total number of times the state has been entered.           |
| `$averageDurationMs`   | `float`  | The average time (in milliseconds) spent in this state.        |
| `$minDurationMs`       | `?int`   | The minimum time (in milliseconds) spent in this state, or null if not applicable. |
| `$maxDurationMs`       | `?int`   | The maximum time (in milliseconds) spent in this state, or null if not applicable. |

## Constructor Method

```php
public function __construct(
    string|array $state,
    int $totalDurationMs = 0,
    int $occurrenceCount = 0,
    float $averageDurationMs = 0.0,
    ?int $minDurationMs = null,
    ?int $maxDurationMs = null,
)
```

### Purpose
The constructor initializes a new instance of the `StateTimeAnalysisData` class. It can be called with either a state string and other relevant parameters (for named parameter initialization) or an associative array containing the state-related values.

### Parameters

- `$state` (`string|array`): 
  - A string representing the state name, or an associative array that must contain the keys: 'state', 'totalDurationMs', 'occurrenceCount', and 'averageDurationMs'. 
- `$totalDurationMs` (`int`): 
  - Optional. The total duration in milliseconds the entity has spent in this state. Default is `0`.
- `$occurrenceCount` (`int`): 
  - Optional. The total count of how many times this state has been entered. Default is `0`.
- `$averageDurationMs` (`float`): 
  - Optional. The average time spent in this state in milliseconds. Default is `0.0`.
- `$minDurationMs` (`?int`): 
  - Optional. The minimum duration spent in this state. Default is `null`.
- `$maxDurationMs` (`?int`): 
  - Optional. The maximum duration spent in this state. Default is `null`.

### Functionality

- The constructor accepts parameters either in a traditional named parameter format or as a single associative array for flexible initialization.
- It performs robust validation of input parameters, ensuring that all required fields are present and of the correct types. 
- If initialized via an associative array, it will enforce that the array is associative and checks for required keys. If the checks fail, it throws an `InvalidArgumentException`.
- If specific optional properties (`minDurationMs` and `maxDurationMs`) are provided and are not integers, appropriate exceptions are raised.

The constructor makes it easy for developers to create instances of `StateTimeAnalysisData` while ensuring that the integrity of the data remains intact through comprehensive validation processes.

---

This documentation aims to clarify the role and usage of the `StateTimeAnalysisData` class within the context of a state management system, providing developers with both the understanding and technical specifics necessary for effective application development and integration.