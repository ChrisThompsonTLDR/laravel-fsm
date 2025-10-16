# Documentation: TransitionBuilder.php

Original file: `src/Fsm/TransitionBuilder.php`

# TransitionBuilder Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
- [Constructor](#constructor)
- [Methods](#methods)
  - [normalizeStateValue](#normalizeStateValue)
  - [initial](#initial)
  - [state](#state)
  - [onEntry](#onEntry)
  - [onExit](#onExit)
  - [transition](#transition)
  - [description](#description)
  - [type](#type)
  - [category](#category)
  - [behavior](#behavior)
  - [metadata](#metadata)
  - [isTerminal](#isTerminal)
  - [priority](#priority)
  - [withChildFsm](#withChildFsm)
  - [from](#from)
  - [to](#to)
  - [event](#event)
  - [guard](#guard)
  - [policy](#policy)
  - [immediateAction](#immediateAction)
  - [queuedAction](#queuedAction)
  - [notify](#notify)
  - [log](#log)
  - [cleanup](#cleanup)
  - [onFailure](#onFailure)
  - [onSuccess](#onSuccess)
  - [buildRuntimeDefinition](#buildRuntimeDefinition)
  - [build](#build)
  - [removeTransition](#removeTransition)

## Introduction
The `TransitionBuilder` class is a core component of the finite state machine (FSM) framework implemented in this PHP codebase. It is designed to build the states and transitions for a specific FSM associated with a model and a specified column. This class collects all definitions of states and transitions, which can then be compiled into an `FsmRuntimeDefinition` object, typically via the `FsmRegistry`. The class enables developers to easily define states, transitions, actions, and guards in a fluent and readable manner.

## Class Properties
The class properties include:
- **modelClass**: A string that represents the Eloquent model class for which the FSM is built.
- **columnName**: The name of the column in the model that stores the state.
- Various arrays for managing `states`, `transitions`, and transition-building state variables like `fluentFrom`, `fluentTo`, etc.

## Constructor
```php
public function __construct(string $modelClass, string $columnName)
```
- **Parameters**:
  - `modelClass` (string): The fully qualified class name of the Eloquent model.
  - `columnName` (string): The name of the column that will store the state.
- **Return Value**: None (initializes the object).
- **Functionality**: Sets up the builder for a specific model and column to collect FSM configurations.

## Methods

### normalizeStateValue
```php
private static function normalizeStateValue(FsmStateEnum|string $state): string
```
- **Purpose**: Normalizes a given state value to a string representation.
- **Parameters**:
  - `state` (FsmStateEnum|string): The state to normalize.
- **Return Value**: Returns the normalized state value as a string.

### initial
```php
public function initial(FsmStateEnum|string $state): self
```
- **Purpose**: Defines the initial state for the FSM.
- **Parameters**:
  - `state` (FsmStateEnum|string): The state to set as the initial state.
- **Return Value**: Returns the current instance (to allow method chaining).
- **Functionality**: Sets the initial state and ensures that it is already defined.

### state
```php
public function state(FsmStateEnum|string $state, ?callable $configurator = null): self
```
- **Purpose**: Defines a state within the FSM.
- **Parameters**:
  - `state` (FsmStateEnum|string): The name of the state to define.
  - `configurator` (callable|null): A callback function to configure the state further.
- **Return Value**: Returns the current instance.
- **Functionality**: Adds a new state and runs the configurator callback if provided.

### onEntry
```php
public function onEntry(string|Closure|array $callable, array $parameters = [], bool $runAfterTransition = false, bool $queued = false): self
```
- **Purpose**: Registers the entry action for a defined state.
- **Parameters**:
  - `callable` (string|Closure|array): A callable action to execute on entry.
  - `parameters` (array): Arguments to pass to the callable.
  - `runAfterTransition` (bool): Whether to run the action after the transition.
  - `queued` (bool): If true, queues the action for later execution.
- **Return Value**: Returns the current instance.
- **Functionality**: Adds the entry action to the state's callbacks.

### onExit
```php
public function onExit(string|Closure|array $callable, array $parameters = [], bool $runAfterTransition = false, bool $queued = false): self
```
- **Purpose**: Registers the exit action for a defined state.
- **Parameters**: Same as `onEntry`.
- **Return Value**: Returns the current instance.
- **Functionality**: Adds the exit action to the state's callbacks.

### transition
```php
public function transition(FsmStateEnum|string|null $fromOrDescription = null, FsmStateEnum|string|null $to = null): self
```
- **Purpose**: Starts or finalizes a state transition.
- **Parameters**:
  - `fromOrDescription` (FsmStateEnum|string|null): Indicates the source state or a description if it's the finalizing call.
  - `to` (FsmStateEnum|string|null): The destination state (only when starting a new transition).
- **Return Value**: Returns the current instance.
- **Functionality**: Creates a transition if from and to states are provided; otherwise, it finalizes the transition and may set a description.

### description
```php
public function description(string $description): self
```
- **Purpose**: Sets a description for the current transition.
- **Parameters**:
  - `description` (string): The description text.
- **Return Value**: Returns the current instance.
- **Functionality**: Stores a description associated with the current fluent transition or the state.

### type
```php
public function type(string $type): self
```
- **Purpose**: Sets the type of the current state.
- **Parameters**:
  - `type` (string): The type of the state (e.g., 'initial', 'final').
- **Return Value**: Returns the current instance.
- **Functionality**: Updates the state definition with the specified type.

### category
```php
public function category(?string $category): self
```
- **Purpose**: Sets the category for the current state.
- **Parameters**:
  - `category` (string|null): The category name.
-