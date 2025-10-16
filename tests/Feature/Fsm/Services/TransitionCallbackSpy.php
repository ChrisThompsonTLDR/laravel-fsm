<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Services;

use Fsm\Data\TransitionInput;
use Tests\Feature\Fsm\Data\TestContextData;

class TransitionCallbackSpy
{
    /** @var array<int, array<string, mixed>> */
    public array $called = [];

    /** @var array<int, array<string, mixed>> */
    public static array $staticCalled = [];

    public ?TransitionInput $lastInput = null;

    /**
     * @param  array<string, mixed>  $params
     */
    public function recordCall(string $methodName, TransitionInput $input, array $params = []): void
    {
        $this->called[] = [
            'method' => $methodName,
            'model_id' => $input->model->getKey(),
            'from' => $input->fromState,
            'to' => $input->toState,
            'context' => $input->context,
            'event' => $input->event,
            'is_dry_run' => $input->isDryRun,
            'params' => $params,
        ];
        $this->lastInput = $input;
    }

    public function __invoke(TransitionInput $input): void
    {
        $this->recordCall('__invoke', $input);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public static function staticRecordCall(string $methodName, TransitionInput $input, array $params = []): void
    {
        self::$staticCalled[] = [
            'method' => $methodName,
            'model_id' => $input->model->getKey(),
            'from' => $input->fromState,
            'to' => $input->toState,
            'context' => $input->context,
            'event' => $input->event,
            'is_dry_run' => $input->isDryRun,
            'params' => $params,
        ];
    }

    // Guards
    public function successfulGuard(TransitionInput $input): bool
    {
        $this->recordCall(__FUNCTION__, $input);

        return true;
    }

    public function failingGuard(TransitionInput $input): bool
    {
        $this->recordCall(__FUNCTION__, $input);

        return false;
    }

    // Callbacks
    public function onExitCallback(TransitionInput $input, string $message = 'default onExit'): void
    {
        $this->recordCall(__FUNCTION__, $input, ['message' => $message]);
        if ($input->context instanceof TestContextData && $input->context->triggerFailure && $message === 'fail_on_exit') {
            throw new \RuntimeException('Simulated onExit failure');
        }
    }

    public function onEnterCallback(TransitionInput $input, string $message = 'default onEnter'): void
    {
        $this->recordCall(__FUNCTION__, $input, ['message' => $message]);
        if ($input->context instanceof TestContextData && $input->context->triggerFailure && $message === 'fail_on_enter') {
            throw new \RuntimeException('Simulated onEnter failure');
        }
    }

    public function onTransitionCallback(TransitionInput $input, string $message = 'default onTransition'): void
    {
        $this->recordCall(__FUNCTION__, $input, ['message' => $message]);
        if ($input->context instanceof TestContextData && $input->context->triggerFailure && $message === 'fail_on_transition') {
            throw new \RuntimeException('Simulated onTransition failure');
        }
    }

    public function reset(): void
    {
        $this->called = [];
        $this->lastInput = null;
        self::$staticCalled = [];
    }

    public function getCallCount(string $methodName): int
    {
        return count(array_filter($this->called, fn ($call) => $call['method'] === $methodName));
    }

    public function wasCalledWith(string $methodName, mixed $modelId = null, mixed $fromState = null, mixed $toState = null): bool
    {
        foreach ($this->called as $call) {
            if ($call['method'] === $methodName &&
                ($modelId === null || $call['model_id'] === $modelId) &&
                ($fromState === null || $this->normalizeCompare($call['from']) === $this->normalizeCompare($fromState)) &&
                ($toState === null || $this->normalizeCompare($call['to']) === $this->normalizeCompare($toState))
            ) {
                return true;
            }
        }

        return false;
    }

    private function normalizeCompare(mixed $state): string
    {
        return is_object($state) && method_exists($state, 'value') ? $state->value : (string) $state;
    }
}
