# Documentation: ValidateHistoryRequest.php

Original file: `src/Fsm/Data/ValidateHistoryRequest.php`

# ValidateHistoryRequest Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Definition](#class-definition)
- [Constructor `__construct`](#constructor-__construct)
- [Method `rules`](#method-rules)

## Introduction
The `ValidateHistoryRequest.php` file defines a Data Transfer Object (DTO) class called `ValidateHistoryRequest` which is integral to validating FSM (Finite State Machine) transition history within a Laravel application. This class is designed to encapsulate and validate the input parameters necessary for ensuring the integrity and consistency of transition history in the context of an application's state management. 

As part of a larger framework for managing state transitions, this DTO helps in ensuring that the data provided for transitions adheres to required constraints before processing, improving overall system robustness and reliability.

## Class Definition

```php
namespace Fsm\Data;

use Illuminate\Database\Eloquent\Model;
```

The `ValidateHistoryRequest` class extends from a base class `Dto`, which is presumably defined elsewhere in the codebase, providing foundational DTO features. 

### Properties
- **`public string $modelClass`**: The fully qualified class name of the Eloquent model involved in the transition.
- **`public string $modelId`**: The unique identifier for the particular instance of the Eloquent model.
- **`public string $columnName`**: The name of the column in the model that holds the transition state.

## Constructor `__construct`

```php
public function __construct(
    string|array $modelClass,
    string $modelId = '',
    string $columnName = '',
)
```

### Purpose
The constructor initializes the `ValidateHistoryRequest` object with required properties, either through an associative array or through positional parameters. It validates the input to ensure that the properties are correctly defined.

### Parameters
- **`string|array $modelClass`**: Either a string representing the model class name or an associative array containing attributes in camelCase.
- **`string $modelId`** (optional): The ID of the model instance. Defaults to an empty string.
- **`string $columnName`** (optional): The name of the column that represents the transition state. Defaults to an empty string.

### Functionality
- If an associative array is passed as `$modelClass` and it is validated to be in the correct format, the constructor attempts to extract `modelClass`, `modelId`, and `columnName` from it. It raises an `InvalidArgumentException` if any of those required fields are missing or empty.
- If a string is passed for `$modelClass`, the constructor directly validates the parameters and throws exceptions if any required fields are invalid or empty.
- The successful validation results in the parent `Dto` class being instantiated with the validated properties.

## Method `rules`

```php
public static function rules(): array
```

### Purpose
This method defines validation rules for the properties of the `ValidateHistoryRequest` class. It assists in ensuring that the required attributes follow specific validation criteria.

### Return Value
- **`array<string, array<int, string|callable>>`**: Returns an array of validation rules for each property, where each property maps to an array of rules.

### Functionality
- **`modelClass`**: 
  - Required as a non-empty string.
  - Must correspond to an existing class and be a subclass of Laravel's `Model`.
  
- **`modelId`**: 
  - Required as a non-empty string.

- **`columnName`**: 
  - Required as a non-empty string.

Each validation checks the value against specified conditions, and if any check fails, an error message is passed to the `$fail` callback, which is essential for handling validation responses in a Laravel application.

## Conclusion
The `ValidateHistoryRequest` class plays a pivotal role in validating input data for FSM transitions by ensuring that all required properties are defined and that they meet specific contextual rules. Proper usage and understanding of this class contribute significantly to maintaining data integrity when managing state transitions in applications utilizing the FSM pattern. 

### Note
Developers should consider extending the validation rules or modifying error messages in accordance with the requirements of the specific application context as needed.