# Documentation: PublishModularConfigCommand.php

Original file: `src/Fsm/Commands/PublishModularConfigCommand.php`

# PublishModularConfigCommand Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Definition](#class-definition)
- [Properties](#properties)
- [Methods](#methods)
  - [handle](#handle)
  - [publishConfig](#publishconfig)
  - [publishExamples](#publishexamples)
  - [getModularConfigStub](#getmodularconfigstub)
  - [getExampleExtensionStub](#getexampleextensionstub)
  - [getExampleStateDefinitionStub](#getexamplestatedefinitionstub)
  - [getExampleTransitionDefinitionStub](#getexampletransitiondefinitionstub)

## Introduction
The `PublishModularConfigCommand` class is a console command within a PHP application that utilizes the Laravel framework. Its primary purpose is to publish modular configuration files and example extension classes for a finite state machine (FSM) system. This class allows developers to define how FSM configurations can be modified without altering the base definitions, promoting modular development practices.

## Class Definition
```php
namespace Fsm\Commands;

use Illuminate\Console\Command;
```

The class extends Laravel's base `Command` class to benefit from built-in console functionalities.

## Properties

| Property Name   | Type   | Description                                                        |
|------------------|--------|--------------------------------------------------------------------|
| `$signature`     | string | Defines the command's name, options, and parameters.             |
| `$description`   | string | Provides a brief description of what the command does.            |

## Methods

### handle
```php
public function handle(): int
```
The `handle` method is the entry point when the command is executed. This method processes user options for publishing configurations and examples, calling the appropriate methods based on user input.

- **Parameters:** None
- **Returns:** `int` - The exit status code indicating the outcome of command execution.
- **Functionality:**
  - Checks if the user specified options (`--config`, `--examples`, `--all`).
  - Prompts the user if no valid options are given.
  - Calls either `publishConfig` or `publishExamples` based on the selected options.

### publishConfig
```php
private function publishConfig(): void
```
This method publishes the FSM modular configuration file.

- **Parameters:** None
- **Returns:** `void`
- **Functionality:**
  - Defines the path for the configuration file.
  - Warns if the configuration file already exists and prompts for confirmation to overwrite it.
  - Writes the configuration stub content to the designated file path.

### publishExamples
```php
private function publishExamples(): void
```
This method publishes example extension classes for the FSM system.

- **Parameters:** None
- **Returns:** `void`
- **Functionality:**
  - Checks if the target directory for examples exists and attempts to create it if it doesn't.
  - Iterates through a predefined list of example files, checking for existing files and confirming overwrites.
  - Writes each example file's contents to the specified directory.

### getModularConfigStub
```php
private function getModularConfigStub(): string
```
Returns the contents of the modular configuration stub.

- **Parameters:** None
- **Returns:** `string` - The configuration stub template.
- **Functionality:**
  - Provides a template configuration that can be used to set up FSM extensions and state transitions.

### getExampleExtensionStub
```php
private function getExampleExtensionStub(): string
```
Returns the example FSM extension class stub.

- **Parameters:** None
- **Returns:** `string` - The example extension class template.
- **Functionality:**
  - Outputs a class template for an FSM extension that defines custom states and transitions.

### getExampleStateDefinitionStub
```php
private function getExampleStateDefinitionStub(): string
```
Returns the example state definition stub.

- **Parameters:** None
- **Returns:** `string` - The example state definition class template.
- **Functionality:**
  - Provides a template for a state definition that can be used in modular FSM implementations.

### getExampleTransitionDefinitionStub
```php
private function getExampleTransitionDefinitionStub(): string
```
Returns the example transition definition stub.

- **Parameters:** None
- **Returns:** `string` - The example transition definition class template.
- **Functionality:**
  - Outputs a class template that defines how transitions between states are managed within the FSM.

This command facilitates modular configuration publishing, which is essential for developers extending or integrating a finite state machine in their applications. The provided stubs and configurations help maintain a clean separation of logic while also providing examples for developers to follow.