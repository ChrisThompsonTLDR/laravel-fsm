# Documentation: ReplayStatisticsResponse.php

Original file: `src/Fsm/Data/ReplayStatisticsResponse.php`

# ReplayStatisticsResponse Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Constructor](#constructor)

## Introduction

The `ReplayStatisticsResponse` class serves as a Data Transfer Object (DTO) that structures the response data for the FSM (Finite State Machine) transition statistics API. Its primary role is to provide a comprehensive representation of analytics and metrics regarding FSM usage, consolidating the data into a standardized format for easier access and manipulation. This response DTO simplifies the communication between the backend service and the client, ensuring that both success states and potential error conditions are encapsulated effectively.

## Class Overview

The `ReplayStatisticsResponse` class inherits from a base class `Dto`. Its main attributes and functionalities include:

- **success**: A boolean indicating whether the request was successful.
- **data**: An array holding the primary data returned from the FSM statistics API.
- **message**: A string that conveys a message about the response, typically used for additional context or informational messages.
- **error**: An optional string that can contain error messages in case of unsuccessful requests.
- **details**: An optional array for including further details about the request, useful for debugging or additional information.

## Constructor

```php
public function __construct(
    bool|array $success,
    ?array $data = null,
    ?string $message = null,
    ?string $error = null,
    ?array $details = null,
)
```

### Purpose

The constructor initializes a new instance of the `ReplayStatisticsResponse` class, allowing the creation of the object using either a success flag and accompanying parameters or an array representing the response data.

### Parameters

- **`bool|array $success`**: This parameter can either be:
  - A boolean indicating the success status of the API request (true means successful, false indicates an error).
  - An array containing keys such as `success`, `data`, `message`, `error`, and `details` for initializing the object more comprehensively.

- **`?array $data = null`**: Optional parameter for the main data returned by the API. If not provided, it defaults to an empty array.

- **`?string $message = null`**: Optional message parameter to provide additional context about the response. Defaults to an empty string if not provided.

- **`?string $error = null`**: Optional parameter containing any error message generated during the request. If not applicable, it remains null.

- **`?array $details = null`**: Optional parameter for further details or metadata related to the request. Defaults to null if not provided.

### Functionality

The constructor checks the type of the `$success` parameter to determine how to initialize the object:

1. **Array-based initialization**:
   - If `$success` is an array and only this argument is provided, it validates the array for required fields. Then, it initializes the parent `Dto` with the prepared attributes from the array.

2. **Array with additional parameters**:
   - If `$success` is an array but additional parameters are also provided, it validates the array, prepares the attributes with certain fields (like defaults for `message`), and initializes the parent `Dto`.

3. **Standard initialization**:
   - When `$success` is a boolean, the constructor directly initializes the attributes using the provided parameters, filling in defaults where necessary.

This comprehensive initialization approach ensures that the `ReplayStatisticsResponse` can adapt to different use cases and is robust against various input formats. 

By combining flexibility with validation, it facilitates the correct representation of FSM transition statistics in the API's responses.