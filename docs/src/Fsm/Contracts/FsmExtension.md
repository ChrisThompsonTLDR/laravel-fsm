# Documentation: FsmExtension.php

Original file: `src/Fsm/Contracts/FsmExtension.php`

# FsmExtension Documentation

## Table of Contents
- [Introduction](#introduction)
- [Methods](#methods)
  - [extend](#extend)
  - [appliesTo](#appliesto)
  - [getPriority](#getpriority)
  - [getName](#getname)

## Introduction

The `FsmExtension.php` file defines the `FsmExtension` interface, which is a crucial component of the Finite State Machine (FSM) functionality in the application. This interface allows developers to create modular extensions that can enhance existing FSM definitions without modifying the original implementation. Through these extensions, developers can add new states and transitions or alter existing ones, promoting better code maintainability and flexibility.

The design of `FsmExtension` encourages a plugin-like architecture where FSMs can be extended with additional functionalities, making it easier to manage complex state machines in a clean and organized manner.

## Methods

### extend

```php
public function extend(string $modelClass, string $columnName, TransitionBuilder $builder): void;
```

#### Purpose
The `extend` method is responsible for applying the extension to a specific FSM definition. It allows the developer to modify the state machine's behavior by leveraging the provided `TransitionBuilder`.

#### Parameters
- **string $modelClass**: The fully qualified name of the model class that this FSM is associated with.
- **string $columnName**: The name of the database column that the FSM will manage.
- **TransitionBuilder $builder**: An instance of `TransitionBuilder` which provides methods to add or modify states and transitions.

#### Functionality
The method should implement the logic to augment the FSM defined for the specified model and column. This involves using the `TransitionBuilder` to add new transitions or states dynamically, thus allowing for greater flexibility without altering the core FSM definitions directly.

---

### appliesTo

```php
public function appliesTo(string $modelClass, string $columnName): bool;
```

#### Purpose
The `appliesTo` method checks if the extension should be applied to a given FSM based on the model class and column name.

#### Parameters
- **string $modelClass**: The fully qualified name of the model class being evaluated.
- **string $columnName**: The name of the column being evaluated.

#### Return Value
- **bool**: Returns `true` if this extension can be applied to the specified model and column, or `false` otherwise.

#### Functionality
This method provides a mechanism for conditional application of extensions. Developers can implement logic to determine whether the FSM extension is relevant based on the incoming parameters, enabling selective enhancement of FSMs.

---

### getPriority

```php
public function getPriority(): int;
```

#### Purpose
The `getPriority` method returns an integer that defines the order of execution for the FSM extensions.

#### Return Value
- **int**: A numerical value indicating the priority; higher numbers indicate that the extension will be processed first.

#### Functionality
The priority system allows FSM extensions to be executed in a defined order. This is particularly useful when multiple extensions might affect the same states or transitions, enabling the developer to control which extensions take precedence and in what order they should be applied.

---

### getName

```php
public function getName(): string;
```

#### Purpose
The `getName` method provides a unique identifier for the FSM extension.

#### Return Value
- **string**: A unique string identifier that represents the extension.

#### Functionality
Having a unique name for each extension helps in distinguishing between different FSM extensions within the application. This can be particularly useful for debugging, logging, or when there is a need to reference or manage extensions programmatically.

---

This documentation aims to provide a comprehensive understanding of the `FsmExtension` interface and its methods, fostering efficient usage and extension of FSM functionalities within the PHP application. By adhering to this structure, developers can enhance FSMs in a modular, maintainable way, thereby improving overall code quality.