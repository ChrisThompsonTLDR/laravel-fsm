# Documentation: FsmCacheCommand.php

Original file: `src/Fsm/Commands/FsmCacheCommand.php`

# FsmCacheCommand Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Method: handle](#method-handle)

## Introduction
The `FsmCacheCommand` class is a console command defined within the Laravel application, specifically for managing cached Finite State Machine (FSM) definitions. It is part of the larger FSM mechanism, which stores and manages the state transitions of entities within the Laravel framework. This command allows developers to clear or rebuild the cache that stores FSM definitions, ensuring that they can manage state transitions effectively and maintain cache consistency.

## Class Overview
The `FsmCacheCommand` class extends the `Illuminate\Console\Command` class, inheriting functionality for creating console commands within a Laravel application. This class defines two main options for its command: clearing the cache and rebuilding it, making it a crucial tool for developers working with FSM in the application.

### Properties
- **$signature (string)**: Defines the command signature, including its options.
- **$description (string)**: Provides a brief description of the command's purpose.

## Method: handle
The `handle` method is the core function of the `FsmCacheCommand` class. It executes the logic based on the options provided when the command is called. 

### Purpose
The `handle` method is responsible for either clearing the FSM cache or rebuilding it and caching the FSM definitions again.

### Parameters
- **FsmRegistry $registry**: An instance of the `FsmRegistry` used to interact with FSM definitions and their respective cache.
- **ConfigRepository $config**: An instance of the configuration repository that retrieves configuration values, specifically for the FSM cache path.

### Return Values
- Returns an integer status code: `self::SUCCESS` (0) if the operation completes successfully.

### Functionality
1. **Path Retrieval**: The method retrieves the cache path from the configuration, falling back to a default storage path if not defined:
   ```php
   $path = $config->get('fsm.cache.path', storage_path('framework/cache/fsm.php'));
   ```

2. **Clearing the Cache**: If the `--clear` option is provided:
   - The method checks if the specified cache file exists.
   - If it does, it attempts to remove the file using `unlink`, and it informs the user whether the cache was cleared or if no cache file was found:
   ```php
   if (is_file($path)) {
       @unlink($path);
       $this->info('FSM cache cleared.');
   } else {
       $this->info('No FSM cache file found.');
   }
   ```

3. **Rebuilding the Cache**: If the `--rebuild` option is specified:
   - It clears any existing cache using the `clearCache` method of the `FsmRegistry`.
   - Then, it calls the `discoverDefinitions` method to cache the FSM definitions anew, providing feedback to the user upon success:
   ```php
   $registry->clearCache();
   $registry->discoverDefinitions();
   $this->info('FSM definitions cached successfully.');
   ```

4. **Return Status**: The method concludes by returning `self::SUCCESS`, indicating the command completed without issues.

```php
public function handle(FsmRegistry $registry, ConfigRepository $config): int
{
    // Method implementation ...
}
```

### Console Command Signature
The command can be used in the console with the following signature:
```bash
php artisan fsm:cache --clear
php artisan fsm:cache --rebuild
```

### Conclusion
The `FsmCacheCommand` is a vital part of managing FSM definitions in a Laravel application, providing an efficient way to handle the cache effectively. Developers utilizing this command can ensure their FSM definitions remain relevant and accurate, which is crucial for applications relying on FSM logic for their operations.