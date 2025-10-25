<?php

declare(strict_types=1);

namespace Fsm\Events;

use Fsm\Contracts\FsmStateEnum;
use Illuminate\Database\Eloquent\Model;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

class TransitionAttempted
{
    /**
     * Create a new event instance.
     *
     * @param  Model  $model  The model instance undergoing transition.
     * @param  string  $columnName  The name of the FSM state column.
     * @param  FsmStateEnum|string|null  $fromState  The state transitioned from.
     * @param  FsmStateEnum|string  $toState  The state transitioned to.
     * @param  ArgonautDTOContract|null  $context  The context DTO used for the transition.
     */
    public function __construct(
        public readonly Model $model,
        public readonly string $columnName,
        public readonly FsmStateEnum|string|null $fromState,
        public readonly FsmStateEnum|string $toState,
        public readonly ?ArgonautDTOContract $context
    ) {}
}
