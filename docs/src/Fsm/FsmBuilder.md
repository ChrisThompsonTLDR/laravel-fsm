# Documentation: FsmBuilder.php

Original file: `src/Fsm/FsmBuilder.php`

# FsmBuilder Documentation

## Table of Contents
- [Introduction](#introduction)
- [Methods](#methods)
  - [for](#for)
  - [registerFsm](#registerfsm)
  - [getFsm](#getfsm)
  - [getDefinition](#getdefinition)
  - [getDefinitions](#getdefinitions)
  - [reset](#reset)
  - [extend](#extend)
  - [overrideState](#overridestate)
  - [overrideTransition](#overridetransition)
  - [applyExtensions](#applyextensions)

## Introduction
The `FsmBuilder` class is a pivotal component of the finite state machine (FSM) implementation in the Laravel application. It serves the purpose of collecting and building FSM definitions for various models and their state columns. This class allows developers to define states and transitions in an organized manner, facilitating the management of the various possible states an entity can occupy throughout its lifecycle.

## Methods

### `for`
#### Purpose
Creates a new instance of the `TransitionBuilder` for a specified model and state column.

#### Parameters
- `class-string $modelClass`: The fully qualified class name of the model for which the FSM is being defined.
- `string $columnName`: The name of the state column on the model.

#### Returns
- `TransitionBuilder`: An instance of `TransitionBuilder` representing the FSM for the specified model and column.

#### Functionality
This method checks if a `TransitionBuilder` already exists for the given model class and column name. If it does, it returns the existing instance; if not, it creates a new `TransitionBuilder` and stores it in the `$builders` array.

### `registerFsm`
#### Purpose
Registers the details of a single transition for a given FSM.

#### Parameters
- `class-string $modelClass`: The fully qualified class name of the model associated with the FSM.
- `string $columnName`: The name of the state column on the model.
- `array<string, mixed> $transitionDetails`: An associative array holding the definition of a single transition (e.g., `'from'`, `'to'`, `'guards'`, `'callbacks'`).

#### Returns
- `void`: This method does not return any value.

#### Functionality
It stores the transition details in the `$definitions` array under the specific model class and column name, allowing for organized access to FSM definitions.

### `getFsm`
#### Purpose
Retrieves all registered transition definitions for a specific FSM.

#### Parameters
- `class-string $modelClass`: The fully qualified class name of the model whose FSM is being queried.
- `string $columnName`: The state column of the model.

#### Returns
- `array<int, array<string, mixed>>|null`: This returns an array of transition definitions or null if no FSM is defined for the specified parameters.

#### Functionality
This method provides access to the registered FSM definitions for a given model class and state column, enabling the retrieval of transition data when needed.

### `getDefinition`
#### Purpose
Retrieves the TransitionBuilder for a specific FSM if it is defined.

#### Parameters
- `class-string $modelClass`: The fully qualified class name of the model for which the FSM is defined.
- `string $columnName`: The name of the state column on the model.

#### Returns
- `TransitionBuilder|null`: An instance of `TransitionBuilder`, or null if it is not defined.

#### Functionality
This method checks if a `TransitionBuilder` exists for the given model and column name and returns it for further modification or inspection.

### `getDefinitions`
#### Purpose
Get all stored TransitionBuilder instances.

#### Returns
- `array<class-string, array<string, TransitionBuilder>>`: An array containing all the `TransitionBuilder` instances classified by model class name and state column.

#### Functionality
This method returns a comprehensive list of all defined FSMs, allowing for inspection or iteration over all FSM configurations available within the system.

### `reset`
#### Purpose
Clears all registered FSM definitions.

#### Returns
- `void`: This method does not return any value.

#### Functionality
This method resets the state of the `FsmBuilder` class by clearing the `$definitions` and `$builders` arrays. It is particularly useful in testing scenarios where a fresh state is required.

### `extend`
#### Purpose
Extend an existing FSM definition with additional states and transitions.

#### Parameters
- `class-string $modelClass`: The fully qualified class name of the model.
- `string $columnName`: The name of the state column on the model.
- `callable $extension`: A callback function that is intended to receive the existing `TransitionBuilder` instance for modification.

#### Returns
- `void`: This method does not return any value.

#### Functionality
The `extend` method allows developers to add new transitions and states to an existing FSM. The supplied callback receives the `TransitionBuilder`, which can be used to define further behavior within the FSM.

### `overrideState`
#### Purpose
Override a specific state definition for an FSM.

#### Parameters
- `class-string $modelClass`: The fully qualified class name of the model.
- `string $columnName`: The name of the state column.
- `string|Fsm\Contracts\FsmStateEnum $stateName`: The name or enum value of the state to override.
- `array<string, mixed> $stateConfig`: Configuration for the state which includes methods to be called on the `TransitionBuilder`.

#### Returns
- `void`: This method does not return any value.

#### Functionality
Uses the `TransitionBuilder` for the given model and column to override an existing state with new configuration. It ensures that the state's methods are validated before being called, allowing for robust configuration management.

### `overrideTransition`
#### Purpose
Override or add a transition definition for an FSM.

#### Parameters
- `class-string $modelClass`: The fully qualified class name of the model.
- `string $columnName`: The name of the state column on the model.
- `string|Fsm\Contracts\FsmStateEnum|null $fromState`: The state from which to transition, or null.
- `string|Fsm\Contracts\FsmStateEnum $toState`: The state to transition to.
- `string $event`: The event that triggers the transition.
- `array<string, mixed> $transitionConfig`: Configuration for the transition with methods to be called.

#### Returns
- `void`: This method does not return any value.

#### Functionality
It handles transitions by first removing any existing transitions that match the parameters, then it creates a new transition and applies the configuration settings to it. This process ensures that the FSM is flexible and up-to-date with the desired transition states.

### `applyExtensions`
#### Purpose
Apply runtime extensions to an FSM definition.

#### Parameters
- `class-string $modelClass`: The fully qualified class name of the model.
- `string $columnName`: The name of the state column on the model.
- `Fsm\FsmExtensionRegistry $extensionRegistry`: The registry containing FSM extensions.

#### Returns
- `void`: This method does not return any value.

#### Functionality
This method retrieves and