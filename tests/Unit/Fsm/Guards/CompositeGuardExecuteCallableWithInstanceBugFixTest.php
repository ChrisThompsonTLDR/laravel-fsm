<?php

declare(strict_types=1);

use Fsm\Data\TransitionGuard;
use Fsm\Data\TransitionInput;
use Fsm\Guards\CompositeGuard;
use Tests\TestCase;

/**
 * Tests for the bug fix in executeCallableWithInstance method.
 *
 * The bug was that when calling object methods with associative arrays,
 * the spread operator (...) would pass values positionally instead of by name,
 * causing incorrect argument assignment.
 */
class CompositeGuardExecuteCallableWithInstanceBugFixTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that demonstrates the bug fix for associative array parameter passing.
     *
     * Before the fix: Using ...$parameters would pass values positionally,
     * causing incorrect argument assignment.
     *
     * After the fix: Using call_user_func_array with array_values() ensures
     * parameters are passed in the correct order.
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
     * Test that parameters are passed in the correct order regardless of
     * the order in the associative array.
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
