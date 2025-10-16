# Documentation: FsmRuntimeDefinition.php

Original file: `src/Fsm/Data/FsmRuntimeDefinition.php`

# FsmRuntimeDefinition Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
- [Constructor](#constructor)
- [Methods](#methods)
  - [getStateDefinition](#getstatedefinition)
  - [getTransitionsFor](#gettransitionsfor)
  - [export](#export)

## Introduction
The `FsmRuntimeDefinition` class represents a compiled runtime definition of a Finite State Machine (FSM) for a specific Eloquent model and associated column. This class is crucial for managing the states and transitions defined for the model, allowing for a structured approach to handling state changes within the application. It encapsulates all relevant FSM data, including states, transitions, and an initial state, thus enabling efficient querying and manipulation of FSM structures.

## Class Properties
The class contains the following properties:

| Property                | Type                                        | Description                                                   |
|------------------------|---------------------------------------------|---------------------------------------------------------------|
| `states`               | `array<string, StateDefinition>`          | All states defined in the FSM, keyed by their string value.  |
| `transitions`          | `TransitionDefinition[]`                   | All transitions defined in this FSM as a list.                |
| `initialState`         | `FsmStateEnum|string|null`                | The initial state of the FSM.                                 |
| `modelClass`           | `class-string`                             | The Eloquent model class this FSM is for.                     |
| `columnName`           | `string`                                   | The column on the model that stores the state.                |
| `contextDtoClass`      | `string|null`                              | Optional context Data Transfer Object class.                  |
| `description`          | `string|null`                              | Optional description of the FSM.                               |

## Constructor
```php
public function __construct(
    public readonly string $modelClass,
    public readonly string $columnName,
    array $stateDefinitions,
    array $transitionDefinitions,
    FsmStateEnum|string|null $initialState = null,
    public readonly ?string $contextDtoClass = null,
    public readonly ?string $description = null,
)
```

### Purpose
The constructor initializes an instance of the `FsmRuntimeDefinition` class with parameters representing the model class, column name, state definitions, transition definitions, the initial state, and optional contextual information.

### Parameters
- `string $modelClass`: The full class name of the Eloquent model that the FSM is associated with.
- `string $columnName`: The name of the column in the model that will store the FSM's current state.
- `array<int, StateDefinition> $stateDefinitions`: An array of `StateDefinition` objects representing all defined states.
- `array<int, TransitionDefinition> $transitionDefinitions`: An array of `TransitionDefinition` objects representing all defined transitions.
- `FsmStateEnum|string|null $initialState`: An optional initial state for the FSM, which can be a `FsmStateEnum` or a string, or `null`.
- `string|null $contextDtoClass`: An optional class name for a data transfer object that provides contextual information for the FSM.
- `string|null $description`: An optional description of the FSM.

### Functionality
The constructor compiles the state definitions into an associative array keyed by their string values, making them easily retrievable. It also collects transition definitions into a list, and initializes the initial state if provided.

## Methods

### getStateDefinition
```php
public function getStateDefinition(FsmStateEnum|string|null $state): ?StateDefinition
```

#### Purpose
Retrieves the state definition for a given state.

#### Parameters
- `FsmStateEnum|string|null $state`: The state to look up, which can be an instance of `FsmStateEnum`, a string, or `null`.

#### Return Value
- Returns an instance of `StateDefinition` if found; otherwise returns `null`.

#### Functionality
This method checks the provided state against the compiled states in the FSM. If the state is `null`, it returns `null`. Otherwise, it attempts to retrieve the corresponding `StateDefinition` from the `states` property using a helper function to normalize the state value.

### getTransitionsFor
```php
public function getTransitionsFor(FsmStateEnum|string|null $fromState, ?string $event): array
```

#### Purpose
Fetches transitions that can occur from a given state for a specified event.

#### Parameters
- `FsmStateEnum|string|null $fromState`: The state from which transitions are queried; it can be `null`, in which case the method treats it as no defined state.
- `string|null $event`: The event that triggers transitions; if `null`, it defaults to considering all transitions.

#### Return Value
- Returns an array of `TransitionDefinition` objects that match the criteria.

#### Functionality
The method filters the defined transitions based on the provided state and event. It checks if the specified event aligns with the transition's event, allowing wildcard matching. The transitions are then returned as an array after filtering.

### export
```php
public function export(): array
```

#### Purpose
Exports the FSM definition into a simplified associative array format for easier consumption, particularly in external contexts like AI systems.

#### Return Value
- Returns an associative array containing high-level information about the FSM, including model class, column name, initial state, states, and transitions.

#### Functionality
This method compiles the states and transitions into a structured array, transforming state names and transition details into a user-friendly format. It provides a concise overview of the FSM's configuration suitable for non-technical consumers. 

### Summary
The `FsmRuntimeDefinition` class is a vital part of the FSM architecture within a Laravel application. It consolidates state behavior and transition logic, enhances maintainability, and facilitates integration with external features. By documenting its properties and methods comprehensively, developers can quickly understand how to utilize its functionalities in their applications.