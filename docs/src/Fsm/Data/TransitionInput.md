# Documentation: TransitionInput.php

Original file: `src/Fsm/Data/TransitionInput.php`

# TransitionInput Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
- [Constructor](#constructor)
- [Method Descriptions](#method-descriptions)
  - [hydrateContext](#hydratecontext)
  - [contextPayload](#contextpayload)
  - [isDryRun](#isdryrun)
  - [isForced](#isforced)
  - [isSilent](#issilent)
  - [getSource](#getsource)
  - [getMetadata](#getmetadata)
  - [hasMetadata](#hasmetadata)
  - [getTimestamp](#gettimestamp)
  - [parameterAcceptsArray](#parameteracceptsarray)
  - [normalizeTypeName](#normalizetypename)

## Introduction
The `TransitionInput` class serves as a Data Transfer Object (DTO) designed to facilitate the passage of consistent input to various transition lifecycle hooks, such as guards, actions, and callbacks within a finite state machine (FSM) implementation. It is enhanced with readonly properties to ensure immutability and employs typed constants to improve type safety and assist in static analysis.

This class is essential for handling transitions in the FSM while providing a standardized structure for input parameters that govern transition behaviors.

## Class Properties

| Property Name    | Type                                     | Description                                                           |
|------------------|------------------------------------------|-----------------------------------------------------------------------|
| `$model`         | `Model`                                 | The model being transitioned.                                         |
| `$fromState`     | `FsmStateEnum|string|null`              | The state being transitioned from.                                    |
| `$toState`       | `FsmStateEnum|string|null`              | The state being transitioned to (required).                          |
| `$context`       | `ArgonautDTOContract|null`              | Additional context data for the transition.                          |
| `$event`         | `string|null`                           | Optional event that triggered the transition.                        |
| `$isDryRun`      | `bool`                                   | Indicates whether this is a simulation run.                          |
| `$mode`          | `string`                                 | The transition execution mode, using predefined constants.            |
| `$source`        | `string`                                 | The source that initiated the transition.                             |
| `$metadata`      | `array<string, mixed>`                   | Additional metadata for the transition.                               |
| `$timestamp`     | `\DateTimeInterface|null`                | When the transition was initiated.                                   |

## Constructor
```php
public function __construct(
    Model|array $model,
    FsmStateEnum|string|null $fromState = null,
    FsmStateEnum|string|null $toState = null,
    ArgonautDTOContract|array|null $context = null,
    ?string $event = null,
    bool $isDryRun = false,
    string $mode = self::MODE_NORMAL,
    string $source = self::SOURCE_USER,
    array $metadata = [],
    ?\DateTimeInterface $timestamp = null
)
```

### Purpose
The constructor initializes a new instance of `TransitionInput`, allowing either direct model input or an associative array of parameters.

### Parameters
- **Model|array** `$model`: The model being transitioned, or an associative array containing the model attributes.
- **FsmStateEnum|string|null** `$fromState`: The state being transitioned from (optional).
- **FsmStateEnum|string|null** `$toState`: The state being transitioned to (required).
- **ArgonautDTOContract|array|null** `$context`: Additional context data for the transition (optional).
- **string|null** `$event`: Optional event that triggered the transition.
- **bool** `$isDryRun`: Whether this is a simulation run (default: false).
- **string** `$mode`: The transition execution mode (default: `self::MODE_NORMAL`).
- **string** `$source`: The source that initiated the transition (default: `self::SOURCE_USER`).
- **array<string, mixed>** `$metadata`: Additional metadata for the transition (default: empty array).
- **\DateTimeInterface|null** `$timestamp`: When the transition was initiated (optional).

### Functionality
The constructor normalizes the input arguments by either constructing the object using positional parameters or from an associative array if applicable. It ensures that required parameters, like `$toState`, are validated based on the specified transition mode. Furthermore, it hydrates the context only after preparing attributes.

If an invalid state transition is attempted, an `InvalidArgumentException` is thrown.

## Method Descriptions

### hydrateContext
```php
protected static function hydrateContext(ArgonautDTOContract|array|null $context): ?ArgonautDTOContract
```

#### Purpose
Hydrates the context from either an instance of `ArgonautDTOContract` or an associative array containing class name and payload.

#### Parameters
- **ArgonautDTOContract|array|null** `$context`: The context data to be hydrated.

#### Return Value
Returns an instance of `ArgonautDTOContract` or `null` if hydration fails.

#### Functionality
This method checks if the given context is an instance of `ArgonautDTOContract`. If it's an array, it attempts to instantiate the DTO class defined within the array, passing the payload for correct reconstruction. Proper exceptions are thrown if class does not exist or fails to implement the required contract.

### contextPayload
```php
public function contextPayload(): ?array
```

#### Purpose
Retrieves the payload from the context in structured format.

#### Return Value
Returns an associative array with keys `class` and `payload`, or `null` if the context is not set or serialization fails.

#### Functionality
This method converts the context to an array format while ensuring it is indeed an array. If the conversion fails, it logs an error to help with diagnostics without interrupting the transition process.

### isDryRun
```php
public function isDryRun(): bool
```

#### Purpose
Checks if the transition is a dry run.

#### Return Value
Returns a boolean indicating whether the transition is in dry run mode.

### isForced
```php
public function isForced(): bool
```

#### Purpose
Determines if the transition should bypass any guards.

#### Return Value
Returns a boolean indicating whether the transition is forced.

### isSilent
```php
public function isSilent(): bool
```

#### Purpose
Checks if the transition should occur with minimal logging or events.

#### Return Value
Returns a boolean indicating whether the transition is silent.

### getSource
```php
public function getSource(): string
```

#### Purpose
Retrieves the source that initiated the transition.

#### Return Value
Returns a string representing the transition source.

### getMetadata
```php
public function getMetadata(string $key, mixed $default = null): mixed
```

#### Purpose
Retrieves a specific metadata value.

#### Parameters
- **string** `$key`: The metadata key to retrieve.
- **mixed** `$default`: Optional default value to return if the key does not exist.

#### Return Value
Returns the value associated with the key or the default if the key doesn't exist.

### hasMetadata
```php
public function hasMetadata(string $key): bool
```

#### Purpose
