<?php

declare(strict_types=1);

namespace Fsm\Services;

use Fsm\Contracts\FsmStateEnum;
use Fsm\Events\TransitionMetric;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

class FsmMetricsService
{
    private const CACHE_KEY_SUCCESS = 'fsm:transitions:success';

    private const CACHE_KEY_FAILURE = 'fsm:transitions:failure';

    public function __construct(
        private readonly Dispatcher $dispatcher
    ) {}

    public function record(
        Model $model,
        string $columnName,
        FsmStateEnum|string|null $fromState,
        FsmStateEnum|string $toState,
        bool $successful,
        ?ArgonautDTOContract $context = null
    ): void {
        $key = $successful ? self::CACHE_KEY_SUCCESS : self::CACHE_KEY_FAILURE;
        Cache::increment($key);

        $this->dispatcher->dispatch(new TransitionMetric(
            model: $model,
            columnName: $columnName,
            fromState: $fromState,
            toState: $toState,
            successful: $successful,
            context: $context,
        ));
    }
}
