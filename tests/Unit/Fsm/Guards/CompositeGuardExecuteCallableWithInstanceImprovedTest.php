<?php

declare(strict_types=1);

use Fsm\Data\TransitionGuard;
use Fsm\Data\TransitionInput;
use Fsm\Guards\CompositeGuard;
use Tests\TestCase;

/**
 * Tests for the improved executeCallableWithInstance method.
 *
 * This test covers the new implementation that properly handles both
 * associative and positional arrays using reflection for object instances.
 */
class CompositeGuardExecuteCallableWithInstanceImprovedTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that positional arrays are handled correctly.
     */
    public function test_positional_array_parameters_are_passed_correctly(): void
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

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with positional array parameters
        $parameters = [
            'hello',  // param1
            42,       // param2
            $this->createTransitionInput(), // input
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
     * Test that associative arrays are handled correctly.
     */
    public function test_associative_array_parameters_are_passed_correctly(): void
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

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with associative array parameters
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
     * Test that mixed associative and positional arrays work correctly.
     */
    public function test_mixed_array_parameters_are_handled_correctly(): void
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

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with mixed array (some named, some positional)
        $parameters = [
            'param1' => 'hello',  // named
            1 => 42,              // positional at index 1
            'input' => $this->createTransitionInput(), // named
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
     * Test that default values are used when parameters are missing.
     */
    public function test_default_values_are_used_for_missing_parameters(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(string $param1, int $param2 = 100, ?TransitionInput $input = null): bool
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

        // Test with only required parameters
        $parameters = [
            'param1' => 'hello',
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with default values
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertSame(100, $guardSpy->receivedParams[1]); // default value
        $this->assertNull($guardSpy->receivedParams[2]); // default value
        $this->assertTrue($result);
    }

    /**
     * Test that missing required parameters throw ArgumentCountError.
     */
    public function test_missing_required_parameters_throw_argument_count_error(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public function guardMethod(string $param1, int $param2): bool
            {
                $this->called = true;

                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with missing required parameters
        $parameters = [
            'param1' => 'hello',
            // param2 is missing
        ];

        $this->expectException(\ArgumentCountError::class);

        $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);
    }

    /**
     * Test that missing required parameters in positional arrays throw ArgumentCountError.
     */
    public function test_missing_required_positional_parameters_throw_argument_count_error(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public function guardMethod(string $param1, int $param2): bool
            {
                $this->called = true;

                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with missing required parameters in positional array
        $parameters = [
            'hello', // param1
            // param2 is missing
        ];

        $this->expectException(\ArgumentCountError::class);

        $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);
    }

    /**
     * Test that empty arrays work correctly with default values.
     */
    public function test_empty_array_uses_all_default_values(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(string $param1 = 'default1', int $param2 = 200): bool
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

        // Test with empty array
        $parameters = [];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with default values
        $this->assertTrue($guardSpy->called);
        $this->assertSame('default1', $guardSpy->receivedParams[0]);
        $this->assertSame(200, $guardSpy->receivedParams[1]);
        $this->assertTrue($result);
    }

    /**
     * Test that parameters are passed in the correct order regardless of array order.
     */
    public function test_parameters_order_is_preserved_despite_array_order(): void
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
     * Test that extra parameters in associative arrays are ignored.
     */
    public function test_extra_associative_parameters_are_ignored(): void
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
     * Test that null values are passed correctly.
     */
    public function test_null_values_are_passed_correctly(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(?string $param1, ?int $param2): bool
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

        // Test with associative array that has null values
        $parameters = [
            'param1' => null,
            'param2' => null,
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with null values
        $this->assertTrue($guardSpy->called);
        $this->assertNull($guardSpy->receivedParams[0]);
        $this->assertNull($guardSpy->receivedParams[1]);
        $this->assertTrue($result);
    }

    /**
     * Test that mixed types are passed correctly.
     */
    public function test_mixed_types_are_passed_correctly(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(string $param1, array $param2, object $param3): bool
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

        // Test with associative array that has mixed types
        $parameters = [
            'param1' => 'hello',
            'param2' => ['nested', 'array'],
            'param3' => new \stdClass,
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with correct types
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertSame(['nested', 'array'], $guardSpy->receivedParams[1]);
        $this->assertInstanceOf(\stdClass::class, $guardSpy->receivedParams[2]);
        $this->assertTrue($result);
    }

    /**
     * Test that the fix works with actual guard execution (integration test).
     */
    public function test_guard_execution_with_improved_parameter_handling(): void
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
     * Test that positional arrays with extra elements are handled correctly.
     */
    public function test_positional_array_with_extra_elements_works_correctly(): void
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

        // Test with positional array that has extra elements
        $parameters = [
            'hello',  // param1
            42,       // param2
            'extra',  // extra element
            'another', // another extra element
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with only the required parameters
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertSame(42, $guardSpy->receivedParams[1]);
        $this->assertCount(2, $guardSpy->receivedParams);
        $this->assertTrue($result);
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
