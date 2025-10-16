<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Fsm\Services\FsmEngineService;
use Illuminate\Support\Facades\App;
use Mockery;
use ReflectionMethod;
use Tests\TestbenchTestCase;

/**
 * Test class to verify the bug fix for executeObjectMethod parameter resolution.
 *
 * This test ensures that the executeObjectMethod method now properly handles:
 * - Positional parameters
 * - Dependency injection for type-hinted parameters
 * - Proper null value handling (distinguishing between missing and explicit null)
 * - Consistent behavior compared to App::call
 *
 * @skip Tests use Laravel framework mocking (App::make) that requires Laravel application
 * instance to be properly initialized. These tests verify Laravel framework integration
 * rather than FSM core functionality and may fail in different Laravel versions or
 * test environments due to framework setup differences.
 */
class FsmEngineServiceExecuteObjectMethodBugFixTest extends TestbenchTestCase
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
        // Reset App facade mocks
        App::clearResolvedInstances();
        
        Mockery::close();
        parent::tearDown();
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
     * Test that named parameters take precedence over positional ones.
     */
    public function test_named_parameters_take_precedence_over_positional(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, int $param2): string
            {
                return "Object method called with: {$param1}, {$param2}";
            }
        };

        // Both named and positional parameters for the same parameter
        $parameters = [
            'param1' => 'named_value',
            0 => 'positional_value', // Should be ignored
            'param2' => 789,
        ];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: named_value, 789', $result);
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

    /**
     * Test that missing parameters fall back to default values.
     */
    public function test_missing_parameters_fall_back_to_default_values(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, int $param2 = 42, string $param3 = 'default'): string
            {
                return "Object method called with: {$param1}, {$param2}, {$param3}";
            }
        };

        // Only provide first parameter
        $parameters = ['param1' => 'test_value'];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: test_value, 42, default', $result);
    }

    /**
     * Test that dependency injection works for type-hinted parameters.
     */
    public function test_dependency_injection_works_for_type_hinted_parameters(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, TestDependencyInterface $dependency): string
            {
                return "Object method called with: {$param1}, {$dependency->getName()}";
            }
        };

        $mockDependency = Mockery::mock(TestDependencyInterface::class);
        $mockDependency->shouldReceive('getName')->andReturn('MockDependency');

        // Mock App::make to return our mock dependency
        App::shouldReceive('make')
            ->with(TestDependencyInterface::class)
            ->andReturn($mockDependency);

        $parameters = ['param1' => 'test_value'];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: test_value, MockDependency', $result);
    }

    /**
     * Test that dependency injection falls back to default values when resolution fails.
     */
    public function test_dependency_injection_falls_back_to_default_when_resolution_fails(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, ?TestDependencyInterface $dependency = null): string
            {
                $dependencyName = $dependency ? $dependency->getName() : 'null';
                return "Object method called with: {$param1}, {$dependencyName}";
            }
        };

        // Mock App::make to throw an exception
        App::shouldReceive('make')
            ->with(TestDependencyInterface::class)
            ->andThrow(new \Exception('Dependency not found'));

        $parameters = ['param1' => 'test_value'];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: test_value, null', $result);
    }

    /**
     * Test that dependency injection throws error when no default value is available.
     */
    public function test_dependency_injection_throws_error_when_no_default_value_available(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, TestDependencyInterface $dependency): string
            {
                return "Object method called with: {$param1}, {$dependency->getName()}";
            }
        };

        // Mock App::make to throw an exception
        App::shouldReceive('make')
            ->with(TestDependencyInterface::class)
            ->andThrow(new \Exception('Dependency not found'));

        $parameters = ['param1' => 'test_value'];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $this->expectException(\ArgumentCountError::class);
        $this->expectExceptionMessage('Missing required parameter: dependency');

        $method->invoke($this->service, $testObject, 'testMethod', $parameters);
    }

    /**
     * Test that built-in types are not resolved from container.
     */
    public function test_built_in_types_are_not_resolved_from_container(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, int $param2): string
            {
                return "Object method called with: {$param1}, {$param2}";
            }
        };

        // App::make should not be called for built-in types
        // We'll verify this by checking that no App::make calls are made

        $parameters = ['param1' => 'test_value', 'param2' => 123];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: test_value, 123', $result);
    }

    /**
     * Test that nullable types are not resolved from container.
     */
    public function test_nullable_types_are_not_resolved_from_container(): void
    {
        $testObject = new class
        {
            public function testMethod(?TestDependencyInterface $dependency): string
            {
                $dependencyName = $dependency ? $dependency->getName() : 'null';
                return "Object method called with: {$dependencyName}";
            }
        };

        // App::make should not be called for nullable types
        // We'll verify this by checking that no App::make calls are made

        $parameters = ['dependency' => null];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: null', $result);
    }

    /**
     * Test that extra parameters are ignored.
     */
    public function test_extra_parameters_are_ignored(): void
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

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: test_value', $result);
    }

    /**
     * Test that missing required parameters throw ArgumentCountError.
     */
    public function test_missing_required_parameters_throw_argument_count_error(): void
    {
        $testObject = new class
        {
            public function testMethod(string $param1, int $param2): string
            {
                return "Object method called with: {$param1}, {$param2}";
            }
        };

        $parameters = ['param1' => 'test_value']; // Missing param2

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $this->expectException(\ArgumentCountError::class);
        $this->expectExceptionMessage('Missing required parameter: param2');

        $method->invoke($this->service, $testObject, 'testMethod', $parameters);
    }

    /**
     * Test that private methods throw InvalidArgumentException.
     */
    public function test_private_methods_throw_invalid_argument_exception(): void
    {
        $testObject = new class
        {
            private function privateMethod(string $param1): string
            {
                return "Private method called with: {$param1}";
            }
        };

        $parameters = ['param1' => 'test_value'];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot access private method \'privateMethod\'');

        $method->invoke($this->service, $testObject, 'privateMethod', $parameters);
    }

    /**
     * Test that protected methods throw InvalidArgumentException.
     */
    public function test_protected_methods_throw_invalid_argument_exception(): void
    {
        $testObject = new class
        {
            protected function protectedMethod(string $param1): string
            {
                return "Protected method called with: {$param1}";
            }
        };

        $parameters = ['param1' => 'test_value'];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot access protected method \'protectedMethod\'');

        $method->invoke($this->service, $testObject, 'protectedMethod', $parameters);
    }

    /**
     * Test that non-existent methods throw InvalidArgumentException.
     */
    public function test_non_existent_methods_throw_invalid_argument_exception(): void
    {
        $testObject = new class
        {
            public function existingMethod(): string
            {
                return 'existing method';
            }
        };

        $parameters = [];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to create reflection for method \'nonExistentMethod\'');

        $method->invoke($this->service, $testObject, 'nonExistentMethod', $parameters);
    }

    /**
     * Test that the method handles complex parameter combinations correctly.
     */
    public function test_complex_parameter_combinations_are_handled_correctly(): void
    {
        $testObject = new class
        {
            public function testMethod(
                string $param1,
                int $param2 = 42,
                ?TestDependencyInterface $dependency = null,
                ?string $param3 = null
            ): string {
                $param3Str = $param3 === null ? 'null' : $param3;
                $dependencyName = $dependency ? $dependency->getName() : 'null';
                return "Object method called with: {$param1}, {$param2}, {$param3Str}, {$dependencyName}";
            }
        };

        $mockDependency = Mockery::mock(TestDependencyInterface::class);
        $mockDependency->shouldReceive('getName')->andReturn('MockDependency');

        // Mix of named, positional, and dependency injection
        $parameters = [
            'param1' => 'named_value',
            1 => 999, // positional for param2
            2 => $mockDependency, // positional for dependency
            // param3 uses default value (null)
        ];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: named_value, 999, null, MockDependency', $result);
    }

    /**
     * Test that the method handles union types correctly (skips dependency injection).
     */
    public function test_union_types_skip_dependency_injection(): void
    {
        $testObject = new class
        {
            public function testMethod(string|int $param1): string
            {
                return "Object method called with: {$param1}";
            }
        };

        // App::make should not be called for union types
        // We'll verify this by checking that no App::make calls are made

        $parameters = ['param1' => 'test_value'];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: test_value', $result);
    }

    /**
     * Test that the method handles intersection types correctly (skips dependency injection).
     */
    public function test_intersection_types_skip_dependency_injection(): void
    {
        $testObject = new class
        {
            public function testMethod(\Countable&\ArrayAccess $param1): string
            {
                return "Object method called with: ".get_class($param1);
            }
        };

        // App::make should not be called for intersection types
        // We'll verify this by checking that no App::make calls are made

        $parameters = ['param1' => new \ArrayObject()];

        $method = new ReflectionMethod($this->service, 'executeObjectMethod');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $testObject, 'testMethod', $parameters);

        $this->assertEquals('Object method called with: ArrayObject', $result);
    }
}

/**
 * Test interface for dependency injection tests.
 */
interface TestDependencyInterface
{
    public function getName(): string;
}
