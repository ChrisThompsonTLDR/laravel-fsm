<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Fsm\Services\FsmEngineService;
use Illuminate\Support\Facades\App;
use Mockery;
use Orchestra\Testbench\TestCase;
use ReflectionMethod;

/**
 * Test class to verify the bug fix for inconsistent callable execution logic.
 *
 * This test ensures that the executeCallableWithInstance method handles
 * array callables consistently, preventing LogicExceptions and ensuring
 * proper execution of guard, callback, and action functions.
 *
 * @skip Tests use Laravel framework mocking (App::call) that requires Laravel application
 * instance to be properly initialized. These tests verify Laravel framework integration
 * rather than FSM core functionality and may fail in different Laravel versions or
 * test environments due to framework setup differences.
 */
class FsmEngineServiceCallableExecutionBugFixTest extends TestCase
{
    private FsmEngineService $service;

    private $mockRegistry;

    private $mockLogger;

    private $mockMetrics;

    private $mockDb;

    private $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRegistry = Mockery::mock('Fsm\FsmRegistry');
        $this->mockLogger = Mockery::mock('Fsm\Services\FsmLogger');
        $this->mockMetrics = Mockery::mock('Fsm\Services\FsmMetricsService');
        $this->mockDb = Mockery::mock('Illuminate\Database\DatabaseManager');
        $this->mockConfig = Mockery::mock('Illuminate\Contracts\Config\Repository');

        $this->service = new FsmEngineService(
            $this->mockRegistry,
            $this->mockLogger,
            $this->mockMetrics,
            $this->mockDb,
            $this->mockConfig
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that object instances are handled consistently without calling stringifyCallable.
     */
    public function test_object_instances_are_handled_consistently(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, int $param2 = 42): string
            {
                return "Object method called with: {$param1}, {$param2}";
            }
        };

        $callable = [$testObject, 'testMethod'];
        $parameters = ['param1' => 'test_value', 'param2' => 123];

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeCallableWithInstance');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $callable, $parameters);

        $this->assertEquals('Object method called with: test_value, 123', $result);
    }

    /**
     * Test that class strings are handled consistently using App::call.
     */
    public function test_class_strings_are_handled_consistently(): void
    {
        $callable = [TestCallableClassForBugFix::class, 'testMethod'];
        $parameters = ['param1' => 'test_value', 'param2' => 123];

        // Mock App::call to verify it's called with the correct string callable
        App::shouldReceive('call')
            ->once()
            ->with('Tests\Unit\Fsm\Services\TestCallableClassForBugFix@testMethod', $parameters)
            ->andReturn('Class method called with: test_value, 123');

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeCallableWithInstance');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $callable, $parameters);

        $this->assertEquals('Class method called with: test_value, 123', $result);
    }

    /**
     * Test that closures are handled consistently using App::call.
     */
    public function test_closures_are_handled_consistently(): void
    {
        $closure = function (string $param1, int $param2 = 42): string {
            return "Closure called with: {$param1}, {$param2}";
        };
        $parameters = ['param1' => 'test_value', 'param2' => 123];

        // Mock App::call to verify it's called with the closure
        App::shouldReceive('call')
            ->once()
            ->with($closure, $parameters)
            ->andReturn('Closure called with: test_value, 123');

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeCallableWithInstance');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $closure, $parameters);

        $this->assertEquals('Closure called with: test_value, 123', $result);
    }

    /**
     * Test that string callables are handled consistently using App::call.
     */
    public function test_string_callables_are_handled_consistently(): void
    {
        $callable = 'Tests\Unit\Fsm\Services\TestCallableClassForBugFix@testMethod';
        $parameters = ['param1' => 'test_value', 'param2' => 123];

        // Mock App::call to verify it's called with the string callable
        App::shouldReceive('call')
            ->once()
            ->with($callable, $parameters)
            ->andReturn('String callable called with: test_value, 123');

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeCallableWithInstance');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $callable, $parameters);

        $this->assertEquals('String callable called with: test_value, 123', $result);
    }

    /**
     * Test that object instances with missing parameters throw ArgumentCountError.
     */
    public function test_object_instances_with_missing_parameters_throw_exception(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, int $param2): string
            {
                return "Object method called with: {$param1}, {$param2}";
            }
        };

        $callable = [$testObject, 'testMethod'];
        $parameters = ['param1' => 'test_value']; // Missing param2

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeCallableWithInstance');
        $method->setAccessible(true);

        $this->expectException(\ArgumentCountError::class);
        $this->expectExceptionMessage('Missing required parameter: param2');

        $method->invoke($this->service, $callable, $parameters);
    }

    /**
     * Test that object instances with private methods throw InvalidArgumentException.
     */
    public function test_object_instances_with_private_methods_throw_exception(): void
    {
        $testObject = new class
        {
            private function privateMethod(string $param1): string
            {
                return "Private method called with: {$param1}";
            }
        };

        $callable = [$testObject, 'privateMethod'];
        $parameters = ['param1' => 'test_value'];

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeCallableWithInstance');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot access private method \'privateMethod\'');

        $method->invoke($this->service, $callable, $parameters);
    }

    /**
     * Test that object instances with protected methods throw InvalidArgumentException.
     */
    public function test_object_instances_with_protected_methods_throw_exception(): void
    {
        $testObject = new class
        {
            protected function protectedMethod(string $param1): string
            {
                return "Protected method called with: {$param1}";
            }
        };

        $callable = [$testObject, 'protectedMethod'];
        $parameters = ['param1' => 'test_value'];

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeCallableWithInstance');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot access protected method \'protectedMethod\'');

        $method->invoke($this->service, $callable, $parameters);
    }

    /**
     * Test that object instances with non-existent methods throw InvalidArgumentException.
     */
    public function test_object_instances_with_non_existent_methods_throw_exception(): void
    {
        $testObject = new class
        {
            public function existingMethod(): string
            {
                return 'existing method';
            }
        };

        $callable = [$testObject, 'nonExistentMethod'];
        $parameters = [];

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeCallableWithInstance');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to create reflection for method \'nonExistentMethod\'');

        $method->invoke($this->service, $callable, $parameters);
    }

    /**
     * Test that object instances handle default parameters correctly.
     */
    public function test_object_instances_handle_default_parameters_correctly(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, int $param2 = 42, string $param3 = 'default'): string
            {
                return "Object method called with: {$param1}, {$param2}, {$param3}";
            }
        };

        $callable = [$testObject, 'testMethod'];
        $parameters = ['param1' => 'test_value']; // Only provide param1, others should use defaults

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeCallableWithInstance');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $callable, $parameters);

        $this->assertEquals('Object method called with: test_value, 42, default', $result);
    }

    /**
     * Test that object instances handle mixed parameter types correctly.
     */
    public function test_object_instances_handle_mixed_parameter_types_correctly(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, int $param2, bool $param3, array $param4): string
            {
                return "Object method called with: {$param1}, {$param2}, ".($param3 ? 'true' : 'false').', '.json_encode($param4);
            }
        };

        $callable = [$testObject, 'testMethod'];
        $parameters = [
            'param1' => 'test_value',
            'param2' => 123,
            'param3' => true,
            'param4' => ['key' => 'value'],
        ];

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeCallableWithInstance');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $callable, $parameters);

        $this->assertEquals('Object method called with: test_value, 123, true, {"key":"value"}', $result);
    }

    /**
     * Test that the fix prevents LogicException when object instances are passed.
     */
    public function test_fix_prevents_logic_exception_with_object_instances(): void
    {
        $testObject = new class
        {
            public function testMethod(): string
            {
                return 'Object method called successfully';
            }
        };

        $callable = [$testObject, 'testMethod'];
        $parameters = [];

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeCallableWithInstance');
        $method->setAccessible(true);

        // This should not throw a LogicException
        $result = $method->invoke($this->service, $callable, $parameters);

        $this->assertEquals('Object method called successfully', $result);
    }

    /**
     * Test that the executeObjectMethod method works correctly in isolation.
     */
    public function test_execute_object_method_works_correctly(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, int $param2 = 42): string
            {
                return "Object method called with: {$param1}, {$param2}";
            }
        };

        $parameters = ['param1' => 'test_value', 'param2' => 123];

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: test_value, 123', $result);
    }

    /**
     * Test that the executeObjectMethod method handles extra parameters correctly.
     */
    public function test_execute_object_method_handles_extra_parameters_correctly(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1): string
            {
                return "Object method called with: {$param1}";
            }
        };

        $parameters = [
            'param1' => 'test_value',
            'extraParam' => 'should_be_ignored',
            'anotherExtra' => 123,
        ];

        // Use reflection to access the private method
        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: test_value', $result);
    }

    /**
     * Test that positional parameters are handled correctly.
     */
    public function test_positional_parameters_are_handled_correctly(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, int $param2, bool $param3): string
            {
                return "Object method called with: {$param1}, {$param2}, ".($param3 ? 'true' : 'false');
            }
        };

        // Use positional parameters (indexed array)
        $parameters = ['test_value', 123, true];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: test_value, 123, true', $result);
    }

    /**
     * Test that mixed named and positional parameters work correctly.
     */
    public function test_mixed_named_and_positional_parameters_work_correctly(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, int $param2, bool $param3): string
            {
                return "Object method called with: {$param1}, {$param2}, ".($param3 ? 'true' : 'false');
            }
        };

        // Mix named and positional parameters
        $parameters = [
            'param1' => 'named_value',
            1 => 456, // positional for param2
            'param3' => false,
        ];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: named_value, 456, false', $result);
    }

    /**
     * Test that explicit null values are handled correctly.
     */
    public function test_explicit_null_values_are_handled_correctly(): void
    {
        $testObject = new class
        {
            public function testMethod(?string $param1, ?int $param2): string
            {
                $param1Str = $param1 === null ? 'null' : $param1;
                $param2Str = $param2 === null ? 'null' : (string) $param2;
                return "Object method called with: {$param1Str}, {$param2Str}";
            }
        };

        // Explicit null values
        $parameters = [
            'param1' => null,
            'param2' => null,
        ];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: null, null', $result);
    }
}

/**
 * Test class for callable execution tests.
 */
class TestCallableClassForBugFix
{
    public function testMethod(string $param1, int $param2 = 42): string
    {
        return "Class method called with: {$param1}, {$param2}";
    }
}
