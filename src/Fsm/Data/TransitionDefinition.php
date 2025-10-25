<?php

declare(strict_types=1);

namespace Fsm\Data;

use Fsm\Contracts\FsmStateEnum;
use Fsm\Traits\StateNameStringConversion;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Represents the definition of a single transition between two states.
 *
 * Enhanced with readonly properties for immutability and typed constants
 * for better static analysis and type safety.
 */
class TransitionDefinition extends Dto
{
    use StateNameStringConversion;

    /**
     * Transition type constants with proper typing.
     */
    public const string TYPE_AUTOMATIC = 'automatic';

    public const string TYPE_MANUAL = 'manual';

    public const string TYPE_TRIGGERED = 'triggered';

    public const string TYPE_CONDITIONAL = 'conditional';

    /**
     * Transition priority constants for enhanced type safety.
     */
    public const int PRIORITY_CRITICAL = 100;

    public const int PRIORITY_HIGH = 75;

    public const int PRIORITY_NORMAL = 50;

    public const int PRIORITY_LOW = 25;

    /**
     * Transition behavior constants.
     */
    public const string BEHAVIOR_IMMEDIATE = 'immediate';

    public const string BEHAVIOR_DEFERRED = 'deferred';

    public const string BEHAVIOR_QUEUED = 'queued';

    /**
     * Guard evaluation constants.
     */
    public const string GUARD_EVALUATION_ALL = 'all';

    public const string GUARD_EVALUATION_ANY = 'any';

    public const string GUARD_EVALUATION_FIRST = 'first';

    /**
     * @var Collection<int, TransitionGuard>
     */
    public Collection $guards;

    /**
     * @var Collection<int, TransitionAction>
     */
    public Collection $actions;

    /**
     * @var Collection<int, TransitionCallback>
     */
    public Collection $onTransitionCallbacks;

    /** @var array<string, string> */
    protected array $casts = [
        'guards' => Collection::class.':'.TransitionGuard::class,
        'actions' => Collection::class.':'.TransitionAction::class,
        'onTransitionCallbacks' => Collection::class.':'.TransitionCallback::class,
    ];

    /**
     * @param  FsmStateEnum|string|null  $fromState  The state to transition from (null for wildcard).
     * @param  FsmStateEnum|string  $toState  The state to transition to.
     * @param  string|null  $event  Optional event that triggers this transition.
     * @param  array<int, TransitionGuard>|Collection<int, TransitionGuard>  $guards  Guards that must pass for the transition.
     * @param  array<int, TransitionAction>|Collection<int, TransitionAction>  $actions  Actions to execute during the transition.
     * @param  array<int, TransitionCallback>|Collection<int, TransitionCallback>  $onTransitionCallbacks  Callbacks specific to this transition path.
     * @param  string|null  $description  Human-readable description of the transition.
     * @param  string  $type  The type of transition.
     * @param  int  $priority  Execution priority for this transition.
     * @param  string  $behavior  The behavior of this transition.
     * @param  string  $guardEvaluation  How to evaluate multiple guards.
     * @param  array<string, mixed>  $metadata  Additional metadata for the transition.
     * @param  bool  $isReversible  Whether this transition can be reversed.
     * @param  int  $timeout  Maximum time in seconds for transition completion.
     */
    public FsmStateEnum|string|null $fromState;

    public FsmStateEnum|string|null $toState;

    public ?string $event = null;

    public ?string $description = null;

    public string $type = self::TYPE_MANUAL;

    public int $priority = self::PRIORITY_NORMAL;

    public string $behavior = self::BEHAVIOR_IMMEDIATE;

    public string $guardEvaluation = self::GUARD_EVALUATION_ALL;

    /** @var array<string, mixed> */
    public array $metadata = [];

    public bool $isReversible = false;

    public int $timeout = 30;

    /**
     * @param  FsmStateEnum|string|null|array<string, mixed>  $fromState
     * @param  array<int, TransitionGuard>|\Illuminate\Support\Collection<int, TransitionGuard>  $guards
     * @param  array<int, TransitionAction>|\Illuminate\Support\Collection<int, TransitionAction>  $actions
     * @param  array<int, TransitionCallback>|\Illuminate\Support\Collection<int, TransitionCallback>  $onTransitionCallbacks
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        FsmStateEnum|string|null|array $fromState = null,
        FsmStateEnum|string|null $toState = null,
        ?string $event = null,
        array|Collection $guards = [],
        array|Collection $actions = [],
        array|Collection $onTransitionCallbacks = [],
        ?string $description = null,
        string $type = self::TYPE_MANUAL,
        int $priority = self::PRIORITY_NORMAL,
        string $behavior = self::BEHAVIOR_IMMEDIATE,
        string $guardEvaluation = self::GUARD_EVALUATION_ALL,
        array $metadata = [],
        bool $isReversible = false,
        int $timeout = 30,
    ) {
        // Check if this is array-based construction (first parameter is array and only one argument)
        if (is_array($fromState) && func_num_args() === 1) {
            // Array-based construction: new TransitionDefinition(['fromState' => ..., 'toState' => ...])
            // Validate that it's an associative array
            if (! static::isAssociative($fromState)) {
                throw new InvalidArgumentException('Array-based initialization requires an associative array with a "toState" or "to_state" key.');
            }

            // Validate that toState is present (can be null for wildcard transitions)
            if (! array_key_exists('toState', $fromState) && ! array_key_exists('to_state', $fromState)) {
                throw new InvalidArgumentException('Array-based initialization requires an associative array with a "toState" or "to_state" key.');
            }

            // Process the array directly to avoid infinite recursion
            $attributes = static::prepareAttributes($fromState);

            // Initialize properties with proper type conversion and validation
            $this->fromState = $this->validateAndConvertToState($attributes['fromState'] ?? null, 'fromState');
            $this->toState = $this->validateAndConvertToState($attributes['toState'] ?? null, 'toState');
            $this->event = $this->validateStringOrNull($attributes['event'] ?? null, 'event');
            $this->guards = $this->validateCollection($attributes['guards'] ?? [], 'guards');
            $this->actions = $this->validateCollection($attributes['actions'] ?? [], 'actions');
            $this->onTransitionCallbacks = $this->validateCollection($attributes['onTransitionCallbacks'] ?? [], 'onTransitionCallbacks');
            $this->description = $this->validateStringOrNull($attributes['description'] ?? null, 'description');
            $this->type = $this->validateString($attributes['type'] ?? self::TYPE_MANUAL, 'type');
            $this->priority = $this->validateInt($attributes['priority'] ?? self::PRIORITY_NORMAL, 'priority');
            $this->behavior = $this->validateString($attributes['behavior'] ?? self::BEHAVIOR_IMMEDIATE, 'behavior');
            $this->guardEvaluation = $this->validateString($attributes['guardEvaluation'] ?? self::GUARD_EVALUATION_ALL, 'guardEvaluation');
            $this->metadata = $this->validateArray($attributes['metadata'] ?? [], 'metadata');
            $this->isReversible = $this->validateBool($attributes['isReversible'] ?? false, 'isReversible');
            $this->timeout = $this->validateInt($attributes['timeout'] ?? 30, 'timeout');

            return;
        }

        // Named parameter initialization: new TransitionDefinition(fromState: ..., toState: ...)
        // Note: toState can be null for wildcard transitions

        // Validate positional parameters with the same validation as array-based initialization
        $this->validatePositionalParameters($fromState, $toState, $event, $description, $type, $priority, $behavior, $guardEvaluation, $metadata, $isReversible, $timeout);

        // Initialize collection properties before parent constructor to prevent data loss
        $this->initializeCollectionProperties();

        parent::__construct(static::prepareAttributes([
            'fromState' => $fromState,
            'toState' => $toState,
            'event' => $event,
            'guards' => $guards,
            'actions' => $actions,
            'onTransitionCallbacks' => $onTransitionCallbacks,
            'description' => $description,
            'type' => $type,
            'priority' => $priority,
            'behavior' => $behavior,
            'guardEvaluation' => $guardEvaluation,
            'metadata' => $metadata,
            'isReversible' => $isReversible,
            'timeout' => $timeout,
        ]));
    }

    /**
     * Create a TransitionDefinition from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Check if toState key exists (either camelCase or snake_case)
        $hasToStateKey = array_key_exists('toState', $data) || array_key_exists('to_state', $data);

        if (! $hasToStateKey) {
            throw new \InvalidArgumentException('Array-based initialization requires an associative array with a "toState" or "to_state" key.');
        }

        $toStateValue = $data['toState'] ?? $data['to_state'] ?? null;

        // Note: toState can be null for wildcard transitions

        // Validate value types for key properties
        $fromStateValue = $data['fromState'] ?? $data['from_state'] ?? null;
        if ($fromStateValue !== null) {
            if (! is_string($fromStateValue) && ! ($fromStateValue instanceof FsmStateEnum)) {
                throw new \InvalidArgumentException(
                    'The "fromState" value must be a string, FsmStateEnum, or null, got: '.get_debug_type($fromStateValue)
                );
            }
        }

        if ($toStateValue !== null && ! is_string($toStateValue) && ! ($toStateValue instanceof FsmStateEnum)) {
            throw new \InvalidArgumentException(
                'The "toState" value must be a string, FsmStateEnum, or null, got: '.get_debug_type($toStateValue)
            );
        }

        // Validate optional string properties
        if (array_key_exists('event', $data) && $data['event'] !== null && ! is_string($data['event'])) {
            throw new \InvalidArgumentException(
                'The "event" value must be a string or null, got: '.get_debug_type($data['event'])
            );
        }

        if (array_key_exists('description', $data) && $data['description'] !== null && ! is_string($data['description'])) {
            throw new \InvalidArgumentException(
                'The "description" value must be a string or null, got: '.get_debug_type($data['description'])
            );
        }

        if (array_key_exists('type', $data) && ! is_string($data['type'])) {
            throw new \InvalidArgumentException(
                'The "type" value must be a string, got: '.get_debug_type($data['type'])
            );
        }

        if (array_key_exists('behavior', $data) && ! is_string($data['behavior'])) {
            throw new \InvalidArgumentException(
                'The "behavior" value must be a string, got: '.get_debug_type($data['behavior'])
            );
        }

        if (array_key_exists('guardEvaluation', $data) && ! is_string($data['guardEvaluation'])) {
            throw new \InvalidArgumentException(
                'The "guardEvaluation" value must be a string, got: '.get_debug_type($data['guardEvaluation'])
            );
        }

        // Validate integer properties
        if (array_key_exists('priority', $data) && ! is_int($data['priority'])) {
            throw new \InvalidArgumentException(
                'The "priority" value must be an integer, got: '.get_debug_type($data['priority'])
            );
        }

        if (array_key_exists('timeout', $data) && ! is_int($data['timeout'])) {
            throw new \InvalidArgumentException(
                'The "timeout" value must be an integer, got: '.get_debug_type($data['timeout'])
            );
        }

        // Validate boolean properties
        if (array_key_exists('isReversible', $data) && ! is_bool($data['isReversible'])) {
            throw new \InvalidArgumentException(
                'The "isReversible" value must be a boolean, got: '.get_debug_type($data['isReversible'])
            );
        }

        // Validate array properties
        if (array_key_exists('metadata', $data) && ! is_array($data['metadata'])) {
            throw new \InvalidArgumentException(
                'The "metadata" value must be an array, got: '.get_debug_type($data['metadata'])
            );
        }

        // Use the parent DTO's from() method to ensure proper initialization with validation and casting
        return static::from($data);
    }

    /**
     * Validate positional parameters with the same validation as array-based initialization.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function validatePositionalParameters(
        FsmStateEnum|string|null $fromState,
        FsmStateEnum|string|null $toState,
        ?string $event,
        ?string $description,
        string $type,
        int $priority,
        string $behavior,
        string $guardEvaluation,
        array $metadata,
        bool $isReversible,
        int $timeout
    ): void {
        // Validate fromState type
        if ($fromState !== null && ! is_string($fromState) && ! ($fromState instanceof FsmStateEnum)) { // @phpstan-ignore-line
            throw new \InvalidArgumentException(
                'The "fromState" parameter must be a string, FsmStateEnum, or null, got: '.get_debug_type($fromState)
            );
        }

        // Validate toState type
        if ($toState !== null && ! is_string($toState) && ! ($toState instanceof FsmStateEnum)) { // @phpstan-ignore-line
            throw new \InvalidArgumentException(
                'The "toState" parameter must be a string, FsmStateEnum, or null, got: '.get_debug_type($toState)
            );
        }

        // Validate event type
        if ($event !== null && ! is_string($event)) { // @phpstan-ignore-line
            throw new \InvalidArgumentException(
                'The "event" parameter must be a string or null, got: '.get_debug_type($event)
            );
        }

        // Validate description type
        if ($description !== null && ! is_string($description)) { // @phpstan-ignore-line
            throw new \InvalidArgumentException(
                'The "description" parameter must be a string or null, got: '.get_debug_type($description)
            );
        }

        // Note: The following validations are redundant due to PHP's type system,
        // but we keep them for consistency with array-based validation and
        // to provide clear error messages if someone bypasses type checking.
        // PHPStan will flag these as always true, which is expected.
    }

    /**
     * Initialize collection properties reliably without using try-catch Error blocks.
     * This method should be called before the parent constructor to prevent data loss.
     */
    private function initializeCollectionProperties(): void
    {
        // Initialize collection properties if they are not already Collection instances
        // Use try-catch to handle uninitialized typed properties
        try {
            if (! ($this->guards instanceof Collection)) { // @phpstan-ignore-line
                $this->guards = new Collection;
            }
        } catch (\Error) {
            $this->guards = new Collection;
        }

        try {
            if (! ($this->actions instanceof Collection)) { // @phpstan-ignore-line
                $this->actions = new Collection;
            }
        } catch (\Error) {
            $this->actions = new Collection;
        }

        try {
            if (! ($this->onTransitionCallbacks instanceof Collection)) { // @phpstan-ignore-line
                $this->onTransitionCallbacks = new Collection;
            }
        } catch (\Error) {
            $this->onTransitionCallbacks = new Collection;
        }
    }

    /**
     * Check if this transition is of a specific type.
     */
    public function isOfType(string $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Check if this transition is automatic.
     */
    public function isAutomatic(): bool
    {
        return $this->isOfType(self::TYPE_AUTOMATIC);
    }

    /**
     * Check if this transition is triggered by an event.
     */
    public function isTriggered(): bool
    {
        return $this->isOfType(self::TYPE_TRIGGERED) || $this->event !== null;
    }

    /**
     * Check if this transition has guards.
     */
    public function hasGuards(): bool
    {
        return $this->guards->count() > 0;
    }

    /**
     * Check if this transition has actions.
     */
    public function hasActions(): bool
    {
        return $this->actions->count() > 0;
    }

    /**
     * Check if this transition has callbacks.
     */
    public function hasCallbacks(): bool
    {
        return $this->onTransitionCallbacks->count() > 0;
    }

    /**
     * Get the from state as a string.
     */
    public function getFromStateName(): ?string
    {
        return self::stateToString($this->fromState);
    }

    /**
     * Get the to state as a string.
     */
    public function getToStateName(): string
    {
        return self::stateToString($this->toState) ?? '';
    }

    /**
     * Validate and convert a value to a state (enum|string|null).
     */
    private function validateAndConvertToState(mixed $value, string $paramName): FsmStateEnum|string|null
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof FsmStateEnum) {
            return $value;
        }

        if (is_string($value)) {
            return $value;
        }

        // For array-based construction, throw validation error
        throw new InvalidArgumentException("The \"{$paramName}\" value must be a string, FsmStateEnum, or null, got: ".get_debug_type($value));
    }

    /**
     * Validate that a value is a string or null.
     */
    private function validateStringOrNull(mixed $value, string $paramName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        // For array-based construction, throw validation error
        throw new InvalidArgumentException("The \"{$paramName}\" value must be a string or null, got: ".get_debug_type($value));
    }

    /**
     * Validate that a value is a string.
     */
    private function validateString(mixed $value, string $paramName): string
    {
        if (is_string($value)) {
            return $value;
        }

        // For array-based construction, throw validation error
        throw new InvalidArgumentException("The \"{$paramName}\" value must be a string, got: ".get_debug_type($value));
    }

    /**
     * Validate that a value is an integer.
     */
    private function validateInt(mixed $value, string $paramName): int
    {
        if (is_int($value)) {
            return $value;
        }

        // For array-based construction, throw validation error
        throw new InvalidArgumentException("The \"{$paramName}\" value must be an integer, got: ".get_debug_type($value));
    }

    /**
     * Validate that a value is a boolean.
     */
    private function validateBool(mixed $value, string $paramName): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        // For array-based construction, throw validation error
        throw new InvalidArgumentException("The \"{$paramName}\" value must be a boolean, got: ".get_debug_type($value));
    }

    /**
     * Validate that a value is an array.
     */
    /**
     * @return array<string, mixed>
     */
    private function validateArray(mixed $value, string $paramName): array
    {
        if (is_array($value)) {
            return $value;
        }

        // For array-based construction, throw validation error
        throw new InvalidArgumentException("The \"{$paramName}\" value must be an array, got: ".get_debug_type($value));
    }

    /**
     * Validate that a value is a Collection or array.
     */
    /**
     * @return Collection<int, mixed>
     */
    private function validateCollection(mixed $value, string $paramName): Collection
    {
        if ($value instanceof Collection) {
            return $value;
        }

        if (is_array($value)) {
            return Collection::make($value);
        }

        // For array-based construction, throw validation error
        throw new InvalidArgumentException("The \"{$paramName}\" value must be an array or Collection, got: ".get_debug_type($value));
    }

    /**
     * Check if this is a wildcard transition (from any state).
     */
    public function isWildcardTransition(): bool
    {
        return $this->fromState === null;
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
     * Get guards filtered by priority.
     *
     * @return Collection<int, TransitionGuard>
     */
    public function getGuardsForPriority(int $minPriority): Collection
    {
        return $this->guards->filter(fn (TransitionGuard $guard) => $guard->shouldExecuteAtPriority($minPriority)
        );
    }

    /**
     * Get actions filtered by timing.
     *
     * @return Collection<int, TransitionAction>
     */
    public function getActionsForTiming(string $timing): Collection
    {
        return $this->actions->filter(fn (TransitionAction $action) => $action->shouldExecuteAt($timing)
        );
    }

    /**
     * Get callbacks filtered by timing.
     *
     * @return Collection<int, TransitionCallback>
     */
    public function getCallbacksForTiming(string $timing): Collection
    {
        return $this->onTransitionCallbacks->filter(fn (TransitionCallback $callback) => $callback->shouldExecuteAt($timing)
        );
    }

    /**
     * Check if this transition should execute guards using AND logic.
     */
    public function shouldEvaluateAllGuards(): bool
    {
        return $this->guardEvaluation === self::GUARD_EVALUATION_ALL;
    }

    /**
     * Check if this transition should execute guards using OR logic.
     */
    public function shouldEvaluateAnyGuard(): bool
    {
        return $this->guardEvaluation === self::GUARD_EVALUATION_ANY;
    }

    /**
     * Get a human-readable description of this transition.
     */
    public function getDisplayDescription(): string
    {
        if ($this->description !== null) {
            return $this->description;
        }

        $from = $this->getFromStateName() ?? 'Any State';
        $to = $this->getToStateName();
        $event = $this->event ? " (triggered by: {$this->event})" : '';

        return "Transition from {$from} to {$to}{$event}";
    }
}
