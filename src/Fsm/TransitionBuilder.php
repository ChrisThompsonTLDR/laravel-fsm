<?php

declare(strict_types=1);

namespace Fsm;

use Closure;
use Fsm\Contracts\FsmStateEnum;
use Fsm\Data\FsmRuntimeDefinition; // Will be used by FsmRegistry to compile
use Fsm\Data\HierarchicalStateDefinition;
use Fsm\Data\StateDefinition;
use Fsm\Data\TransitionAction;
use Fsm\Data\TransitionCallback;
use Fsm\Data\TransitionDefinition;
use Fsm\Data\TransitionGuard;
use LogicException;

/**
 * Builds the states and transitions for a specific FSM on a model and column.
 * This class collects all definitions and is then typically passed to FsmRegistry
 * to compile into an FsmRuntimeDefinition.
 */
class TransitionBuilder
{
    /**
     * @var class-string
     */
    private readonly string $modelClass;

    private readonly string $columnName;

    private ?string $contextDtoClass = null;

    /** @var array<string, StateDefinition> */
    private array $states = [];

    /** @var array<int, TransitionDefinition> */
    private array $transitions = [];

    private FsmStateEnum|string|null $initialState = null;

    // Fluent transition building state
    private FsmStateEnum|string|null $fluentFrom = null;

    /** @var array<FsmStateEnum|string> */
    private array $fluentFromStates = [];

    private FsmStateEnum|string|null $fluentTo = null;

    private ?string $fluentEvent = null;

    /** @var array<TransitionGuard> */
    private array $fluentGuards = [];

    /** @var array<TransitionAction> */
    private array $fluentActions = [];

    /** @var array<TransitionCallback> */
    private array $fluentOnTransitionCallbacks = [];

    private ?string $fluentTransitionDescription = null;

    // Current state context for state-level callbacks
    private FsmStateEnum|string|null $currentStateDefinitionContext = null;

    /**
     * @param  class-string  $modelClass  The Eloquent model class this FSM is for.
     * @param  string  $columnName  The column on the model that stores the state.
     */
    public function __construct(string $modelClass, string $columnName)
    {
        $this->modelClass = $modelClass;
        $this->columnName = $columnName;
    }

    private static function normalizeStateValue(FsmStateEnum|string $state): string
    {
        return $state instanceof FsmStateEnum ? $state->value : $state;
    }

    public function initial(FsmStateEnum|string $state): self
    {
        $this->initialState = $state;
        $this->ensureStateIsDefined($state);

        return $this;
    }

    public function state(FsmStateEnum|string $state, ?callable $configurator = null): self
    {
        $stateValue = self::normalizeStateValue($state);
        if (! isset($this->states[$stateValue])) {
            $this->states[$stateValue] = new StateDefinition(name: $state);
        }

        if ($configurator) {
            $this->currentStateDefinitionContext = $state;
            $configurator($this);
            $this->currentStateDefinitionContext = null;
        }

        return $this;
    }

    private function assertCurrentStateContext(string $methodName): void
    {
        if ($this->currentStateDefinitionContext === null) {
            throw new LogicException("{$methodName}() can only be called within the configurator of a state() definition.");
        }
    }

    /**
     * @param  string|Closure|array<string>  $callable  Callbacks may use PHP's first-class callable syntax.
     * @param  array<string, mixed>  $parameters
     */
    public function onEntry(string|Closure|array $callable, array $parameters = [], bool $runAfterTransition = false, bool $queued = false): self
    {
        $this->assertCurrentStateContext('onEntry');
        if ($this->currentStateDefinitionContext === null) {
            throw new LogicException('onEntry() can only be called within a state context.');
        }
        $stateValue = self::normalizeStateValue($this->currentStateDefinitionContext);

        /** @var class-string|Closure|array<string> $callableParam */
        $callableParam = $callable;
        $this->states[$stateValue]->onEntryCallbacks[] = new TransitionCallback($callableParam, $parameters, $runAfterTransition, TransitionCallback::TIMING_AFTER_SAVE, TransitionCallback::PRIORITY_NORMAL, null, true, $queued);

        return $this;
    }

    /**
     * @param  string|Closure|array<string>  $callable  Callbacks may use PHP's first-class callable syntax.
     * @param  array<string, mixed>  $parameters
     */
    public function onExit(string|Closure|array $callable, array $parameters = [], bool $runAfterTransition = false, bool $queued = false): self
    {
        $this->assertCurrentStateContext('onExit');
        if ($this->currentStateDefinitionContext === null) {
            throw new LogicException('onExit() can only be called within a state context.');
        }
        $stateValue = self::normalizeStateValue($this->currentStateDefinitionContext);

        /** @var class-string|Closure|array<string> $callableParam */
        $callableParam = $callable;
        $this->states[$stateValue]->onExitCallbacks[] = new TransitionCallback($callableParam, $parameters, $runAfterTransition, TransitionCallback::TIMING_AFTER_SAVE, TransitionCallback::PRIORITY_NORMAL, null, true, $queued);

        return $this;
    }

    /**
     * Start a new transition or finalize the current one.
     *
     * When called with two parameters, starts a transition from one state to another.
     * When called with one parameter (string) or no parameters, finalizes the current transition
     * and optionally sets a description.
     *
     * @param  FsmStateEnum|string|null  $fromOrDescription  Either the source state or description
     * @param  FsmStateEnum|string|null  $to  The destination state (when starting a new transition)
     * @return self For method chaining
     */
    public function transition(FsmStateEnum|string|null $fromOrDescription = null, FsmStateEnum|string|null $to = null): self
    {
        // If two parameters provided, treat as from/to transition
        if ($to !== null) {
            if ($fromOrDescription === null) {
                throw new LogicException('The source state cannot be null when defining a from/to transition.');
            }

            $this->finalizeCurrentFluentTransition();
            $this->fluentFromStates = [$fromOrDescription];
            $this->fluentFrom = $fromOrDescription;
            $this->fluentTo = $to;

            return $this;
        }

        // Otherwise, finalize current transition and optionally set description
        $this->finalizeCurrentFluentTransition();
        if (is_string($fromOrDescription)) {
            $this->fluentTransitionDescription = $fromOrDescription;
        }

        return $this;
    }

    /**
     * Set a description for the current fluent transition.
     *
     * @param  string  $description  The description for this transition
     * @return self For method chaining
     */
    public function description(string $description): self
    {
        // If we're in a state context, set state description
        if ($this->currentStateDefinitionContext !== null) {
            $stateValue = self::normalizeStateValue($this->currentStateDefinitionContext);

            // Create a new StateDefinition with the description
            $currentState = $this->states[$stateValue];
            $this->states[$stateValue] = new StateDefinition(
                name: $currentState->name,
                onEntryCallbacks: $currentState->onEntryCallbacks,
                onExitCallbacks: $currentState->onExitCallbacks,
                description: $description,
                type: $currentState->type,
                category: $currentState->category,
                behavior: $currentState->behavior,
                metadata: $currentState->metadata,
                isTerminal: $currentState->isTerminal,
                priority: $currentState->priority,
            );

            return $this;
        }

        // Otherwise, set transition description
        $this->fluentTransitionDescription = $description;

        return $this;
    }

    /**
     * Set the type for the current state context.
     *
     * @param  string  $type  The state type (e.g., 'initial', 'intermediate', 'final', 'error')
     * @return self For method chaining
     */
    public function type(string $type): self
    {
        $this->assertCurrentStateContext('type');
        $stateValue = self::normalizeStateValue($this->currentStateDefinitionContext);

        $currentState = $this->states[$stateValue];
        $this->states[$stateValue] = new StateDefinition(
            name: $currentState->name,
            onEntryCallbacks: $currentState->onEntryCallbacks,
            onExitCallbacks: $currentState->onExitCallbacks,
            description: $currentState->description,
            type: $type,
            category: $currentState->category,
            behavior: $currentState->behavior,
            metadata: $currentState->metadata,
            isTerminal: $currentState->isTerminal,
            priority: $currentState->priority,
        );

        return $this;
    }

    /**
     * Set the category for the current state context.
     *
     * @param  string|null  $category  The state category
     * @return self For method chaining
     */
    public function category(?string $category): self
    {
        $this->assertCurrentStateContext('category');
        $stateValue = self::normalizeStateValue($this->currentStateDefinitionContext);

        $currentState = $this->states[$stateValue];
        $this->states[$stateValue] = new StateDefinition(
            name: $currentState->name,
            onEntryCallbacks: $currentState->onEntryCallbacks,
            onExitCallbacks: $currentState->onExitCallbacks,
            description: $currentState->description,
            type: $currentState->type,
            category: $category,
            behavior: $currentState->behavior,
            metadata: $currentState->metadata,
            isTerminal: $currentState->isTerminal,
            priority: $currentState->priority,
        );

        return $this;
    }

    /**
     * Set the behavior for the current state context.
     *
     * @param  string  $behavior  The state behavior (e.g., 'persistent', 'transient', 'terminal')
     * @return self For method chaining
     */
    public function behavior(string $behavior): self
    {
        $this->assertCurrentStateContext('behavior');
        $stateValue = self::normalizeStateValue($this->currentStateDefinitionContext);

        $currentState = $this->states[$stateValue];
        $this->states[$stateValue] = new StateDefinition(
            name: $currentState->name,
            onEntryCallbacks: $currentState->onEntryCallbacks,
            onExitCallbacks: $currentState->onExitCallbacks,
            description: $currentState->description,
            type: $currentState->type,
            category: $currentState->category,
            behavior: $behavior,
            metadata: $currentState->metadata,
            isTerminal: $currentState->isTerminal,
            priority: $currentState->priority,
        );

        return $this;
    }

    /**
     * Set metadata for the current state context.
     *
     * @param  array<string, mixed>  $metadata  The state metadata
     * @return self For method chaining
     */
    public function metadata(array $metadata): self
    {
        $this->assertCurrentStateContext('metadata');
        $stateValue = self::normalizeStateValue($this->currentStateDefinitionContext);

        $currentState = $this->states[$stateValue];
        $this->states[$stateValue] = new StateDefinition(
            name: $currentState->name,
            onEntryCallbacks: $currentState->onEntryCallbacks,
            onExitCallbacks: $currentState->onExitCallbacks,
            description: $currentState->description,
            type: $currentState->type,
            category: $currentState->category,
            behavior: $currentState->behavior,
            metadata: $metadata,
            isTerminal: $currentState->isTerminal,
            priority: $currentState->priority,
        );

        return $this;
    }

    /**
     * Set whether the current state is terminal.
     *
     * @param  bool  $isTerminal  Whether this state is terminal
     * @return self For method chaining
     */
    public function isTerminal(bool $isTerminal): self
    {
        $this->assertCurrentStateContext('isTerminal');
        $stateValue = self::normalizeStateValue($this->currentStateDefinitionContext);

        $currentState = $this->states[$stateValue];
        $this->states[$stateValue] = new StateDefinition(
            name: $currentState->name,
            onEntryCallbacks: $currentState->onEntryCallbacks,
            onExitCallbacks: $currentState->onExitCallbacks,
            description: $currentState->description,
            type: $currentState->type,
            category: $currentState->category,
            behavior: $currentState->behavior,
            metadata: $currentState->metadata,
            isTerminal: $isTerminal,
            priority: $currentState->priority,
        );

        return $this;
    }

    /**
     * Set the priority for the current state context.
     *
     * @param  int  $priority  The state priority
     * @return self For method chaining
     */
    public function priority(int $priority): self
    {
        $this->assertCurrentStateContext('priority');
        $stateValue = self::normalizeStateValue($this->currentStateDefinitionContext);

        $currentState = $this->states[$stateValue];
        $this->states[$stateValue] = new StateDefinition(
            name: $currentState->name,
            onEntryCallbacks: $currentState->onEntryCallbacks,
            onExitCallbacks: $currentState->onExitCallbacks,
            description: $currentState->description,
            type: $currentState->type,
            category: $currentState->category,
            behavior: $currentState->behavior,
            metadata: $currentState->metadata,
            isTerminal: $currentState->isTerminal,
            priority: $priority,
        );

        return $this;
    }

    /**
     * Attach a child FSM to the current state context.
     */
    public function withChildFsm(FsmRuntimeDefinition $childFsm, ?string $parentState = null): self
    {
        $this->assertCurrentStateContext('withChildFsm');
        $stateValue = self::normalizeStateValue($this->currentStateDefinitionContext);

        $currentState = $this->states[$stateValue];
        $this->states[$stateValue] = new HierarchicalStateDefinition(
            name: $currentState->name,
            onEntryCallbacks: $currentState->onEntryCallbacks,
            onExitCallbacks: $currentState->onExitCallbacks,
            description: $currentState->description,
            type: $currentState->type,
            category: $currentState->category,
            behavior: $currentState->behavior,
            metadata: $currentState->metadata,
            isTerminal: $currentState->isTerminal,
            priority: $currentState->priority,
            childStateMachine: $childFsm,
            parentState: $parentState,
        );

        return $this;
    }

    /**
     * Internal method to finalize the current fluent transition definition.
     *
     * This method takes all the fluent state (from states, to state, event,
     * guards, actions, callbacks) and creates formal TransitionDefinition
     * objects. It handles both single and multi-from-state transitions.
     *
     * Called automatically when starting a new transition or building the FSM.
     */
    private function finalizeCurrentFluentTransition(): void
    {
        if (! empty($this->fluentFromStates) && $this->fluentTo !== null) {
            $this->ensureStateIsDefined($this->fluentTo);

            /** @var FsmStateEnum|string $toState */
            $toState = $this->fluentTo;

            foreach ($this->fluentFromStates as $fromState) {
                $this->ensureStateIsDefined($fromState);

                // Check if a transition with the same from, to, and event already exists
                $existingIndex = -1;
                foreach ($this->transitions as $index => $transition) {
                    if (
                        self::normalizeStateValue($transition->fromState) === self::normalizeStateValue($fromState) &&
                        self::normalizeStateValue($transition->toState) === self::normalizeStateValue($toState) &&
                        $transition->event === $this->fluentEvent
                    ) {
                        $existingIndex = $index;
                        break;
                    }
                }

                $newTransition = new TransitionDefinition(
                    fromState: $fromState,
                    toState: $toState,
                    event: $this->fluentEvent,
                    guards: $this->fluentGuards,
                    actions: $this->fluentActions,
                    onTransitionCallbacks: $this->fluentOnTransitionCallbacks,
                    description: $this->fluentTransitionDescription
                );

                if ($existingIndex !== -1) {
                    // Replace existing transition
                    $this->transitions[$existingIndex] = $newTransition;
                } else {
                    // Add new transition
                    $this->transitions[] = $newTransition;
                }
            }
        } elseif ($this->fluentFrom !== null && $this->fluentTo !== null) {
            // Fallback for backward compatibility
            $this->ensureStateIsDefined($this->fluentFrom);
            $this->ensureStateIsDefined($this->fluentTo);

            // Type assertion for PHPStan - we know these are not null due to the condition above
            assert($this->fluentFrom !== null);
            assert($this->fluentTo !== null);

            $this->transitions[] = new TransitionDefinition(
                fromState: $this->fluentFrom,
                toState: $this->fluentTo,
                event: $this->fluentEvent,
                guards: $this->fluentGuards,
                actions: $this->fluentActions,
                onTransitionCallbacks: $this->fluentOnTransitionCallbacks,
                description: $this->fluentTransitionDescription
            );
        }
        $this->resetFluentTransitionState();
    }

    private function resetFluentTransitionState(): void
    {
        $this->fluentFrom = null;
        $this->fluentFromStates = [];
        $this->fluentTo = null;
        $this->fluentEvent = null;
        $this->fluentGuards = [];
        $this->fluentActions = [];
        $this->fluentOnTransitionCallbacks = [];
        $this->fluentTransitionDescription = null;
    }

    private function ensureStateIsDefined(FsmStateEnum|string|null $state): void
    {
        if ($state === null) {
            return;
        }
        $stateValue = self::normalizeStateValue($state);
        if (! isset($this->states[$stateValue])) {
            $this->state($state);
        }
    }

    /**
     * @param  FsmStateEnum|string|array<int, FsmStateEnum|string>  $state
     */
    public function from(FsmStateEnum|string|array $state): self
    {
        if ($this->fluentTo !== null) {
            $this->finalizeCurrentFluentTransition();
        }

        // Handle arrays by storing all from states
        if (is_array($state)) {
            $this->fluentFromStates = $state;
            $this->fluentFrom = $state[0] ?? null; // Keep backward compatibility
        } else {
            $this->fluentFromStates = [$state];
            $this->fluentFrom = $state;
        }

        return $this;
    }

    public function to(FsmStateEnum|string $state): self
    {
        $this->fluentTo = $state;

        return $this;
    }

    public function event(string $eventName): self
    {
        $this->fluentEvent = $eventName;

        return $this;
    }

    /**
     * Alias for event() method for backward compatibility
     */
    public function on(string $eventName): self
    {
        return $this->event($eventName);
    }

    /**
     * @param  string|Closure|array<string>  $callable  Guards may use PHP's first-class callable syntax.
     * @param  array<string, mixed>  $parameters
     */
    public function guard(string|Closure|array $callable, array $parameters = [], ?string $description = null): self
    {
        if ($this->fluentFrom === null || $this->fluentTo === null) {
            throw new LogicException('guard() must be called after from() and to() in a transition definition.');
        }

        /** @var class-string|Closure|array<string> $callableParam */
        $callableParam = $callable;
        $this->fluentGuards[] = new TransitionGuard($callableParam, $parameters, $description);

        return $this;
    }

    /**
     * Add a Laravel Policy-based guard to the current transition.
     *
     * @param  string  $ability  The policy ability to check
     * @param  array<string, mixed>  $parameters  Additional parameters for the policy
     * @param  string|null  $description  Optional description for debugging
     */
    public function policy(string $ability, array $parameters = [], ?string $description = null): self
    {
        if ($this->fluentFrom === null || $this->fluentTo === null) {
            throw new LogicException('policy() must be called after from() and to() in a transition definition.');
        }

        $description = $description ?? "Policy check: {$ability}";

        $this->fluentGuards[] = new TransitionGuard(
            callable: fn ($input) => app(\Fsm\Guards\PolicyGuard::class)->check($input, $ability, null, $parameters),
            parameters: ['ability' => $ability, ...$parameters],
            description: $description
        );

        return $this;
    }

    /**
     * Add a guard that checks if user can perform the transition event via policy.
     *
     * @param  array<string, mixed>  $parameters  Additional parameters for the policy
     * @param  string|null  $description  Optional description for debugging
     */
    public function policyCanTransition(array $parameters = [], ?string $description = null): self
    {
        if ($this->fluentFrom === null || $this->fluentTo === null) {
            throw new LogicException('policyCanTransition() must be called after from() and to() in a transition definition.');
        }

        $description = $description ?? 'Policy check: can transition';

        $this->fluentGuards[] = new TransitionGuard(
            callable: fn ($input) => app(\Fsm\Guards\PolicyGuard::class)->canTransition($input, null, $parameters),
            parameters: $parameters,
            description: $description
        );

        return $this;
    }

    /**
     * Add a guard with high priority that stops execution on failure.
     *
     * @param  string|Closure|array<string>  $callable
     * @param  array<string, mixed>  $parameters
     */
    public function criticalGuard(string|Closure|array $callable, array $parameters = [], ?string $description = null): self
    {
        if ($this->fluentFrom === null || $this->fluentTo === null) {
            throw new LogicException('criticalGuard() must be called after from() and to() in a transition definition.');
        }

        /** @var class-string|Closure|array<string> $callableParam */
        $callableParam = $callable;
        $this->fluentGuards[] = new TransitionGuard(
            callable: $callableParam,
            parameters: $parameters,
            description: $description,
            priority: TransitionGuard::PRIORITY_CRITICAL,
            stopOnFailure: true
        );

        return $this;
    }

    /**
     * Alias for guard() method for backward compatibility
     *
     * @param  array<string, mixed>  $parameters
     */
    public function when(string|Closure $callable, ?string $description = null, array $parameters = []): self
    {
        return $this->guard($callable, $parameters, $description);
    }

    /**
     * @param  string|Closure|array<string>  $callable  Callbacks may use PHP's first-class callable syntax.
     * @param  array<string, mixed>  $parameters
     */
    public function before(string|Closure|array $callable, array $parameters = [], bool $queued = false): self
    {
        return $this->onTransitionCallback($callable, $parameters, false, $queued);
    }

    /**
     * @param  string|Closure|array<string>  $callable  Callbacks may use PHP's first-class callable syntax.
     * @param  array<string, mixed>  $parameters
     */
    public function after(string|Closure|array $callable, array $parameters = [], bool $queued = false): self
    {
        return $this->onTransitionCallback($callable, $parameters, true, $queued);
    }

    /**
     * @param  string|Closure|array<string>  $callable  Callbacks may use PHP's first-class callable syntax.
     * @param  array<string, mixed>  $parameters
     */
    public function action(string|Closure|array $callable, array $parameters = [], bool $runAfterTransition = false, ?string $description = null, bool $queued = false): self
    {
        if ($this->fluentFrom === null || $this->fluentTo === null) {
            throw new LogicException('action() must be called after from() and to() in a transition definition.');
        }

        /** @var class-string|Closure|array<string> $callableParam */
        $callableParam = $callable;
        $this->fluentActions[] = new TransitionAction($callableParam, $parameters, $runAfterTransition, TransitionAction::TIMING_AFTER, 50, null, $queued);

        return $this;
    }

    /**
     * @param  string|Closure|array<string>  $callable  Callbacks may use PHP's first-class callable syntax.
     * @param  array<string, mixed>  $parameters
     */
    public function onTransitionCallback(string|Closure|array $callable, array $parameters = [], bool $runAfterTransition = false, bool $queued = false): self
    {
        if ($this->fluentFrom === null || $this->fluentTo === null) {
            throw new LogicException('onTransitionCallback() must be called after from() and to() in a transition definition.');
        }

        /** @var class-string|Closure|array<string> $callableParam */
        $callableParam = $callable;
        $this->fluentOnTransitionCallbacks[] = new TransitionCallback($callableParam, $parameters, $runAfterTransition, TransitionCallback::TIMING_AFTER_SAVE, TransitionCallback::PRIORITY_NORMAL, null, true, $queued);

        return $this;
    }

    public function add(): self
    {
        $this->finalizeCurrentFluentTransition();

        return $this;
    }

    public function contextDto(string $dtoClass): self
    {
        $this->contextDtoClass = $dtoClass;

        return $this;
    }

    /**
     * Add a high-priority action that runs immediately during the transition.
     *
     * @param  string|Closure|array<string>  $callable
     * @param  array<string, mixed>  $parameters
     */
    public function immediateAction(string|Closure|array $callable, array $parameters = [], ?string $description = null): self
    {
        if ($this->fluentFrom === null || $this->fluentTo === null) {
            throw new LogicException('immediateAction() must be called after from() and to() in a transition definition.');
        }

        /** @var class-string|Closure|array<string> $callableParam */
        $callableParam = $callable;
        $this->fluentActions[] = new TransitionAction(
            $callableParam,
            $parameters,
            false, // Run during transition
            TransitionAction::TIMING_BEFORE,
            TransitionAction::PRIORITY_HIGH,
            $description
        );

        return $this;
    }

    /**
     * Add a queued action for heavy operations that should run asynchronously.
     *
     * @param  string|Closure|array<string>  $callable
     * @param  array<string, mixed>  $parameters
     */
    public function queuedAction(string|Closure|array $callable, array $parameters = [], ?string $description = null): self
    {
        if ($this->fluentFrom === null || $this->fluentTo === null) {
            throw new LogicException('queuedAction() must be called after from() and to() in a transition definition.');
        }

        /** @var class-string|Closure|array<string> $callableParam */
        $callableParam = $callable;
        $this->fluentActions[] = new TransitionAction(
            $callableParam,
            $parameters,
            true, // Run after transition
            TransitionAction::TIMING_AFTER,
            TransitionAction::PRIORITY_NORMAL,
            $description,
            true // Queued
        );

        return $this;
    }

    /**
     * Add a notification action that sends notifications about the state change.
     *
     * @param  string|Closure|array<string>  $callable
     * @param  array<string, mixed>  $parameters
     */
    public function notify(string|Closure|array $callable, array $parameters = []): self
    {
        return $this->queuedAction($callable, $parameters, 'Notification action');
    }

    /**
     * Add a logging action that records transition details.
     *
     * @param  string|Closure|array<string>  $callable
     * @param  array<string, mixed>  $parameters
     */
    public function log(string|Closure|array $callable, array $parameters = []): self
    {
        return $this->after($callable, $parameters, false);
    }

    /**
     * Add a cleanup action that runs after the transition to clean up resources.
     *
     * @param  string|Closure|array<string>  $callable
     * @param  array<string, mixed>  $parameters
     */
    public function cleanup(string|Closure|array $callable, array $parameters = []): self
    {
        if ($this->fluentFrom === null || $this->fluentTo === null) {
            throw new LogicException('cleanup() must be called after from() and to() in a transition definition.');
        }

        /** @var class-string|Closure|array<string> $callableParam */
        $callableParam = $callable;
        $this->fluentActions[] = new TransitionAction(
            $callableParam,
            $parameters,
            true, // Run after transition
            TransitionAction::TIMING_AFTER,
            TransitionAction::PRIORITY_LOW, // Low priority for cleanup
            'Cleanup action'
        );

        return $this;
    }

    /**
     * Add error handling for transition failures.
     *
     * @param  string|Closure|array<string>  $callable
     * @param  array<string, mixed>  $parameters
     */
    public function onFailure(string|Closure|array $callable, array $parameters = []): self
    {
        if ($this->fluentFrom === null || $this->fluentTo === null) {
            throw new LogicException('onFailure() must be called after from() and to() in a transition definition.');
        }

        /** @var class-string|Closure|array<string> $callableParam */
        $callableParam = $callable;
        $this->fluentActions[] = new TransitionAction(
            $callableParam,
            $parameters,
            false,
            TransitionAction::TIMING_ON_FAILURE,
            TransitionAction::PRIORITY_HIGH,
            'Failure handler'
        );

        return $this;
    }

    /**
     * Add success handling for completed transitions.
     *
     * @param  string|Closure|array<string>  $callable
     * @param  array<string, mixed>  $parameters
     */
    public function onSuccess(string|Closure|array $callable, array $parameters = []): self
    {
        if ($this->fluentFrom === null || $this->fluentTo === null) {
            throw new LogicException('onSuccess() must be called after from() and to() in a transition definition.');
        }

        /** @var class-string|Closure|array<string> $callableParam */
        $callableParam = $callable;
        $this->fluentActions[] = new TransitionAction(
            $callableParam,
            $parameters,
            true,
            TransitionAction::TIMING_ON_SUCCESS,
            TransitionAction::PRIORITY_NORMAL,
            'Success handler'
        );

        return $this;
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function getInitialState(): FsmStateEnum|string|null
    {
        $this->finalizeCurrentFluentTransition();

        return $this->initialState;
    }

    /**
     * @return array<string, StateDefinition>
     */
    public function getStateDefinitions(): array
    {
        $this->finalizeCurrentFluentTransition();

        return $this->states;
    }

    /**
     * @return array<TransitionDefinition>
     */
    public function getTransitionDefinitions(): array
    {
        $this->finalizeCurrentFluentTransition();

        return $this->transitions;
    }

    public function buildRuntimeDefinition(): FsmRuntimeDefinition
    {
        $this->finalizeCurrentFluentTransition();

        /** @var class-string $modelClass */
        $modelClass = $this->modelClass;

        /** @var array<int, StateDefinition> $stateDefinitions */
        $stateDefinitions = array_values($this->states);

        return new FsmRuntimeDefinition(
            modelClass: $modelClass,
            columnName: $this->columnName,
            stateDefinitions: $stateDefinitions,
            transitionDefinitions: $this->transitions,
            initialState: $this->initialState,
            contextDtoClass: $this->contextDtoClass
        );
    }

    /**
     * Sets the initial state for this FSM.
     */
    public function initialState(FsmStateEnum|string $state): self
    {
        $this->initialState = $state;
        $this->ensureStateIsDefined($state);

        return $this;
    }

    /**
     * Build and return the runtime definition.
     * This also registers the definition with the FsmRegistry if available.
     */
    public function build(): FsmRuntimeDefinition
    {
        $runtimeDefinition = $this->buildRuntimeDefinition();

        // If we have a Laravel container (common in tests/runtime), register the
        // definition with the FsmRegistry so it is immediately available to
        // services like FsmEngineService without requiring discovery.
        try {
            /** @var \Fsm\FsmRegistry|null $registry */
            $registry = \Illuminate\Support\Facades\App::make(\Fsm\FsmRegistry::class);
        } catch (\Throwable) {
            $registry = null; // Outside of a Laravel container – just ignore.
        }

        if ($registry instanceof \Fsm\FsmRegistry) {
            $registry->registerDefinition($this->modelClass, $this->columnName, $runtimeDefinition);
        }

        return $runtimeDefinition;
    }

    /**
     * Remove a transition from the FSM definition.
     */
    public function removeTransition(
        FsmStateEnum|string|null $fromState,
        FsmStateEnum|string $toState,
        string $event
    ): self {
        $fromStateValue = $fromState ? self::normalizeStateValue($fromState) : null;
        $toStateValue = self::normalizeStateValue($toState);

        $this->transitions = array_values(array_filter(
            $this->transitions,
            fn (TransitionDefinition $transition) => ! (self::normalizeStateValue($transition->fromState) === $fromStateValue &&
                self::normalizeStateValue($transition->toState) === $toStateValue &&
                $transition->event === $event)
        ));

        return $this;
    }
}
