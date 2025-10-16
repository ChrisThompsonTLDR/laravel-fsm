<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Services;

use Fsm\Contracts\FsmStateEnum;
use Fsm\Data\TransitionInput;
use Tests\Feature\Fsm\Data\TestContextData;

class TestSpyService
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
            'from' => $this->getStateValue($input->fromState),
            'to' => $this->getStateValue($input->toState),
            'context' => $input->context,
            'event' => $input->event,
            'params' => $params,
        ];
        $this->lastInput = $input;
    }

    public function onEntryCallback(TransitionInput $input): void
    {
        $this->recordCall(__FUNCTION__, $input);
    }

    /**
     * Handles instance method calls with error suppression.
     *
     * @param  array<string, mixed>  $params
     */
    private static function handleInstanceCall(string $methodName, TransitionInput $input, array $params = []): void
    {
        try {
            $instance = app(self::class);
            if ($instance) {
                $instance->recordCall($methodName, $input, $params);
            }
        } catch (\Exception $e) {
            // Ignore container resolution errors in tests
        }
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public static function staticRecordCall(string $methodName, TransitionInput $input, array $params = []): void
    {
        self::$staticCalled[] = [
            'method' => $methodName,
            'model_id' => $input->model->getKey(),
            'from' => self::getStateValue($input->fromState),
            'to' => self::getStateValue($input->toState),
            'context' => $input->context,
            'event' => $input->event,
            'params' => $params,
        ];
    }

    public static function OrderStatusProcessingEntry(TransitionInput $input): void
    {
        self::staticRecordCall('OrderStatusProcessingEntry', $input);
        self::handleInstanceCall('OrderStatusProcessingEntry', $input);
    }

    public static function OrderBeforeProcess(TransitionInput $input): void
    {
        self::staticRecordCall('OrderBeforeProcess', $input);
        self::handleInstanceCall('OrderBeforeProcess', $input);
    }

    public static function OrderAfterProcess(TransitionInput $input): void
    {
        self::staticRecordCall('OrderAfterProcess', $input);
        self::handleInstanceCall('OrderAfterProcess', $input);
        if ($input->context instanceof TestContextData && $input->context->triggerFailure) {
            throw new \RuntimeException('Simulated onTransition failure');
        }
    }

    public static function PaymentCompletedEntry(TransitionInput $input): void
    {
        self::staticRecordCall('PaymentCompletedEntry', $input);
        self::handleInstanceCall('PaymentCompletedEntry', $input);
    }

    // Example Guard - Keep both static and instance methods for backwards compatibility
    public static function successfulGuard(TransitionInput $input): bool
    {
        self::staticRecordCall(__FUNCTION__, $input);
        self::handleInstanceCall(__FUNCTION__, $input);

        return true;
    }

    public static function failingGuard(TransitionInput $input): bool
    {
        self::staticRecordCall(__FUNCTION__, $input);
        self::handleInstanceCall(__FUNCTION__, $input);

        return false;
    }

    // Example Action - Keep both static and instance methods for backwards compatibility
    public static function anAction(TransitionInput $input): void
    {
        self::staticRecordCall(__FUNCTION__, $input);
        self::handleInstanceCall(__FUNCTION__, $input);

        if ($input->context instanceof TestContextData && $input->context->triggerFailure) {
            throw new \RuntimeException('Simulated action failure');
        }
    }

    // Example Callback - Keep both static and instance methods for backwards compatibility
    public static function onExitCallback(TransitionInput $input): void
    {
        self::staticRecordCall(__FUNCTION__, $input);
        self::handleInstanceCall(__FUNCTION__, $input);
    }

    // Additional callback methods for TestFeatureFsmDefinition
    public function guardCallback(TransitionInput $input): bool
    {
        $this->recordCall(__FUNCTION__, $input);

        return true;
    }

    public function actionCallback(TransitionInput $input): void
    {
        $this->recordCall(__FUNCTION__, $input);
    }

    public static function onTransitionCallback(TransitionInput $input, array $params = []): void
    {
        self::staticRecordCall(__FUNCTION__, $input, $params);

        self::handleInstanceCall(__FUNCTION__, $input, $params);

        // Check if we should trigger a failure for testing
        if (isset($params['message']) && $params['message'] === 'fail_on_transition') {
            throw new \RuntimeException('Simulated onTransition failure');
        }

        // Also check context for triggerFailure flag
        if ($input->context instanceof TestContextData && $input->context->triggerFailure) {
            throw new \RuntimeException('Simulated onTransition failure');
        }
    }

    public function reset(): void
    {
        $this->called = [];
        $this->lastInput = null;
        self::$staticCalled = []; // Reset static calls too
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
        if (is_object($state)) {
            if (method_exists($state, 'value')) {
                return $state->value;
            }
            if ($state instanceof \BackedEnum) {
                return $state->value;
            }
        }

        return (string) $state;
    }

    private static function getStateValue(FsmStateEnum|string|null $state): ?string
    {
        if ($state instanceof FsmStateEnum) {
            return $state->value;
        }

        return $state;
    }
}
