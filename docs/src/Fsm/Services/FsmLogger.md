# Documentation: FsmLogger.php

Original file: `src/Fsm/Services/FsmLogger.php`

# FsmLogger Documentation

## Table of Contents
- [Introduction](#introduction)
- [Methods](#methods)
  - [extractUserId](#extractuserid)
  - [logToChannel](#logtochannel)
  - [normalizeState](#normalizestate)
  - [filterContextForLogging](#filtercontextforlogging)
  - [recursivelyRemoveSensitiveKeys](#recursivelyremovesensitivekeys)
  - [subjectFromVerbs](#subjectfromverbs)
  - [logSuccess](#logsuccess)
  - [logFailure](#logfailure)
  - [logTransition](#logtransition)

## Introduction

The `FsmLogger` class is part of a PHP application that implements a finite state machine (FSM) logging mechanism. This class facilitates tracking FSM transitions and errors effectively through organized logging. It utilizes structured and unstructured logging based on configuration settings, and it assists in capturing relevant information about the context of each FSM transition.

The `FsmLogger` reads configuration settings regarding logging preferences, captures relevant state transition data, manages sensitive information filtering, and logs the transition events either as successes or failures. The integration with external logging libraries also allows the flexibility of different logging channels and formats.

## Methods

### extractUserId

```php
private static function extractUserId($state): ?string
```

#### Purpose
Extracts the `user_id` from a given state object regardless of its visibility (public or private).

#### Parameters
- **$state** (`object|null`): The state object from which to extract the `user_id`. It can be null.

#### Return Value
- **string|null**: Returns the user ID as a string if found; otherwise, returns null.

#### Functionality
The method ensures that it can extract the `user_id` from various scenarios:
1. Checks for a public property called `user_id`.
2. Checks for a getter method `getUserId`.
3. Utilizes PHP's Reflection classes to access a private property if necessary.
4. Catches exceptions that may arise during the reflection process to ensure robustness.

### logToChannel

```php
protected function logToChannel(array $data, bool $isFailure = false): void
```

#### Purpose
Writes log information to the specified logging channel based on the application configuration.

#### Parameters
- **$data** (`array<string, mixed>`): An associative array containing the logging data.
- **$isFailure** (`bool`): Indicates whether the log corresponds to a failed FSM transition.

#### Return Value
- **void**: Does not return a value.

#### Functionality
The method checks:
- The logging channel configuration.
- If structured logging is enabled, log data is recorded in a structured format; otherwise, it creates a flattened string message.
It categorizes logs as either "info" for successes or "error" for failures.

### normalizeState

```php
protected function normalizeState(FsmStateEnum|string $state): string
```

#### Purpose
Normalizes the state value, ensuring it is consistently represented as a string.

#### Parameters
- **$state** (`FsmStateEnum|string`): An FSM state that can either be an instance of `FsmStateEnum` or a string.

#### Return Value
- **string**: Returns the normalized state value as a string.

#### Functionality
This method verifies if the input state is an enumeration (instance of `FsmStateEnum`). If so, it retrieves the value corresponding to that enumeration; otherwise, it returns the state as-is.

### filterContextForLogging

```php
protected function filterContextForLogging(?ArgonautDTOContract $context): ?array
```

#### Purpose
Prepares context data for logging by filtering out any sensitive information.

#### Parameters
- **$context** (`ArgonautDTOContract|null`): The context object to be filtered, which can be null.

#### Return Value
- **array<string, mixed>|null**: Returns an array of filtered context data, or null if no context is provided.

#### Functionality
The method attempts to convert the DTO context into an array. It retrieves a list of sensitive keys from configuration and subsequently removes any sensitive data via recursive filtering.

### recursivelyRemoveSensitiveKeys

```php
protected function recursivelyRemoveSensitiveKeys(array $data, array $sensitiveKeys, string $prefix = ''): array
```

#### Purpose
Recursively removes sensitive keys from an associative array, ensuring compliance with privacy constraints.

#### Parameters
- **$data** (`array<string, mixed>`): The input data from which sensitive keys need to be removed.
- **$sensitiveKeys** (`array<int, string>`): An array of keys that should be filtered out.
- **$prefix** (`string`): The current key prefix for nested arrays.

#### Return Value
- **array<string, mixed>**: Returns an array with sensitive data removed.

#### Functionality
The method performs a depth-first search through the data structure, checking each key against the list of sensitive keys and removing those that match or correspond to wildcard entries.

### subjectFromVerbs

```php
protected function subjectFromVerbs(): ?array
```

#### Purpose
Retrieves subject information from the current Verbs state when applicable.

#### Return Value
- **array{subject_id: string, subject_type: string}|null**: Returns an associative array with subject ID and type or null if not available.

#### Functionality
It checks whether the logging of user subject data from Verbs is enabled, attempts to access the current Verbs state, and extracts the `user_id`, aligning it with its model type for logging.

### logSuccess

```php
public function logSuccess(
    Model $model,
    string $columnName,
    FsmStateEnum|string|null $fromState,
    FsmStateEnum|string $toState,
    ?string $transitionEvent,
    ?ArgonautDTOContract $context,
    ?int $durationMs = null
): void
```

#### Purpose
Records a successful FSM transition into the logs and optionally into the database.

#### Parameters
- **$model** (`Model`): The model instance involved in the transition.
- **$columnName** (`string`): The DB column related to the FSM.
- **$fromState** (`FsmStateEnum|string|null`): The state before the transition, can be null.
- **$toState** (`FsmStateEnum|string`): The state after the transition.
- **$transitionEvent** (`string|null`): Optional event name that triggered the transition.
- **$context** (`ArgonautDTOContract|null`): Optional context data associated with the transition.
- **$durationMs** (`int|null`): Optional duration of the transition in milliseconds.

#### Return Value
- **void**: Does not return any value.

#### Functionality
The method creates a structured log entry, adds to the database log, and then logs the data to the configured logging channel. It handles transitions cleanly and checks if logging is enabled through configuration.

### logFailure

```php
public function logFailure(
    Model $model,
    string $columnName,
    FsmStateEnum|string|null $fromState,
    FsmStateEnum|string $to