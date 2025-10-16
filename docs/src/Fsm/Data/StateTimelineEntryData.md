# Documentation: StateTimelineEntryData.php

Original file: `src/Fsm/Data/StateTimelineEntryData.php`

# StateTimelineEntryData Documentation

## Table of Contents
1. [Introduction](#introduction)
2. [Class Overview](#class-overview)
3. [Constructor](#constructor)
4. [Properties](#properties)
5. [Usage Examples](#usage-examples)

## Introduction

`StateTimelineEntryData.php` defines the `StateTimelineEntryData` class, which represents a Data Transfer Object (DTO) for individual state transitions within a finite state machine (FSM) context. The class encapsulates details pertinent to a state transition event—such as transition timestamps, state details, and additional context—that can be useful for logging, debugging, or analysis purposes.

This class is designed to be versatile, acting as both a structure for direct instantiation with named parameters or associative arrays, making it easier to construct instances with varying requirements.

## Class Overview

### Namespace

```php
namespace Fsm\Data;
```

The `StateTimelineEntryData` class is part of the `Fsm\Data` namespace, organizing it within the broader finite state machine module.

### Extends

```php
class StateTimelineEntryData extends Dto
```

This class extends the base `Dto` class, inheriting its properties and functionalities, while providing additional specific properties and behaviors suited for handling state transition data.

## Constructor

```php
public function __construct(
    string|array $id,
    ?string $model_id = null,
    ?string $model_type = null,
    ?string $fsm_column = null,
    ?string $from_state = null,
    ?string $to_state = null,
    ?string $transition_event = null,
    ?array $context_snapshot = null,
    ?string $exception_details = null,
    ?int $duration_ms = null,
    ?CarbonImmutable $happened_at = null,
    ?string $subject_id = null,
    ?string $subject_type = null,
)
```

### Purpose

The constructor initializes a new instance of `StateTimelineEntryData`, accepting a variety of parameters for the different aspects of the state transition event. It allows for flexibility in constructing instances either via individual arguments or an associative array.

### Parameters

| Parameter             | Type                      | Description                                           |
|-----------------------|---------------------------|-------------------------------------------------------|
| `$id`                 | `string | array`        | Unique identifier for the transition entry. Accepts either a string or an associative array (if an array, it must contain the key 'id'). |
| `$model_id`          | `?string`                 | The ID of the model associated with the transition. Default is `null`. |
| `$model_type`        | `?string`                 | The type of the model associated with the transition. Default is `null`. |
| `$fsm_column`        | `?string`                 | The FSM column used for the state transition. Default is `null`. |
| `$from_state`        | `?string`                 | The state prior to the transition. Default is `null`. |
| `$to_state`          | `?string`                 | The state after the transition. Default is `null`. |
| `$transition_event`   | `?string`                 | The event triggering the transition. Default is `null`. |
| `$context_snapshot`  | `?array<string, mixed>`   | Optional context data at the time of the transition. Default is `null`. |
| `$exception_details` | `?string`                 | Details about any exceptions that occurred during the transition. Default is `null`. |
| `$duration_ms`       | `?int`                    | The duration of the transition in milliseconds. Default is `null`. |
| `$happened_at`       | `?CarbonImmutable`        | The timestamp when the transition occurred. Default is `null`. |
| `$subject_id`        | `?string`                 | The ID of the subject associated with the transition. Default is `null`. |
| `$subject_type`      | `?string`                 | The type of the subject associated with the transition. Default is `null`. |

### Functionality

The constructor contains logic that distinguishes between array-based construction and named parameter initialization. If an array is passed as the first argument:

- The method checks if it is associative and prepares attributes using a private method `prepareAttributes()`.
- It validates the presence of required keys (only `id` is mandatory) and prepares the properties for the object.
- If an SDK-compliant associative array is not passed, an exception will be thrown to ensure clarity about expected input.

For named parameter initialization, it directly prepares and assigns attributes using `prepareAttributes()`.

## Properties

The class defines several public properties that store the details of the state transition. All properties are initialized to `null` by default except for `$id`, which is mandatory (type string).

### Property Table

| Property               | Type                      | Description                                           |
|-----------------------|--------------------------|-------------------------------------------------------|
| `public string $id`   | string                   | Unique identifier for the transition entry.           |
| `public ?string $modelId` | string | ID of the associated model, or `null`.                    |
| `public ?string $modelType` | string | Type of the associated model, or `null`.                |
| `public ?string $fsmColumn` | string | FSM column involved in the transition, or `null`.      |
| `public ?string $fromState` | string | Previous state before the transition, or `null`.       |
| `public ?string $toState` | string | Next state after the transition, or `null`.            |
| `public ?string $transitionEvent` | string | Event triggering the transition, or `null`.             |
| `public ?array $contextSnapshot` | array | Contextual data at transition time, or `null`.         |
| `public ?string $exceptionDetails` | string | Any exception details during the process, or `null`.    |
| `public ?int $durationMs` | int | Duration of the transition in milliseconds, or `null`.    |
| `public ?CarbonImmutable $happenedAt` | CarbonImmutable | Timestamp of when the transition occurred, or `null`.   |
| `public ?string $subjectId` | string | ID of the subject for this transition, or `null`.       |
| `public ?string $subjectType` | string | Type of subject associated with the transition, or `null`. |

## Usage Examples

To create a new instance of `StateTimelineEntryData`, you can do so either through named parameters or by passing an associative array:

### Named Parameter Initialization

```php
$entry = new StateTimelineEntryData(
    id: 'transition_1',
    model_id: 'model_1',
    from_state: 'initial',
    to_state: 'completed',
    transition_event: 'start_process',
    duration_ms: 200,
    happened_at: Carbon\CarbonImmutable::now()
);
```

### Associative Array Initialization

```php
$entry = new StateTimelineEntryData([
    'id' => 'transition_1',
    'model_id' => 'model_1',
    'from_state' => 'initial