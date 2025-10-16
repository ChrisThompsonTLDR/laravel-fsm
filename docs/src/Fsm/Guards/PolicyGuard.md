# Documentation: PolicyGuard.php

Original file: `src/Fsm/Guards/PolicyGuard.php`

# PolicyGuard Documentation

## Table of Contents
- [Introduction](#introduction)
- [Methods](#methods)
  - [check](#check)
  - [canTransition](#cantransition)
  - [canTransitionFrom](#cantransitionfrom)
  - [canTransitionTo](#cantransitionto)

## Introduction
The `PolicyGuard.php` file defines the `PolicyGuard` class, which serves as a policy-based guard for state transitions in a finite state machine (FSM) integrated with Laravel's authorization system. This class enhances access control by utilizing Laravel's authorization gates to determine if authenticated users have the appropriate permissions to execute specific state transitions. It provides a clean and reusable way to enforce authorization rules in the context of FSM operations.

## Methods

### check
```php
public function check(
    TransitionInput $input,
    string $ability,
    ?Authenticatable $user = null,
    array $parameters = []
): bool
```

#### Purpose
The `check` method verifies whether a user is authorized to perform a specific state transition based on a defined policy ability.

#### Parameters
| Parameter      | Type                        | Description                                                  |
|----------------|-----------------------------|--------------------------------------------------------------|
| `$input`       | `TransitionInput`           | The transition input data containing the model and states.  |
| `$ability`     | `string`                    | The policy ability to check (e.g., 'update', 'cancel').     |
| `$user`        | `Authenticatable|null`      | The user instance or null for the current authenticated user.|
| `$parameters`  | `array<string, mixed>`      | Additional parameters to pass to the policy.                 |

#### Return Value
- **bool**: Returns `true` if the user is authorized to perform the specified transition; otherwise, returns `false`.

#### Functionality
The `check` method retrieves the current authenticated user if none is provided. It then merges the input model and additional parameters, using Laravel's `Gate` to determine if the user has the required ability to execute the transition against the model.

### canTransition
```php
public function canTransition(
    TransitionInput $input,
    ?Authenticatable $user = null,
    array $parameters = []
): bool
```

#### Purpose
This method checks if the authenticated user can perform the transition operation defined in the input.

#### Parameters
| Parameter      | Type                        | Description                                                  |
|----------------|-----------------------------|--------------------------------------------------------------|
| `$input`       | `TransitionInput`           | The transition input data containing the model and event.   |
| `$user`        | `Authenticatable|null`      | The user instance or null for the current authenticated user.|
| `$parameters`  | `array<string, mixed>`      | Additional parameters to pass to the policy.                 |

#### Return Value
- **bool**: Returns `true` if the user can perform the specified transition; otherwise, returns `false`.

#### Functionality
`canTransition` deduces the event from the `TransitionInput`, defaulting to `'transition'` if not specified. It then calls the `check` method to evaluate permissions for that event, under the premise that each event corresponds to an ability defined in the policy.

### canTransitionFrom
```php
public function canTransitionFrom(
    TransitionInput $input,
    ?Authenticatable $user = null,
    array $parameters = []
): bool
```

#### Purpose
Checks if a user can transition from the current state defined in the transition input.

#### Parameters
| Parameter      | Type                        | Description                                                  |
|----------------|-----------------------------|--------------------------------------------------------------|
| `$input`       | `TransitionInput`           | The transition input data representing the current state.   |
| `$user`        | `Authenticatable|null`      | The user instance or null for the current authenticated user.|
| `$parameters`  | `array<string, mixed>`      | Additional parameters to pass to the policy.                 |

#### Return Value
- **bool**: Returns `true` if transitioning from the current state is allowed for the user; otherwise, returns `false`.

#### Functionality
In `canTransitionFrom`, the method extracts the current state from the `TransitionInput` and generates the corresponding ability name (e.g., `transitionFromActive`). It then uses the `check` method to determine if the authenticated user can perform the transition from this state.

### canTransitionTo
```php
public function canTransitionTo(
    TransitionInput $input,
    ?Authenticatable $user = null,
    array $parameters = []
): bool
```

#### Purpose
Validates if a user can transition to a target state as specified in the transition input.

#### Parameters
| Parameter      | Type                        | Description                                                  |
|----------------|-----------------------------|--------------------------------------------------------------|
| `$input`       | `TransitionInput`           | The transition input data containing the target state.      |
| `$user`        | `Authenticatable|null`      | The user instance or null for the current authenticated user.|
| `$parameters`  | `array<string, mixed>`      | Additional parameters to pass to the policy.                 |

#### Return Value
- **bool**: Returns `true` if the user can perform a transition to the target state; otherwise, returns `false`.

#### Functionality
`canTransitionTo` derives the ability name for the target state (e.g., `transitionToCompleted`) based on the `TransitionInput`, similar to `canTransitionFrom`. This method checks whether the user has the ability to transition to the target state, leveraging the `check` method for authorization verification.

---

By utilizing the `PolicyGuard` class, developers can seamlessly integrate state transition controls with Laravel's authorization system, leading to improved security and maintainability within FSMs in their applications.