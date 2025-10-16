# Documentation: FsmTransitionFailedException.php

Original file: `src/Fsm/Exceptions/FsmTransitionFailedException.php`

# FsmTransitionFailedException Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Constructor](#constructor)
- [Methods](#methods)
  - [getFromState](#getfromstate)
  - [getToState](#gettoState)
  - [getReason](#getreason)
  - [getOriginalException](#getoriginalexception)
  - [getStateValueForMessage](#getstatevalueformessage)
  - [forInvalidTransition](#forinvalidtransition)
  - [forGuardFailure](#forguardfailure)
  - [forCallbackException](#forcalbackexception)
  - [forConcurrentModification](#forconcurrentmodification)

## Introduction
The `FsmTransitionFailedException` class is a custom exception designed for use within a finite state machine (FSM) context. It encapsulates details related to specific transitions between states that fail for various reasons, allowing developers to easily understand the context and cause of the failure. This class extends PHP's built-in `RuntimeException`, ensuring that it behaves like a typical exception while providing additional informational capabilities related to FSM transitions.

## Class Overview
```php
namespace Fsm\Exceptions;

use Fsm\Contracts\FsmStateEnum;
use RuntimeException;
use Throwable;

class FsmTransitionFailedException extends RuntimeException
```
The `FsmTransitionFailedException` class is part of the `Fsm\Exceptions` namespace. It serves to clearly identify its purpose within a broader framework of exceptions that may arise during the operation of an FSM.

### Constructor
```php
public function __construct(
    public readonly FsmStateEnum|string|null $fromState,
    public readonly FsmStateEnum|string $toState,
    public readonly string $reason,
    string $message = '',
    int $code = 0,
    ?Throwable $previous = null,
    public readonly ?Throwable $originalException = null
)
```
#### Purpose
Constructs a new `FsmTransitionFailedException` with specified states, reasons, and additional exception information.

#### Parameters
- **FsmStateEnum|string|null $fromState**: The state that the transition is initiated from.
- **FsmStateEnum|string $toState**: The state that the transition is attempted to.
- **string $reason**: A detailed reason why the transition failed.
- **string $message**: A detailed message for the exception. If left empty, a default message will be generated based on the states and reason.
- **int $code**: An optional error code that represents the type of error. Defaults to 0.
- **Throwable|null $previous**: The previous throwable that caused this exception, if applicable.
- **Throwable|null $originalException**: The original exception that triggered the failure, if any.

#### Functionality
- The constructor initializes the properties of the exception and potentially constructs a default error message if one hasn't been provided.
- It calls the parent constructor of `RuntimeException` to ensure proper exception handling.

## Methods

### getFromState
```php
public function getFromState(): FsmStateEnum|string|null
```
#### Purpose
Retrieves the state that the transition is being made from.

#### Return Value
- **FsmStateEnum|string|null**: The state being transitioned from.

#### Functionality
This method provides access to the `fromState` property of the exception, allowing users to identify the originating state of the transition failure.

### getToState
```php
public function getToState(): FsmStateEnum|string
```
#### Purpose
Retrieves the state that the transition is being made to.

#### Return Value
- **FsmStateEnum|string**: The state being transitioned to.

#### Functionality
This method allows users to discern the intended state of the transition that failed, providing additional context for debugging.

### getReason
```php
public function getReason(): string
```
#### Purpose
Retrieves the reason for the transition failure.

#### Return Value
- **string**: A description explaining why the transition did not succeed.

#### Functionality
This method exposes the `reason` property, giving effective insight into the nature of the failure, thus aiding in error resolution.

### getOriginalException
```php
public function getOriginalException(): ?Throwable
```
#### Purpose
Retrieves the original exception that triggered the transition failure, if one exists.

#### Return Value
- **?Throwable**: The original throwable instance that caused this failure, or `null` if none exists.

#### Functionality
This method can be useful for tracing back the specific error that led to the transition failing, allowing for in-depth debugging of issues.

### getStateValueForMessage
```php
private static function getStateValueForMessage(FsmStateEnum|string|null $state): string
```
#### Purpose
Converts the state value into a string for display purposes.

#### Parameters
- **FsmStateEnum|string|null $state**: The state to be converted to a string. This can be either an enum value, a string, or null.

#### Return Value
- **string**: The string representation of the state.

#### Functionality
This utility method checks if the provided state is a `FsmStateEnum` instance or a string and returns its string representation. If it is `null`, it returns a string "(null)".

### forInvalidTransition
```php
public static function forInvalidTransition(
    FsmStateEnum|string|null $from,
    FsmStateEnum|string $to,
    string $modelClass,
    string $columnName
): self
```
#### Purpose
Static method for creating an `FsmTransitionFailedException` specifically when an invalid transition is attempted.

#### Parameters
- **FsmStateEnum|string|null $from**: The state being transitioned from.
- **FsmStateEnum|string $to**: The state being transitioned to.
- **string $modelClass**: The class of the model associated with the transition.
- **string $columnName**: The name of the column within the model that represents the state.

#### Return Value
- **self**: A new instance of `FsmTransitionFailedException`.

#### Functionality
This method constructs a specific error message indicating that no defined transition exists for the given states within a specified model's context.

### forGuardFailure
```php
public static function forGuardFailure(
    FsmStateEnum|string|null $from,
    FsmStateEnum|string $to,
    string $guardDescription,
    string $modelClass,
    string $columnName
): self
```
#### Purpose
Static method to handle transition failures due to guard conditions not being met.

#### Parameters
- **FsmStateEnum|string|null $from**: The origin state of the transition.
- **FsmStateEnum|string $to**: The target state of the transition.
- **string $guardDescription**: Description of the guard condition that failed.
- **string $modelClass**: The associated model's class.
- **string $columnName**: The state column within the model.

#### Return Value
- **self**: A new `FsmTransitionFailedException` instance detailing the guard failure.

#### Function