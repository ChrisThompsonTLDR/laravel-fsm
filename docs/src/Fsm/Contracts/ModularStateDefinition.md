# Documentation: ModularStateDefinition.php

Original file: `src/Fsm/Contracts/ModularStateDefinition.php`

# ModularStateDefinition Documentation

## Table of Contents
- [Introduction](#introduction)
- [Methods](#methods)
  - [getStateName](#getstatename)
  - [getDefinition](#getdefinition)
  - [shouldOverride](#shouldoverride)
  - [getPriority](#getpriority)

## Introduction

The `ModularStateDefinition.php` file defines an interface named `ModularStateDefinition` within the `Fsm\Contracts` namespace. This interface is designed to represent modular state definitions in the context of a finite state machine (FSM). Its purpose is to establish a contract for implementing different state definitions that can be easily overridden or extended, allowing for flexible state management within a system. By adhering to this interface, developers can create specific state behaviors while ensuring consistency and extensibility.

## Methods

### `getStateName`

```php
public function getStateName(): string|FsmStateEnum;
```

#### Purpose
This method is responsible for retrieving the name or enum value associated with the state definition.

#### Parameters
- None

#### Return Value
- Returns a `string` or `FsmStateEnum` that corresponds to the state this definition relates to.

#### Functionality
The `getStateName` method allows users to identify which state a given definition is referencing. This is critical for understanding the context in which the state operates within the FSM. The return type can either be a simple string (representing the state name) or an instance of the `FsmStateEnum`, which provides an enumeration of possible states.

---

### `getDefinition`

```php
public function getDefinition(): array;
```

#### Purpose
This method is intended to return the definition data associated with the state.

#### Parameters
- None

#### Return Value
- Returns an array containing string keys and mixed values (`array<string, mixed>`).

#### Functionality
The `getDefinition` method encapsulates the core data that defines a particular state. This array might include various attributes or settings that determine the behavior and characteristics of the state in a state machine context. Since the return type is generic, it allows for maximum flexibility in the structure of the state definitions.

---

### `shouldOverride`

```php
public function shouldOverride(): bool;
```

#### Purpose
This method determines if the current state definition intends to override an existing state definition.

#### Parameters
- None

#### Return Value
- Returns a boolean value: `true` if this definition should take precedence over existing definitions, otherwise `false`.

#### Functionality
The `shouldOverride` method plays a crucial role in managing state conflicts. When multiple state definitions exist for the same state, this method can be invoked to decide if the new (or modified) definition should replace the previous one. By encapsulating this logic within the interface, it ensures that the calling code can handle state definitions dynamically based on prioritization rules.

---

### `getPriority`

```php
public function getPriority(): int;
```

#### Purpose
This method retrieves the priority level of the state definition.

#### Parameters
- None

#### Return Value
- Returns an integer that represents the priority, where higher numbers indicate greater precedence.

#### Functionality
The `getPriority` method is integral to the resolution strategy of state definitions. By defining a priority system, developers can manage which state definitions should be active when there are conflicts. For instance, when multiple definitions exist for the same state, the one with the higher priority will be executed. This mechanism allows for flexible state management, ensuring that the most relevant definition is applied.

--- 

Each method in the `ModularStateDefinition` interface is designed to facilitate the management and differentiation of state definitions. By employing this interface, developers can create robust and modular state management solutions within their applications.