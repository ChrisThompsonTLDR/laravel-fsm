# Documentation: Dto.php

Original file: `src/Fsm/Data/Dto.php`

# Dto.php Documentation

## Table of Contents
- [Introduction](#introduction)
- [Methods](#methods)
  - [isAssociative](#isassociative)
  - [hasOnlyStringKeys](#hasonlystringkeys)
  - [hasStringKeys](#hasstringkeys)
  - [isCallableArray](#iscallablearray)
  - [isDtoPropertyArray](#isdtopropertyarray)
  - [validateArrayForConstruction](#validatearrayforconstruction)
  - [prepareAttributes](#prepareattributes)
  - [guardAssociativePayload](#guardassociativepayload)
  - [from](#from)
  - [fromCollection](#fromcollection)
  - [collect](#collect)

## Introduction
The `Dto.php` file defines an abstract class `Dto` that extends the `ArgonautDTO` class. This class acts as a foundation for Data Transfer Objects (DTOs) in a Laravel application. Its primary role is to handle the validation and preparation of data structures used to pass between different parts of the application, ensuring that the data meets specific requirements before being processed.

DTOs are particularly useful in scenarios where strict data integrity is required, allowing for a clean separation between layers of an application, reducing the chances of unintended transformations or errors. This class includes a variety of utility methods that check array structures, validate data for construction, and facilitate the conversion of data into specific DTO instances.

## Methods

### isAssociative

```php
protected static function isAssociative(array $value): bool
```

#### Purpose
Determines if the provided array is associative (i.e., has named keys).

#### Parameters
- **`array $value`**: The array being checked.

#### Return Value
- **`bool`**: Returns `true` if the array is associative, `false` otherwise.

#### Functionality
This method compares the keys of the array against a range of numeric indices. If the keys do not match, the array is considered associative.

---

### hasOnlyStringKeys

```php
protected static function hasOnlyStringKeys(array $value): bool
```

#### Purpose
Checks if the array contains only string keys.

#### Parameters
- **`array $value`**: The array being checked.

#### Return Value
- **`bool`**: Returns `true` if all keys are strings, `false` otherwise.

#### Functionality
The method iterates through each key of the array and verifies that they are all strings. If it encounters any non-string key, it returns `false`.

---

### hasStringKeys

```php
protected static function hasStringKeys(array $value): bool
```

#### Purpose
Checks if the array has at least one string key.

#### Parameters
- **`array $value`**: The array being checked.

#### Return Value
- **`bool`**: Returns `true` if at least one key is a string, `false` otherwise.

#### Functionality
This method loops through the keys of the array to confirm the presence of at least one string key, allowing for mixed key arrays.

---

### isCallableArray

```php
public static function isCallableArray(array $value): bool
```

#### Purpose
Determines if the provided array is a callable structure.

#### Parameters
- **`array $value`**: The array being checked.

#### Return Value
- **`bool`**: Returns `true` if the array is a valid callable array, `false` otherwise.

#### Functionality
Checks the structure of the array to confirm it has exactly two elements and verifies that the second element is a non-empty string (the method name) and the first element is either an object or a non-empty string (the class name).

---

### isDtoPropertyArray

```php
public static function isDtoPropertyArray(array $value, array $expectedKeys = []): bool
```

#### Purpose
Checks if the array represents a valid structure for DTO construction.

#### Parameters
- **`array $value`**: The array being checked.
- **`array $expectedKeys`**: An optional array of keys that are expected to be present.

#### Return Value
- **`bool`**: Returns `true` if the array is suitable for DTO construction, `false` otherwise.

#### Functionality
Validates the structure of the array to ensure it is associative, not callable, and potentially contains expected keys or at least one string key.

---

### validateArrayForConstruction

```php
protected static function validateArrayForConstruction(array $value, array $expectedKeys = []): void
```

#### Purpose
Validates the array structure for DTO construction.

#### Parameters
- **`array $value`**: The array being validated.
- **`array $expectedKeys`**: An optional array of keys that are expected to be present.

#### Exception
- Throws `InvalidArgumentException` if the validation fails.

#### Functionality
Checks for several conditions:
- The array must be non-empty.
- It must not be a callable array.
- It must be associative.
- It must contain at least one string key.
- If expected keys are provided, it checks if at least one exists in the array.

---

### prepareAttributes

```php
protected static function prepareAttributes(array $attributes, array $defaults = []): array
```

#### Purpose
Prepares the attributes for DTO construction by converting keys to camelCase.

#### Parameters
- **`array $attributes`**: The array of attributes for the DTO.
- **`array $defaults`**: An optional array of default values.

#### Return Value
- **`array`**: Returns a prepared array of attributes.

#### Functionality
This method processes the attributes to convert keys from snake_case to camelCase where appropriate, while also maintaining any defaults that may have been provided.

---

### guardAssociativePayload

```php
protected static function guardAssociativePayload(mixed $payload): array
```

#### Purpose
Validates that the given payload is an associative array and prepares it for use.

#### Parameters
- **`mixed $payload`**: The payload to be validated and prepared.

#### Return Value
- **`array`**: Returns a prepared associative array.

#### Exception
- Throws `InvalidArgumentException` if the validation fails.

#### Functionality
Checks that the payload is an array, ensures it is associative, and prepares the attributes using the `prepareAttributes` method.

---

### from

```php
public static function from(mixed $payload): static
```

#### Purpose
Creates a new DTO instance from the given payload.

#### Parameters
- **`mixed $payload`**: The data used to create the DTO instance.

#### Return Value
- **`static`**: Returns a new instance of the DTO.

#### Exception
- Throws `InvalidArgumentException` if the payload cannot be converted.

#### Functionality
This method checks the type of payload (array, object, or `Request`) and appropriately constructs the DTO. It also validates the instance if a `rules` method is present.

---

### fromCollection

```php
public static function fromCollection(iterable $items): Collection
```

#### Purpose
Creates a collection