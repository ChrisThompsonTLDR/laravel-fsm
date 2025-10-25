<?php

declare(strict_types=1);

namespace Fsm\Data;

use Fsm\Contracts\FsmStateEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * DTO for passing consistent input to transition lifecycle hooks (guards, actions, callbacks).
 *
 * Enhanced with readonly properties for immutability and typed constants
 * for better type safety and static analysis.
 */
class TransitionInput extends Dto
{
    /**
     * Transition mode constants with proper typing.
     */
    public const string MODE_NORMAL = 'normal';

    public const string MODE_DRY_RUN = 'dry_run';

    public const string MODE_FORCE = 'force';

    public const string MODE_SILENT = 'silent';

    /**
     * Transition source constants for enhanced type safety.
     */
    public const string SOURCE_USER = 'user';

    public const string SOURCE_SYSTEM = 'system';

    public const string SOURCE_API = 'api';

    public const string SOURCE_SCHEDULER = 'scheduler';

    public const string SOURCE_MIGRATION = 'migration';

    /**
     * @param  Model  $model  The model being transitioned.
     * @param  FsmStateEnum|string|null  $fromState  The state being transitioned from.
     * @param  FsmStateEnum|string  $toState  The state being transitioned to (required).
     * @param  ArgonautDTOContract|array|null  $context  Additional context data for the transition.
     * @param  string|null  $event  Optional event that triggered the transition.
     * @param  bool  $isDryRun  Whether this is a simulation run.
     * @param  string  $mode  The transition execution mode.
     * @param  string  $source  The source that initiated the transition.
     * @param  array<string, mixed>  $metadata  Additional metadata for the transition.
     * @param  \DateTimeInterface|null  $timestamp  When the transition was initiated.
     */
    public Model $model;

    public FsmStateEnum|string|null $fromState;

    public FsmStateEnum|string|null $toState = null;

    public ?ArgonautDTOContract $context = null;

    public ?string $event = null;

    public bool $isDryRun = false;

    public string $mode = self::MODE_NORMAL;

    public string $source = self::SOURCE_USER;

    /** @var array<string, mixed> */
    public array $metadata = [];

    public ?\DateTimeInterface $timestamp = null;

    /**
     * @param  array<string, mixed>|Model  $model
     * @param  array{class: class-string<ArgonautDTOContract>, payload: array<string, mixed>}|ArgonautDTOContract|null  $context
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        Model|array $model,
        FsmStateEnum|string|null $fromState = null,
        FsmStateEnum|string|null $toState = null,
        ArgonautDTOContract|array|null $context = null,
        ?string $event = null,
        bool $isDryRun = false,
        string $mode = self::MODE_NORMAL,
        string $source = self::SOURCE_USER,
        array $metadata = [],
        ?\DateTimeInterface $timestamp = null,
    ) {
        if (is_array($model) && func_num_args() === 1 && static::isAssociative($model)) {
            $attributes = $model;

            // Extract the model from the attributes array if it exists
            $actualModel = $attributes['model'] ?? null;
            if ($actualModel === null) {
                throw new \InvalidArgumentException('TransitionInput array-based construction requires a "model" key in the attributes array.');
            }

            // Prepare attributes to normalize snake_case to camelCase FIRST
            $preparedAttributes = static::prepareAttributes($attributes);

            // Hydrate context AFTER preparing attributes to avoid double processing
            $context = $preparedAttributes['context'] ?? null;
            if ($context !== null) {
                $preparedAttributes['context'] = self::hydrateContext($context);
            }

            // Validate toState for array-based construction - only require for normal mode
            $mode = $preparedAttributes['mode'] ?? self::MODE_NORMAL;
            if ($mode === self::MODE_NORMAL) {
                $toStateValue = $preparedAttributes['toState'] ?? null;
                if ($toStateValue === null) {
                    throw new \InvalidArgumentException('TransitionInput requires a non-null "toState" or "to_state" value for normal mode transitions.');
                }
            }

            // Use the extracted model as the first parameter and remaining attributes for other parameters
            // Set isDryRun based on mode if not explicitly provided
            $mode = $preparedAttributes['mode'] ?? self::MODE_NORMAL;
            $isDryRun = $preparedAttributes['isDryRun'] ?? ($mode === self::MODE_DRY_RUN);

            parent::__construct(static::prepareAttributes([
                'model' => $actualModel,
                'fromState' => $preparedAttributes['fromState'] ?? null,
                'toState' => $preparedAttributes['toState'] ?? null,
                'context' => $preparedAttributes['context'] ?? null,
                'event' => $preparedAttributes['event'] ?? null,
                'isDryRun' => $isDryRun,
                'mode' => $mode,
                'source' => $preparedAttributes['source'] ?? self::SOURCE_USER,
                'metadata' => $preparedAttributes['metadata'] ?? [],
                'timestamp' => $preparedAttributes['timestamp'] ?? null,
            ]));

            return;
        }

        // Validate toState for positional construction - only require non-null for normal mode
        if ($toState === null && $mode === self::MODE_NORMAL) {
            throw new \InvalidArgumentException('TransitionInput requires a non-null "toState" or "to_state" value for normal mode transitions.');
        }

        $resolvedContext = self::hydrateContext($context);

        parent::__construct(static::prepareAttributes([
            'model' => $model,
            'fromState' => $fromState,
            'toState' => $toState,
            'context' => $resolvedContext,
            'event' => $event,
            'isDryRun' => $isDryRun,
            'mode' => $mode,
            'source' => $source,
            'metadata' => $metadata,
            'timestamp' => $timestamp,
        ]));
    }

    /**
     * @param  array{class: class-string<ArgonautDTOContract>, payload: array<string, mixed>}|ArgonautDTOContract|null  $context
     */
    protected static function hydrateContext(ArgonautDTOContract|array|null $context): ?ArgonautDTOContract
    {
        if ($context instanceof ArgonautDTOContract) {
            return $context;
        }

        if (! is_array($context)) {
            return null;
        }

        $class = $context['class'] ?? null;
        $payload = $context['payload'] ?? [];

        if (! is_string($class)) {
            $classType = self::normalizeTypeName(gettype($class));
            throw new \RuntimeException("Context hydration failed: class is not a string (got {$classType})");
        }

        if (! class_exists($class)) {
            throw new \RuntimeException("Context hydration failed for class {$class}: class does not exist");
        }

        if (! is_a($class, ArgonautDTOContract::class, true)) {
            throw new \RuntimeException("Context hydration failed for class {$class}: class does not implement ArgonautDTOContract");
        }

        // Payload validation will be handled by the DTO's from() method or constructor

        try {
            // Use the DTO's from() factory method to properly reconstruct the instance
            // This avoids TypeError when DTOs have positional scalar parameters
            if (is_subclass_of($class, Dto::class)) {
                // Check if the Dto class has a static from() method with proper parameter compatibility
                if (method_exists($class, 'from')) { // @phpstan-ignore-line
                    try {
                        $reflection = new \ReflectionMethod($class, 'from');
                        if ($reflection->isStatic() && $reflection->isPublic()) {
                            // Check parameter compatibility - the method should accept an array parameter
                            $parameters = $reflection->getParameters();
                            if (count($parameters) === 1) {
                                $param = $parameters[0];
                                $paramType = $param->getType();

                                // Check if the parameter accepts array or mixed using improved validation
                                if (self::parameterAcceptsArray($paramType)) {
                                    try {
                                        return $class::from($payload); // @phpstan-ignore staticMethod.notFound
                                    } catch (\Throwable $e) {
                                        throw new \RuntimeException("Context hydration failed for class {$class}: {$e->getMessage()}", 0, $e);
                                    }
                                }
                            }
                        }
                    } catch (\ReflectionException) {
                        // Method doesn't exist or is not accessible, continue to fallback
                    }
                }
            }

            // Fallback for non-Dto ArgonautDTOContract implementations
            // Check if the class has a static, public from() method
            if (method_exists($class, 'from')) {
                try {
                    $reflection = new \ReflectionMethod($class, 'from');
                    if ($reflection->isStatic() && $reflection->isPublic()) {
                        // Check parameter compatibility - the method should accept an array parameter
                        $parameters = $reflection->getParameters();
                        if (count($parameters) === 1) {
                            $param = $parameters[0];
                            $paramType = $param->getType();

                            // Check if the parameter accepts array or mixed using improved validation
                            if (self::parameterAcceptsArray($paramType)) {
                                try {
                                    return $class::from($payload); // @phpstan-ignore staticMethod.notFound
                                } catch (\Throwable $e) {
                                    throw new \RuntimeException("Context hydration failed for class {$class}: {$e->getMessage()}", 0, $e);
                                }
                            }
                        }
                    }
                } catch (\ReflectionException) {
                    // Method doesn't exist or is not accessible, continue to fallback
                }
            }

            // Final fallback: try direct instantiation with the payload array
            // Many DTOs accept an array parameter in their constructor
            try {
                /** @var ArgonautDTOContract $instance */
                $instance = new $class($payload);

                return $instance;
                // @phpstan-ignore catch.neverThrown
            } catch (\Throwable $instantiationError) {
                throw new \RuntimeException("Failed to instantiate DTO class {$class}: {$instantiationError->getMessage()}", 0, $instantiationError);
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException("Context hydration failed for class {$class}: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @return array{class: class-string<ArgonautDTOContract>, payload: array<string, mixed>}|null
     */
    public function contextPayload(): ?array
    {
        if (! $this->context) {
            return null;
        }

        try {
            $payload = $this->context->toArray();

            // Validate that the payload is an array (defensive check)
            if (! is_array($payload)) {
                // Only log when not running in PHPUnit tests to avoid polluting test output
                // and when debug mode is enabled in FSM configuration
                if (! defined('PHPUNIT_COMPOSER_INSTALL') && ! defined('__PHPUNIT_PHAR__') && config('fsm.debug', false)) {
                    Log::warning('[FSM] Context serialization failed: toArray() did not return an array', [
                        'class' => $this->context::class,
                        'returned_type' => gettype($payload),
                        'returned_value' => $payload,
                    ]);
                }

                return null;
            }

            return [
                'class' => $this->context::class,
                'payload' => $payload,
            ];
        } catch (\Throwable $e) {
            // Log the error but don't throw - we don't want to break the transition
            // The context will be null rather than failing completely
            // Only log when not running in PHPUnit tests to avoid polluting test output
            // and when debug mode is enabled in FSM configuration
            if (! defined('PHPUNIT_COMPOSER_INSTALL') && ! defined('__PHPUNIT_PHAR__') && config('fsm.debug', false)) {
                Log::error('[FSM] Context serialization failed', [
                    'class' => $this->context::class,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return null;
        }
    }

    /**
     * Check if this is a dry run transition.
     */
    public function isDryRun(): bool
    {
        return $this->isDryRun;
    }

    /**
     * Check if this is a forced transition (bypasses guards).
     */
    public function isForced(): bool
    {
        return $this->mode === self::MODE_FORCE;
    }

    /**
     * Check if this is a silent transition (minimal logging/events).
     */
    public function isSilent(): bool
    {
        return $this->mode === self::MODE_SILENT;
    }

    /**
     * Get the transition source with type safety.
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get metadata value with optional default.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if metadata key exists.
     */
    public function hasMetadata(string $key): bool
    {
        return array_key_exists($key, $this->metadata);
    }

    /**
     * Get the timestamp of the transition initiation.
     */
    public function getTimestamp(): \DateTimeInterface
    {
        return $this->timestamp ?? now();
    }

    /**
     * Check if a parameter type accepts an array value.
     *
     * This method properly handles union types, intersection types, and named types
     * to determine if a parameter can accept an array value.
     *
     * @param  \ReflectionType|null  $paramType  The parameter type to check
     * @return bool True if the parameter accepts an array, false otherwise
     */
    private static function parameterAcceptsArray(?\ReflectionType $paramType): bool
    {
        if ($paramType === null) {
            // No type declaration means it accepts any type including array
            return true;
        }

        // Handle union types (e.g., array|string|null)
        if ($paramType instanceof \ReflectionUnionType) {
            foreach ($paramType->getTypes() as $type) {
                if (self::parameterAcceptsArray($type)) {
                    return true;
                }
            }

            return false;
        }

        // Handle intersection types (e.g., Countable&ArrayAccess)
        if ($paramType instanceof \ReflectionIntersectionType) {
            // For intersection types, we need to check if ALL types in the intersection
            // are compatible with array. Since array implements Countable and ArrayAccess,
            // we can be more permissive here, but we should still validate.
            foreach ($paramType->getTypes() as $type) {
                if (! self::parameterAcceptsArray($type)) {
                    return false;
                }
            }

            return true;
        }

        // Handle named types
        if ($paramType instanceof \ReflectionNamedType) {
            $typeName = $paramType->getName();

            // Direct array type
            if ($typeName === 'array') {
                return true;
            }

            // Mixed type accepts everything including array
            if ($typeName === 'mixed') {
                return true;
            }

            // Nullable array types are already handled above

            // For other types, check if they can accept array
            // This is more restrictive than the original implementation
            return false;
        }

        // Unknown type - be conservative and return false
        return false;
    }

    /**
     * Normalize type names from gettype() to consistent lowercase format.
     */
    private static function normalizeTypeName(string $type): string
    {
        return match ($type) {
            'NULL' => 'null',
            'double' => 'float',
            'boolean' => 'bool',
            'integer' => 'int',
            default => strtolower($type),
        };
    }
}
