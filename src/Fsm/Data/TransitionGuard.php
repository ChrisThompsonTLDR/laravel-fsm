<?php

declare(strict_types=1);

namespace Fsm\Data;

use Closure;

/**
 * Represents a guard condition for a transition.
 *
 * Enhanced with readonly properties for immutability and typed constants
 * for improved type safety and static analysis.
 */
class TransitionGuard extends Dto
{
    /**
     * Guard type constants with proper typing.
     */
    public const string TYPE_CLOSURE = 'closure';

    public const string TYPE_INVOKABLE = 'invokable';

    public const string TYPE_CALLABLE = 'callable';

    public const string TYPE_SERVICE = 'service';

    /**
     * Guard result constants for enhanced type safety.
     */
    public const bool RESULT_ALLOW = true;

    public const bool RESULT_DENY = false;

    /**
     * Priority levels for guard execution.
     */
    public const int PRIORITY_CRITICAL = 100;

    public const int PRIORITY_HIGH = 75;

    public const int PRIORITY_NORMAL = 50;

    public const int PRIORITY_LOW = 25;

    /**
     * @param  array<string, mixed>|class-string|Closure|array<string>  $callable  The guard logic. Can be DTO attributes array, class string, Closure, or callable array.
     * @param  array<mixed>  $parameters  Static parameters to pass to the guard, in addition to TransitionInput.
     * @param  string|null  $description  Optional description of the guard for logging/debugging.
     * @param  int  $priority  Execution priority (higher numbers execute first).
     * @param  bool  $stopOnFailure  Whether to stop executing other guards if this one fails.
     * @param  string|null  $name  Optional name for the guard for debugging/logging.
     */
    /** @var string|Closure|array{0: class-string|object, 1: string} */
    public string|Closure|array $callable;

    /** @var array<string, mixed> */
    public array $parameters = [];

    public ?string $description = null;

    public int $priority = self::PRIORITY_NORMAL;

    public bool $stopOnFailure = false;

    public ?string $name = null;

    /**
     * @param  array<string, mixed>|string|Closure|array<int, string>  $callable
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        array|string|Closure $callable,
        array $parameters = [],
        ?string $description = null,
        int $priority = self::PRIORITY_NORMAL,
        bool $stopOnFailure = false,
        ?string $name = null,
    ) {
        // Check for array-based construction using improved logic
        if (is_array($callable) && func_num_args() === 1) {
            $expectedKeys = ['callable', 'parameters', 'description', 'priority', 'stopOnFailure', 'name'];

            // If it's a callable array, treat it as positional parameter
            if (static::isCallableArray($callable)) {
                parent::__construct(static::prepareAttributes([
                    'callable' => $callable,
                    'parameters' => $parameters,
                    'description' => $description,
                    'priority' => $priority,
                    'stopOnFailure' => $stopOnFailure,
                    'name' => $name,
                ]));

                return;
            }

            // If it's a DTO property array, use it for construction
            if (static::isDtoPropertyArray($callable, $expectedKeys)) {
                parent::__construct(static::prepareAttributes($callable));

                return;
            }

            // If it's not a callable array and not a DTO property array, it's invalid
            throw new \InvalidArgumentException('Array parameter must be either a callable array [class, method] or an associative array with DTO property keys.');
        }

        parent::__construct(static::prepareAttributes([
            'callable' => $callable,
            'parameters' => $parameters,
            'description' => $description,
            'priority' => $priority,
            'stopOnFailure' => $stopOnFailure,
            'name' => $name,
        ]));
    }

    /**
     * Determine the guard type based on the callable.
     */
    public function getType(): string
    {
        return match (true) {
            $this->callable instanceof Closure => self::TYPE_CLOSURE,
            is_string($this->callable) && class_exists($this->callable) => self::TYPE_INVOKABLE,
            is_array($this->callable) => self::TYPE_CALLABLE,
            default => self::TYPE_SERVICE,
        };
    }

    /**
     * Get a human-readable name for this guard.
     */
    public function getDisplayName(): string
    {
        if ($this->name !== null) {
            return $this->name;
        }

        return match (
            $this->getType()
        ) {
            self::TYPE_INVOKABLE => is_string($this->callable) ? class_basename($this->callable) : 'Invokable',
            self::TYPE_CLOSURE => 'Closure Guard',
            self::TYPE_CALLABLE => is_array($this->callable) && isset($this->callable[0], $this->callable[1]) ?
                (is_string($this->callable[0]) ? $this->callable[0] : get_class($this->callable[0])).'::'.$this->callable[1] :
                'Callable Guard',
            self::TYPE_SERVICE => $this->getServiceDisplayName(),
            default => 'Unknown Guard',
        };
    }

    /**
     * Get display name for service-type callables (Laravel string callables).
     */
    private function getServiceDisplayName(): string
    {
        if (! is_string($this->callable)) {
            return 'Service Guard';
        }

        // Handle Laravel string callable format: 'Class@method'
        if (str_contains($this->callable, '@')) {
            return str_replace('@', '::', $this->callable);
        }

        return $this->callable;
    }

    /**
     * Check if this guard should be executed based on priority threshold.
     */
    public function shouldExecuteAtPriority(int $minPriority): bool
    {
        return $this->priority >= $minPriority;
    }
}
