<?php

declare(strict_types=1);

namespace Fsm\Data;

use Closure;

/**
 * Represents an action to be performed during a transition.
 * This might involve dispatching a Verb or other side effects.
 *
 * Enhanced with readonly properties for immutability and typed constants
 * for better static analysis and IDE support.
 */
class TransitionAction extends Dto
{
    /**
     * Action execution timing constants with proper typing.
     */
    public const string TIMING_BEFORE = 'before';

    public const string TIMING_AFTER = 'after';

    public const string TIMING_ON_SUCCESS = 'on_success';

    public const string TIMING_ON_FAILURE = 'on_failure';

    /**
     * Action type constants for enhanced type safety.
     */
    public const string TYPE_VERB = 'verb';

    public const string TYPE_CLOSURE = 'closure';

    public const string TYPE_CALLABLE = 'callable';

    public const string TYPE_SERVICE = 'service';

    /**
     * Action priority constants for enhanced type safety.
     */
    public const int PRIORITY_CRITICAL = 100;

    public const int PRIORITY_HIGH = 75;

    public const int PRIORITY_NORMAL = 50;

    public const int PRIORITY_LOW = 25;

    /**
     * @param  class-string|Closure|array<string>  $callable  The action logic. Strings, arrays, or first-class callables are supported.
     * @param  array<mixed>  $parameters  Static parameters to pass to the action.
     * @param  bool  $runAfterTransition  Indicates if it should run after the model is saved.
     * @param  string  $timing  When to execute the action relative to the transition.
     * @param  int  $priority  Execution priority (higher numbers execute first).
     * @param  string|null  $name  Optional name for the action for debugging/logging.
     */
    /** @var string|Closure|array{0: class-string|object, 1: string} */
    public string|Closure|array $callable;

    /** @var array<string, mixed> */
    public array $parameters = [];

    public bool $runAfterTransition = true;

    public string $timing = self::TIMING_AFTER;

    public int $priority = self::PRIORITY_NORMAL;

    public ?string $name = null;

    public bool $queued = false;

    /**
     * @param  array<string, mixed>|string|Closure|array<int, string>  $callable
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        string|Closure|array $callable,
        array $parameters = [],
        bool $runAfterTransition = true,
        string $timing = self::TIMING_AFTER,
        int $priority = self::PRIORITY_NORMAL,
        ?string $name = null,
        bool $queued = false,
    ) {
        // Check for array-based construction using improved logic
        if (is_array($callable) && func_num_args() === 1) {
            $expectedKeys = ['callable', 'parameters', 'runAfterTransition', 'timing', 'priority', 'name', 'queued'];

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
            'queued' => $queued,
        ]));
    }

    /**
     * Determine the action type based on the callable.
     */
    public function getType(): string
    {
        return match (true) {
            is_string($this->callable) && class_exists($this->callable) => self::TYPE_VERB,
            $this->callable instanceof Closure => self::TYPE_CLOSURE,
            is_array($this->callable) => self::TYPE_CALLABLE,
            default => self::TYPE_SERVICE,
        };
    }

    /**
     * Check if this action should execute at the given timing.
     */
    public function shouldExecuteAt(string $timing): bool
    {
        return $this->timing === $timing;
    }

    /**
     * Get a human-readable name for this action.
     */
    public function getDisplayName(): string
    {
        if ($this->name !== null) {
            return $this->name;
        }

        return match (
            $this->getType()
        ) {
            self::TYPE_VERB => is_string($this->callable) ? class_basename($this->callable) : 'Verb',
            self::TYPE_CLOSURE => 'Closure',
            self::TYPE_CALLABLE => is_array($this->callable) && isset($this->callable[0], $this->callable[1]) ?
                (is_string($this->callable[0]) ? $this->callable[0] : get_class($this->callable[0])).'::'.$this->callable[1] :
                'Callable',
            default => 'Unknown Action',
        };
    }
}
