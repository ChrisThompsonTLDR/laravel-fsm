# Documentation: FsmEventLog.php

Original file: `src/Fsm/Models/FsmEventLog.php`

# FsmEventLog.php Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Properties](#properties)
- [Methods](#methods)
  - [model()](#model)
  - [getReplayData()](#getreplaydata)
  - [forModel()](#formodel)

## Introduction
The `FsmEventLog` class serves as a model for logging state transition events in a finite state machine (FSM) system. It enables the storage and retrieval of detailed information about the transitions between states, which is crucial for event replay, analytics, and debugging purposes. By separating these logs from general FSM logs, the class facilitates more targeted tracking and analysis of state changes within various models.

## Class Overview
The `FsmEventLog` model extends the `Illuminate\Database\Eloquent\Model` and utilizes the `HasUuids` trait for handling universal unique identifiers. This model properly leverages Laravel's Eloquent ORM to provide an interface for interacting with the `fsm_event_logs` database table.

### Properties
| Property      | Type                                  | Description                                                               |
|---------------|---------------------------------------|---------------------------------------------------------------------------|
| `$id`         | `string`                              | The unique identifier for the event log entry.                          |
| `$model_id`   | `string`                              | The ID of the model that has undergone a state change.                   |
| `$model_type` | `string`                              | The type of the model that has undergone a state change.                 |
| `$column_name`| `string`                              | The name of the column in the model that is affected by the transition.  |
| `$from_state` | `string|null`                         | The state the model was in before the transition.                        |
| `$to_state`   | `string`                              | The state the model transitioned to.                                     |
| `$transition_name`| `string|null`                     | The name of the transition that occurred.                                |
| `$occurred_at`| `\Illuminate\Support\Carbon|null`    | The date and time when the event occurred.                              |
| `$context`    | `array<string, mixed>|null`          | Additional context data for the transition event.                        |
| `$metadata`   | `array<string, mixed>|null`          | Metadata related to the transition event.                                |
| `$created_at` | `\Illuminate\Support\Carbon`          | Timestamp indicating when the log entry was created.                    |

## Methods

### model()
```php
public function model(): MorphTo
```
- **Purpose:** Establishes a polymorphic relationship to retrieve the model that underwent the state transition.
  
- **Return Value:** Returns a `MorphTo` relationship instance representing the associated model.

- **Functionality:** This method allows you to access the model related to the event log. It employs polymorphic relations, meaning that the `model` can be of any type specified by the `model_type` property.

### getReplayData()
```php
public function getReplayData(): array
```
- **Purpose:** Retrieves an array representation of the transition event for replay purposes.

- **Return Value:** Returns an associative array containing the transition data structured as follows:
  ```php
  [
      'from_state' => string|null,
      'to_state' => string,
      'transition_name' => string|null,
      'occurred_at' => string|null,
      'context' => array<string, mixed>|null,
      'metadata' => array<string, mixed>|null,
  ]
  ```

- **Functionality:** This method extracts and constructs relevant transition data from the `fsm_event_logs`. It formats the occurrence date to ISO 8601 format for consistency and ease of parsing during event replay or historical analysis.

### forModel()
```php
public static function forModel(string $modelClass, string $modelId, string $columnName): \Illuminate\Database\Eloquent\Builder
```
- **Purpose:** Queries for event logs associated with a specific model instance and column.

- **Parameters:**
  - `string $modelClass`: The fully qualified class name of the model.
  - `string $modelId`: The unique identifier of the model instance.
  - `string $columnName`: The name of the column within the model.

- **Return Value:** Returns an Eloquent Query Builder instance for the matching event logs.

- **Functionality:** This static method facilitates filtering logs based on the `model_type`, `model_id`, and `column_name`, and sorts results by the `occurred_at` timestamp. It enables efficient querying for analysis or debugging of state transitions for specific model instances.

---

### Relationships
- **Polymorphic Relationship:** The `FsmEventLog` class uses a polymorphic relationship with the method `model()`, which allows it to link dynamically to any model that has undergone state changes, based on `model_type` and `model_id`.

### Conclusion
The `FsmEventLog` model encapsulates the logic necessary to track state transitions within an FSM context effectively. By using Eloquent ORM principles, it provides a convenient interface for storing, retrieving, and processing transition event data, which is essential for maintaining the integrity of the FSM and aiding in debugging and analytics.