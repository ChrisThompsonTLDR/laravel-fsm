<?php

declare(strict_types=1);

namespace Fsm\Guards;

use Fsm\Data\TransitionGuard;
use Fsm\Data\TransitionInput;
use Fsm\Exceptions\FsmTransitionFailedException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Throwable;

/**
 * Composite guard manager for advanced guard composition and execution strategies.
 *
 * Provides enhanced guard evaluation with support for:
 * - Priority-based execution
 * - Short-circuit evaluation
 * - Conditional guard execution
 * - Enhanced error reporting and debugging
 */
class CompositeGuard
{
    public const string STRATEGY_ALL_MUST_PASS = 'all_must_pass';

    public const string STRATEGY_ANY_MUST_PASS = 'any_must_pass';

    public const string STRATEGY_PRIORITY_FIRST = 'priority_first';

    /**
     * @param  Collection<int, TransitionGuard>  $guards
     */
    public function __construct(
        private readonly Collection $guards,
        private readonly string $evaluationStrategy = self::STRATEGY_ALL_MUST_PASS,
    ) {}

    /**
     * Create a composite guard from an array of guards.
     *
     * @param  array<TransitionGuard>  $guards
     */
    public static function create(array $guards, string $strategy = self::STRATEGY_ALL_MUST_PASS): self
    {
        return new self(collect($guards), $strategy);
    }

    /**
     * Evaluate all guards according to the specified strategy.
     *
     * @param  string  $columnName  The FSM state column name (e.g., 'status')
     *
     * @throws FsmTransitionFailedException
     */
    public function evaluate(TransitionInput $input, string $columnName): bool
    {
        if ($this->guards->isEmpty()) {
            return true;
        }

        return match ($this->evaluationStrategy) {
            self::STRATEGY_ALL_MUST_PASS => $this->evaluateAllMustPass($input, $columnName),
            self::STRATEGY_ANY_MUST_PASS => $this->evaluateAnyMustPass($input, $columnName),
            self::STRATEGY_PRIORITY_FIRST => $this->evaluatePriorityFirst($input, $columnName),
            default => throw new \LogicException("Unknown guard evaluation strategy: {$this->evaluationStrategy}"),
        };
    }

    /**
     * All guards must return true for the evaluation to pass.
     *
     * @throws FsmTransitionFailedException
     */
    private function evaluateAllMustPass(TransitionInput $input, string $columnName): bool
    {
        $sortedGuards = $this->guards
            ->sortByDesc(fn (TransitionGuard $guard) => $guard->priority);

        $failedGuards = collect();

        foreach ($sortedGuards as $guard) {
            try {
                $result = $this->executeGuard($guard, $input);

                if ($result !== true) {
                    if ($guard->stopOnFailure) {
                        throw FsmTransitionFailedException::forGuardFailure(
                            $this->getStateValue($input->fromState),
                            $input->toState,
                            $this->formatGuardDescription($guard),
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
                throw $e;
            } catch (Throwable $e) {
                if ($guard->stopOnFailure) {
                    throw FsmTransitionFailedException::forCallbackException(
                        $this->getStateValue($input->fromState),
                        $input->toState,
                        $this->formatGuardDescription($guard),
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

        if ($failedGuards->isNotEmpty()) {
            $failureReasons = $failedGuards->map(fn ($failure) => $this->formatGuardDescription($failure['guard']).': '.$failure['reason']
            )->join(', ');

            throw FsmTransitionFailedException::forGuardFailure(
                $this->getStateValue($input->fromState),
                $input->toState,
                "Multiple guards failed: {$failureReasons}",
                $input->model::class,
                $columnName
            );
        }

        return true;
    }

    /**
     * At least one guard must return true for the evaluation to pass.
     *
     * @throws FsmTransitionFailedException
     */
    private function evaluateAnyMustPass(TransitionInput $input, string $columnName): bool
    {
        $sortedGuards = $this->guards
            ->sortByDesc(fn (TransitionGuard $guard) => $guard->priority);

        $allFailed = true;
        $failedGuards = collect();

        foreach ($sortedGuards as $guard) {
            try {
                $result = $this->executeGuard($guard, $input);

                if ($result === true) {
                    $allFailed = false;
                    break; // Short-circuit on first success
                }

                $failedGuards->push([
                    'guard' => $guard,
                    'reason' => 'Guard returned false',
                ]);

            } catch (Throwable $e) {
                $failedGuards->push([
                    'guard' => $guard,
                    'reason' => $e->getMessage(),
                    'exception' => $e,
                ]);
            }
        }

        if ($allFailed) {
            $failureReasons = $failedGuards->map(fn ($failure) => $this->formatGuardDescription($failure['guard']).': '.$failure['reason']
            )->join(', ');

            throw FsmTransitionFailedException::forGuardFailure(
                $this->getStateValue($input->fromState),
                $input->toState,
                "All guards failed: {$failureReasons}",
                $input->model::class,
                $columnName
            );
        }

        return true;
    }

    /**
     * Execute guards in priority order until one passes or all fail.
     *
     * @throws FsmTransitionFailedException
     */
    private function evaluatePriorityFirst(TransitionInput $input, string $columnName): bool
    {
        $sortedGuards = $this->guards
            ->sortByDesc(fn (TransitionGuard $guard) => $guard->priority);

        foreach ($sortedGuards as $guard) {
            try {
                $result = $this->executeGuard($guard, $input);

                if ($result === true) {
                    return true; // First guard that passes wins
                }

                // Continue to next guard if this one failed

            } catch (Throwable $e) {
                // Log the exception but continue to next guard
                if (function_exists('report')) {
                    report($e);
                }
            }
        }

        // All guards failed
        throw FsmTransitionFailedException::forGuardFailure(
            $this->getStateValue($input->fromState),
            $input->toState,
            'All priority guards failed',
            $input->model::class,
            $columnName
        );
    }

    /**
     * Execute a single guard and return its result.
     *
     * @throws Throwable
     */
    private function executeGuard(TransitionGuard $guard, TransitionInput $input): mixed
    {
        $parameters = array_merge($guard->parameters, ['input' => $input]);
        $callable = $guard->callable;

        return $this->executeCallableWithInstance($callable, $parameters);
    }

    /**
     * Execute a callable that may contain an object instance.
     *
     * @param  array{0: class-string|object, 1: string}|string|\Closure  $callable
     * @param  array<string, mixed>  $parameters
     */
    private function executeCallableWithInstance(mixed $callable, array $parameters): mixed
    {
        if (is_array($callable) && count($callable) === 2 && is_object($callable[0])) {
            // For object instances, always use reflection to properly handle both
            // associative and positional parameters
            try {
                $reflection = new \ReflectionMethod($callable[0], $callable[1]);

                // Check if the method is accessible (not private or protected)
                if (! $reflection->isPublic()) {
                    $visibility = $reflection->isPrivate() ? 'private' : 'protected';
                    throw new \InvalidArgumentException(
                        "Cannot access {$visibility} method '{$callable[1]}' on class '".get_class($callable[0])."'"
                    );
                }
            } catch (\ReflectionException $e) {
                throw new \InvalidArgumentException(
                    "Failed to create reflection for method '{$callable[1]}' on class '".get_class($callable[0])."': ".$e->getMessage(),
                    0,
                    $e
                );
            }

            $orderedParameters = [];

            foreach ($reflection->getParameters() as $param) {
                $paramName = $param->getName();
                $paramIndex = $param->getPosition();

                if (array_key_exists($paramName, $parameters)) {
                    // Named parameter found
                    $orderedParameters[] = $parameters[$paramName];
                } elseif (array_key_exists($paramIndex, $parameters)) {
                    // Positional parameter found
                    $orderedParameters[] = $parameters[$paramIndex];
                } elseif ($param->isDefaultValueAvailable()) {
                    // Use default value
                    $orderedParameters[] = $param->getDefaultValue();
                } else {
                    // Required parameter missing
                    throw new \ArgumentCountError("Missing required parameter: {$paramName}");
                }
            }

            /** @var callable $callback */
            $callback = [$callable[0], $callable[1]];

            return call_user_func_array($callback, $orderedParameters);
        }

        // For other callable types, use App::call
        if (is_array($callable) && count($callable) === 2) {
            $classPart = is_object($callable[0]) ? get_class($callable[0]) : $callable[0];
            $callable = $classPart.'@'.$callable[1];
        }

        /** @var string|\Closure $callable */
        return App::call($callable, $parameters);
    }

    /**
     * Format a guard description for error messages.
     */
    private function formatGuardDescription(TransitionGuard $guard): string
    {
        if ($guard->description) {
            return $guard->description;
        }

        return $guard->getDisplayName();
    }

    /**
     * Get string representation of a state value.
     */
    private function getStateValue(mixed $state): ?string
    {
        if ($state instanceof \Fsm\Contracts\FsmStateEnum) {
            return $state->value;
        }

        return $state !== null ? (string) $state : null;
    }

    /**
     * Get the number of guards in this composite.
     */
    public function count(): int
    {
        return $this->guards->count();
    }

    /**
     * Get the evaluation strategy.
     */
    public function getStrategy(): string
    {
        return $this->evaluationStrategy;
    }

    /**
     * Get all guards ordered by priority.
     *
     * @return Collection<int, TransitionGuard>
     */
    public function getGuards(): Collection
    {
        return $this->guards->sortByDesc(fn (TransitionGuard $guard) => $guard->priority)->values();
    }
}
