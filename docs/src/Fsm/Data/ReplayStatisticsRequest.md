# Documentation: ReplayStatisticsRequest.php

Original file: `src/Fsm/Data/ReplayStatisticsRequest.php`

# ReplayStatisticsRequest Documentation

## Table of Contents
- [Introduction](#introduction)
- [Constructor](#constructor)
- [Validation Rules](#validation-rules)

## Introduction

The `ReplayStatisticsRequest.php` file defines the `ReplayStatisticsRequest` class, which serves as a Data Transfer Object (DTO) for retrieving FSM (Finite State Machine) transition statistics. This class structures and validates the input parameters necessary for analyzing and obtaining statistics related to FSM usage patterns.

By encapsulating the data and validation rules in this class, the codebase ensures that only valid requests are processed for analytics, promoting better data integrity and maintainability.

## Constructor

### Method Signature

```php
public function __construct(string|array $modelClass, string $modelId = '', string $columnName = '');
```

### Purpose

The constructor initializes a new instance of the `ReplayStatisticsRequest` class. It processes the input parameters, either as individual strings or as a single associative array, and prepares them for later validation and usage.

### Parameters

- `string|array $modelClass`: The name of the model class as a string, or an associative array representing the attributes to initialize the object. If an array is provided with associative keys, the keys must correspond to `snake_case` naming convention attributes.
- `string $modelId` (optional): The identifier of the model instance for which statistics are requested. Defaults to an empty string.
- `string $columnName` (optional): The specific column name for which statistics are requested. Defaults to an empty string.

### Functionality

- The constructor checks if the `$modelClass` parameter is an array and contains associative keys (this is determined using `static::isAssociative()`). 
- If it meets the criteria, it prepares the attributes (converting `snake_case` to `camelCase`) using `static::prepareAttributes($modelClass)`.
- The data is then merged with default values to ensure all necessary keys are included.
- Finally, if the input is valid, the parent constructor of `Dto` is called with the prepared data.

Here is an example of how to invoke this constructor:

```php
$request = new ReplayStatisticsRequest([
    'modelClass' => 'App\Models\User',
    'modelId' => '123',
    'columnName' => 'status',
]);
```

## Validation Rules

### Method Signature

```php
public static function rules(): array;
```

### Purpose

The `rules` method defines the validation rules for the parameters of the `ReplayStatisticsRequest` class. This method ensures that all incoming data complies with the expected format and type restrictions, which protects the application from invalid input.

### Return Value

- Returns an array containing validation rules for each attribute of the class.

### Functionality

- The method returns an associative array where each key corresponds to a property of the `ReplayStatisticsRequest` class:
  - **modelClass**: 
    - Required - Must be a string.
    - Includes a custom validation function to check if the class exists and is a subclass of `Illuminate\Database\Eloquent\Model`.
  - **modelId**: 
    - Required - Must be a string.
  - **columnName**: 
    - Required - Must be a string.

The custom validation for `modelClass` performs two key checks:
1. Ensures that the specified class exists using `class_exists()`.
2. Validates that the class is an Eloquent model by checking its subclass status with `is_subclass_of()`.

Here's an example of how this method could be used in a validation context:

```php
$rules = ReplayStatisticsRequest::rules();
// Expected $rules structure:
// [
//     'modelClass' => [
//         'required',
//         'string',
//         function ($attribute, $value, $fail) { /* ... */ },
//     ],
//     'modelId' => ['required', 'string'],
//     'columnName' => ['required', 'string'],
// ]
```

The use of this structured approach enhances code readability and maintains separation of concerns, ensuring that requests are properly validated before being processed further.