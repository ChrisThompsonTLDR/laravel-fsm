# Documentation: ReplayTransitionsResponse.php

Original file: `src/Fsm/Data/ReplayTransitionsResponse.php`

# ReplayTransitionsResponse Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
- [Constructor](#constructor)
- [fromArray Method](#fromarray-method)

## Introduction
The `ReplayTransitionsResponse` class is part of the `Fsm\Data` namespace within a PHP codebase designed to handle finite state machine (FSM) transitions. This class acts as a Data Transfer Object (DTO) specifically for the replay functionality of the FSM transition API. It structures the response data from transition replay requests, providing crucial information such as the success status, initial and final states, the complete sequence of transitions, and any error messages that might have occurred during the process.

## Class Properties
The `ReplayTransitionsResponse` class contains the following properties:

| Property    | Type                          | Description                                                                                          |
|-------------|-------------------------------|------------------------------------------------------------------------------------------------------|
| `$success`  | `bool`                        | Indicates the success status of the transition replay request.                                        |
| `$data`     | `array<string, mixed>`        | Holds the data related to the transition replay response.                                            |
| `$message`  | `string`                     | Contains a message relevant to the response.                                                        |
| `$error`    | `?string`                    | An optional error message that may be present if the request was unsuccessful.                       |
| `$details`  | `?array<string, mixed>`       | Optional additional details about the response.                                                     |

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
The constructor initializes a new instance of the `ReplayTransitionsResponse` class. It can accept parameters in either array format for initializing the object's properties, or it can take individual arguments.

#### Parameters
- `$success` (`bool|array`): Indicates the success of the replay operation. Can be a boolean or an associative array, depending on how the object is instantiated.
- `$data` (`?array`): An optional array containing data relevant to the response. Defaults to an empty array if not provided.
- `$message` (`?string`): An optional message string. Defaults to an empty string if not provided.
- `$error` (`?string`): An optional error message. Defaults to `null` if not provided.
- `$details` (`?array`): Optional additional details related to the response. Defaults to `null` if not provided.

#### Functionality
The constructor includes functionality to handle different initialization scenarios:
1. If called with an array (`$success` as an array), the constructor validates the array structure and populates the properties accordingly.
2. If called with individual boolean parameters or an array that does not consist solely of `$success`, it prepares and validates the attributes from the input parameters and initializes the object.

## fromArray Method
### `fromArray`
```php
public static function fromArray(array $data): self
```

#### Purpose
The `fromArray` method is a static method designed to create an instance of `ReplayTransitionsResponse` from an associative array. This method is useful for converting data received from an API or external source into a structured object.

#### Parameters
- `$data` (`array<string, mixed>`): An associative array containing the values for the properties `success`, `data`, `message`, `error`, and `details`.

#### Returns
- Returns an instance of `ReplayTransitionsResponse`.

#### Functionality
1. This method validates that the provided array contains all required keys.
2. It extracts values from the array and passes them to the constructor of `ReplayTransitionsResponse`.
3. If an error is detected during the validation of the array, it will throw an exception, ensuring that the created instance is valid and complete.

## Summary
The `ReplayTransitionsResponse` class provides a robust structure for handling FSM transition replay API responses. It ensures that responses are consistent, easily understandable, and ready for further processing or transmission within the system. Developers utilizing this class will benefit from its flexibility in initialization and clear response structuring, enhancing the overall effectiveness of the FSM implementation in the PHP application.