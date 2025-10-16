# Documentation: BootstrapDetector.php

Original file: `src/Fsm/Services/BootstrapDetector.php`

# BootstrapDetector Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
- [Constructor](#constructor)
- [Methods](#methods)
  - [inBootstrapMode](#inbootstrapmode)
  - [isRunningDiscoveryCommand](#isrunningdiscoverycommand)
  - [isDatabaseUnavailable](#isdatabaseunavailable)
  - [areEssentialServicesUnavailable](#areessentialservicesunavailable)

## Introduction
The `BootstrapDetector` class is responsible for discerning the current operational mode of a Laravel application. Specifically, it determines whether the application is running in "bootstrap mode" or "package discovery mode." This is essential in scenarios where certain services, such as database access, must be avoided to ensure proper functioning of the application initialization and command line interface operations. This class helps to avoid potential errors in situations where the application's full service container is not available.

## Class Properties
| Property | Type            | Description                           |
|----------|-----------------|---------------------------------------|
| `$app`   | `Application`   | The Laravel application instance injected via constructor. |

## Constructor
```php
public function __construct(Application $app)
```
### Purpose
The constructor initializes the `BootstrapDetector` with an instance of the Laravel application.

### Parameters
- `Application $app`: An instance of the Laravel `Application` contract, which provides access to various application services.

### Return Values
- This constructor does not return any value.

### Functionality
The constructor assigns the application instance to the private property `$app`, enabling the class to access Laravel's service container and other application functionalities.

## Methods

### inBootstrapMode
```php
public function inBootstrapMode(): bool
```
#### Purpose
Determines whether the application is in bootstrap mode, where certain operations need to be restricted, especially those involving database access and service availability.

#### Parameters
- None.

#### Return Values
- `bool`: Returns `true` if the application is in bootstrap mode; otherwise, returns `false`.

#### Functionality
This method checks several conditions:
1. It verifies if the application is currently executing a package discovery command.
2. It checks if the database service is available.
3. It assesses the availability of essential services such as configuration management.

If any of these checks indicate that the application is not fully initialized, the method returns `true`.

### isRunningDiscoveryCommand
```php
private function isRunningDiscoveryCommand(): bool
```
#### Purpose
Checks if the application is currently processing a command related to package discovery.

#### Parameters
- None.

#### Return Values
- `bool`: `true` if a discovery command is being executed; otherwise, `false`.

#### Functionality
The method first checks if the application is running in console mode. If not, it returns false. It then retrieves the command-line arguments to identify if the current command is in the list of known discovery commands defined by `Constants::SKIP_DISCOVERY_COMMANDS`. Exact matching is employed to ensure precision.

### isDatabaseUnavailable
```php
private function isDatabaseUnavailable(): bool
```
#### Purpose
Determines if the database connection is unavailable or inaccessible.

#### Parameters
- None.

#### Return Values
- `bool`: Returns `true` if the database connection cannot be accessed; otherwise, returns `false`.

#### Functionality
The method attempts the following actions:
1. It checks if the `db` service is bound in the application container.
2. It tries to access the database connection by obtaining a PDO instance.

If any part of this process fails (e.g., if the database is down or misconfigured), it catches the exception and returns `true`, signifying that the database is unavailable.

### areEssentialServicesUnavailable
```php
private function areEssentialServicesUnavailable(): bool
```
#### Purpose
Checks if key Laravel services (like application configuration) are unavailable.

#### Parameters
- None.

#### Return Values
- `bool`: Returns `true` if essential services are unavailable; otherwise, returns `false`.

#### Functionality
This method assesses the availability of critical functions and services:
1. It checks the existence of fundamental functions `app()` and `config()`.
2. It verifies if the `config` service is bound in the application container.

If any checks fail, it catches any exceptions and indicates that essential services are not operational by returning `true`.

## Conclusion
The `BootstrapDetector` class plays a crucial role in ensuring that a Laravel application appropriately manages its operational modes, particularly during command line operations. By distinguishing between bootstrap and discovery modes, it helps prevent errors and maintains application stability. Understanding its methods and conditions can greatly aid in debugging and developing robust Laravel applications.