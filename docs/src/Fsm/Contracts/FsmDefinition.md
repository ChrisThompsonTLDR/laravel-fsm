# Documentation: FsmDefinition.php

Original file: `src/Fsm/Contracts/FsmDefinition.php`

# FsmDefinition Documentation

## Table of Contents
- [Introduction](#introduction)
- [Methods](#methods)
  - [define](#define)

## Introduction
The file `FsmDefinition.php` defines an interface for creating configurations for Finite State Machines (FSMs) within the context of a PHP application, specifically designed for use with the Laravel framework. This interface lays out the essential method that any class implementing `FsmDefinition` must contain, ensuring that the FSM configurations can be consistently defined across different implementations. The primary goal is to leverage the `FsmBuilder` class to establish states and transitions in a structured manner, promoting code reusability and maintainability.

## Methods

### define
```php
public function define(): void;
```

#### Purpose
The `define` method is intended to configure the states and transitions of a finite state machine. It must be implemented by any class that adheres to the `FsmDefinition` interface.

#### Parameters
This method does not accept any parameters.

#### Return Values
This method does not return any values. It has a `void` return type, meaning its purpose is solely to establish the FSM configuration.

#### Functionality
The `define` method is crucial for laying out the structure of the finite state machine. When implementing this method, the developer will utilize the `FsmBuilder::for()` method to define:

- **States**: Individual states that the FSM can be in (e.g., `idle`, `active`, `completed`).
- **Transitions**: Rules that determine how and when the FSM can switch states.

Here is an example of how one might implement the `define()` method in a concrete class that implements the `FsmDefinition` interface:

```php
use Fsm\FsmBuilder;

class MyFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        FsmBuilder::for('my_fsm')
            ->addState('idle')
            ->addState('processing')
            ->addState('completed')
            ->addTransition('idle', 'start', 'processing')
            ->addTransition('processing', 'complete', 'completed')
            ->addTransition('completed', 'reset', 'idle');
    }
}
```

In this example:
- The FSM is defined with three states: `idle`, `processing`, and `completed`.
- It includes transitions that allow moving from one state to another based on specific events (e.g., starting processing, completing the task, resetting to idle).

By adhering to the `FsmDefinition` contract, developers can create scalable and maintainable FSM implementations within their PHP applications, streamlining the process of state management. 

This design pattern supports clean code principles by clearly separating the FSM's configuration from its logic, allowing for easier testing and modification.