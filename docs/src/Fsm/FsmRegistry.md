# Documentation: FsmRegistry.php

Original file: `src/Fsm/FsmRegistry.php`

# FsmRegistry Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
- [Methods](#methods)
  - [__construct](#__construct)
  - [getDefinition](#getdefinition)
  - [getDefinitionsForModel](#getdefinitionsformodel)
  - [compileFsmDefinitions](#compilefsmdefinitions)
  - [getDefaultDiscoveryPaths](#getdefaultdiscoverypaths)
  - [loadFromCache](#loadfromcache)
  - [writeCache](#writecache)
  - [clearCache](#clearcache)
  - [discoverDefinitions](#discoverdefinitions)
  - [applyRuntimeExtensions](#applyruntimeextensions)
  - [registerDefinition](#registerdefinition)
- [Logging and Debugging](#logging-and-debugging)

## Introduction
The `FsmRegistry` class is an essential component of the Finite State Machine (FSM) implementation within the Laravel application. It serves as a registry to manage FSM runtime definitions associated with models and their respective columns, ensuring that the definitions are discovered, compiled, cached, and retrieved efficiently. This class handles the complexities of loading definitions from both configuration files and the cache, while also providing mechanisms for debugging and logging actions performed during these operations.

## Class Properties

| Property                | Type                                                | Description                                            |
|------------------------|-----------------------------------------------------|--------------------------------------------------------|
| `compiledDefinitions`  | `array<string, array<string, FsmRuntimeDefinition>>`| Stores compiled FSM runtime definitions, keyed by model class and column name. |
| `compiled`             | `bool`                                             | Indicates whether FSMs have been discovered and compiled. |
| `cacheLoaded`          | `bool`                                             | Tracks whether a cache loading attempt has been made. |

## Methods

### __construct
```php
public function __construct(
    private readonly BootstrapDetector $bootstrapDetector,
    private readonly ConfigRepository $config
)
```
**Purpose:** Initializes a new instance of the `FsmRegistry` class with dependencies required for its operations.

**Parameters:**
- `BootstrapDetector $bootstrapDetector`: An instance that detects if the application is in bootstrap mode.
- `ConfigRepository $config`: An instance of the configuration repository, used for accessing configuration settings.

**Functionality:** This constructor sets up the `FsmRegistry`, making it ready to manage FSM definitions based on configured paths and bootstrap state.

### getDefinition
```php
public function getDefinition(string $modelClass, string $columnName): ?FsmRuntimeDefinition
```
**Purpose:** Retrieves a specific compiled FSM definition for the given model class and column name.

**Parameters:**
- `string $modelClass`: The fully qualified name of the model class.
- `string $columnName`: The name of the column associated with the FSM.

**Returns:** `FsmRuntimeDefinition|null`: The compiled FSM definition or `null` if it doesn't exist.

**Functionality:** This method checks if the FSM definitions have already been compiled. If not, it attempts to load them from the cache or compiles new definitions. It finally returns the definition for the specified model and column.

### getDefinitionsForModel
```php
public function getDefinitionsForModel(string $modelClass): array
```
**Purpose:** Retrieves all compiled FSM definitions for a given model.

**Parameters:**
- `string $modelClass`: The fully qualified name of the model class.

**Returns:** `array<string, FsmRuntimeDefinition>`: An array of compiled FSM definitions for the model, indexed by column name.

**Functionality:** Similar to `getDefinition`, it ensures that definitions are compiled and returns all definitions associated with the specified model class.

### compileFsmDefinitions
```php
private function compileFsmDefinitions(): void
```
**Purpose:** Discovers all FSM definition implementations, builds them, and stores the runtime definitions.

**Functionality:** This method handles the complete process of:
- Checking if compilation is necessary (aborts if already compiled or in bootstrap mode).
- Managing configuration paths for discovery.
- Discovering FSM definitions using `DefinitionDiscoverer`.
- Compiling definitions into readable format and potentially writing them to the cache.
- Applying any runtime extensions if enabled by configuration.

### getDefaultDiscoveryPaths
```php
private function getDefaultDiscoveryPaths(): array
```
**Purpose:** Safely retrieves default discovery paths for FSM definitions.

**Returns:** `array<string>`: An array of paths to look for FSM definitions.

**Functionality:** This method checks for the existence of required system functions and safely retrieves the default application path to FSM definitions while handling potential exceptions.

### loadFromCache
```php
private function loadFromCache(): void
```
**Purpose:** Attempts to load compiled FSM definitions from the cache.

**Functionality:** This method checks if caching is enabled and if cache data has already been loaded. If not, it reads from the specified cache file and unserializes the definitions, marking them as compiled if successful.

### writeCache
```php
private function writeCache(): void
```
**Purpose:** Writes the compiled FSM definitions to cache.

**Functionality:** This method serializes the compiled definitions and attempts to write them to the specified cache file while creating necessary directories if they do not exist. It logs any failures during this operation for debugging purposes.

### clearCache
```php
public function clearCache(): void
```
**Purpose:** Removes the cached definitions file.

**Functionality:** This method checks if a cache file exists and attempts to delete it, ensuring that any existing cache can be cleared.

### discoverDefinitions
```php
public function discoverDefinitions(): void
```
**Purpose:** Forces discovery and compilation of FSM definitions.

**Functionality:** This method calls upon `compileFsmDefinitions` to discover and register FSM definitions, bypassing any cache.

### applyRuntimeExtensions
```php
private function applyRuntimeExtensions(): void
```
**Purpose:** Applies runtime extensions to all discovered FSM definitions.

**Functionality:** This method attempts to retrieve the `FsmExtensionRegistry` and applies potential extensions to the definitions. It handles any exceptions that occur during this process without interrupting the definition compilation.

### registerDefinition
```php
public function registerDefinition(string $modelClass, string $columnName, FsmRuntimeDefinition $definition): void
```
**Purpose:** Manually registers or overrides a compiled FSM definition.

**Parameters:**
- `string $modelClass`: The fully qualified name of the model class.
- `string $columnName`: The name of the column associated with the FSM.
- `FsmRuntimeDefinition $definition`: The FSM runtime definition to register.

**Functionality:** This method allows the user to manually register a definition for a model and column, which will update the internal collection of compiled definitions.

## Logging and Debugging
The `FsmRegistry` class includes a private method `debug` for logging debug messages, activated through configuration. The debug messages assist in monitoring the execution of critical methods and the integrity of computed paths and definitions throughout the discovery and compilation processes. When the `fsm.debug` configuration option is set to `true`, relevant debug information is logged using Laravel's logging facilities.

This comprehensive documentation aims to provide an understanding of the `FsmRegistry` class, its