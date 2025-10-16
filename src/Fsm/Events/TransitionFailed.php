<?php

declare(strict_types=1);

namespace Fsm\Events;

use Fsm\Contracts\FsmStateEnum;
use Illuminate\Database\Eloquent\Model;
use Throwable;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

class TransitionFailed
{
    /**
     * Create a new event instance.
     *
     * @param  Model  $model  The model instance for which transition failed.
     * @param  string  $columnName  The name of the FSM state column.
     * @param  FsmStateEnum|string|null  $fromState  The state attempted to transition from.
     * @param  FsmStateEnum|string  $toState  The state attempted to transition to.
     * @param  ArgonautDTOContract|null  $context  The context DTO used for the transition attempt.
     * @param  Throwable|null  $exception  The exception that caused the failure.
     */
    public function __construct(
        public readonly Model $model,
        public readonly string $columnName,
        public readonly FsmStateEnum|string|null $fromState,
        public readonly FsmStateEnum|string $toState,
        public readonly ?ArgonautDTOContract $context,
        public readonly ?Throwable $exception
    ) {}
}
