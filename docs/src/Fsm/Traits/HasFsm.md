# Documentation: HasFsm.php

Original file: `src/Fsm/Traits/HasFsm.php`

# HasFsm Trait Documentation

## Table of Contents
1. [Introduction](#introduction)
2. [Methods](#methods)
   - [fsm](#fsm)
   - [fsmEngine](#fsmengine)
   - [getFsmState](#getfsmstate)
   - [canTransitionFsm](#cantransitionfsm)
   - [transitionFsm](#transitionfsm)
   - [dryRunFsm](#dryrunfsm)
   - [bootHasFsm](#boothasfsm)
   - [applyFsmInitialStates](#applyfsminitialstates)

## Introduction
The `HasFsm` trait provides functionality to integrate Finite State Machine (FSM) capabilities into Eloquent Models in a Laravel application. It allows models to trigger state transitions, check if transitions are possible, and simulate transitions without changing the model's state. This trait leverages services like `FsmRegistry` and `FsmEngineService` to manage state transitions based on defined FSM rules. The design supports easy integration and initialization of FSM states when the model is created.

## Methods

### fsm
```php
public function fsm(?string $column = null): object
```
**Purpose:**  
Creates a fluent interface for managing the model's FSM, allowing events to trigger transitions.

**Parameters:**
- `?string $column`: The column name in which the FSM state is stored. Defaults to the value specified in `config('fsm.default_column_name')`.

**Returns:**  
An anonymous object that provides methods for triggering events, checking transition possibilities, and simulating transitions.

**Functionality:**  
- Initializes the column to use for FSM transitions.
- Maps the state transition events to their respective target states using the configured FSM definitions.
- Handles current state retrieval and wildcard state transitions.

### fsmEngine
```php
protected function fsmEngine(): FsmEngineService
```
**Purpose:**  
Provides access to the FSM engine service.

**Returns:**  
An instance of `FsmEngineService`.

**Functionality:**  
- Uses Laravel's service container to resolve the `FsmEngineService`, allowing for additional FSM operations.

### getFsmState
```php
public function getFsmState(?string $columnName = null): FsmStateEnum|string|null
```
**Purpose:**  
Retrieves the current FSM state of the model.

**Parameters:**
- `?string $columnName`: The FSM state column name. Defaults to the value specified in `config('fsm.default_column_name')`.

**Returns:**  
The current state of the FSM, which can be `FsmStateEnum`, a string representation, or null.

**Functionality:**  
- Ensures that the trait is used within an Eloquent Model.
- Fetches the current state from the model's specified column using the FSM engine.

### canTransitionFsm
```php
public function canTransitionFsm(?string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context = null): bool
```
**Purpose:**  
Checks if a transition to a specified state is possible.

**Parameters:**
- `?string $columnName`: The FSM state column name. Defaults to `config('fsm.default_column_name')`.
- `FsmStateEnum|string $toState`: The target state to check for transition.
- `?ArgonautDTOContract $context`: Optional context for guards.

**Returns:**  
`bool` indicating whether the transition is possible.

**Functionality:**  
- Validates the column name and ensures it’s used within an Eloquent Model.
- Uses the FSM engine to check transition permissions against defined rules.

### transitionFsm
```php
public function transitionFsm(?string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context = null): static
```
**Purpose:**  
Performs a transition to a specified state, updating the model.

**Parameters:**
- `?string $columnName`: FSM state column name. Defaults to `config('fsm.default_column_name')`.
- `FsmStateEnum|string $toState`: The target state to transition to.
- `?ArgonautDTOContract $context`: Optional context for guards, callbacks, and actions.

**Returns:**  
The updated model instance.

**Functionality:**  
- Validates inputs and ensures that the trait is implemented on an Eloquent Model.
- Calls the FSM engine to perform the state transition.

### dryRunFsm
```php
public function dryRunFsm(?string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context = null): array
```
**Purpose:**  
Simulates a transition and returns the expected outcome without making any changes to the model.

**Parameters:**
- `?string $columnName`: FSM state column name. Defaults to `config('fsm.default_column_name')`.
- `FsmStateEnum|string $toState`: The target state to simulate.
- `?ArgonautDTOContract $context`: Optional context for guards.

**Returns:**  
An array containing details about the dry run outcome, such as whether the transition is possible, the current state, target state, and messages.

**Functionality:**  
- Conducts a validation check similar to `canTransitionFsm`.
- Retrieves a transition outcome from the FSM engine without altering the model state.

### bootHasFsm
```php
protected static function bootHasFsm(): void
```
**Purpose:**  
Initializes FSM states when a model instance is created.

**Functionality:**  
- Sets a model event listener for the `creating` event.
- Calls `applyFsmInitialStates` to configure the initial FSM state based on defined FSM configurations.

### applyFsmInitialStates
```php
protected function applyFsmInitialStates(): void
```
**Purpose:**  
Sets the initial states for FSMs defined for the model, if not already set.

**Functionality:**  
- Uses the `FsmRegistry` to retrieve definitions for the model's FSMs.
- If the model's FSM state column is null, it assigns the initial state as per the FSM definition.
- Supports multiple FSMs defined on the model.

## Conclusion
The `HasFsm` trait encapsulates robust mechanisms for embedding FSM logic directly into Laravel Eloquent models. This documentation provides an extensive overview of its methods, parameters, and functionality to assist developers in understanding and utilizing this trait effectively within their applications.