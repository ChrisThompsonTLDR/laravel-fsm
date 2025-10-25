<?php

declare(strict_types=1);

namespace Fsm\Data;

use Fsm\Contracts\FsmStateEnum;
use Fsm\Traits\StateNameStringConversion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Represents the definition of a single state within an FSM.
 *
 * Enhanced with readonly properties for immutability and typed constants
 * for better static analysis and type safety.
 */
class StateDefinition extends Dto
{
    use StateNameStringConversion;

    /**
     * State type constants with proper typing.
     */
    public const string TYPE_INITIAL = 'initial';

    public const string TYPE_INTERMEDIATE = 'intermediate';

    public const string TYPE_FINAL = 'final';

    public const string TYPE_ERROR = 'error';

    /**
     * State category constants for enhanced type safety.
     */
    public const string CATEGORY_PENDING = 'pending';

    public const string CATEGORY_ACTIVE = 'active';

    public const string CATEGORY_COMPLETED = 'completed';

    public const string CATEGORY_CANCELLED = 'cancelled';

    public const string CATEGORY_FAILED = 'failed';

    /**
     * State behavior constants.
     */
    public const string BEHAVIOR_TRANSIENT = 'transient';

    public const string BEHAVIOR_PERSISTENT = 'persistent';

    public const string BEHAVIOR_TERMINAL = 'terminal';

    /**
     * @var Collection<int, TransitionCallback>
     */
    public Collection $onEntryCallbacks;

    /**
     * @var Collection<int, TransitionCallback>
     */
    public Collection $onExitCallbacks;

    /** @var array<string, string> */
    protected array $casts = [
        'onEntryCallbacks' => Collection::class.':'.TransitionCallback::class,
        'onExitCallbacks' => Collection::class.':'.TransitionCallback::class,
    ];

    /**
     * @param  FsmStateEnum|string  $name  The state name or enum value.
     * @param  array<int, TransitionCallback>|\Illuminate\Support\Collection<int, TransitionCallback>  $onEntryCallbacks  Callbacks executed when entering this state.
     * @param  array<int, TransitionCallback>|\Illuminate\Support\Collection<int, TransitionCallback>  $onExitCallbacks  Callbacks executed when exiting this state.
     * @param  string|null  $description  Human-readable description of the state.
     * @param  string  $type  The type of state (initial, intermediate, final, error).
     * @param  string|null  $category  The category this state belongs to.
     * @param  string  $behavior  The behavior of this state.
     * @param  array<string, mixed>  $metadata  Additional metadata for the state.
     * @param  bool  $isTerminal  Whether this state is terminal (no transitions out).
     * @param  int  $priority  Priority for state processing.
     */
    public FsmStateEnum|string $name;

    public ?string $description = null;

    public string $type = self::TYPE_INTERMEDIATE;

    public ?string $category = null;

    public string $behavior = self::BEHAVIOR_PERSISTENT;

    /** @var array<string, mixed> */
    public array $metadata = [];

    public bool $isTerminal = false;

    public int $priority = 50;

    /**
     * @param  array<string, mixed>|FsmStateEnum|string  $name
     * @param  array<int, TransitionCallback>|\Illuminate\Support\Collection<int, TransitionCallback>  $onEntryCallbacks
     * @param  array<int, TransitionCallback>|\Illuminate\Support\Collection<int, TransitionCallback>  $onExitCallbacks
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        array|FsmStateEnum|string $name,
        array|Collection $onEntryCallbacks = [],
        array|Collection $onExitCallbacks = [],
        ?string $description = null,
        string $type = self::TYPE_INTERMEDIATE,
        ?string $category = null,
        string $behavior = self::BEHAVIOR_PERSISTENT,
        array $metadata = [],
        bool $isTerminal = false,
        int $priority = 50,
    ) {
        // Array-based initialization: new StateDefinition(['name' => ..., 'description' => ...])
        if (is_array($name) && func_num_args() === 1 && static::isAssociative($name)) {
            parent::__construct(static::prepareAttributes($name));

            return;
        }

        // Reject non-associative arrays for clarity
        if (is_array($name)) {
            throw new \InvalidArgumentException('Array-based initialization requires an associative array.');
        }

        // Named parameter initialization: new StateDefinition(name: ..., description: ...)
        parent::__construct(static::prepareAttributes([
            'name' => $name,
            'onEntryCallbacks' => $onEntryCallbacks,
            'onExitCallbacks' => $onExitCallbacks,
            'description' => $description,
            'type' => $type,
            'category' => $category,
            'behavior' => $behavior,
            'metadata' => $metadata,
            'isTerminal' => $isTerminal,
            'priority' => $priority,
        ]));
    }

    /**
     * Check if this state is of a specific type.
     */
    public function isOfType(string $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Check if this state is an initial state.
     */
    public function isInitial(): bool
    {
        return $this->isOfType(self::TYPE_INITIAL);
    }

    /**
     * Check if this state is a final state.
     */
    public function isFinal(): bool
    {
        return $this->isOfType(self::TYPE_FINAL);
    }

    /**
     * Check if this state is an error state.
     */
    public function isError(): bool
    {
        return $this->isOfType(self::TYPE_ERROR);
    }

    /**
     * Check if this state is terminal (no transitions out).
     */
    public function isTerminal(): bool
    {
        return $this->isTerminal || $this->behavior === self::BEHAVIOR_TERMINAL;
    }

    /**
     * Check if this state is transient (automatically transitions).
     */
    public function isTransient(): bool
    {
        return $this->behavior === self::BEHAVIOR_TRANSIENT;
    }

    /**
     * Get the state name as a string.
     */
    public function getStateName(): string
    {
        return self::stateToString($this->name) ?? '';
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
     * Get display name for this state
     */
    public function getDisplayName(): string
    {
        $displayName = $this->getMetadata('display_name');
        if ($displayName && is_string($displayName)) {
            return $displayName;
        }

        // For enum states, call displayName directly
        if ($this->name instanceof FsmStateEnum) {
            return $this->name->displayName();
        }

        return Str::title(str_replace('_', ' ', $this->getStateName()));
    }

    /**
     * Get icon for this state
     */
    public function getIcon(): string
    {
        $icon = $this->getMetadata('icon');
        if ($icon && is_string($icon)) {
            return $icon;
        }

        // For enum states, call icon directly
        if ($this->name instanceof FsmStateEnum) {
            return $this->name->icon();
        }

        return '';
    }

    /**
     * Get callbacks that should execute at specific timing
     *
     * @return Collection<int, TransitionCallback>
     */
    public function getCallbacksForTiming(string $timing): Collection
    {
        $allCallbacks = $this->onEntryCallbacks->merge($this->onExitCallbacks);

        return $allCallbacks->filter(fn (TransitionCallback $callback) => $callback->shouldExecuteAt($timing)
        );
    }
}
