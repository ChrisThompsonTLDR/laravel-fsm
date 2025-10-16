# Documentation: HierarchicalStateDefinition.php

Original file: `src/Fsm/Data/HierarchicalStateDefinition.php`

# HierarchicalStateDefinition Documentation

## Table of Contents
- [Introduction](#introduction)
- [Constructor](#constructor)

## Introduction
The `HierarchicalStateDefinition` class extends the functionality of the `StateDefinition` class within the finite state machine (FSM) framework. This class is designed to support nested state machines, enabling complex hierarchical state representations. This capability is crucial for applications that require a structured approach to managing state transitions in various contexts, exemplified by user interfaces, workflows, and complex data processing.

By allowing the inclusion of child state machines, this class enriches the FSM implementation with the ability to encapsulate related states and transitions, promoting reusability and separation of concerns.

## Constructor

```php
public function __construct(
    FsmStateEnum|string $name,
    array|Collection $onEntryCallbacks = [],
    array|Collection $onExitCallbacks = [],
    ?string $description = null,
    string $type = self::TYPE_INTERMEDIATE,
    ?string $category = null,
    string $behavior = self::BEHAVIOR_PERSISTENT,
    array $metadata = [],
    bool $isTerminal = false,
    int $priority = 50,
    public readonly ?FsmRuntimeDefinition $childStateMachine = null,
    public readonly ?string $parentState = null,
)
```

### Purpose
The constructor initializes a new instance of the `HierarchicalStateDefinition` class, setting the parameters that define the state, its transitions, and any hierarchical relationships.

### Parameters

| Parameter                | Type                              | Description                                                                                     |
|--------------------------|-----------------------------------|-------------------------------------------------------------------------------------------------|
| `$name`                  | `FsmStateEnum|string`             | The name of the state, which can be an enumeration or a string identifier.                     |
| `$onEntryCallbacks`      | `array<int, TransitionCallback>|Collection<int, TransitionCallback>` | Callbacks executed when the state is entered. Defaults to an empty array or collection.       |
| `$onExitCallbacks`       | `array<int, TransitionCallback>|Collection<int, TransitionCallback>` | Callbacks executed when the state is exited. Defaults to an empty array or collection.        |
| `$description`           | `?string`                         | An optional description of the state.                                                          |
| `$type`                  | `string`                          | The type of the state. Defaults to `self::TYPE_INTERMEDIATE`.                                  |
| `$category`              | `?string`                        | An optional category for grouping states.                                                      |
| `$behavior`              | `string`                         | Defines the behavior of the state. Defaults to `self::BEHAVIOR_PERSISTENT`.                    |
| `$metadata`              | `array<string, mixed>`            | Additional metadata associated with the state.                                                |
| `$isTerminal`            | `bool`                           | Indicates whether the state is terminal (final) or not. Defaults to `false`.                  |
| `$priority`              | `int`                            | The priority of the state, which determines the order of processing. Defaults to `50`.         |
| `$childStateMachine`     | `?FsmRuntimeDefinition`           | An optional child state machine encapsulated within this state.                               |
| `$parentState`           | `?string`                         | An optional reference to the parent state, forming a hierarchical relationship.               |

### Functionality
The constructor sets the initial state properties and establishes relationships with potential child and parent states. Upon calling the constructor, the following actions are performed:
1. It invokes the parent class's constructor, passing along parameters related to the state setup.
2. Initializes the additional properties specific to hierarchical state management, including any defined child state machines and relationships to parent states.

This structure not only organizes state-related information but also effectively manages complex state transition scenarios, making it particularly useful for developers building dynamic applications that rely on an FSM framework. 

### Example Usage
```php
use Fsm\Data\HierarchicalStateDefinition;
use Fsm\Contracts\MyStateEnum;

$state = new HierarchicalStateDefinition(
    name: MyStateEnum::INITIAL,
    onEntryCallbacks: [$this->handleInitialEntry()],
    onExitCallbacks: [$this->handleInitialExit()],
    description: 'The initial state of the workflow.',
    childStateMachine: $childStateDefinition, // Assuming $childStateDefinition is defined
    parentState: null  // This is a root state
);
```

### Conclusion
The `HierarchicalStateDefinition` class serves as a foundational component in building complex state machines that require organized and manageable state hierarchies. By leveraging this class, developers can create robust applications that effectively handle various states and transitions, enhancing the overall software architecture.