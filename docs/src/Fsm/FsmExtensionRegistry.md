# Documentation: FsmExtensionRegistry.php

Original file: `src/Fsm/FsmExtensionRegistry.php`

# FsmExtensionRegistry Documentation

## Table of Contents
- [Introduction](#introduction)
- [Methods](#methods)
  - [registerExtension](#registerextension)
  - [registerStateDefinition](#registerstatedefinition)
  - [registerTransitionDefinition](#registertransitiondefinition)
  - [getExtensionsFor](#getextensionsfor)
  - [getStateDefinitionsFor](#getstatedefinitionsfor)
  - [getTransitionDefinitionsFor](#gettransitiondefinitionsfor)
  - [loadFromConfig](#loadfromconfig)
  - [makeKey](#makekey)
  - [makeTransitionKey](#maketransitionkey)
- [Classes](#classes)
  - [ConfigStateDefinition](#configstatedefinition)
  - [ConfigTransitionDefinition](#configtransitiondefinition)

## Introduction
The `FsmExtensionRegistry` class serves as a central hub for managing the extensions and modular definitions related to Finite State Machines (FSM) in a Laravel application. It is responsible for registering various FSM extensions, state definitions, and transition definitions while ensuring that these components can be efficiently accessed based on their respective models and columns. The registry is designed to load configuration data from a repository, allowing dynamic extension and definition management, which enhances the flexibility of FSM implementations.

## Methods

### `registerExtension`
```php
public function registerExtension(FsmExtension $extension): void
```
- **Purpose**: Registers an FSM extension to the registry.
- **Parameters**:
  - `FsmExtension $extension`: An object of a class implementing the `FsmExtension` interface.
- **Functionality**: This method takes an FSM extension, retrieves its name using `getName()`, and adds it to the internal `$extensions` array, associating it with its name. This enables the registry to manage multiple FSM extensions for future retrieval.

### `registerStateDefinition`
```php
public function registerStateDefinition(string $modelClass, string $columnName, ModularStateDefinition $definition): void
```
- **Purpose**: Registers a modular state definition associated with a specific model and column.
- **Parameters**:
  - `string $modelClass`: The class name of the model to which the state definition belongs.
  - `string $columnName`: The specific column of the model for which the state is defined.
  - `ModularStateDefinition $definition`: An instance of a class implementing the `ModularStateDefinition` interface, representing the state definition.
- **Functionality**: Constructs a unique key based on the model class and column name. It then registers the state definition by storing it under this key and its defined state name in the internal `$stateDefinitions` array, facilitating efficient retrieval later.

### `registerTransitionDefinition`
```php
public function registerTransitionDefinition(string $modelClass, string $columnName, ModularTransitionDefinition $definition): void
```
- **Purpose**: Registers a modular transition definition associated with a specific model and column.
- **Parameters**:
  - `string $modelClass`: The class name of the model to which the transition definition belongs.
  - `string $columnName`: The specific column of the model related to the transition.
  - `ModularTransitionDefinition $definition`: An instance of a class implementing the `ModularTransitionDefinition` interface.
- **Functionality**: Similar to `registerStateDefinition`, this method creates a key for the transition based on the model class and column name. It then calls `makeTransitionKey` to create a unique transition key based on the transition definition. The transition definition is stored in the internal `$transitionDefinitions` array.

### `getExtensionsFor`
```php
public function getExtensionsFor(string $modelClass, string $columnName): array
```
- **Purpose**: Retrieves all FSM extensions applicable to a specified model and column.
- **Parameters**:
  - `string $modelClass`: The class name of the target model.
  - `string $columnName`: The specific column of the model.
- **Return Value**: Returns an array of `FsmExtension` objects that are applicable.
- **Functionality**: Filters the registered extensions based on whether they apply to the given model class and column name using the `appliesTo` method. These extensions are sorted by priority (higher first), and the function returns them as a numerically indexed array for easy access.

### `getStateDefinitionsFor`
```php
public function getStateDefinitionsFor(string $modelClass, string $columnName): array
```
- **Purpose**: Retrieves modular state definitions for a specified model and column.
- **Parameters**:
  - `string $modelClass`: The class name of the targeted model.
  - `string $columnName`: The specific column name of the model.
- **Return Value**: Returns an array of `ModularStateDefinition` instances.
- **Functionality**: Generates a key, retrieves the state's definitions corresponding to that key, and sorts them by priority before returning. This allows users to easily access the most important definitions first.

### `getTransitionDefinitionsFor`
```php
public function getTransitionDefinitionsFor(string $modelClass, string $columnName): array
```
- **Purpose**: Retrieves modular transition definitions for a specific model and column.
- **Parameters**:
  - `string $modelClass`: The class name of the targeted model.
  - `string $columnName`: The specific column name of the model.
- **Return Value**: Returns an array of `ModularTransitionDefinition`.
- **Functionality**: Similar to `getStateDefinitionsFor`, this method constructs a key for the model and column, retrieves the definitions, and sorts them by priority before returning the data.

### `loadFromConfig`
```php
private function loadFromConfig(): void
```
- **Purpose**: Loads FSM extensions and definitions from the application configuration.
- **Functionality**: This method accesses the configuration repository to retrieve modular configurations. It first loads and registers extensions, followed by state and transition overrides if specified in the config. Each override is registered through appropriate class constructors.

### `makeKey`
```php
private function makeKey(string $modelClass, string $columnName): string
```
- **Purpose**: Generates a unique key for a model and column for use in internal storage.
- **Parameters**:
  - `string $modelClass`: The model's class name.
  - `string $columnName`: The column of the model.
- **Return Value**: Returns a formatted string as the unique key (in the format `modelClass:columnName`).
- **Functionality**: This helper method simplifies key management by uniforming the structure for internal arrays.

### `makeTransitionKey`
```php
private function makeTransitionKey(ModularTransitionDefinition $definition): string
```
- **Purpose**: Creates a unique key for a transition definition based on its properties.
- **Parameters**:
  - `ModularTransitionDefinition $definition`: The transition definition object.
- **Return Value**: Returns a formatted string key based on the transition's `from` state, `to` state, and the associated `event`.
- **Functionality**: The function constructs the