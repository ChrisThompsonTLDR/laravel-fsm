<?php

declare(strict_types=1);

use Fsm\Data\TransitionGuard;
use Fsm\Data\TransitionInput;
use Fsm\Guards\CompositeGuard;
use Tests\TestCase;

/**
 * Comprehensive tests for the bug fix in executeCallableWithInstance method.
 *
 * This test covers both bugs:
 * 1. Parameter mapping bug that caused ArgumentCountError when resolving positional arguments from associative arrays
 * 2. Unreachable isPublic() check for reflected method if ReflectionMethod constructor fails
 */
class CompositeGuardExecuteCallableWithInstanceComprehensiveBugFixTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that the isPublic() check is now reachable and works correctly for private methods.
     */
    public function test_is_public_check_is_reachable_for_private_methods(): void
    {
        // Create an object with a private method
        $guardSpy = new class
        {
            private function privateMethod(string $param1): bool
            {
                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        $parameters = ['param1' => 'hello'];

        // This should now throw an InvalidArgumentException with the correct message
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot access private method 'privateMethod' on class");

        $method->invoke($composite, [$guardSpy, 'privateMethod'], $parameters);
    }

    /**
     * Test that the isPublic() check is now reachable and works correctly for protected methods.
     */
    public function test_is_public_check_is_reachable_for_protected_methods(): void
    {
        // Create an object with a protected method
        $guardSpy = new class
        {
            protected function protectedMethod(string $param1): bool
            {
                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        $parameters = ['param1' => 'hello'];

        // This should now throw an InvalidArgumentException with the correct message
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot access protected method 'protectedMethod' on class");

        $method->invoke($composite, [$guardSpy, 'protectedMethod'], $parameters);
    }

    /**
     * Test that ReflectionException is still properly caught and wrapped.
     */
    public function test_reflection_exception_is_still_caught_and_wrapped(): void
    {
        // Create an object with a non-existent method
        $guardSpy = new class
        {
            public function existingMethod(): bool
            {
                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        $parameters = ['param1' => 'hello'];

        // This should throw an InvalidArgumentException with the reflection error message
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Failed to create reflection for method 'nonExistentMethod' on class");

        $method->invoke($composite, [$guardSpy, 'nonExistentMethod'], $parameters);
    }

    /**
     * Test that parameter mapping bug is fixed - associative arrays work correctly.
     */
    public function test_associative_array_parameters_are_passed_correctly(): void
    {
        // Create a spy object to track calls and verify correct parameter assignment
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(string $param1, int $param2, TransitionInput $input): bool
            {
                $this->called = true;
                $this->receivedParams = [$param1, $param2, $input];

                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with associative array parameters (this was the bug)
        $parameters = [
            'param1' => 'hello',
            'param2' => 42,
            'input' => $this->createTransitionInput(),
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called and parameters were passed correctly
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertSame(42, $guardSpy->receivedParams[1]);
        $this->assertInstanceOf(TransitionInput::class, $guardSpy->receivedParams[2]);
        $this->assertTrue($result);
    }

    /**
     * Test that parameters are passed in the correct order regardless of associative array order.
     */
    public function test_parameters_order_is_preserved_despite_associative_array_order(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(TransitionInput $input, string $param1, int $param2): bool
            {
                $this->called = true;
                $this->receivedParams = [$input, $param1, $param2];

                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with associative array parameters in different order
        $parameters = [
            'param2' => 42,
            'input' => $this->createTransitionInput(),
            'param1' => 'world',
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called and parameters were passed in correct order
        $this->assertTrue($guardSpy->called);
        $this->assertInstanceOf(TransitionInput::class, $guardSpy->receivedParams[0]);
        $this->assertSame('world', $guardSpy->receivedParams[1]);
        $this->assertSame(42, $guardSpy->receivedParams[2]);
        $this->assertTrue($result);
    }

    /**
     * Test that extra parameters in the associative array are ignored.
     */
    public function test_extra_parameters_are_ignored(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(string $param1): bool
            {
                $this->called = true;
                $this->receivedParams = [$param1];

                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with associative array that has extra parameters
        $parameters = [
            'param1' => 'hello',
            'extra_param' => 'should_be_ignored',
            'another_extra' => 123,
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with only the required parameters
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertCount(1, $guardSpy->receivedParams);
        $this->assertTrue($result);
    }

    /**
     * Test that missing parameters cause an ArgumentCountError.
     */
    public function test_missing_parameters_throw_argument_count_error(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(string $param1, int $param2): bool
            {
                $this->called = true;
                $this->receivedParams = [$param1, $param2];

                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with associative array that has missing parameters
        $parameters = [
            'param1' => 'hello',
            // param2 is missing
        ];

        // This should throw an ArgumentCountError
        $this->expectException(\ArgumentCountError::class);

        $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);
    }

    /**
     * Test that default values are used when parameters are missing.
     */
    public function test_default_values_are_used_when_parameters_are_missing(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(string $param1, int $param2 = 42, string $param3 = 'default'): bool
            {
                $this->called = true;
                $this->receivedParams = [$param1, $param2, $param3];

                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with associative array that has only the required parameter
        $parameters = [
            'param1' => 'hello',
            // param2 and param3 should use default values
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with default values
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertSame(42, $guardSpy->receivedParams[1]);
        $this->assertSame('default', $guardSpy->receivedParams[2]);
        $this->assertTrue($result);
    }

    /**
     * Test that mixed parameter types (named and positional) work correctly.
     */
    public function test_mixed_parameter_types_work_correctly(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(string $param1, int $param2, string $param3 = 'default'): bool
            {
                $this->called = true;
                $this->receivedParams = [$param1, $param2, $param3];

                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with mixed parameter types
        $parameters = [
            'param1' => 'hello',
            1 => 42, // positional parameter
            // param3 should use default value
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with correct parameters
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertSame(42, $guardSpy->receivedParams[1]);
        $this->assertSame('default', $guardSpy->receivedParams[2]);
        $this->assertTrue($result);
    }

    /**
     * Test that the fix works with actual guard execution (integration test).
     */
    public function test_guard_execution_with_associative_parameters_works_correctly(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(string $param1, int $param2, TransitionInput $input): bool
            {
                $this->called = true;
                $this->receivedParams = [$param1, $param2, $input];

                return true;
            }
        };

        // Create a guard with parameters that will be merged into an associative array
        $guard = new TransitionGuard(
            callable: [$guardSpy, 'guardMethod'],
            parameters: [
                'param1' => 'hello',
                'param2' => 42,
            ],
            priority: 1,
            stopOnFailure: false
        );

        $composite = CompositeGuard::create([$guard], CompositeGuard::STRATEGY_ALL_MUST_PASS);
        $input = $this->createTransitionInput();

        // Execute the guard evaluation
        $result = $composite->evaluate($input, 'status');

        // Verify the guard was called with correct parameters
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertSame(42, $guardSpy->receivedParams[1]);
        $this->assertInstanceOf(TransitionInput::class, $guardSpy->receivedParams[2]);
        $this->assertTrue($result);
    }

    /**
     * Test that the error handling works correctly with complex scenarios.
     */
    public function test_error_handling_works_with_complex_scenarios(): void
    {
        // Test 1: Non-existent method should throw ReflectionException
        $guardSpy1 = new class
        {
            public function existingMethod(): bool
            {
                return true;
            }
        };

        $composite = CompositeGuard::create([]);
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Failed to create reflection for method 'nonExistentMethod' on class");

        $method->invoke($composite, [$guardSpy1, 'nonExistentMethod'], ['param1' => 'hello']);

        // Test 2: Private method should throw accessibility error
        $guardSpy2 = new class
        {
            private function privateMethod(): bool
            {
                return true;
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot access private method 'privateMethod' on class");

        $method->invoke($composite, [$guardSpy2, 'privateMethod'], ['param1' => 'hello']);
    }

    private function createTransitionInput(): TransitionInput
    {
        $model = Mockery::mock(\Illuminate\Database\Eloquent\Model::class);
        $model->shouldReceive('getForeignKey')->andReturn('test_id');

        return new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null,
            event: 'test_event',
            isDryRun: false
        );
    }
}
