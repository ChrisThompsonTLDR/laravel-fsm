<?php

declare(strict_types=1);

namespace Fsm\Traits;

use Fsm\Constants;
use Fsm\Contracts\FsmStateEnum;
use Fsm\FsmRegistry;
use Fsm\Services\FsmEngineService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * Trait HasFsm
 *
 * @mixin Model
 */
trait HasFsm
{
    /* existing low-level helpers … */

    /**
     * Fluent helper: $model->fsm()->trigger('event')
     */
    public function fsm(?string $column = null): object
    {
        $column ??= config('fsm.default_column_name', 'status');
        $model = $this;

        return new class($model, $column)
        {
            private Model $model;

            private string $column;

            private FsmRegistry $registry;

            private FsmEngineService $engine;

            public function __construct(Model $model, string $column)
            {
                $this->model = $model;
                $this->column = $column;
                $this->registry = App::make(FsmRegistry::class);
                $this->engine = App::make(FsmEngineService::class);
            }

            private function mapEvent(string $event): string
            {
                $definition = $this->registry->getDefinition($this->model::class, $this->column);
                if ($definition) {
                    $currentState = $this->model->getAttribute($this->column);
                    $currentStateValue = match (true) {
                        $currentState instanceof FsmStateEnum => $currentState->value,
                        $currentState === null => null,
                        default => (string) $currentState,
                    };

                    $wildcardFallback = null;

                    foreach ($definition->transitions as $transition) {
                        if ($transition->event !== $event) {
                            continue;
                        }

                        $fromState = $transition->fromState;
                        $fromStateValue = match (true) {
                            $fromState instanceof FsmStateEnum => $fromState->value,
                            $fromState === null => null,
                            default => (string) $fromState,
                        };

                        if ($fromStateValue === $currentStateValue) {
                            return $this->normalizeStateValue($transition->toState);
                        }

                        if ($fromStateValue === null || $fromStateValue === Constants::STATE_WILDCARD) {
                            $wildcardFallback ??= $transition;
                        }
                    }

                    if ($wildcardFallback !== null) {
                        return $this->normalizeStateValue($wildcardFallback->toState);
                    }
                }

                // Fallback: assume caller passed the *state* directly
                return $event;
            }

            private function normalizeStateValue(FsmStateEnum|string|null $state): string
            {
                if ($state instanceof FsmStateEnum) {
                    return $state->value;
                }

                if ($state === null) {
                    throw new \LogicException('FSM transition must define a non-null target state.');
                }

                return (string) $state;
            }

            public function trigger(string $event, ?ArgonautDTOContract $ctx = null): Model
            {
                $to = $this->mapEvent($event);

                return $this->engine->performTransition($this->model, $this->column, $to, $ctx);
            }

            public function can(string $event, ?ArgonautDTOContract $ctx = null): bool
            {
                $to = $this->mapEvent($event);

                return $this->engine->canTransition($this->model, $this->column, $to, $ctx);
            }

            public function dryRun(string $event, ?ArgonautDTOContract $ctx = null): array
            {
                $to = $this->mapEvent($event);

                return $this->engine->dryRunTransition($this->model, $this->column, $to, $ctx);
            }
        };
    }

    /**
     * Get the FSM engine service instance.
     */
    protected function fsmEngine(): FsmEngineService
    {
        return App::make(FsmEngineService::class);
    }

    /**
     * Get the current state of a specific FSM on the model.
     *
     * @param  string|null  $columnName  The FSM state column, defaults to config('fsm.default_column_name', 'status').
     * @return FsmStateEnum|string|null The current state, or null if not set.
     */
    public function getFsmState(?string $columnName = null): FsmStateEnum|string|null
    {
        $actualColumnName = $columnName ?? config('fsm.default_column_name', 'status');
        if (! ($this instanceof Model)) {
            throw new \LogicException('HasFsm trait must be used on an Eloquent Model.');
        }

        return $this->fsmEngine()->getCurrentState($this, $actualColumnName);
    }

    /**
     * Check if a transition to the given state is possible for a specific FSM.
     *
     * @param  string|null  $columnName  The FSM state column. Defaults to config('fsm.default_column_name', 'status').
     * @param  FsmStateEnum|string  $toState  The target state.
     * @param  ArgonautDTOContract|null  $context  Optional context DTO for guards.
     * @return bool True if the transition is possible, false otherwise.
     */
    public function canTransitionFsm(?string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context = null): bool
    {
        $actualColumnName = $columnName ?? config('fsm.default_column_name', 'status');
        if (! is_string($actualColumnName) || empty($actualColumnName)) {
            throw new \InvalidArgumentException('FSM column name must be a non-empty string.');
        }
        if (! ($this instanceof Model)) {
            throw new \LogicException('HasFsm trait must be used on an Eloquent Model.');
        }

        return $this->fsmEngine()->canTransition($this, $actualColumnName, $toState, $context);
    }

    /**
     * Transition the FSM to the given state for a specific FSM column.
     *
     * @param  string|null  $columnName  The FSM state column. Defaults to config('fsm.default_column_name', 'status').
     * @param  FsmStateEnum|string  $toState  The target state.
     * @param  ArgonautDTOContract|null  $context  Optional context DTO for guards, callbacks, and actions.
     * @return static The model instance.
     *
     * @throws \Fsm\Exceptions\FsmTransitionFailedException If the transition fails.
     */
    public function transitionFsm(?string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context = null): static
    {

        $actualColumnName = $columnName ?? config('fsm.default_column_name', 'status');
        if (! is_string($actualColumnName) || empty($actualColumnName)) {
            throw new \InvalidArgumentException('FSM column name must be a non-empty string.');
        }
        if (! ($this instanceof Model)) {
            throw new \LogicException('HasFsm trait must be used on an Eloquent Model.');
        }

        /** @var static */
        return $this->fsmEngine()->performTransition($this, $actualColumnName, $toState, $context);
    }

    /**
     * Simulate a transition for a specific FSM and get the outcome without persisting changes.
     *
     * @param  string|null  $columnName  The FSM state column. Defaults to config('fsm.default_column_name', 'status').
     * @param  FsmStateEnum|string  $toState  The target state.
     * @param  ArgonautDTOContract|null  $context  Optional context DTO.
     * @return array{can_transition: bool, from_state: string, to_state: string, message: string, reason?: string} An array containing details of the dry run outcome.
     */
    public function dryRunFsm(?string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context = null): array
    {
        $actualColumnName = $columnName ?? config('fsm.default_column_name', 'status');
        if (! is_string($actualColumnName) || empty($actualColumnName)) {
            throw new \InvalidArgumentException('FSM column name must be a non-empty string.');
        }
        if (! ($this instanceof Model)) {
            throw new \LogicException('HasFsm trait must be used on an Eloquent Model.');
        }

        return $this->fsmEngine()->dryRunTransition($this, $actualColumnName, $toState, $context);
    }

    /**
     * Initialize FSM states when a model is created.
     * This method can be overridden in models if specific initial states need to be set
     * based on multiple FSMs or complex logic.
     * By default, it tries to set the initial state for any FSMs defined for this model
     * if the current state column is null.
     */
    protected static function bootHasFsm(): void
    {
        $initializer = function (Model $model): void {
            $model->applyFsmInitialStates();
        };

        static::creating($initializer);
    }

    protected function applyFsmInitialStates(): void
    {
        /** @var FsmRegistry $registry */
        $registry = App::make(FsmRegistry::class);

        foreach ($registry->getDefinitionsForModel(static::class) as $column => $definition) {
            // Always set initial state if attribute is null, even if explicitly set to null
            if ($this->getAttribute($column) === null && $definition->initialState !== null) {
                $initial = $definition->initialState instanceof FsmStateEnum
                    ? $definition->initialState->value
                    : (string) $definition->initialState;

                $this->setAttribute($column, $initial);
            }
        }
    }
}
