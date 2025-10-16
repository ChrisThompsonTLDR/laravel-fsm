# Documentation: FsmDiagramCommand.php

Original file: `src/Fsm/Commands/FsmDiagramCommand.php`

# FsmDiagramCommand Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Methods](#methods)
  - [handle](#handle)
  - [stateName](#statename)
  - [buildTransitionEdges](#buildtransitionedges)
  - [toPlantUml](#toplantuml)
  - [toDot](#todot)

## Introduction
The `FsmDiagramCommand` class is a console command in the Laravel framework designed to generate visual representations (diagrams) of Finite State Machines (FSMs). This class provides functionality for exporting FSMs to either PlantUML or DOT formats, which are useful for creating diagrams in a variety of tools and environments. The command takes an optional output directory where the diagrams will be stored and a format option to specify which type of diagram to generate.

## Class Overview
```php
namespace Fsm\Commands;

use Illuminate\Console\Command;
```
The `FsmDiagramCommand` extends the `Illuminate\Console\Command` class, allowing it to be executed through the Laravel Artisan CLI. It includes methods to handle user input, generate diagrams, and manage file operations.

## Methods

### handle
```php
public function handle(): int
```
#### Purpose
The `handle` method is the main entry point for the command, invoked when the command is executed. It manages user inputs, generates diagrams for the registered FSMs, and saves them to the specified output directory.

#### Parameters
- None

#### Return Values
- `int`: Returns `self::SUCCESS` if the command completes successfully, or `self::FAILURE` if an error occurs.

#### Functionality
- Retrieves the output directory argument and format option.
- Validates the format input to ensure it is either "plantuml" or "dot".
- Checks if the output directory exists; if not, it creates it.
- Uses PHP Reflection to access the private property `compiledDefinitions` within the `FsmRegistry` to gather all FSM definitions.
- Iterates through each FSM definition and generates a corresponding diagram in the specified format, saving it to the output directory while providing feedback to the user.

### stateName
```php
private function stateName(FsmStateEnum|string|null $state): ?string
```
#### Purpose
Converts an FSM state (either an instance of `FsmStateEnum`, a string, or null) to its string representation.

#### Parameters
- `$state` (`FsmStateEnum|string|null`): The state to be converted.

#### Return Values
- `string|null`: Returns the state as a string, or null if the input state is null.

#### Functionality
This utility function handles multiple types of state inputs to standardize state names for diagram generation. If the state is an instance of `FsmStateEnum`, it retrieves the `value` property; otherwise, it casts the state to a string.

### buildTransitionEdges
```php
private function buildTransitionEdges(FsmRuntimeDefinition $definition, callable $formatter): array
```
#### Purpose
Constructs the transition edges for the FSM diagram based on the defined transitions in the FSM runtime definition.

#### Parameters
- `$definition` (`FsmRuntimeDefinition`): The definition of the FSM containing transitions to be represented.
- `$formatter` (`callable`): A function that formats the output for each transition.

#### Return Values
- `array<string>`: An array of strings, each representing a transition edge for the FSM diagram.

#### Functionality
Iterates through the transitions in the provided FSM definition. For each transition, it uses the provided formatter function to build a string representation of the edge, which consists of the states involved along with the associated event label.

### toPlantUml
```php
private function toPlantUml(FsmRuntimeDefinition $definition): string
```
#### Purpose
Generates a PlantUML representation of the FSM based on the provided `FsmRuntimeDefinition`.

#### Parameters
- `$definition` (`FsmRuntimeDefinition`): The definition of the FSM to be converted to PlantUML format.

#### Return Values
- `string`: A PlantUML string that describes the FSM.

#### Functionality
- Initializes a PlantUML diagram with `@startuml` and populates it with edges representing the FSM transitions.
- Uses the `buildTransitionEdges` method to generate the transition lines and merges these lines to create a complete representation.
- Concludes the diagram with `@enduml`.

### toDot
```php
private function toDot(FsmRuntimeDefinition $definition): string
```
#### Purpose
Generates a DOT representation of the FSM based on the provided `FsmRuntimeDefinition`.

#### Parameters
- `$definition` (`FsmRuntimeDefinition`): The definition of the FSM to be converted to DOT format.

#### Return Values
- `string`: A DOT format string that describes the FSM.

#### Functionality
- Initializes a DOT graph with a header and specifies the ranking direction.
- Uses the `buildTransitionEdges` method to formulate the transition edges for the FSM and appends them to the graph.
- Forwards the constructed lines to create a complete DOT representation, concluding with the closing brace.

## Conclusion
The `FsmDiagramCommand` is an important utility for generating visual representations of finite state machines in various formats. By leveraging the Laravel console command framework, it provides an accessible way for developers to visualize FSMs, which can facilitate better understanding and documentation of application state transitions. This class is crucial for maintaining clarity and enhancing the development process around FSMs in the application.