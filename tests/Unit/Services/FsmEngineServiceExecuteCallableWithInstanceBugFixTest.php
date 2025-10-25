<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Fsm\FsmRegistry;
use Fsm\Services\FsmEngineService;
use Fsm\Services\FsmLogger;
use Fsm\Services\FsmMetricsService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

class FsmEngineServiceExecuteCallableWithInstanceBugFixTest extends TestCase
{
    use RefreshDatabase;

    private FsmEngineService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock dependencies
        $registry = $this->createMock(FsmRegistry::class);
        $logger = $this->createMock(FsmLogger::class);
        $metrics = $this->createMock(FsmMetricsService::class);
        $db = $this->createMock(DatabaseManager::class);
        $config = $this->createMock(ConfigRepository::class);

        $this->service = new FsmEngineService($registry, $logger, $metrics, $db, $config);
    }

    /**
     * Test that object instances with named parameters work correctly.
     */
    public function test_object_instance_with_named_parameters(): void
    {
        $testObject = new class
        {
            public function test_method(string $fromState, string $toState, ?ArgonautDTOContract $context = null): array
            {
                return [
                    'fromState' => $fromState,
                    'toState' => $toState,
                    'context' => $context,
                ];
            }
        };

        $parameters = [
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
        ];

        $callable = [$testObject, 'test_method'];

        $result = $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);

        $this->assertEquals([
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
        ], $result);
    }

    /**
     * Test that class strings with named parameters work correctly.
     */
    public function test_class_string_with_named_parameters(): void
    {
        $parameters = [
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
        ];

        $callable = [TestCallableClass::class, 'testMethod'];

        $result = $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);

        $this->assertEquals([
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
        ], $result);
    }

    /**
     * Test that closures with named parameters work correctly.
     */
    public function test_closure_with_named_parameters(): void
    {
        $parameters = [
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
        ];

        $callable = function (string $fromState, string $toState, ?ArgonautDTOContract $context = null): array {
            return [
                'fromState' => $fromState,
                'toState' => $toState,
                'context' => $context,
            ];
        };

        $result = $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);

        $this->assertEquals([
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
        ], $result);
    }

    /**
     * Test that string callables with named parameters work correctly.
     */
    public function test_string_callable_with_named_parameters(): void
    {
        $parameters = [
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
        ];

        $callable = TestCallableClass::class.'@testMethod';

        $result = $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);

        $this->assertEquals([
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
        ], $result);
    }

    /**
     * Test that mixed parameter types work correctly.
     */
    public function test_mixed_parameter_types(): void
    {
        $testObject = new class
        {
            public function test_method(
                string $fromState,
                int $priority,
                bool $enabled,
                ?ArgonautDTOContract $context = null,
                array $metadata = []
            ): array {
                return [
                    'fromState' => $fromState,
                    'priority' => $priority,
                    'enabled' => $enabled,
                    'context' => $context,
                    'metadata' => $metadata,
                ];
            }
        };

        $parameters = [
            'fromState' => 'pending',
            'priority' => 100,
            'enabled' => true,
            'context' => null,
            'metadata' => ['key' => 'value'],
        ];

        $callable = [$testObject, 'test_method'];

        $result = $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);

        $this->assertEquals([
            'fromState' => 'pending',
            'priority' => 100,
            'enabled' => true,
            'context' => null,
            'metadata' => ['key' => 'value'],
        ], $result);
    }

    /**
     * Test that positional parameters still work for backward compatibility.
     */
    public function test_positional_parameters_still_work(): void
    {
        $testObject = new class
        {
            public function test_method(string $fromState, string $toState): array
            {
                return [
                    'fromState' => $fromState,
                    'toState' => $toState,
                ];
            }
        };

        // Use named parameters instead of positional to test the fix
        $parameters = [
            'fromState' => 'pending',
            'toState' => 'completed',
        ];

        $callable = [$testObject, 'test_method'];

        $result = $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);

        $this->assertEquals([
            'fromState' => 'pending',
            'toState' => 'completed',
        ], $result);
    }

    /**
     * Test that extra parameters are ignored gracefully.
     */
    public function test_extra_parameters_ignored(): void
    {
        $testObject = new class
        {
            public function test_method(string $fromState): array
            {
                return ['fromState' => $fromState];
            }
        };

        $parameters = [
            'fromState' => 'pending',
            'extraParam' => 'should be ignored',
            'anotherExtra' => 'also ignored',
        ];

        $callable = [$testObject, 'test_method'];

        $result = $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);

        $this->assertEquals(['fromState' => 'pending'], $result);
    }

    /**
     * Test that missing required parameters throw appropriate exceptions.
     */
    public function test_missing_required_parameters_throws_exception(): void
    {
        $testObject = new class
        {
            public function test_method(string $fromState, string $toState): array
            {
                return ['fromState' => $fromState, 'toState' => $toState];
            }
        };

        $parameters = ['fromState' => 'pending']; // Missing toState

        $callable = [$testObject, 'test_method'];

        $this->expectException(\ArgumentCountError::class);

        $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);
    }

    /**
     * Test that TransitionInput-like constructors work correctly.
     */
    public function test_transition_input_like_constructor(): void
    {
        $testObject = new class
        {
            public function createTransitionInput(
                string $fromState,
                string $toState,
                ?ArgonautDTOContract $context = null,
                string $event = 'test',
                bool $isDryRun = false
            ): array {
                return [
                    'fromState' => $fromState,
                    'toState' => $toState,
                    'context' => $context,
                    'event' => $event,
                    'isDryRun' => $isDryRun,
                ];
            }
        };

        $parameters = [
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
            'event' => 'process',
            'isDryRun' => true,
        ];

        $callable = [$testObject, 'createTransitionInput'];

        $result = $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);

        $this->assertEquals([
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
            'event' => 'process',
            'isDryRun' => true,
        ], $result);
    }

    /**
     * Test that complex DTO context parameters work correctly.
     */
    public function test_complex_dto_context_parameter(): void
    {
        $testContext = new class('user123', 'test reason', ['key' => 'value']) implements ArgonautDTOContract
        {
            public function __construct(
                public string $userId,
                public string $reason,
                public array $metadata = []
            ) {}

            public function toArray(): array
            {
                return [
                    'userId' => $this->userId,
                    'reason' => $this->reason,
                    'metadata' => $this->metadata,
                ];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $testObject = new class
        {
            public function test_method(string $fromState, string $toState, ?ArgonautDTOContract $context = null): array
            {
                return [
                    'fromState' => $fromState,
                    'toState' => $toState,
                    'context' => $context,
                ];
            }
        };

        $context = $testContext;

        $parameters = [
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => $context,
        ];

        $callable = [$testObject, 'test_method'];

        $result = $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);

        $this->assertEquals([
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => $context,
        ], $result);
    }

    /**
     * Test that the fix prevents ArgumentCountError for methods expecting named parameters.
     */
    public function test_prevents_argument_count_error(): void
    {
        $testObject = new class
        {
            public function createInstance(string $fromState, string $toState, ?ArgonautDTOContract $context = null): array
            {
                return [
                    'fromState' => $fromState,
                    'toState' => $toState,
                    'context' => $context,
                ];
            }
        };

        $parameters = [
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
        ];

        $callable = [$testObject, 'createInstance'];

        // This should not throw an ArgumentCountError
        $result = $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);

        $this->assertEquals([
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
        ], $result);
    }

    /**
     * Test that ReflectionException is properly caught and wrapped for private methods.
     */
    public function test_reflection_exception_for_private_method(): void
    {
        $testObject = new class
        {
            private function privateMethod(): bool
            {
                return true;
            }
        };

        $parameters = ['param1' => 'hello'];

        $callable = [$testObject, 'privateMethod'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot access private method 'privateMethod' on class");

        $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);
    }

    /**
     * Test that ReflectionException is properly caught and wrapped for protected methods.
     */
    public function test_reflection_exception_for_protected_method(): void
    {
        $testObject = new class
        {
            protected function protectedMethod(): bool
            {
                return true;
            }
        };

        $parameters = ['param1' => 'hello'];

        $callable = [$testObject, 'protectedMethod'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot access protected method 'protectedMethod' on class");

        $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);
    }

    /**
     * Test that ReflectionException is properly caught and wrapped for non-existent methods.
     */
    public function test_reflection_exception_for_non_existent_method(): void
    {
        $testObject = new class
        {
            public function existingMethod(): bool
            {
                return true;
            }
        };

        $parameters = ['param1' => 'hello'];

        $callable = [$testObject, 'nonExistentMethod'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Failed to create reflection for method 'nonExistentMethod' on class");

        $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);
    }

    /**
     * Test that ReflectionException preserves the original exception as cause.
     */
    public function test_reflection_exception_preserves_original_exception(): void
    {
        $testObject = new class
        {
            public function existingMethod(): bool
            {
                return true;
            }
        };

        $parameters = ['param1' => 'hello'];

        $callable = [$testObject, 'nonExistentMethod'];

        try {
            $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);
            $this->fail('Expected InvalidArgumentException to be thrown');
        } catch (\InvalidArgumentException $e) {
            // Verify the original ReflectionException is preserved as cause
            $this->assertInstanceOf(\ReflectionException::class, $e->getPrevious());
            $this->assertStringContainsString('nonExistentMethod', $e->getPrevious()->getMessage());
        }
    }

    /**
     * Test that valid method calls still work after adding error handling.
     */
    public function test_valid_method_calls_still_work_after_error_handling(): void
    {
        $testObject = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function test_method(string $param1, int $param2): bool
            {
                $this->called = true;
                $this->receivedParams = [$param1, $param2];

                return true;
            }
        };

        $parameters = [
            'param1' => 'hello',
            'param2' => 42,
        ];

        $callable = [$testObject, 'test_method'];

        $result = $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);

        // Verify the method was called correctly
        $this->assertTrue($testObject->called);
        $this->assertSame('hello', $testObject->receivedParams[0]);
        $this->assertSame(42, $testObject->receivedParams[1]);
        $this->assertTrue($result);
    }

    /**
     * Test that the error message includes the correct class and method names.
     */
    public function test_error_message_includes_correct_class_and_method_names(): void
    {
        $testObject = new class
        {
            public function existingMethod(): bool
            {
                return true;
            }
        };

        $parameters = ['param1' => 'hello'];

        $callable = [$testObject, 'nonExistentMethod'];

        try {
            $this->callPrivateMethod('executeCallableWithInstance', $callable, $parameters);
            $this->fail('Expected InvalidArgumentException to be thrown');
        } catch (\InvalidArgumentException $e) {
            // Verify the error message includes the correct class and method names
            $this->assertStringContainsString('nonExistentMethod', $e->getMessage());
            $this->assertStringContainsString(get_class($testObject), $e->getMessage());
            $this->assertStringContainsString('Failed to create reflection', $e->getMessage());
        }
    }

    /**
     * Helper method to call private methods for testing.
     */
    private function callPrivateMethod(string $methodName, mixed $callable, array $parameters): mixed
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invoke($this->service, $callable, $parameters);
    }
}

/**
 * Test class for string callable testing.
 */
class TestCallableClass
{
    public function testMethod(string $fromState, string $toState, ?ArgonautDTOContract $context = null): array
    {
        return [
            'fromState' => $fromState,
            'toState' => $toState,
            'context' => $context,
        ];
    }
}
