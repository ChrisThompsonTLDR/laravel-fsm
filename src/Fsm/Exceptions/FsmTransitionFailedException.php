<?php

declare(strict_types=1);

namespace Fsm\Exceptions;

use Fsm\Contracts\FsmStateEnum;
use RuntimeException;
use Throwable;

class FsmTransitionFailedException extends RuntimeException
{
    /**
     * FsmTransitionFailedException constructor.
     *
     * @param  FsmStateEnum|string|null  $fromState  The state being transitioned from.
     * @param  FsmStateEnum|string  $toState  The state being transitioned to.
     * @param  string  $reason  A short description of why the transition failed.
     * @param  string  $message  The detailed exception message. If empty, a default will be generated.
     * @param  int  $code  The exception code.
     * @param  Throwable|null  $previous  The previous throwable used for the exception chain.
     * @param  Throwable|null  $originalException  The original exception that caused the transition failure, if applicable.
     */
    public function __construct(
        public readonly FsmStateEnum|string|null $fromState,
        public readonly FsmStateEnum|string $toState,
        public readonly string $reason,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?Throwable $originalException = null
    ) {
        if (empty($message)) {
            $message = sprintf(
                "Transition from '%s' to '%s' failed: %s",
                self::getStateValueForMessage($fromState),
                self::getStateValueForMessage($toState),
                $reason
            );
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the state being transitioned from.
     */
    public function getFromState(): FsmStateEnum|string|null
    {
        return $this->fromState;
    }

    /**
     * Get the state being transitioned to.
     */
    public function getToState(): FsmStateEnum|string
    {
        return $this->toState;
    }

    /**
     * Get the reason for the transition failure.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Get the original exception that triggered the failure, if any.
     */
    public function getOriginalException(): ?Throwable
    {
        return $this->originalException;
    }

    /**
     * Helper to get the string value of a state, whether it's an enum or a string.
     */
    private static function getStateValueForMessage(FsmStateEnum|string|null $state): string
    {
        if ($state === null) {
            return '(null)';
        }

        return $state instanceof FsmStateEnum ? $state->value : $state;
    }

    /**
     * Creates an exception for an invalid transition attempt where no such transition is defined.
     */
    public static function forInvalidTransition(
        FsmStateEnum|string|null $from,
        FsmStateEnum|string $to,
        string $modelClass,
        string $columnName
    ): self {
        $fromValueDisplay = self::getStateValueForMessage($from);
        $toValueDisplay = self::getStateValueForMessage($to);
        $reason = sprintf(
            "No defined transition from '%s' to '%s' for %s::%s.",
            $fromValueDisplay,
            $toValueDisplay,
            $modelClass,
            $columnName
        );

        return new self($from, $to, $reason, $reason);
    }

    /**
     * Creates an exception for a transition failure due to a guard condition not being met.
     */
    public static function forGuardFailure(
        FsmStateEnum|string|null $from,
        FsmStateEnum|string $to,
        string $guardDescription, // e.g., "Guard [GuardClassName::class]" or "Condition 'is_paid'"
        string $modelClass,
        string $columnName
    ): self {
        $fromValueDisplay = self::getStateValueForMessage($from);
        $toValueDisplay = self::getStateValueForMessage($to);
        $reason = sprintf(
            "%s failed for transition from '%s' to '%s' on %s::%s.",
            $guardDescription,
            $fromValueDisplay,
            $toValueDisplay,
            $modelClass,
            $columnName
        );

        return new self($from, $to, $reason, $reason);
    }

    /**
     * Creates an exception for a transition failure due to an exception during a callback execution (e.g., onEntry, onExit, action).
     */
    public static function forCallbackException(
        FsmStateEnum|string|null $from,
        FsmStateEnum|string $to,
        string $callbackType, // e.g., "onEntry hook", "onExit hook", "action execution"
        Throwable $exception,
        string $modelClass,
        string $columnName
    ): self {
        $fromValueDisplay = self::getStateValueForMessage($from);
        $toValueDisplay = self::getStateValueForMessage($to);
        $reason = sprintf(
            "Exception during '%s' for transition from '%s' to '%s' on %s::%s: %s",
            $callbackType,
            $fromValueDisplay,
            $toValueDisplay,
            $modelClass,
            $columnName,
            $exception->getMessage()
        );

        // Pass the original exception as both $previous (for chaining) and $originalException (for specific access)
        return new self($from, $to, $reason, $reason, -2, $exception, $exception);
    }

    public static function forConcurrentModification(
        FsmStateEnum|string|null $from,
        FsmStateEnum|string $to,
        string $modelClass,
        string $columnName
    ): self {
        $fromValueDisplay = self::getStateValueForMessage($from);
        $toValueDisplay = self::getStateValueForMessage($to);
        $reason = sprintf(
            "Concurrent modification detected for transition from '%s' to '%s' on %s::%s.",
            $fromValueDisplay,
            $toValueDisplay,
            $modelClass,
            $columnName
        );

        return new self($from, $to, $reason, $reason);
    }
}
