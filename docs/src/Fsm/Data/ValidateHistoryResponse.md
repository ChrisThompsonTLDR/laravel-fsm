# Documentation: ValidateHistoryResponse.php

Original file: `src/Fsm/Data/ValidateHistoryResponse.php`

# ValidateHistoryResponse Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Constructor](#constructor)

## Introduction

The `ValidateHistoryResponse.php` file contains the definition of the `ValidateHistoryResponse` class, which is a Data Transfer Object (DTO) used in the context of finite state machine (FSM) transition history validation APIs. This class structures the response data resulting from history validation requests by encapsulating various aspects of the validation result, including success status, outcome data, an informative message, any errors encountered, and detailed validation notes. 

The focus of this class is to provide a clean and consistent interface for managing and returning validation results related to FSM transitions, making it easier for developers to handle responses from the validation process effectively.

## Class Overview

### `ValidateHistoryResponse`

The `ValidateHistoryResponse` class extends a base DTO class, providing properties and methods to manage and structure response data for transition history validation operations.

#### Properties

| Property Name | Type                          | Description                                                  |
|---------------|-------------------------------|--------------------------------------------------------------|
| `success`     | `bool`                        | Indicates whether the validation was successful.            |
| `data`        | `array<string, mixed>`        | Contains the main response data from the validation request.|
| `message`     | `string`                     | An informational message about the validation result.       |
| `error`       | `?string`                    | Contains details about any errors that occurred, if any.    |
| `details`     | `?array<string, mixed>`       | Optional details about the validation results.              |

## Constructor

### `__construct`

```php
public function __construct(
    bool|array $success,
    ?array $data = null,
    ?string $message = null,
    ?string $error = null,
    ?array $details = null,
)
```

#### Purpose

The constructor initializes a new instance of the `ValidateHistoryResponse` class. It can accept both array-based initialization and named parameter initialization to accommodate different usage scenarios.

#### Parameters

- **`$success`** (`bool|array`):
  - An indication of whether the validation was successful. This can also be an array for array-based initialization.
  
- **`$data`** (`?array`):
  - This parameter holds additional data related to the validation that might be returned. It defaults to an empty array if not provided.
  
- **`$message`** (`?string`):
  - A message that generally explains the outcome of the validation. Defaults to an empty string if not provided.
  
- **`$error`** (`?string`):
  - An optional parameter that details any errors encountered during the validation. Defaults to `null` if not provided.

- **`$details`** (`?array`):
  - Additional information regarding the validation result. This parameter is optional and defaults to `null`.

#### Functionality

- The constructor first checks if the `$success` parameter is an array. If it is, the array is validated using the `validateArrayForConstruction` method to ensure that required keys are present.
  
- If the array-based initialization is used, it initializes the parent DTO with prepared attributes extracted from the `success` array.

- If the `$success` parameter is not an array, the constructor initializes the parent DTO with a direct associative array, passing boolean values along with any provided data, message, error, and details.

This flexibility allows developers to instantiate `ValidateHistoryResponse` either by using an associative array for easier construction or through named parameters for clearer and more explicit value definitions.

### Example Usage

```php
// Named parameter initialization
$response = new ValidateHistoryResponse(
    success: true,
    data: ['transitionId' => 12345],
    message: 'Validation successful',
    error: null,
    details: ['timestamp' => '2023-10-01T12:00:00Z']
);

// Array-based initialization
$response = new ValidateHistoryResponse([
    'success' => false,
    'data' => [],
    'message' => 'Validation failed',
    'error' => 'Transition ID not found',
    'details' => ['transitionId' => 99999]
]);
``` 

By providing a structured response for validation processes, `ValidateHistoryResponse` simplifies error handling and outcome presentation, making the codebase clearer and more maintainable.