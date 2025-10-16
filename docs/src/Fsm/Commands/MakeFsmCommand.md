# Documentation: MakeFsmCommand.php

Original file: `src/Fsm/Commands/MakeFsmCommand.php`

# MakeFsmCommand Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
- [Methods](#methods)
  - [getStub](#getstub)
  - [resolveStubPath](#resolvestubpath)
  - [getDefaultNamespace](#getdefaultnamespace)
  - [getNameInput](#getnameinput)
  - [getEnumNameInput](#getenumnameinput)
  - [getTestNameInput](#gettestnameinput)
  - [getFsmColumnName](#getfsmcolumnname)
  - [getModelDetails](#getmodeldetails)
  - [buildClass](#buildclass)
  - [replaceDefinitionPlaceholders](#replacedefinitionplaceholders)
  - [handle](#handle)
  - [generateEnum](#generateenum)
  - [getEnumBaseName](#getenumbasename)
  - [qualifyEnumClass](#qualifyenumclass)
  - [generateTest](#generatetest)
  - [getTestBaseName](#gettestbasename)
  - [getArguments](#getarguments)
  - [rootNamespace](#rootnamespace)

## Introduction
The `MakeFsmCommand` class is a custom Artisan command in a Laravel application that facilitates the creation of a new Finite State Machine (FSM) definition, state enum, and associated feature test for a given model. This command automates the process of generating boilerplate code, ensuring that developers can quickly establish FSM functionality without having to manually write the necessary class definitions and test stubs.

## Class Properties
| Property                         | Type   | Description                                                   |
|----------------------------------|--------|---------------------------------------------------------------|
| `$name`                          | string | The name of the command, used to register the command in Laravel. |
| `$description`                   | string | A short description of the command that gets displayed when the command list is viewed. |
| `$type`                          | string | The type of class being generated (in this case, 'FSM Definition'). |

## Methods

### getStub
```php
protected function getStub(): string
```
**Purpose:**  
Returns the path to the stub file that will be used as a template for generating the FSM definition.

**Return Value:**  
- Returns the fully resolved path of the `fsm.definition.stub`.

**Functionality:**  
This method calls `resolveStubPath` to get the correct path to the FSM definition stub, which is then used to create the FSM class files.

---

### resolveStubPath
```php
protected function resolveStubPath(string $stub): string
```
**Purpose:**  
Resolves and returns the file path for a specified stub.

**Parameters:**  
- `string $stub`: The filename of the stub.

**Return Value:**  
- Returns a string representing the path to the stub file.

**Functionality:**  
Combines the current directory path with the provided stub filename to create the full file path. This is where the stub files for generating FSM-related code are located.

---

### getDefaultNamespace
```php
protected function getDefaultNamespace($rootNamespace): string
```
**Purpose:**  
Determines the default namespace for the FSM definition class.

**Parameters:**  
- `string $rootNamespace`: The root namespace of the application.

**Return Value:**  
- Returns the string representing the default namespace for FSM classes (e.g., `App\Fsm`).

**Functionality:**  
This method ensures that the generated FSM definitions are placed within the appropriate namespace structure in the Laravel application.

---

### getNameInput
```php
protected function getNameInput(): string
```
**Purpose:**  
Retrieves and formats the FSM name from user input.

**Return Value:**  
- Returns a formatted string representing the FSM class name derived from the user-supplied name argument.

**Functionality:**  
Validates the input argument for the FSM name, ensuring it is a string. It then transforms the name into a studly case and appends 'Fsm' to it for use in class creation.

---

### getEnumNameInput
```php
protected function getEnumNameInput(): string
```
**Purpose:**  
Retrieves the desired enum class name for the FSM.

**Return Value:**  
- Returns a formatted string representing the enum class name derived from the user input.

**Functionality:**  
Similar to `getNameInput`, it validates and formats the FSM name to generate the corresponding enum class name. 

---

### getTestNameInput
```php
protected function getTestNameInput(): string
```
**Purpose:**  
Retrieves and formats the name of the feature test class.

**Return Value:**  
- Returns a string that represents the feature test class name formatted by the user input.

**Functionality:**  
Validates the input argument for the name and transforms it into a studly case format. This ensures that the generated test class follows the naming conventions established by the developer.

---

### getFsmColumnName
```php
protected function getFsmColumnName(): string
```
**Purpose:**  
Generates a column name in snake case for the FSM based on the user input.

**Return Value:**  
- Returns a string representing the FSM column name formatted to snake case.

**Functionality:**  
The method takes the user input, checks its validity, and converts it to a snake_case representation, which is commonly used for database column names in Laravel.

---

### getModelDetails
```php
protected function getModelDetails(): array
```
**Purpose:**  
Retrieves the fully qualified name and base name of the model specified by the user.

**Return Value:**  
- Returns an array containing two strings: the fully qualified model class name and its base name.

**Functionality:**  
This method validates the user input for the model, constructs the fully qualified name if the input is relative, and checks if the class exists. It returns the model's details needed for generating FSMs and related artifacts.

---

### buildClass
```php
protected function buildClass($name): string
```
**Purpose:**  
Builds the class definition for the FSM based on the specified name.

**Parameters:**  
- `string $name`: The name of the FSM class to be generated.

**Return Value:**  
- Returns a string containing the generated FSM class definition.

**Functionality:**  
This method retrieves the stub content, replaces namespace and class placeholders, and adds specific definitions related to the FSM, such as model dependencies and methods.

---

### replaceDefinitionPlaceholders
```php
protected function replaceDefinitionPlaceholders(string &$stub, string $modelFqn, string $modelClass): self
```
**Purpose:**  
Replaces placeholders in the FSM stub with actual values before saving.

**Parameters:**  
- `string &$stub`: A reference to the stub string to modify.
- `string $modelFqn`: The fully qualified name of the model.
- `string $modelClass`: The base name of the model.

**Return Value:**  
- Returns `$this`, allowing for method chaining.

**Functionality:**  
Replaces various placeholders in the stub with actual namespace and class names, which will be utilized in the generated FSM class.

---

### handle
```