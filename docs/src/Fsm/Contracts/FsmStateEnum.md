# Documentation: FsmStateEnum.php

Original file: `src/Fsm/Contracts/FsmStateEnum.php`

# FsmStateEnum Documentation

## Table of Contents
- [Introduction](#introduction)
- [Methods](#methods)
  - [displayName](#method-displayname)
  - [icon](#method-icon)

## Introduction
The `FsmStateEnum.php` file defines the `FsmStateEnum` interface, which serves as a contract for all state enums used within a Finite State Machine (FSM) in the application. This interface mandates that any concrete implementation must represent a string-backed enum, thereby providing a standardized way to define FSM states with associated properties and methods.

By adhering to this interface, developers can ensure consistency across different state enum implementations, leveraging the shared methods for better interoperability within the FSM framework. Enums that implement this interface should encapsulate the state string values and provide additional information such as display names and icons associated with each state.

## Methods

### `displayName`

```php
public function displayName(): string;
```

#### Purpose
The `displayName()` method is intended to provide a human-readable representation of the enum state. This can be particularly useful for UI presentations, logging, or debugging.

#### Parameters
This method does not take any parameters.

#### Return Value
- **Type**: `string`
- **Description**: Returns the display name of the enum state which is often a more descriptive version of the state value.

#### Functionality
The concrete implementation of this method should return a string that succinctly describes the state represented by the enum. For example, if the state is `ACTIVE`, the `displayName()` might return `"Active State"`.

### `icon`

```php
public function icon(): string;
```

#### Purpose
The `icon()` method is meant to provide an icon representation associated with the state. This can be used in UI displays to visually represent the state.

#### Parameters
This method does not take any parameters.

#### Return Value
- **Type**: `string`
- **Description**: Returns a string that represents the icon's file path or CSS class name for the enum state, which can be used to render an icon in a user interface.

#### Functionality
The concrete implementation will return a string that corresponds to an icon related to the specific state. For example, if a state represented is `INACTIVE`, the icon method might return `"/icons/inactive.png"` or a CSS class such as `"icon-inactive"`.

## Conclusion
The `FsmStateEnum` interface provides a clear and functional contract for developers implementing state enums in a Finite State Machine context. By defining required methods like `displayName()` and `icon()`, developers can ensure that their enum states not only contain identifiable values but also facilitate understanding and interaction through user interfaces. 

All implementations of this interface must adhere to these specifications, ensuring cohesive behavior across different parts of the system involving finite states.