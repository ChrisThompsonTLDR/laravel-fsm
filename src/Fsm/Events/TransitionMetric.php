<?php

declare(strict_types=1);

namespace Fsm\Events;

use Fsm\Contracts\FsmStateEnum;
use Illuminate\Database\Eloquent\Model;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

class TransitionMetric
{
    public function __construct(
        public readonly Model $model,
        public readonly string $columnName,
        public readonly FsmStateEnum|string|null $fromState,
        public readonly FsmStateEnum|string $toState,
        public readonly bool $successful,
        public readonly ?ArgonautDTOContract $context = null,
    ) {}
}
