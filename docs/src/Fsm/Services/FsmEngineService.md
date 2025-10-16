# Documentation: FsmEngineService.php

Original file: `src/Fsm/Services/FsmEngineService.php`

# FsmEngineService Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Constructor](#constructor)
- [Methods](#methods)
  - [getDefinition](#getdefinition)
  - [getCurrentState](#getcurrentstate)
  - [canTransition](#cantransition)
  - [dryRunTransition](#dryruntransition)
  - [performTransition](#performtransition)
  - [findTransition](#findtransition)
  - [normalizeStateForTransition](#normalizestatefortransition)
  - [processTransition](#processtransition)
  - [executeGuards](#executeGuards)
  - [executeCallbacks](#executeCallbacks)
  - [executeActions](#executeActions)
  - [buildJobPayload](#buildJobPayload)

## Introduction
The `FsmEngineService` class in this file is designed to provide a core engine for handling state transitions within a finite state machine (FSM). It validates and executes transitions, evaluates guard conditions, runs actions, and emits events related to state changes. This service facilitates a comprehensive environment for state machine operations, including logging, metrics collection, and error management.

## Class Overview
The `FsmEngineService` class encapsulates the logic required to handle FSM operations in Laravel. It leverages various components including guards, callbacks, and actions to ensure transitions are executed correctly and according to defined rules.

### Key Responsibilities:
- Validate and execute state transitions.
- Assess guard conditions and handle potential errors.
- Manage actions associated with state changes.
- Emit events for logging and tracking transitions.
- Execute actions and callbacks in the specified order during transitions.

## Constructor
```php
public function __construct(
    private readonly FsmRegistry $registry,
    private readonly FsmLogger $logger,
    private readonly FsmMetricsService $metrics,
    private readonly DatabaseManager $db,
    private readonly ConfigRepository $config
)
```
### Parameters:
- `FsmRegistry $registry`: The registry that holds definitions of FSMs.
- `FsmLogger $logger`: The service for logging FSM operations and transitions.
- `FsmMetricsService $metrics`: The service for recording metrics related to FSM transitions.
- `DatabaseManager $db`: Database management service to handle transactions.
- `ConfigRepository $config`: Configuration repository to retrieve settings.

## Methods

### getDefinition
```php
protected function getDefinition(string $modelClass, string $columnName): FsmRuntimeDefinition
```
#### Purpose
Retrieves the FSM runtime definition for a given model class and column name.

#### Parameters
- `string $modelClass`: Fully qualified class name of the model.
- `string $columnName`: The name of the state column in the model.

#### Returns
- `FsmRuntimeDefinition`: The FSM definition corresponding to the specified model and column.

#### Functionality
- Fetches the FSM definition from the registry.
- Throws a `LogicException` if the definition is not found.

---

### getCurrentState
```php
public function getCurrentState(Model $model, string $columnName): FsmStateEnum|string|null
```
#### Purpose
Obtains the current state of the FSM for a specified Eloquent model and state column.

#### Parameters
- `Model $model`: The Eloquent model instance whose state is being queried.
- `string $columnName`: The state column name.

#### Returns
- `FsmStateEnum|string|null`: The current state as an enum or string, or `null` if no initial state exists.

#### Functionality
- Automatically converts enums to strings if necessary.
- Returns the model's current state or the initial state defined in the FSM.

---

### canTransition
```php
public function canTransition(Model $model, string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context = null): bool
```
#### Purpose
Determines if a transition to a specified state can occur without executing the transition.

#### Parameters
- `Model $model`: The Eloquent model instance.
- `string $columnName`: The state column name.
- `FsmStateEnum|string $toState`: The target state for the transition.
- `ArgonautDTOContract|null $context`: Optional context for the transition.

#### Returns
- `bool`: `true` if the transition can succeed, `false` otherwise.

#### Functionality
- Performs a dry-run of the transition process.
- Executes guards without modifying the current state of the model.
  
---

### dryRunTransition
```php
public function dryRunTransition(Model $model, string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context = null): array
```
#### Purpose
Simulates a transition attempt, returning the outcome without actually transitioning the model state.

#### Parameters
- `Model $model`: The Eloquent model instance.
- `string $columnName`: The state column name.
- `FsmStateEnum|string $toState`: The intended target state.
- `ArgonautDTOContract|null $context`: Optional transition context.

#### Returns
- `array`: Contains details about the transition attempt, including:
  - `can_transition`: Whether the transition can occur.
  - `from_state`: Current state before transition.
  - `to_state`: Target state for the transition.
  - `message`: Descriptive message about the attempt.
  - `reason`: Reason for failure, if applicable.

#### Functionality
- Dispatches a transition attempt event.
- Evaluates guards without changing the state of the model.

---

### performTransition
```php
public function performTransition(Model $model, string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context = null): Model
```
#### Purpose
Performs a state transition for the specified model, updating the state in the database.

#### Parameters
- `Model $model`: The Eloquent model instance to transition.
- `string $columnName`: The state column name.
- `FsmStateEnum|string $toState`: The target state for the transition.
- `ArgonautDTOContract|null $context`: Optional context for the transition.

#### Returns
- `Model`: The updated model instance after the transition.

#### Functionality
- Initiates a new database transaction (if configured).
- Executes the transition logic, including validating guards and actions.
- Updates the model's state and broadcasts transition events.

---

### findTransition
```php
protected function findTransition(FsmRuntimeDefinition $definition, FsmStateEnum|string|null $fromState, FsmStateEnum|string $toState): ?TransitionDefinition
```
#### Purpose
Locates a transition definition based on the current state and target state.

#### Parameters
- `FsmRuntimeDefinition $definition`: The FSM definition containing possible transitions.
- `FsmStateEnum|string|null $fromState`: The state from which the transition is made.
- `FsmStateEnum|string $toState`: The target state for the transition.

#### Returns
- `TransitionDefinition|null`: The found transition definition or `null` if not found.

#### Functionality
- First, checks for an exact match of the current state to