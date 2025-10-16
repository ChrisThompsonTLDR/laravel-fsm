# Documentation: ReplayHistoryResponse.php

Original file: `src/Fsm/Data/ReplayHistoryResponse.php`

# ReplayHistoryResponse Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
- [Constructor](#constructor)

## Introduction
The `ReplayHistoryResponse` class serves as a Data Transfer Object (DTO) for the FSM (Finite State Machine) transition history API. It structures the response data returned from transition history requests, ensuring that the API provides a consistent format, complete with success indicators, data payloads, informative messages, and error handling. This class is pivotal in promoting clarity and readability in API communication and plays a central role in how clients receive and interpret transition history responses.

## Class Properties

| Property Name | Type                      | Description                                                                           |
|---------------|---------------------------|-------------------------------------------------------------|
| `success`     | `bool`                   | Indicates if the request was successful.                                  |
| `data`       | `array<string, mixed>`    | Contains the primary data returned from the transition history request. |
| `message`     | `string`                 | A message providing additional context about the request outcome.          |
| `error`       | `?string`                | An optional field for error messages in case of failure.                  |
| `details`     | `?array<string, mixed>`   | An optional field that can hold detailed information about the response. |

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
The constructor initializes a new instance of the `ReplayHistoryResponse` class, allowing for both array-based initialization and traditional parameter-based construction.

#### Parameters
- `success` (`bool|array`): 
  - A boolean indicating whether the request was successful. 
  - Alternatively, this parameter can be an array, allowing for the construction of the DTO using an associative array of attributes.
  
- `data` (`?array`, optional):
  - An optional associative array containing the data returned from the transition history API. Default is an empty array.

- `message` (`?string`, optional):
  - An optional string message that provides additional context. Default is an empty string.

- `error` (`?string`, optional):
  - An optional error message string. Defaults to null, meaning no errors occurred.

- `details` (`?array`, optional):
  - An optional associative array that contains additional details related to the response. Default is null.

#### Functionality
1. **Array-based Initialization**:
   - If the first parameter is an array and only one argument is provided, the constructor validates the array structure to ensure it contains the required fields (`success`, `data`, `message`, `error`, `details`).
   - If the parameter is valid, it prepares the attributes for the parent `Dto` class's constructor.

2. **Array with Additional Parameters**:
   - If the first parameter is an array and additional parameters are passed, the constructor still validates the array and ensures that `message` has a default value if not specified.

3. **Standard Initialization**:
   - If `success` is not an array, the constructor calls the parent constructor directly with parameters packed in an associative array. 

This dual approach to initialization ensures that the `ReplayHistoryResponse` can be instantiated flexibly by both API consumers and system internal processes while ensuring data integrity.

## Conclusion
The `ReplayHistoryResponse` class is an essential component within the FSM transition history response framework. It encapsulates operational outcomes, provides detailed error handling, and structures response data in a reproducible manner, ensuring an improved developer experience through its clear, logical construction. Use this documentation to effectively implement and utilize the `ReplayHistoryResponse` within your applications.