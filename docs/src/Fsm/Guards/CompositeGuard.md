# Documentation: CompositeGuard.php

Original file: `src/Fsm/Guards/CompositeGuard.php`

# CompositeGuard Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Constructor](#constructor)
- [Static Methods](#static-methods)
- [Public Methods](#public-methods)
  - [evaluate](#evaluate)
  - [count](#count)
  - [getStrategy](#getstrategy)
  - [getGuards](#getguards)
- [Private Methods](#private-methods)
  - [evaluateAllMustPass](#evaluateallmustpass)
  - [evaluateAnyMustPass](#evaluateanymustpass)
  - [evaluatePriorityFirst](#evaluatepriorityfirst)
  - [executeGuard](#executeguard)
  - [executeCallableWithInstance](#executecallablewithinstance)
  - [formatGuardDescription](#formatguarddescription)
  - [getStateValue](#getstatevalue)

## Introduction

The `CompositeGuard` class is a manager for executing a collection of guards during state transitions in a finite state machine (FSM). Its primary purpose is to offer advanced composition and execution strategies for guards, allowing them to be evaluated according to various strategies such as priority-based execution and short-circuit evaluation. The class raises exceptions with enhanced error reporting when guards fail, aiding in debugging and state management.

## Class Overview

```php
namespace Fsm\Guards;

use Fsm\Data\TransitionGuard;
use Fsm\Data\TransitionInput;
use Fsm\Exceptions\FsmTransitionFailedException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Throwable;
```

### Properties
- **guards**: A collection of `TransitionGuard` instances used for evaluation.
- **evaluationStrategy**: Defines the strategy for evaluating the guards, with options for all must pass, any must pass, or priority first.

## Constructor

```php
public function __construct(
    private readonly Collection $guards,
    private readonly string $evaluationStrategy = self::STRATEGY_ALL_MUST_PASS,
)
```

### Purpose
Initializes the composite guard with a collection of guards and an optional evaluation strategy.

### Parameters
- `Collection<int, TransitionGuard> $guards`: A collection containing guard objects.
- `string $evaluationStrategy`: The strategy to evaluate the guards (default is `self::STRATEGY_ALL_MUST_PASS`).

## Static Methods

### create

```php
public static function create(array $guards, string $strategy = self::STRATEGY_ALL_MUST_PASS): self
```

#### Purpose
Creates a new instance of the `CompositeGuard` class with a given array of guards and evaluation strategy.

#### Parameters
- `array<TransitionGuard> $guards`: An array of guard instances.
- `string $strategy`: The evaluation strategy to use.

#### Return Value
- Returns an instance of `CompositeGuard`.

## Public Methods

### evaluate

```php
public function evaluate(TransitionInput $input, string $columnName): bool
```

#### Purpose
Evaluates all the instantiated guards based on the specified evaluation strategy.

#### Parameters
- `TransitionInput $input`: The input containing transition data used for evaluating the guards.
- `string $columnName`: The name of the FSM state column (e.g., 'status').

#### Return Value
- Returns `true` if the evaluation passes, otherwise throws `FsmTransitionFailedException`.

### count

```php
public function count(): int
```

#### Purpose
Retrieves the count of guards in the composite guard.

#### Return Value
- Returns the number of guards as an integer.

### getStrategy

```php
public function getStrategy(): string
```

#### Purpose
Returns the evaluation strategy currently being used by the composite guard.

#### Return Value
- Returns the evaluation strategy as a string.

### getGuards

```php
public function getGuards(): Collection
```

#### Purpose
Retrieves all the guards in the composite, ordered by their priority.

#### Return Value
- Returns a collection of `TransitionGuard` instances sorted by priority.

## Private Methods

### evaluateAllMustPass

```php
private function evaluateAllMustPass(TransitionInput $input, string $columnName): bool
```

#### Purpose
Evaluates all guards, requiring that each must pass for the evaluation to succeed.

#### Return Value
- Returns `true` if all guards pass; otherwise, throws `FsmTransitionFailedException`.

### evaluateAnyMustPass

```php
private function evaluateAnyMustPass(TransitionInput $input, string $columnName): bool
```

#### Purpose
Evaluates the guards such that at least one must pass for the evaluation to succeed.

#### Return Value
- Returns `true` if at least one guard passes; otherwise, throws `FsmTransitionFailedException`.

### evaluatePriorityFirst

```php
private function evaluatePriorityFirst(TransitionInput $input, string $columnName): bool
```

#### Purpose
Executes guards in priority order until one returns true or all fail.

#### Return Value
- Returns `true` on the first successful guard; throws `FsmTransitionFailedException` if all guards fail.

### executeGuard

```php
private function executeGuard(TransitionGuard $guard, TransitionInput $input): mixed
```

#### Purpose
Executes an individual guard and returns its result.

#### Parameters
- `TransitionGuard $guard`: The guard to execute.
- `TransitionInput $input`: The input data used for the execution.

#### Return Value
- Returns the result of the guard execution, which can be any type.

### executeCallableWithInstance

```php
private function executeCallableWithInstance(mixed $callable, array $parameters): mixed
```

#### Purpose
Executes a callable which may include an object instance, ensuring correct parameter mapping.

#### Parameters
- `mixed $callable`: The callable to execute, either as an array containing an object instance and method name or just a function/closure.
- `array<string, mixed> $parameters`: The parameters to pass to the callable.

#### Return Value
- Returns the result of the callable execution.

### formatGuardDescription

```php
private function formatGuardDescription(TransitionGuard $guard): string
```

#### Purpose
Formats and returns a description for a given guard for error message reporting.

#### Parameters
- `TransitionGuard $guard`: The guard instance to format.

#### Return Value
- Returns a string description of the guard.

### getStateValue

```php
private function getStateValue(mixed $state): ?string
```

#### Purpose
Converts a state value into its string representation for cleaner logging and exception messages.

#### Parameters
- `mixed $state`: The state value to convert.

#### Return Value
- Returns the string representation of the state or `null`.

This documentation provides a comprehensive overview of the `CompositeGuard` class, detailing its methods, functionality, and purpose within the system. By understanding this documentation, developers can gain insights into the workings of FSM guard evaluation and can effectively utilize the `CompositeGuard` class in their applications.