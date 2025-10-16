# Documentation: RunActionJob.php

Original file: `src/Fsm/Jobs/RunActionJob.php`

# RunActionJob Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
- [Methods](#methods)
  - [__construct](#__construct)
  - [handle](#handle)

## Introduction
The `RunActionJob` class is part of the Finite State Machine (FSM) system in this Laravel based application. It is designed to encapsulate the execution of actions associated with state transitions, allowing them to be queued for delayed execution in a background job. This is essential for implementing features that require asynchronous processing, ensuring that the application remains responsive while handling possible long-running tasks.

The class implements the `ShouldQueue` interface, making it compatible with Laravel's job queueing system. By using this class, actions within state transitions can be deferred, logged for diagnostics, and executed with flexible context provided through input data.

## Class Properties
The `RunActionJob` class defines the following properties:

| Property      | Type                  | Description                                               |
|---------------|-----------------------|-----------------------------------------------------------|
| `callable`    | `string`              | The name of the callable action to be executed.          |
| `parameters`  | `array<string,mixed>` | An associative array of parameters to pass to the action.|
| `inputData`   | `array<string,mixed>` | An associative array containing context and model data.  |

### Constructor Parameters
The constructor (`__construct`) accepts the following parameters:

- `callable` (`string`): A string representing the callable action, which can be a class method.
- `parameters` (`array<string,mixed>`): An associative array of parameters necessary for executing the action.
- `inputData` (`array<string,mixed>`): An associative array containing the model class and model ID associated with the action.

## Methods

### __construct
```php
public function __construct(
    public string $callable,
    public array $parameters,
    public array $inputData,
)
```

#### Purpose
The constructor initializes the `RunActionJob` with the necessary data required for execution, including the desired callable action, its parameters, and any relevant input data.

#### Parameters
- `callable`: Name of the action to call (string).
- `parameters`: Parameters for the action (array).
- `inputData`: Contextual data including model class and ID (array).

### handle
```php
public function handle(): void
```

#### Purpose
This method is responsible for carrying out the action specified by the `callable` property. It retrieves the necessary model, prepares the input for the callable, and invokes it.

#### Functionality
1. **Model Retrieval**: The method begins by attempting to locate the model associated with the provided `model_class` and `model_id` in the `inputData`. 
   - If the model cannot be found, it logs a warning and exits without performing the action.

2. **Input Preparation**: Upon successfully finding the model, it prepares the input data:
   - It adds the retrieved model to the `inputData`.
   - It removes `model_class` and `model_id` keys from the `inputData`.

3. **Context Logging**: It captures the original context from `inputData` to ensure it is not lost during the transition to the `TransitionInput`. If the context is lost, it logs a warning.

4. **Callable Normalization**: The method normalizes the format of the `callable` property. If it is in class method format (`ClassName::method`), it transforms it to Laravel's callable format (`ClassName@method`).

5. **Action Dispatching**: Finally, it utilizes the Laravel `App` facade to call the specified action with merged parameters and prepared input.

#### Return Values
- Returns `void`, as the method is designed to perform actions rather than return a value.

---

This documentation should help developers understand how the `RunActionJob` class functions, its role within the application, and how to extend its capabilities as needed. Understanding this component is crucial for adding new actions or managing the application’s state transitions efficiently.