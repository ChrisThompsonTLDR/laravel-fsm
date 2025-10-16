<?php

declare(strict_types=1);

namespace Fsm;

/**
 * Collects and builds FSM definitions for various models and their state columns.
 */
class FsmBuilder
{
    /**
     * Stores all FSM definitions.
     * Structure: [modelClassName => [columnName => [arrayOfTransitionDetails]]]
     *
     * @var array<class-string, array<string, array<int, array<string, mixed>>>>
     */
    private static array $definitions = [];

    /**
     * Stores the TransitionBuilder instances for each FSM.
     * Structure: [modelClassName => [columnName => TransitionBuilder]]
     *
     * @var array<class-string, array<string, TransitionBuilder>>
     */
    private static array $builders = [];

    /**
     * Creates a new TransitionBuilder for the given model and state column.
     *
     * @param  class-string  $modelClass  The fully qualified class name of the model.
     * @param  string  $columnName  The name of the state column on the model.
     */
    public static function for(string $modelClass, string $columnName): TransitionBuilder
    {
        return self::$builders[$modelClass][$columnName] ??=
            new TransitionBuilder($modelClass, $columnName);
    }

    /**
     * Registers the details of a single transition for a given FSM.
     * This method is typically called by TransitionBuilder.
     *
     * @param  class-string  $modelClass  The fully qualified class name of the model.
     * @param  string  $columnName  The name of the state column on the model.
     * @param  array<string, mixed>  $transitionDetails  An associative array holding the definition of a single transition
     *                                                   (e.g., 'from', 'to', 'guards', 'callbacks').
     */
    public static function registerFsm(string $modelClass, string $columnName, array $transitionDetails): void
    {
        self::$definitions[$modelClass][$columnName][] = $transitionDetails;
    }

    /**
     * Retrieves all registered transition definitions for a specific FSM.
     *
     * @param  class-string  $modelClass  The fully qualified class name of the model.
     * @param  string  $columnName  The name of the state column on the model.
     * @return array<int, array<string, mixed>>|null An array of transition definitions, or null if no FSM is defined.
     */
    public static function getFsm(string $modelClass, string $columnName): ?array
    {
        return self::$definitions[$modelClass][$columnName] ?? null;
    }

    /**
     * Retrieve the TransitionBuilder for a specific FSM if defined.
     *
     * @param  class-string  $modelClass
     */
    public static function getDefinition(string $modelClass, string $columnName): ?TransitionBuilder
    {
        return self::$builders[$modelClass][$columnName] ?? null;
    }

    /**
     * Get all stored TransitionBuilder instances.
     *
     * @return array<class-string, array<string, TransitionBuilder>>
     */
    public static function getDefinitions(): array
    {
        return self::$builders;
    }

    /**
     * Clears all registered FSM definitions.
     * Useful for testing purposes.
     */
    public static function reset(): void
    {
        self::$definitions = [];
        self::$builders = [];
    }

    /**
     * Extend an existing FSM definition with additional states and transitions.
     *
     * @param  class-string  $modelClass
     * @param  callable  $extension  Callback that receives the existing TransitionBuilder to modify
     */
    public static function extend(string $modelClass, string $columnName, callable $extension): void
    {
        $builder = self::for($modelClass, $columnName);
        $extension($builder);
    }

    /**
     * Override a specific state definition for an FSM.
     *
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $stateConfig
     */
    public static function overrideState(string $modelClass, string $columnName, string|\Fsm\Contracts\FsmStateEnum $stateName, array $stateConfig): void
    {
        $builder = self::for($modelClass, $columnName);

        // Apply state override using the builder's state method
        $builder->state($stateName, function ($builder) use ($stateConfig) {
            // Validate that the builder object is valid before attempting to call methods on it
            if ($builder === null || ! is_object($builder)) {
                throw new \InvalidArgumentException('State configuration received invalid builder object, got: '.gettype($builder));
            }

            foreach ($stateConfig as $method => $value) {
                if ($value !== null) {
                    if (! method_exists($builder, $method)) {
                        // Log warning for invalid method and throw exception
                        \Illuminate\Support\Facades\Log::warning("Invalid state configuration method '{$method}' does not exist on builder object");
                        throw new \InvalidArgumentException("Invalid state configuration method: {$method}");
                    }

                    // Handle methods that might take different parameter types/counts
                    try {
                        $reflectionMethod = new \ReflectionMethod($builder, $method);
                        $parameterCount = $reflectionMethod->getNumberOfRequiredParameters();

                        if ($parameterCount <= 1) {
                            $builder->{$method}($value);
                        } else {
                            // For methods that require multiple parameters, pass as array expansion
                            if (is_array($value)) {
                                $builder->{$method}(...$value);
                            } else {
                                $builder->{$method}($value);
                            }
                        }
                    } catch (\ReflectionException $e) {
                        throw new \InvalidArgumentException("Could not reflect on method {$method}: ".$e->getMessage());
                    } catch (\ArgumentCountError $e) {
                        throw new \InvalidArgumentException("Invalid argument count for method {$method}: ".$e->getMessage());
                    }
                }
            }

            return $builder;
        });
    }

    /**
     * Override or add a transition definition for an FSM.
     *
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $transitionConfig
     */
    public static function overrideTransition(
        string $modelClass,
        string $columnName,
        string|\Fsm\Contracts\FsmStateEnum|null $fromState,
        string|\Fsm\Contracts\FsmStateEnum $toState,
        string $event,
        array $transitionConfig
    ): void {
        $builder = self::for($modelClass, $columnName);

        // Remove any existing transitions with the same from, to, and event
        $builder->removeTransition($fromState, $toState, $event);

        // Create transition and apply configuration
        $transitionBuilder = $builder->transition($fromState, $toState)->event($event);

        foreach ($transitionConfig as $method => $value) {
            if (method_exists($transitionBuilder, $method) && $value !== null) {
                $transitionBuilder->{$method}($value);
            } elseif (! method_exists($transitionBuilder, $method)) {
                // Log warning or throw exception for invalid method
                throw new \InvalidArgumentException("Invalid transition configuration method: {$method}");
            }
        }
    }

    /**
     * Apply runtime extensions to an FSM definition.
     *
     * @param  class-string  $modelClass
     */
    public static function applyExtensions(string $modelClass, string $columnName, \Fsm\FsmExtensionRegistry $extensionRegistry): void
    {
        // Apply extensions
        $extensions = $extensionRegistry->getExtensionsFor($modelClass, $columnName);
        foreach ($extensions as $extension) {
            $builder = self::for($modelClass, $columnName);
            try {
                $extension->extend($modelClass, $columnName, $builder);
            } catch (\Throwable $e) {
                // Log the error and continue with other extensions
                \Illuminate\Support\Facades\Log::error("Failed to apply FSM extension {$extension->getName()}: ".$e->getMessage(), [
                    'model' => $modelClass,
                    'column' => $columnName,
                    'extension' => get_class($extension),
                    'exception' => $e->getMessage(),
                ]);
                // Continue processing other extensions instead of failing fast
            }
        }

        // Apply modular state definitions
        $stateDefinitions = $extensionRegistry->getStateDefinitionsFor($modelClass, $columnName);
        foreach ($stateDefinitions as $stateDefinition) {
            self::overrideState($modelClass, $columnName, $stateDefinition->getStateName(), $stateDefinition->getDefinition());
        }

        // Apply modular transition definitions
        $transitionDefinitions = $extensionRegistry->getTransitionDefinitionsFor($modelClass, $columnName);
        foreach ($transitionDefinitions as $transitionDefinition) {
            self::overrideTransition(
                $modelClass,
                $columnName,
                $transitionDefinition->getFromState(),
                $transitionDefinition->getToState(),
                $transitionDefinition->getEvent(),
                $transitionDefinition->getDefinition()
            );
        }
    }
}
