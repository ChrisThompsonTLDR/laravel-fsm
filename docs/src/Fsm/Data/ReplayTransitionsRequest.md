# Documentation: ReplayTransitionsRequest.php

Original file: `src/Fsm/Data/ReplayTransitionsRequest.php`

# ReplayTransitionsRequest Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
  - [modelClass](#modelclass)
  - [modelId](#modelid)
  - [columnName](#columnname)
- [Constructor](#constructor)
- [Method: rules](#method-rules)

## Introduction
The `ReplayTransitionsRequest.php` file defines the `ReplayTransitionsRequest` class, which serves as a Data Transfer Object (DTO) designed for handling requests to replay Finite State Machine (FSM) transitions. This class validates and structures the input parameters necessary for reconstructing the state deterministically. By ensuring that the inputs are valid Eloquent model classes, IDs, and column names, it helps maintain the integrity of state transformations in the application.

## Class Properties

### modelClass
- **Type:** `string`
- **Description:** This property holds the name of the Eloquent model class that is being manipulated in the context of FSM transitions.

### modelId
- **Type:** `string`
- **Description:** Contains the identifier of the specific instance of the model class being referenced.

### columnName
- **Type:** `string`
- **Description:** This property indicates the name of the column that is associated with the FSM transitions for the given model.

## Constructor
```php
public function __construct(
    string|array $modelClass,
    string $modelId = '',
    string $columnName = '',
)
```
### Purpose
The constructor initializes the `ReplayTransitionsRequest` instance, populating the properties based on the parameters provided. It also handles the conversion of associative array keys from snake_case to camelCase, preparing the data for easier manipulation.

### Parameters
- **`modelClass`**: This parameter can accept either a string representing the model class name or an associative array containing the properties of the class. If an array is provided, it should be in an associative format.
- **`modelId`** (optional): A string representing the unique identifier of the model instance. Defaults to an empty string.
- **`columnName`** (optional): A string representing the name of the column associated with FSM transitions. Defaults to an empty string.

### Functionality
The constructor performs the following actions:
1. Checks if the `$modelClass` is an associative array. If true, it prepares the attributes for further processing.
2. Merges the prepared attributes with default values ensuring required properties are populated.
3. Calls the parent constructor of the `Dto` class with the appropriately structured data.
4. If the `$modelClass` is a string, it directly populates the DTO properties and calls the parent constructor.

## Method: rules
```php
public function rules(): array
```
### Purpose
The `rules` method defines the validation rules that the properties of the `ReplayTransitionsRequest` must adhere to. It is essential for ensuring that all required data is correctly formatted and that the `modelClass` provided is a legitimate Eloquent model.

### Return Value
- **Type:** `array<string, array<int, string|callable>>`
- **Description:** Returns an associative array where each key corresponds to a property of the class, and the value is an array of validation rules for that property.

### Functionality
1. **modelClass**
   - Must be a required string.
   - Validates that the class name exists using `class_exists()`.
   - Ensures the class is a subclass of `Illuminate\Database\Eloquent\Model` to confirm it is an Eloquent model.

2. **modelId**
   - Must be a required string.

3. **columnName**
   - Must be a required string.

These rules enforce data integrity and help prevent runtime errors by ensuring that the request contains valid information before further processing.

---

This documentation serves as a guide for developers engaging with the `ReplayTransitionsRequest` class, ensuring they understand its purpose, functionality, and structure within the codebase. For further details, refer to the associated classes and methods that interact with this DTO.