# Documentation: FsmLog.php

Original file: `src/Fsm/Models/FsmLog.php`

# FsmLog Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Attributes](#class-attributes)
- [Methods](#methods)
  - [extractUserId](#extractuserid)
  - [subject](#subject)
  - [model](#model)
  - [booted](#booted)

## Introduction
The `FsmLog` class is an Eloquent model responsible for logging state transitions in a Finite State Machine (FSM) context. It is part of the `Fsm\Models` namespace and facilitates the tracking of events related to FSM changes, ensuring that important data regarding state transitions is captured and stored in the `fsm_logs` database table. This class utilizes UUIDs for record identification and supports the ability to relate logs to specific subject and model instances.

## Class Attributes
The `FsmLog` class contains several important attributes that correspond to columns in the `fsm_logs` database table:

| Attribute           | Type                                 | Description                                                         |
|---------------------|--------------------------------------|---------------------------------------------------------------------|
| `id`                | `string`                             | Unique identifier for the log entry (UUID).                        |
| `subject_id`        | `string|null`                        | ID of the subject that triggered the transition (e.g., a user).    |
| `subject_type`      | `string|null`                        | Type of the subject that triggered the transition.                  |
| `model_id`          | `string`                             | ID of the model that this log entry is associated with.            |
| `model_type`        | `string`                             | Type of the model that this log entry is associated with.          |
| `fsm_column`        | `string`                             | Name of the FSM column involved in the transition.                 |
| `from_state`        | `string`                             | The state prior to the transition.                                 |
| `to_state`          | `string`                             | The state after the transition.                                    |
| `transition_event`  | `string|null`                        | The event that triggered the transition.                           |
| `context_snapshot`  | `array<string, mixed>|null`         | Snapshot of the context during the transition.                     |
| `exception_details` | `string|null`                        | Details of any exception encountered during the transition.        |
| `duration_ms`       | `int|null`                          | Duration of the transition in milliseconds.                        |
| `happened_at`       | `\Illuminate\Support\Carbon`         | Timestamp when the transition occurred.                            |

## Methods

### extractUserId
```php
private static function extractUserId($state): ?string
```
- **Purpose**: Extracts a user ID from a state object, regardless of property visibility.
- **Parameters**: 
  - `object|null $state`: The state object from which to extract the user ID. If the state is not an object, it returns `null`.
- **Return Value**: 
  - Returns the user ID as a string if found, or `null` if not.
- **Functionality**: 
  - This method attempts to retrieve the user ID in three ways:
    1. Checks if `user_id` is a public property of the state object.
    2. Looks for a `getUserId()` method on the state object and invokes it.
    3. Utilizes PHP's Reflection API to check for a `user_id` property and accesses it if available.
  - If no user ID is found through these methods, it returns `null`.

### subject
```php
public function subject(): MorphTo
```
- **Purpose**: Defines a polymorphic relationship to the subject that triggered the FSM transition.
- **Return Value**: 
  - Returns an instance of `MorphTo`, which is an Eloquent relationship type for handling polymorphic relations.
- **Functionality**: 
  - This method establishes a relationship with other Eloquent models through the `subject_id` and `subject_type` attributes, allowing the `FsmLog` to dynamically connect to different types of subjects.

### model
```php
public function model(): MorphTo
```
- **Purpose**: Defines a polymorphic relationship to the model associated with the log entry.
- **Return Value**: 
  - Returns an instance of `MorphTo`, enabling dynamic relationship resolution with various models.
- **Functionality**: 
  - Similar to the `subject` method, this allows the `FsmLog` to relate to different model types based on the `model_id` and `model_type` attributes.

### booted
```php
protected static function booted(): void
```
- **Purpose**: Bootstraps model events.
- **Functionality**: 
  - This method is called when the model is initialized and attaches an event listener to the `creating` event. During the creation of a `FsmLog` instance:
    - If `happened_at` is empty, it sets it to the current timestamp.
    - It attempts to set the `subject_id` and `subject_type` based on the current `Verbs` state if configured.
    - This conditional logic helps in automatically capturing the user context during FSM transitions.

## Conclusion
The `FsmLog` class provides integral functionality for tracking FSM transitions in a Laravel application. Its design allows for extensible logging capabilities by utilizing polymorphic relations, ensuring that developers can maintain comprehensive logs related to state changes, exceptions, and associated context. This documentation aims to clarify the inner workings of the `FsmLog` class, aiding developers in leveraging its capabilities effectively.