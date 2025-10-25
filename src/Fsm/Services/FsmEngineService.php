<?php

declare(strict_types=1);

namespace Fsm\Services;

use Closure;
use Fsm\Constants;
use Fsm\Contracts\FsmStateEnum;
use Fsm\Data\Dto;
use Fsm\Data\FsmRuntimeDefinition;
use Fsm\Data\TransitionAction;
use Fsm\Data\TransitionCallback;
use Fsm\Data\TransitionDefinition;
use Fsm\Data\TransitionGuard;
use Fsm\Data\TransitionInput;
use Fsm\Events\StateTransitioned;
use Fsm\Events\TransitionAttempted;
use Fsm\Events\TransitionFailed;
use Fsm\Events\TransitionSucceeded;
use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\FsmRegistry;
use Fsm\Traits\StateNameStringConversion;
use Fsm\Verbs\FsmTransitioned;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Throwable;
use Thunk\Verbs\Facades\Verbs;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * Core engine for FSM state transitions and validation.
 *
 * This service handles the execution of state transitions, guard evaluation,
 * action execution, and event emission. It coordinates between the FSM registry,
 * logging service, and metrics collection to provide a complete state machine
 * execution environment.
 *
 * Key responsibilities:
 * - Validate and execute state transitions
 * - Evaluate guard conditions with proper error handling
 * - Execute transition actions in the correct order
 * - Emit state transition events for logging and replay
 * - Manage database transactions for atomic state changes
 */
class FsmEngineService
{
    use StateNameStringConversion;

    public function __construct(
        private readonly FsmRegistry $registry,
        private readonly FsmLogger $logger,
        private readonly FsmMetricsService $metrics,
        private readonly DatabaseManager $db,
        private readonly ConfigRepository $config
    ) {}

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function getDefinition(string $modelClass, string $columnName): FsmRuntimeDefinition
    {
        $definition = $this->registry->getDefinition($modelClass, $columnName);
        if (! $definition) {
            throw new \LogicException("FSM definition not found for model {$modelClass} and column {$columnName}.");
        }

        return $definition;
    }

    protected static function getStateValue(FsmStateEnum|string|null $state): ?string
    {
        return self::stateToString($state);
    }

    protected static function getStateValueNonNull(FsmStateEnum|string $state): string
    {
        $result = self::stateToString($state);
        if ($result === null) {
            throw new \LogicException('State value cannot be null in getStateValueNonNull.');
        }

        return $result;
    }

    /**
     * Get the string value for a non-null state.
     */
    protected static function getStateString(FsmStateEnum|string $state): string
    {
        return $state instanceof FsmStateEnum ? $state->value : (string) $state;
    }

    protected static function normalizeStateForEvent(FsmStateEnum|string|null $state): ?string
    {
        if ($state instanceof FsmStateEnum) {
            return $state->value;
        }

        return $state !== null ? (string) $state : null;
    }

    /**
     * Return the string value for a non-null FSM state.
     */
    private static function getNonNullStateValue(FsmStateEnum|string $state): string
    {
        return $state instanceof FsmStateEnum ? $state->value : (string) $state;
    }

    /**
     * Get the current state of the FSM for the given model and column.
     *
     * This method handles both enum and string states, with automatic enum
     * conversion when possible. If the model's state column is null, it
     * returns the initial state defined in the FSM definition.
     *
     * @param  Model  $model  The Eloquent model instance
     * @param  string  $columnName  The state column name
     * @return FsmStateEnum|string|null The current state, or null if no initial state is defined
     */
    public function getCurrentState(Model $model, string $columnName): FsmStateEnum|string|null
    {
        $stateValue = $model->getAttribute($columnName);
        $definition = $this->getDefinition($model::class, $columnName);

        if ($stateValue === null) {
            $initial = $definition->initialState;

            return $initial;
        }

        $firstStateDefinitionInFsm = null;
        if (! empty($definition->states)) {
            $firstStateDefinitionInFsm = $definition->states[array_key_first($definition->states)] ?? null;
        }

        // Try to infer the enum type from the initial state or the first defined state.
        $referenceStateForEnumCheck = $definition->initialState ?? ($firstStateDefinitionInFsm ? $firstStateDefinitionInFsm->name : null);

        if ($referenceStateForEnumCheck instanceof FsmStateEnum) {
            $enumClass = get_class($referenceStateForEnumCheck);
            // Ensure it's a backed enum by checking for tryFrom method, which all backed enums have.
            if (enum_exists($enumClass, true) && method_exists($enumClass, 'tryFrom')) {
                /** @var class-string<\BackedEnum&FsmStateEnum> $enumClass */
                if ($stateValue instanceof FsmStateEnum) {
                    return $stateValue;
                }
                $enumCase = $enumClass::tryFrom((string) $stateValue);
                if ($enumCase) {
                    return $enumCase;
                }
            }
        }

        if ($stateValue instanceof FsmStateEnum) {
            return $stateValue;
        }

        return $stateValue;
    }

    /**
     * Check if a transition is possible without executing it.
     *
     * This method performs a dry-run of the transition validation process,
     * including guard condition evaluation, without actually changing the model's
     * state or executing any actions.
     *
     * @param  Model  $model  The Eloquent model instance
     * @param  string  $columnName  The state column name
     * @param  FsmStateEnum|string  $toState  The target state
     * @param  ArgonautDTOContract|null  $context  Optional transition context
     * @return bool True if the transition would succeed, false otherwise
     */
    public function canTransition(Model $model, string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context = null): bool
    {
        try {
            $this->processTransition($model, $columnName, $toState, $context, true, microtime(true));

            return true;
        } catch (FsmTransitionFailedException) {
            return false;
        } catch (Throwable $e) {
            // Other unexpected errors during a "can" check should probably still result in false
            // and potentially be logged if severe.
            report($e); // Or log specifically

            return false;
        }
    }

    /**
     * @return array{can_transition: bool, from_state: string|null, to_state: string, message: string, reason?: string}
     */
    public function dryRunTransition(Model $model, string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context = null): array
    {
        // This is a simplified dry run. A more advanced one might return collected side effects.
        // For now, it validates and returns the outcome.
        $currentActualState = $this->getCurrentState($model, $columnName); // FsmStateEnum|string|null
        $originalStateStringValue = self::normalizeStateForEvent($currentActualState);
        $targetStateStringValue = self::getNonNullStateValue($toState);

        Event::dispatch(new TransitionAttempted($model, $columnName, $originalStateStringValue, $targetStateStringValue, $context));

        try {
            $this->processTransition($model, $columnName, $toState, $context, true, microtime(true)); // isDryRun = true

            // Note: TransitionSucceeded is NOT dispatched for dry runs.
            // It only fires when state actually changes (see performTransition).
            // Dry runs only validate guards without persisting state changes.

            return [
                'can_transition' => true,
                'from_state' => $originalStateStringValue,
                'to_state' => $targetStateStringValue,
                'message' => 'Dry run: Transition from '.($originalStateStringValue ?? '(null)').' to '.$targetStateStringValue.' is possible.',
            ];
        } catch (FsmTransitionFailedException $e) {
            // Note: TransitionFailed is NOT dispatched for dry runs.
            // Dry runs are validation checks, not actual transition attempts.

            return [
                'can_transition' => false,
                'from_state' => $originalStateStringValue,
                'to_state' => $targetStateStringValue,
                'reason' => $e->reason,
                'message' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            // Note: TransitionFailed is NOT dispatched for dry runs.
            report($e);

            return [
                'can_transition' => false,
                'from_state' => $originalStateStringValue,
                'to_state' => $targetStateStringValue,
                'reason' => 'Unexpected error during dry run',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function performTransition(Model $model, string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context = null): Model
    {
        $start = microtime(true);
        $fromState = $this->getCurrentState($model, $columnName); // This is FsmStateEnum|string|null
        $toStateValue = self::getNonNullStateValue($toState); // $toState is non-null

        // Pass original types to events
        Event::dispatch(new TransitionAttempted($model, $columnName, self::normalizeStateForEvent($fromState), self::normalizeStateForEvent($toState), $context));
        $useTransactions = $this->config->get('fsm.use_transactions', true);
        try {
            if ($useTransactions) {
                $result = $this->db->transaction(fn () => $this->processTransition($model, $columnName, $toState, $context, false, $start), 3);
            } else {
                $result = $this->processTransition($model, $columnName, $toState, $context, false, $start);
            }

            return $result;
        } catch (FsmTransitionFailedException $e) {
            Event::dispatch(new TransitionFailed($model, $columnName, self::normalizeStateForEvent($fromState), self::normalizeStateForEvent($toState), $context, $e));
            if ($this->config->get('fsm.logging.enabled', true) && $this->config->get('fsm.logging.log_failures', true)) {
                $duration = (int) ((microtime(true) - $start) * 1000);
                $this->logger->logFailure($model, $columnName, self::getStateValue($fromState), $toState, null, $context, $e, $duration);
            }
            try {
                $this->metrics->record($model, $columnName, $fromState, $toState, false, $context);
            } catch (Throwable $metricsException) {
                // Log metrics failure but don't mask the original exception
                report($metricsException);
            }
            throw $e;
        } catch (Throwable $e) {
            // Catch any other exception during processing, wrap it and dispatch our event.
            $wrappedException = FsmTransitionFailedException::forCallbackException(self::getStateValue($fromState), $toStateValue, 'unknown processing step', $e, $model::class, $columnName);
            Event::dispatch(new TransitionFailed($model, $columnName, self::normalizeStateForEvent($fromState), self::normalizeStateForEvent($toState), $context, $wrappedException));
            // Log the failure
            if ($this->config->get('fsm.logging.enabled', true) && $this->config->get('fsm.logging.log_failures', true)) {
                $duration = (int) ((microtime(true) - $start) * 1000);
                $this->logger->logFailure($model, $columnName, self::getStateValue($fromState), $toState, null, $context, $wrappedException, $duration);
            }
            try {
                $this->metrics->record($model, $columnName, $fromState, $toState, false, $context);
            } catch (Throwable $metricsException) {
                // Log metrics failure but don't mask the original exception
                report($metricsException);
            }
            throw $wrappedException;
        }
    }

    protected function findTransition(FsmRuntimeDefinition $definition, FsmStateEnum|string|null $fromState, FsmStateEnum|string $toState): ?TransitionDefinition
    {
        $fromValue = $this->normalizeStateForTransition($fromState);
        $toValue = $this->normalizeStateForTransition($toState);
        // First, look for an exact match
        foreach ($definition->transitions as $transition) {
            $transitionFrom = $this->normalizeStateForTransition($transition->fromState);
            $transitionTo = $this->normalizeStateForTransition($transition->toState);
            if ($transitionFrom === $fromValue && $transitionTo === $toValue) {
                return $transition;
            }
        }
        // If no exact match, look for a wildcard fromState
        foreach ($definition->transitions as $transition) {
            $transitionFrom = $this->normalizeStateForTransition($transition->fromState);
            $transitionTo = $this->normalizeStateForTransition($transition->toState);
            if ($transitionFrom === Constants::STATE_WILDCARD && $transitionTo === $toValue) {
                return $transition;
            }
        }

        return null;
    }

    /**
     * Normalize a state value for transition comparison. Null stays null, enums to value, strings as-is.
     */
    protected function normalizeStateForTransition(FsmStateEnum|string|null $state): ?string
    {
        if ($state instanceof FsmStateEnum) {
            return $state->value;
        }
        if ($state === null) {
            return null;
        }

        return (string) $state;
    }

    private static function statesEqual(FsmStateEnum|string|null $a, FsmStateEnum|string|null $b): bool
    {
        // Normalize both sides to string for comparison
        $aVal = $a instanceof FsmStateEnum ? $a->value : ($a !== null ? (string) $a : null);
        $bVal = $b instanceof FsmStateEnum ? $b->value : ($b !== null ? (string) $b : null);

        return $aVal === $bVal;
    }

    protected function processTransition(Model $model, string $columnName, FsmStateEnum|string $toState, ?ArgonautDTOContract $context, bool $isDryRun, float $start): Model
    {
        $definition = $this->getDefinition($model::class, $columnName);
        $currentState = $this->getCurrentState($model, $columnName);
        $currentStateValue = $currentState === null ? null : self::getStateValue($currentState); // This check is now valid
        $targetStateValue = self::getNonNullStateValue($toState);

        // Find the transition definition for the given current and target states.
        $transitionDef = $this->findTransition($definition, $currentState, $toState);

        // Allow transitioning to the same state only if an explicit transition (loopback) is defined.
        if ($currentStateValue === $targetStateValue && ! $transitionDef) {
            return $model;
        }

        if (! $transitionDef) {
            throw FsmTransitionFailedException::forInvalidTransition(
                self::getStateValue($currentState),
                $toState,
                $model::class,
                $columnName
            );
        }

        $transitionInput = new TransitionInput(
            model: $model,
            fromState: $currentState,
            toState: $toState,
            context: $context,
            event: $transitionDef->event,
            isDryRun: $isDryRun,
        );

        // 1. Check Guards (short-circuit: if any guard fails, throw before any state change)
        $this->executeGuards($transitionDef, $transitionInput, $columnName);
        if ($isDryRun) {
            // For dry runs or canTransition, stop after guards.
            // No actual state change, no "after" hooks run.
            return $model;
        }

        // --- Actual Transition Processing (not a dry run) ---
        $fromStateDef = $definition->getStateDefinition($currentState);
        $toStateDef = $definition->getStateDefinition($toState);

        // 2. Execute 'onExit' callbacks for the current state (before changing state)
        if ($fromStateDef) {
            $this->executeCallbacks($fromStateDef->onExitCallbacks, $transitionInput, 'onExit', $columnName);
        }

        // 3. Execute 'onTransition' callbacks (before changing state)
        $this->executeCallbacks($transitionDef->onTransitionCallbacks->filter(fn (TransitionCallback $cb) => ! $cb->runAfterTransition), $transitionInput, 'onTransition (before)', $columnName);

        // 4. Execute Actions (before changing state, typically those not marked runAfterTransition)
        $this->executeActions($transitionDef->actions->filter(fn (TransitionAction $act) => ! $act->runAfterTransition), $transitionInput, 'action (before)', $columnName);

        // Debug: Log HP before state change and save
        if ($model->getAttribute('hp') !== null && $this->config->get('fsm.debug', false)) {
            \Log::info('[FSM DEBUG] Before save', [
                'hp' => $model->getAttribute('hp'),
                'id' => $model->getAttribute('id'),
            ]);
        }
        // 5. Update model state with optimistic concurrency check
        if ($model->exists) {
            $updated = $model->newQuery()
                ->whereKey($model->getKey())
                ->where($columnName, $currentStateValue)
                ->update([$columnName => $targetStateValue]);
            if ($updated === 0) {
                throw FsmTransitionFailedException::forConcurrentModification(
                    self::getStateValue($currentState),
                    $toState,
                    $model::class,
                    $columnName
                );
            }
            // Only update the model's attribute; do not call refresh to avoid discarding in-memory changes
            $model->setAttribute($columnName, $targetStateValue);
        } else {
            $model->setAttribute($columnName, $targetStateValue);
            $model->save();
        }
        // Debug: Log HP after save
        if ($model->getAttribute('hp') !== null && $this->config->get('fsm.debug', false)) {
            \Log::info('[FSM DEBUG] After save', [
                'hp' => $model->getAttribute('hp'),
                'id' => $model->getAttribute('id'),
            ]);
        }
        // --- Post-Save Operations ---
        // 6. Execute 'onTransition' callbacks (after changing state and saving)
        $this->executeCallbacks($transitionDef->onTransitionCallbacks->filter(fn (TransitionCallback $cb) => $cb->runAfterTransition), $transitionInput, 'onTransition (after)', $columnName);

        // 7. Execute Actions (after changing state and saving, typically those marked runAfterTransition)
        $this->executeActions($transitionDef->actions->filter(fn (TransitionAction $act) => $act->runAfterTransition), $transitionInput, 'action (after)', $columnName);

        // 8. Execute 'onEntry' callbacks for the new state (after changing state and saving)
        if ($toStateDef) {
            $this->executeCallbacks($toStateDef->onEntryCallbacks, $transitionInput, 'onEntry', $columnName);
        }

        // 9. Logging
        if ($this->config->get('fsm.logging.enabled', true)) {
            $duration = (int) ((microtime(true) - $start) * 1000);
            $this->logger->logTransition(
                $model,
                $columnName,
                self::getStateValue($currentState),
                $toState,
                $this->filterContextForLogging($context),
                $transitionDef->event,
                null,
                $duration
            );
        }

        // 10. Dispatch Success Events
        Event::dispatch(new TransitionSucceeded(
            model: $model,
            columnName: $columnName,
            fromState: self::normalizeStateForEvent($currentState),
            toState: self::getNonNullStateValue($toState),
        ));

        // Dispatch standardized StateTransitioned event for event logging
        Event::dispatch(new StateTransitioned(
            model: $model,
            columnName: $columnName,
            fromState: self::normalizeStateForEvent($currentState),
            toState: self::getNonNullStateValue($toState),
            transitionName: $transitionDef->event ?? 'unknown',
            timestamp: now(),
            context: $context,
            metadata: [
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                'source' => 'fsm_engine',
            ]
        ));
        try {
            $this->metrics->record($model, $columnName, $currentState, $toState, true, $context);
        } catch (Throwable $metricsException) {
            // Log metrics failure but don't interrupt the successful transition
            report($metricsException);
        }
        // 11. Dispatch Verb
        if ($this->config->get('fsm.verbs.dispatch_transitioned_verb', true)) {
            FsmTransitioned::record(
                modelId: (string) $model->getKey(),
                modelType: get_class($model),
                fsmColumn: $columnName,
                fromState: self::getStateValue($currentState),
                toState: $toState,
                result: FsmTransitioned::RESULT_SUCCESS,
                context: $this->filterContextForLogging($context),
                transitionEvent: $transitionDef->event
            );
        }

        return $model;
    }

    protected function executeGuards(TransitionDefinition $transitionDef, TransitionInput $input, string $columnName): void
    {
        if ($transitionDef->guards->count() === 0) {
            return;
        }

        // Sort guards by priority (highest first) and process them
        // Manually extract objects from collection and sort them
        $guardObjects = [];
        foreach ($transitionDef->guards as $guard) {
            $guardObjects[] = $guard;
        }
        $sortedGuards = collect($guardObjects)
            ->sortByDesc(fn (TransitionGuard $guard) => $guard->priority)
            ->values(); // Reset array keys to be sequential

        $failedGuards = collect();

        foreach ($sortedGuards as $guard) {
            /** @var TransitionGuard $guard */
            $guardDescription = $this->formatGuardDescription($guard);
            $debugEnabled = $this->config->get('fsm.debug', false);

            if ($debugEnabled) {
                \Log::info('[FSM Guard Debug] Executing guard', [
                    'guard' => $guardDescription,
                    'priority' => $guard->priority,
                    'stop_on_failure' => $guard->stopOnFailure,
                    'from_state' => self::getStateValue($input->fromState),
                    'to_state' => self::getStateValue($input->toState),
                    'event' => $input->event,
                    'model_id' => $input->model->getKey(),
                ]);
            }

            try {
                $start = microtime(true);
                $result = $this->executeGuard($guard, $input);
                $duration = (microtime(true) - $start) * 1000;

                if ($debugEnabled) {
                    \Log::info('[FSM Guard Debug] Guard execution completed', [
                        'guard' => $guardDescription,
                        'result' => $result,
                        'duration_ms' => round($duration, 2),
                    ]);
                }

                if ($result !== true) { // Guards must return exactly true to pass
                    if ($debugEnabled) {
                        \Log::warning('[FSM Guard Debug] Guard failed', [
                            'guard' => $guardDescription,
                            'result' => $result,
                            'expected' => true,
                            'stop_on_failure' => $guard->stopOnFailure,
                        ]);
                    }

                    if ($guard->stopOnFailure) {
                        throw FsmTransitionFailedException::forGuardFailure(
                            self::getStateValue($input->fromState),
                            $input->toState,
                            $guardDescription,
                            $input->model::class,
                            $columnName
                        );
                    }

                    $failedGuards->push([
                        'guard' => $guard,
                        'reason' => 'Guard returned false',
                    ]);
                }

            } catch (FsmTransitionFailedException $e) {
                // Re-throw our specific exception with enhanced context
                if ($debugEnabled) {
                    \Log::error('[FSM Guard Debug] Guard threw transition exception', [
                        'guard' => $guardDescription,
                        'exception' => $e->getMessage(),
                        'reason' => $e->reason,
                    ]);
                }
                throw $e;
            } catch (Throwable $e) {
                if ($debugEnabled) {
                    \Log::error('[FSM Guard Debug] Guard threw unexpected exception', [
                        'guard' => $guardDescription,
                        'exception' => $e->getMessage(),
                        'exception_class' => get_class($e),
                        'trace' => $e->getTraceAsString(),
                        'stop_on_failure' => $guard->stopOnFailure,
                    ]);
                }

                if ($guard->stopOnFailure) {
                    throw FsmTransitionFailedException::forCallbackException(
                        self::getStateValue($input->fromState),
                        $input->toState,
                        "guard {$guardDescription}",
                        $e,
                        $input->model::class,
                        $columnName
                    );
                }

                $failedGuards->push([
                    'guard' => $guard,
                    'reason' => $e->getMessage(),
                    'exception' => $e,
                ]);
            }
        }

        // If we have failed guards (and none had stopOnFailure=true), throw an exception
        if ($failedGuards->isNotEmpty()) {
            if ($failedGuards->count() === 1) {
                // Single guard failure - use the standard format
                $failure = $failedGuards->first();
                throw FsmTransitionFailedException::forGuardFailure(
                    self::getStateValue($input->fromState),
                    $input->toState,
                    $this->formatGuardDescription($failure['guard']),
                    $input->model::class,
                    $columnName
                );
            } else {
                // Multiple guard failures - use the combined format
                $failureReasons = $failedGuards->map(fn ($failure) => $this->formatGuardDescription($failure['guard']).': '.$failure['reason']
                )->join(', ');

                throw FsmTransitionFailedException::forGuardFailure(
                    self::getStateValue($input->fromState),
                    $input->toState,
                    "Multiple guards failed: {$failureReasons}",
                    $input->model::class,
                    $columnName
                );
            }
        }
    }

    /**
     * Execute a single guard and return its result.
     *
     * @throws Throwable
     */
    protected function executeGuard(TransitionGuard $guard, TransitionInput $input): mixed
    {
        $parameters = array_merge($guard->parameters, ['input' => $input]);
        $callable = $guard->callable;

        return $this->executeCallableWithInstance($callable, $parameters);
    }

    /**
     * Format a guard description for debugging and error reporting.
     */
    protected function formatGuardDescription(TransitionGuard $guard): string
    {
        if ($guard->description) {
            return "Guard [{$guard->description}]";
        }

        if ($guard->name) {
            return "Guard [{$guard->name}]";
        }

        $displayName = $guard->getDisplayName();

        return "Guard [{$displayName}]";
    }

    /**
     * @param  iterable<int, TransitionCallback>  $callbacks
     */
    protected function executeCallbacks(iterable $callbacks, TransitionInput $input, string $callbackType, string $columnName): void
    {
        foreach ($callbacks as $callback) {
            /** @var TransitionCallback $callback */
            try {
                $parameters = array_merge($callback->parameters, ['input' => $input]);
                $callable = $callback->callable;

                if ($callback->queued) {
                    // For queued callbacks, we need to convert to string format
                    if (is_array($callable) && count($callable) === 2) {
                        // Check if the first element is an object instance
                        if (is_object($callable[0])) {
                            throw new \LogicException('Queued callbacks cannot use object instances. Use string callables instead.');
                        }
                        $callableString = $this->stringifyCallable($callable);
                    } elseif ($callable instanceof \Closure) {
                        throw new \LogicException('Queued callbacks cannot use closures. Use string callables instead.');
                    } elseif (is_object($callable) && method_exists($callable, '__invoke')) {
                        throw new \LogicException('Queued callbacks cannot use invokable objects. Use string callables instead.');
                    } elseif (is_string($callable)) {
                        $callableString = $callable;
                    } else {
                        throw new \LogicException('Queued callbacks only support string callables. Unsupported callable type: '.gettype($callable));
                    }

                    \Fsm\Jobs\RunCallbackJob::dispatch(
                        $callableString,
                        $callback->parameters,
                        $this->buildJobPayload($input)
                    );
                } else {
                    // For immediate execution, use the instance-aware method
                    $this->executeCallableWithInstance($callable, $parameters);
                }
            } catch (Throwable $e) {
                // Log or handle callback errors? For now, rethrow wrapped.
                // Depending on policy, some callback errors might not be fatal for the transition.
                throw FsmTransitionFailedException::forCallbackException(
                    self::getStateValue($input->fromState),
                    $input->toState,
                    $callbackType,
                    $e,
                    $input->model::class,
                    $columnName
                );
            }
        }
    }

    /**
     * @param  iterable<int, TransitionAction>  $actions
     */
    protected function executeActions(iterable $actions, TransitionInput $input, string $actionType, string $columnName): void
    {
        foreach ($actions as $action) {
            /** @var TransitionAction $action */
            try {
                // If $action->callable is a Verb class string, Thunk Verbs handles instantiation and firing.
                // If it's a Closure, app()->call() executes it.
                if (is_string($action->callable) && class_exists($action->callable) && is_subclass_of($action->callable, \Thunk\Verbs\Event::class)) {
                    // Thunk Verbs handles instantiation and firing via the static `fire` method.
                    /** @var class-string<\Thunk\Verbs\Event> $verbClass */
                    $verbClass = $action->callable;
                    if ($action->queued) {
                        \Fsm\Jobs\RunActionJob::dispatch(
                            $verbClass.'@fire',
                            $action->parameters,
                            $this->buildJobPayload($input)
                        );
                    } else {
                        $verbClass::fire(...array_merge($action->parameters, ['input' => $input]));
                    }
                } else {
                    $parameters = array_merge($action->parameters, ['input' => $input]);
                    $callable = $action->callable;

                    if ($action->queued) {
                        // For queued actions, we need to convert to string format
                        if (is_array($callable) && count($callable) === 2) {
                            // Check if the first element is an object instance
                            if (is_object($callable[0])) {
                                throw new \LogicException('Queued actions cannot use object instances. Use string callables instead.');
                            }
                            $callableString = $this->stringifyCallable($callable);
                        } elseif ($callable instanceof \Closure) {
                            throw new \LogicException('Queued actions cannot use closures. Use string callables instead.');
                        } elseif (is_object($callable) && method_exists($callable, '__invoke')) {
                            throw new \LogicException('Queued actions cannot use invokable objects. Use string callables instead.');
                        } elseif (is_string($callable)) {
                            $callableString = $callable;
                        } else {
                            throw new \LogicException('Queued actions only support string callables. Unsupported callable type: '.gettype($callable));
                        }

                        \Fsm\Jobs\RunActionJob::dispatch(
                            $callableString,
                            $action->parameters,
                            $this->buildJobPayload($input)
                        );
                    } else {
                        // For immediate execution, use the instance-aware method
                        $this->executeCallableWithInstance($callable, $parameters);
                    }
                }
            } catch (Throwable $e) {
                throw FsmTransitionFailedException::forCallbackException(
                    self::getStateValue($input->fromState),
                    $input->toState,
                    $actionType,
                    $e,
                    $input->model::class,
                    $columnName
                );
            }
        }
    }

    /**
     * Prepare payload for queued jobs.
     *
     * @return array<string,mixed>
     */
    private function buildJobPayload(TransitionInput $input): array
    {
        $contextPayload = null;
        $contextSerializationFailed = false;

        // Handle context serialization with proper error handling
        if ($input->context !== null) {
            try {
                $contextPayload = $input->contextPayload();

                // Check if serialization actually failed (returned null when context exists)
                if ($contextPayload === null) {
                    $contextSerializationFailed = true;
                    // Only log when not running in PHPUnit tests to avoid polluting test output
                    if (! defined('PHPUNIT_COMPOSER_INSTALL') && ! defined('__PHPUNIT_PHAR__')) {
                        \Log::error('[FSM] Context serialization failed during job payload build - queued job will receive null context', [
                            'context_class' => $input->context::class,
                            'model_class' => $input->model::class,
                            'model_id' => $input->model->getKey(),
                            'from_state' => self::getStateValue($input->fromState),
                            'to_state' => self::getStateValue($input->toState),
                            'event' => $input->event,
                            'reason' => 'contextPayload() returned null for non-null context',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                $contextSerializationFailed = true;
                // Only log when not running in PHPUnit tests to avoid polluting test output
                if (! defined('PHPUNIT_COMPOSER_INSTALL') && ! defined('__PHPUNIT_PHAR__')) {
                    \Log::error('[FSM] Context serialization exception during job payload build - queued job will receive null context', [
                        'context_class' => $input->context::class,
                        'model_class' => $input->model::class,
                        'model_id' => $input->model->getKey(),
                        'from_state' => self::getStateValue($input->fromState),
                        'to_state' => self::getStateValue($input->toState),
                        'event' => $input->event,
                        'exception' => $e->getMessage(),
                        'exception_class' => get_class($e),
                    ]);
                }

                // Ensure contextPayload is null when exception occurs
                $contextPayload = null;
            }
        }

        return [
            'model_class' => $input->model::class,
            'model_id' => $input->model->getKey(),
            'fromState' => $input->fromState,
            'toState' => $input->toState,
            'context' => $contextPayload,
            'event' => $input->event,
            'isDryRun' => $input->isDryRun,
            'mode' => $input->mode,
            'source' => $input->source,
            'metadata' => $input->metadata,
            'timestamp' => $input->timestamp,
            // Add metadata to track serialization failures for debugging
            '_context_serialization_failed' => $contextSerializationFailed,
        ];
    }

    /**
     * Convert array callable to string format for App::call.
     *
     * This method should only be called with class string callables, not object instances.
     * Object instances are handled separately in executeCallableWithInstance.
     *
     * @param  array{0: class-string, 1: string}  $callable
     */
    private function stringifyCallable(array $callable): string
    {
        if (is_object($callable[0])) {
            // This method should only be called for class string callables
            throw new \LogicException('stringifyCallable should not be called with object instances. Use executeCallableWithInstance instead.');
        }

        // For class strings, use the standard format
        return $callable[0].'@'.$callable[1];
    }

    /**
     * Execute a callable that may contain an object instance.
     *
     * This method provides consistent handling of all callable types by using
     * App::call for class strings and closures, and direct reflection for
     * object instances to ensure proper parameter resolution.
     *
     * @param  array{0: class-string|object, 1: string}|string|\Closure  $callable
     * @param  array<string, mixed>  $parameters
     */
    private function executeCallableWithInstance(mixed $callable, array $parameters): mixed
    {
        // Handle array callables consistently
        if (is_array($callable) && count($callable) === 2) {
            // For class strings, convert to string format for App::call
            if (is_string($callable[0])) {
                $callable = $this->stringifyCallable($callable);
            }
            // For object instances, use direct reflection for consistent parameter handling
            elseif (is_object($callable[0])) {
                return $this->executeObjectMethod($callable[0], $callable[1], $parameters);
            }
        }

        // At this point, $callable should be a string or closure for App::call
        /** @var callable(): mixed|string $callable */
        return App::call($callable, $parameters);
    }

    /**
     * Execute a method on an object instance using reflection for consistent parameter handling.
     *
     * This method provides the same parameter resolution logic as App::call, including:
     * - Named parameter resolution
     * - Positional parameter resolution
     * - Dependency injection for type-hinted parameters
     * - Proper null value handling (distinguishing between missing and explicit null)
     * - Default value resolution
     *
     * @param  object  $object  The object instance
     * @param  string  $method  The method name
     * @param  array<string, mixed>  $parameters  The parameters to pass
     */
    private function executeObjectMethod(object $object, string $method, array $parameters): mixed
    {
        try {
            $reflection = new \ReflectionMethod($object, $method);
        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException(
                "Failed to create reflection for method '{$method}' on class '".get_class($object)."': ".$e->getMessage(),
                0,
                $e
            );
        }

        // Check if the method is accessible (not private or protected)
        if (! $reflection->isPublic()) {
            $visibility = $reflection->isPrivate() ? 'private' : 'protected';
            throw new \InvalidArgumentException(
                "Cannot access {$visibility} method '{$method}' on class '".get_class($object)."'"
            );
        }

        // Resolve parameters using the same logic as App::call for consistency
        $args = [];
        foreach ($reflection->getParameters() as $param) {
            $paramName = $param->getName();
            $paramIndex = $param->getPosition();
            $paramType = $param->getType();

            // First, try to resolve from named parameters
            if (array_key_exists($paramName, $parameters)) {
                $args[] = $parameters[$paramName];
            }
            // Then try positional parameters
            elseif (array_key_exists($paramIndex, $parameters)) {
                $args[] = $parameters[$paramIndex];
            }
            // Try dependency injection for type-hinted parameters
            elseif ($paramType !== null && $this->canResolveFromContainer($paramType)) {
                try {
                    $args[] = $this->resolveFromContainer($paramType);
                } catch (\Throwable $e) {
                    // If dependency injection fails, fall back to default value or throw error
                    if ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        throw new \ArgumentCountError("Missing required parameter: {$paramName}");
                    }
                }
            }
            // Use default value if available
            elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            }
            // Required parameter missing
            else {
                throw new \ArgumentCountError("Missing required parameter: {$paramName}");
            }
        }

        return $reflection->invokeArgs($object, $args);
    }

    /**
     * Check if a parameter type can be resolved from the container.
     *
     * @param  \ReflectionType  $paramType  The parameter type to check
     * @return bool True if the parameter can be resolved from the container
     */
    private function canResolveFromContainer(\ReflectionType $paramType): bool
    {
        // Only handle named types for dependency injection
        if (! $paramType instanceof \ReflectionNamedType) {
            return false;
        }

        $typeName = $paramType->getName();

        // Skip built-in types and mixed
        if (in_array($typeName, ['string', 'int', 'float', 'bool', 'array', 'object', 'mixed', 'callable', 'iterable', 'resource'])) {
            return false;
        }

        // Skip nullable types for dependency injection
        if ($paramType->allowsNull()) {
            return false;
        }

        // Check if the class exists and is not abstract
        if (! class_exists($typeName) && ! interface_exists($typeName)) {
            return false;
        }

        return true;
    }

    /**
     * Resolve a parameter type from the container.
     *
     * @param  \ReflectionType  $paramType  The parameter type to resolve
     * @return mixed The resolved instance
     *
     * @throws \Throwable If resolution fails
     */
    private function resolveFromContainer(\ReflectionType $paramType): mixed
    {
        if (! $paramType instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException('Cannot resolve non-named type from container');
        }

        $typeName = $paramType->getName();

        // Use App::make to resolve from the container
        return App::make($typeName);
    }

    public function filterContextForLogging(?ArgonautDTOContract $context): ?ArgonautDTOContract
    {
        if (! $context) {
            return null;
        }
        $excluded = $this->config->get('fsm.logging.excluded_context_properties', []);
        if (! is_array($excluded) || $excluded === []) {
            return $context;
        }

        $filtered = collect($context->toArray())
            ->except($excluded)
            ->all();

        if ($filtered === $context->toArray()) {
            return $context;
        }

        $contextClass = $context::class;

        // Use the DTO's from() factory method to properly reconstruct the instance
        // This avoids TypeError when DTOs have positional scalar parameters
        if (is_subclass_of($contextClass, Dto::class)) {
            // Check if the Dto class has a static from() method with proper parameter compatibility
            if (method_exists($contextClass, 'from')) { // @phpstan-ignore-line
                try {
                    $reflection = new \ReflectionMethod($contextClass, 'from');
                    if ($reflection->isStatic() && $reflection->isPublic()) {
                        // Check parameter compatibility - the method should accept an array parameter
                        $parameters = $reflection->getParameters();
                        if (count($parameters) === 1) {
                            $param = $parameters[0];
                            $paramType = $param->getType();

                            // Check if the parameter accepts array or mixed using improved validation
                            if (self::parameterAcceptsArray($paramType)) {
                                return $contextClass::from($filtered); // @phpstan-ignore staticMethod.notFound
                            }
                        }
                    }
                } catch (\ReflectionException) {
                    // Method doesn't exist or is not accessible, continue to fallback
                }
            }
        }

        // Fallback for non-Dto ArgonautDTOContract implementations
        // Check if the class has a static from() method
        if (method_exists($contextClass, 'from')) {
            try {
                $reflection = new \ReflectionMethod($contextClass, 'from');
                if ($reflection->isStatic() && $reflection->isPublic()) {
                    // Check parameter compatibility - the method should accept an array parameter
                    $parameters = $reflection->getParameters();
                    // Ensure exactly one parameter is required for proper validation
                    if (count($parameters) === 1) {
                        $param = $parameters[0];
                        $paramType = $param->getType();

                        // Check if the parameter accepts array or mixed using improved validation
                        if (self::parameterAcceptsArray($paramType)) {
                            return $contextClass::from($filtered); // @phpstan-ignore staticMethod.notFound
                        }
                    }
                    // If parameter count is not exactly 1, skip this method for safety
                }
            } catch (\ReflectionException) {
                // Method doesn't exist or is not accessible, continue to fallback
            }
        }

        // Final fallback: try direct instantiation with the filtered array
        // Many DTOs accept an array parameter in their constructor
        try {
            /** @var ArgonautDTOContract $instance */
            $instance = new $contextClass($filtered);

            return $instance;
            // @phpstan-ignore catch.neverThrown
        } catch (\Throwable $e) {
            // Direct instantiation failed - return original context to avoid data loss
            // Log the failure for debugging
            \Log::warning('[FSM] Context filtering failed: could not reinstantiate DTO, returning original', [
                'context_class' => $contextClass,
                'is_dto' => is_subclass_of($contextClass, Dto::class),
                'has_from_method' => method_exists($contextClass, 'from'),
                'error' => $e->getMessage(),
            ]);

            return $context;
        }
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

            // If no type in union accepts array, return false
            return false;
        }

        // Handle intersection types (e.g., Countable&ArrayAccess)
        if ($paramType instanceof \ReflectionIntersectionType) {
            // For intersection types, check if the built-in array type satisfies
            // ALL types in the intersection. PHP arrays implement Countable, ArrayAccess,
            // Traversable, IteratorAggregate, and Serializable.
            $arrayCompatibleTypes = [
                'Countable',
                'ArrayAccess',
                'Traversable',
                'IteratorAggregate',
                'Serializable',
                'array',
                'mixed',
            ];

            foreach ($paramType->getTypes() as $type) {
                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();
                    if (! in_array($typeName, $arrayCompatibleTypes, true)) {
                        return false;
                    }
                } else {
                    // Nested union/intersection types in intersection - not supported
                    return false;
                }
            }

            // All types in intersection are array-compatible
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

            // Check if it's an interface that arrays implement
            $arrayCompatibleTypes = [
                'Countable',
                'ArrayAccess',
                'Traversable',
                'IteratorAggregate',
                'Serializable',
                'array',
                'mixed',
            ];

            if (in_array($typeName, $arrayCompatibleTypes, true)) {
                return true;
            }

            // For other types, be conservative and reject
            return false;
        }

        // Unknown type - be conservative and return false
        return false;
    }
}
