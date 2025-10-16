# Documentation: FsmTransitioned.php

Original file: `src/Fsm/Verbs/FsmTransitioned.php`

# FsmTransitioned Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Constructor](#constructor)
- [Factory Methods](#factory-methods)
  - [fromModel](#frommodel)
  - [fromTransitionInput](#fromtransitioninput)
- [Recording Events](#record)
- [State Management Methods](#state-management-methods)
  - [getFromStateName](#getfromstatename)
  - [getToStateName](#gettoastatename)
  - [wasSuccessful](#wassuccessful)
  - [wasBlocked](#wasblocked)
  - [hasFailed](#hasfailed)
- [Metadata and Event Handling](#metadata-and-event-handling)
  - [getMetadata](#getmetadata)
  - [getEventType](#geteventtype)
  - [getAggregateType](#getaggregatetype)
  - [getAggregateId](#getaggregateid)
  - [toEventSourcingArray](#toeventsourcingarray)

## Introduction
The `FsmTransitioned` class is a verb that represents a successful transition in a Finite State Machine (FSM) for an Eloquent model in the Laravel framework. This class captures essential details about the transition, including the model involved, previous and new states, results of the transition, and various contextual information. It is designed to enhance event sourcing capabilities with immutable properties and type-safe constants to ensure clarity and maintainability in handling state transitions.

## Class Overview
The `FsmTransitioned` class extends the `Event` class and implements `SerializedByVerbs`, allowing for serialization in the context of event sourcing within the application. It utilizes traits for state name conversion and normalization of properties and class names to enhance functionality the associated event's handling.

### Constants
The class defines several constants for transition results, sources of events, event types, aggregates, and transition priorities, allowing for better type safety and improved code readability.

## Constructor
### `__construct`
```php
public function __construct(
    public readonly string $modelId,
    public readonly string $modelType,
    public readonly string $fsmColumn,
    public readonly FsmStateEnum|string|null $fromState,
    public readonly FsmStateEnum|string|null $toState,
    public readonly string $result = self::RESULT_SUCCESS,
    public readonly ?ArgonautDTOContract $context = null,
    public readonly ?string $transitionEvent = null,
    public readonly string $source = self::SOURCE_USER_ACTION,
    public readonly array $metadata = [],
    public readonly ?\DateTimeInterface $occurredAt = null,
    public readonly int $priority = self::PRIORITY_NORMAL,
    public readonly ?string $correlationId = null,
    public readonly ?string $causationId = null,
)
```
#### Purpose
The constructor initializes an instance of the `FsmTransitioned` class, setting up the properties necessary for capturing event data related to an FSM transition.

#### Parameters
- **modelId** (`string`): The unique identifier of the Eloquent model related to the FSM transition.
- **modelType** (`string`): The class name of the Eloquent model, facilitating polymorphic behavior.
- **fsmColumn** (`string`): The name of the column in the model representing the FSM state.
- **fromState** (`FsmStateEnum|string|null`): The original state of the FSM before the transition.
- **toState** (`FsmStateEnum|string|null`): The new state of the FSM after the transition.
- **result** (`string`, optional): The result of the transition (default is `RESULT_SUCCESS`).
- **context** (`ArgonautDTOContract|null`, optional): Any additional context provided during the transition.
- **transitionEvent** (`string|null`, optional): The specific event name that triggered this transition.
- **source** (`string`, optional): The source of the transition (default is `SOURCE_USER_ACTION`).
- **metadata** (`array<string, mixed>`, optional): Additional information related to the transition.
- **occurredAt** (`\DateTimeInterface|null`, optional): Timestamp of when the transition took place.
- **priority** (`int`, optional): Priority level for this event (default is `PRIORITY_NORMAL`).
- **correlationId** (`string|null`, optional): An optional ID for tracing requests across boundaries.
- **causationId** (`string|null`, optional): An optional ID for linking events in a chain.

## Factory Methods

### `fromModel`
```php
public static function fromModel(
    Model $model,
    string $fsmColumn,
    FsmStateEnum|string $fromState,
    FsmStateEnum|string $toState,
    string $result = self::RESULT_SUCCESS,
    ?ArgonautDTOContract $context = null,
    ?string $transitionEvent = null,
    string $source = self::SOURCE_USER_ACTION,
    array $metadata = [],
): self
```
#### Purpose
This static method creates an instance of `FsmTransitioned` from a model instance, conveniently encapsulating the transition information.

#### Parameters
- **model** (`Model`): The model instance that has undergone a state transition.
- **fsmColumn** (`string`): The FSM column name in the model.
- **fromState** (`FsmStateEnum|string`): The FSM state before the transition.
- **toState** (`FsmStateEnum|string`): The FSM state after the transition.
- **result** (`string`, optional): The result of the transition (default is `RESULT_SUCCESS`).
- **context** (`ArgonautDTOContract|null`, optional): Context object provided during the transition.
- **transitionEvent** (`string|null`, optional): The triggering event for the state transition.
- **source** (`string`, optional): Source of the transition (default is `SOURCE_USER_ACTION`).
- **metadata** (`array<string, mixed>`, optional): Additional metadata for the transition.

#### Returns
Returns a new instance of `FsmTransitioned`.

#### Functionality
The method retrieves the model key for the ID, captures the class name, and initializes the transition details for the FSM transition event.

### `fromTransitionInput`
```php
public static function fromTransitionInput(
    TransitionInput $input,
    string $fsmColumn,
    string $result = self::RESULT_SUCCESS,
): self
```
#### Purpose
Creates an instance of `FsmTransitioned` using data encapsulated within a `TransitionInput`.

#### Parameters
- **input** (`TransitionInput`): Contains data regarding the FSM transition.
- **fsmColumn** (`string`): The FSM column name in the model.
- **result** (`string`, optional): The result of the transition (default is `RESULT_SUCCESS`).

#### Returns
Returns a new instance of `FsmTransitioned`.

#### Functionality
This method utilizes the values stored in the `TransitionInput` object to build and initialize a new `FsmTransitioned` instance. This encapsulates the input's context and metadata effectively.

## Recording Events
### `record`
```php
public static function record(...$args): self
```
####