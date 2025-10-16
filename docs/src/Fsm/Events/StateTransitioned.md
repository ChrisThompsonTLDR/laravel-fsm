# Documentation: StateTransitioned.php

Original file: `src/Fsm/Events/StateTransitioned.php`

# StateTransitioned Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Definition](#class-definition)
- [Constructor](#constructor)

## Introduction
The `StateTransitioned` class is a core component in the `Fsm\Events` namespace of the Laravel application. It is designed to encapsulate the details of a state transition event in a finite state machine (FSM). This class is particularly useful for listening to changes in the states of a model within the framework's event-driven architecture. It organizes relevant data when a model transitions from one state to another, making it available for further processing or event handling.

## Class Definition
The `StateTransitioned` class is defined as follows:

```php
namespace Fsm\Events;

use Illuminate\Database\Eloquent\Model;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

class StateTransitioned
{
    // Constructor and properties
}
```

### Properties
The class has the following public properties, defined in the constructor:

| Property        | Type                             | Description                                                                                  |
|----------------|----------------------------------|----------------------------------------------------------------------------------------------|
| `$model`       | `Model`                          | The Eloquent model instance that has undergone the state transition.                        |
| `$columnName`  | `string`                         | The name of the column in the database that holds the current state of the model.          |
| `$fromState`   | `?string`                       | The state the model was in before the transition, can be null if there was no previous state. |
| `$toState`     | `string`                         | The state the model transitions to.                                                         |
| `$transitionName` | `?string`                     | An optional name for the transition, can be null.                                          |
| `$timestamp`   | `\DateTimeInterface`            | The timestamp when the transition occurred.                                                |
| `$context`     | `?ArgonautDTOContract`           | An optional context object that can contain additional data related to the transition.     |
| `$metadata`    | `array<string, mixed>`           | An array of metadata providing further context about the transition.                        |

## Constructor
The constructor is used to initialize the `StateTransitioned` object with the following parameters:

```php
public function __construct(
    public Model $model,
    public string $columnName,
    public ?string $fromState,
    public string $toState,
    public ?string $transitionName,
    public \DateTimeInterface $timestamp,
    public ?ArgonautDTOContract $context = null,
    public array $metadata = [],
)
```

### Parameters
- **`Model $model`**: 
  - This parameter accepts an instance of an Eloquent model that has experienced a state change.
  
- **`string $columnName`**: 
  - The name of the column in the model that reflects its current state. This is essential for determining the state context.

- **`?string $fromState`**: 
  - Accepts the previous state of the model as a string, or null if there was no prior state (typical for a new model).

- **`string $toState`**: 
  - This required parameter specifies the new state the model is transitioning to.

- **`?string $transitionName`**: 
  - An optional name for the transition, which can be used for logging or understanding the context of the change.

- **`\DateTimeInterface $timestamp`**: 
  - A datetime object that logs when the state transition took place.

- **`?ArgonautDTOContract $context`**: 
  - An optional data transfer object that provides context for the transition. This is useful for carrying additional information as part of the event.

- **`array $metadata`**: 
  - An optional array for attaching extra information about the transition. This metadata can include various details relevant to the state change.

### Functionality
The primary purpose of the constructor is to create an instance of the `StateTransitioned` class while capturing and storing all the necessary details of the state transition event. The class can then be utilized in event listeners or handlers that process state changes, providing valuable context to developers about what has occurred.

When an instance of `StateTransitioned` is created, it serves as a structured representation of the state transition that can be dispatched as an event within the Laravel event system. By doing so, it helps maintain separation of concerns and enhances code readability and maintainability.