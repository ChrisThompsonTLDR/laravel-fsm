# Documentation: DefinitionDiscoverer.php

Original file: `src/Fsm/Support/DefinitionDiscoverer.php`

# DefinitionDiscoverer Documentation

## Table of Contents
- [Introduction](#introduction)
- [Method: discover](#method-discover)

## Introduction
The `DefinitionDiscoverer` class is a critical component of the FSM (Finite State Machine) package within the Laravel framework. Its primary role is to facilitate the discovery of FSM definitions within a given set of paths. The class utilizes Composer's ClassMapGenerator to dynamically locate and load classes that implement the `FsmDefinition` interface. By ensuring that only valid, concrete definitions are returned, it gathers all necessary FSM definitions for proper operation of the FSM system.

## Method: discover

### Purpose
The `discover` method searches for classes within the specified paths that implement the `FsmDefinition` interface and are not abstract. It returns a unique list of these class names.

### Parameters
- `array<int, string> $paths`: An array of directory paths to search for FSM definition classes. Each path should point to a valid directory.

### Return Values
- `array<int, class-string<FsmDefinition>>`: An array of unique class names that extend `FsmDefinition`. The classes returned are guaranteed to be concrete (non-abstract).

### Functionality
The method processes the provided paths as follows:
1. **Initialization**: An empty array named `$definitions` is initialized to hold the found class names.
2. **Directory Validation**: For each provided path, it checks if the path is a valid directory using `is_dir()`. If it's not a directory, the method skips to the next path.
3. **Class Map Generation**: For valid directories, it generates a class map using `ClassMapGenerator::createMap($path)`, which returns an associative array mapping class names to their corresponding file paths.
4. **Class Loading and Validation**:
   - It iterates through the class map, attempting to include each class file using `require_once`. It checks if the class has already been loaded using `class_exists()`.
   - Each class is verified to be a subclass of `FsmDefinition` and ensures it is not abstract by using `ReflectionClass`. If both criteria are met, the class is added to the `$definitions` array.
5. **Error Handling**: The method encompasses a `try-catch` block that handles any errors or exceptions that may arise during the class loading process (e.g., syntax errors, missing dependencies). In such cases, it simply continues to the next class.
6. **Unique Values**: Finally, the method returns a cleaned-up array of class names using `array_unique()` and `array_values()`, which eliminates any duplicates and re-indexes the array numerically.

```php
/**
 * Discovers FSM definition classes from the provided paths.
 *
 * @param  array<int, string>  $paths
 * @return array<int, class-string<FsmDefinition>>
 */
public static function discover(array $paths): array {
    ...
}
```

### Example Usage
```php
$paths = ['/path/to/first/directory', '/path/to/second/directory'];
$definitions = DefinitionDiscoverer::discover($paths);
```
In this example, the `discover` method will scan the given directories for FSM definition classes and return a list of the discovered classes.

By detailing the functionality of the `discover` method, developers will understand how the `DefinitionDiscoverer` class contributes to the overall architecture of the FSM package, ensuring robust and dynamic loading of FSM definitions at runtime.