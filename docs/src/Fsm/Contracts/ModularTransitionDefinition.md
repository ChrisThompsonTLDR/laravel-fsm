# Documentation: ModularTransitionDefinition.php

Original file: `src/Fsm/Contracts/ModularTransitionDefinition.php`

# ModularTransitionDefinition Documentation

## Table of Contents
- [Introduction](#introduction)
- [Methods](#methods)
  - [getFromState](#getfromstate)
  - [getToState](#gettostate)
  - [getEvent](#getevent)
  - [getDefinition](#getdefinition)
  - [shouldOverride](#shouldoverride)
  - [getPriority](#getpriority)

## Introduction
The `ModularTransitionDefinition` interface defines a contract for modular transition definitions within a finite state machine (FSM) implementation. This interface provides a blueprint for creating transition definitions that can be extended or overridden, allowing for flexible and dynamic behavior in state transitions of a system. It encapsulates the fundamental attributes and actions associated with transitions between states in a FSM, enhancing the modularity and configurability of transition definitions.

## Methods

### `getFromState`
```php
public function getFromState(): string|\Fsm\Contracts\FsmStateEnum|null;
```
- **Purpose**: Returns the source state from which a transition can originate.
- **Parameters**: None
- **Return Value**: 
  - Returns a `string` or an instance of `FsmStateEnum`, or `null` if there is no defined source state.
- **Functionality**: This method allows for the retrieval of the initial state that triggers the transition, providing context for the transition's applicability. It can return `null` to indicate that the transition may be valid from any state, enhancing the flexibility of transition definitions.

### `getToState`
```php
public function getToState(): string|\Fsm\Contracts\FsmStateEnum;
```
- **Purpose**: Fetches the destination state that the transition leads to.
- **Parameters**: None
- **Return Value**: 
  - Must return a `string` or an instance of `FsmStateEnum` representing the target state.
- **Functionality**: This method indicates the state into which the FSM will transition when the defined event occurs. It is essential for establishing the flow of the state machine, ensuring that the expected behavior is mapped correctly for each transition.

### `getEvent`
```php
public function getEvent(): string;
```
- **Purpose**: Retrieves the event that triggers the transition.
- **Parameters**: None
- **Return Value**: 
  - Returns a `string` that denotes the specific event associated with the transition.
- **Functionality**: This method provides clarity on what action or input will initiate the transition from the source state to the target state. It encapsulates the event-driven nature of the FSM, facilitating developers in understanding the trigger for each transition.

### `getDefinition`
```php
public function getDefinition(): array;
```
- **Purpose**: Obtains the complete definition data of the transition.
- **Parameters**: None
- **Return Value**: 
  - Returns an associative array (`array<string, mixed>`) containing various properties related to the transition.
- **Functionality**: This method allows for the encapsulation of additional metadata or configuration options related to the transition. The returned array can include details such as conditions, actions associated with the transition, or any custom data necessary for processing the transition appropriately.

### `shouldOverride`
```php
public function shouldOverride(): bool;
```
- **Purpose**: Determines whether this transition definition is intended to override an existing one.
- **Parameters**: None
- **Return Value**: 
  - Returns a `bool` indicating whether the definition should replace an existing transition definition.
- **Functionality**: This method is crucial in managing conflicts between transition definitions within the FSM. If it returns `true`, the current definition will take precedence over any prior definitions that may conflict, enabling developers to enforce specific behaviors in their state transitions.

### `getPriority`
```php
public function getPriority(): int;
```
- **Purpose**: Retrieves the priority value of the transition definition.
- **Parameters**: None
- **Return Value**: 
  - Returns an `int` representing the priority level, with higher numbers indicating greater precedence.
- **Functionality**: This method establishes a priority system for transition definitions. By assigning different priority levels, developers can control the order of transition evaluation, ensuring that more critical transitions are processed before others, which is particularly important in complex state machines.

## Conclusion
The `ModularTransitionDefinition` interface serves as a foundational component in the design of a finite state machine. By ensuring that all transition definitions adhere to this contract, developers can create a robust and flexible state transition system that is easy to extend and maintain. This documentation provides a clear understanding of the responsibilities and behaviors encapsulated within this interface, allowing for effective FSM implementation and manipulation in PHP applications.