<?php

declare(strict_types=1);

namespace Fsm\Data;

use Closure;

/**
 * Represents a callback (onEntry, onExit) associated with a state or transition.
 *
 * Enhanced with readonly properties for immutability and typed constants
 * for better static analysis and type safety.
 */
class TransitionCallback extends Dto
{
    /**
     * Callback type constants with proper typing.
     */
    public const string TYPE_CLOSURE = 'closure';

    public const string TYPE_INVOKABLE = 'invokable';

    public const string TYPE_CALLABLE = 'callable';

    public const string TYPE_SERVICE = 'service';

    /**
     * Callback timing constants for enhanced type safety.
     */
    public const string TIMING_ON_ENTRY = 'on_entry';

    public const string TIMING_ON_EXIT = 'on_exit';

    public const string TIMING_ON_TRANSITION = 'on_transition';

    public const string TIMING_BEFORE_SAVE = 'before_save';

    public const string TIMING_AFTER_SAVE = 'after_save';

    /**
     * Priority levels for callback execution.
     */
    public const int PRIORITY_HIGH = 100;

    public const int PRIORITY_NORMAL = 50;

    public const int PRIORITY_LOW = 10;

    /**
     * @param  class-string|Closure|array<string>  $callable  The callback logic. Strings, arrays, or first-class callables are supported.
     * @param  array<mixed>  $parameters  Static parameters to pass to the callback.
     * @param  bool  $runAfterTransition  For transition-specific callbacks, indicates if it should run after the model is saved.
     * @param  string  $timing  When to execute the callback relative to the transition.
     * @param  int  $priority  Execution priority (higher numbers execute first).
     * @param  string|null  $name  Optional name for the callback for debugging/logging.
     * @param  bool  $continueOnFailure  Whether to continue executing other callbacks if this one fails.
     */
    /** @var string|Closure|array{0: class-string|object, 1: string} */
    public string|Closure|array $callable;

    /** @var array<string, mixed> */
    public array $parameters = [];

    public bool $runAfterTransition = false;

    public string $timing = self::TIMING_AFTER_SAVE;

    public int $priority = self::PRIORITY_NORMAL;

    public ?string $name = null;

    public bool $continueOnFailure = true;

    public bool $queued = false;

    /**
     * @param  array<string, mixed>|string|Closure|array<int, string>  $callable
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        string|Closure|array $callable,
        array $parameters = [],
        bool $runAfterTransition = false,
        string $timing = self::TIMING_AFTER_SAVE,
        int $priority = self::PRIORITY_NORMAL,
        ?string $name = null,
        bool $continueOnFailure = true,
        bool $queued = false,
    ) {
        // Check for array-based construction using improved logic
        if (is_array($callable) && func_num_args() === 1) {
            $expectedKeys = ['callable', 'parameters', 'runAfterTransition', 'timing', 'priority', 'name', 'continueOnFailure', 'queued'];

            // If it's a DTO property array, use it for construction
            if (static::isDtoPropertyArray($callable, $expectedKeys)) {
                parent::__construct(static::prepareAttributes($callable));

                return;
            }

            // Otherwise, treat it as a callable parameter (including callable arrays, simple arrays, etc.)
            parent::__construct(static::prepareAttributes([
                'callable' => $callable,
                'parameters' => $parameters,
                'runAfterTransition' => $runAfterTransition,
                'timing' => $timing,
                'priority' => $priority,
                'name' => $name,
                'continueOnFailure' => $continueOnFailure,
                'queued' => $queued,
            ]));

            return;
        }

        parent::__construct(static::prepareAttributes([
            'callable' => $callable,
            'parameters' => $parameters,
            'runAfterTransition' => $runAfterTransition,
            'timing' => $timing,
            'priority' => $priority,
            'name' => $name,
            'continueOnFailure' => $continueOnFailure,
            'queued' => $queued,
        ]));
    }

    /**
     * Determine the callback type based on the callable.
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
     * Check if this callback should execute at the given timing.
     */
    public function shouldExecuteAt(string $timing): bool
    {
        return $this->timing === $timing;
    }

    /**
     * Get a human-readable name for this callback.
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
            self::TYPE_CLOSURE => 'Closure Callback',
            self::TYPE_CALLABLE => is_array($this->callable) && isset($this->callable[0], $this->callable[1]) ?
                (is_string($this->callable[0]) ? $this->callable[0] : get_class($this->callable[0])).'::'.$this->callable[1] :
                'Callable Callback',
            default => 'Unknown Callback',
        };
    }

    /**
     * Check if this callback should be executed based on priority threshold.
     */
    public function shouldExecuteAtPriority(int $minPriority): bool
    {
        return $this->priority >= $minPriority;
    }
}
