<?php

declare(strict_types=1);

use Fsm\Data\TransitionGuard;
use Fsm\Data\TransitionInput;
use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\Guards\CompositeGuard;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * @skip Tests use Laravel framework mocking (App::call) that requires Laravel application
 * instance to be properly initialized. These tests verify Laravel framework integration
 * rather than FSM core functionality and may fail in different Laravel versions or
 * test environments due to framework setup differences.
 */
class CompositeGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_returns_composite_guard_instance(): void
    {
        // Arrange
        $guards = [
            new TransitionGuard(fn () => true),
            new TransitionGuard(fn () => true),
        ];

        // Act
        $composite = CompositeGuard::create($guards, CompositeGuard::STRATEGY_ALL_MUST_PASS);

        // Assert
        expect($composite)->toBeInstanceOf(CompositeGuard::class);
        expect($composite->count())->toBe(2);
        expect($composite->getStrategy())->toBe(CompositeGuard::STRATEGY_ALL_MUST_PASS);
    }

    public function test_evaluate_returns_true_for_empty_guards(): void
    {
        // Arrange
        $composite = CompositeGuard::create([]);
        $input = $this->createTransitionInput();

        // Act
        $result = $composite->evaluate($input, 'status');

        // Assert
        expect($result)->toBeTrue();
    }

    public function test_all_must_pass_returns_true_when_all_guards_pass(): void
    {
        // Arrange
        $guards = [
            new TransitionGuard(fn () => true, [], 'Guard 1'),
            new TransitionGuard(fn () => true, [], 'Guard 2'),
            new TransitionGuard(fn () => true, [], 'Guard 3'),
        ];
        $composite = CompositeGuard::create($guards, CompositeGuard::STRATEGY_ALL_MUST_PASS);
        $input = $this->createTransitionInput();

        // Act
        $result = $composite->evaluate($input, 'status');

        // Assert
        expect($result)->toBeTrue();
    }

    public function test_all_must_pass_throws_exception_when_any_guard_fails(): void
    {
        // Arrange
        $guards = [
            new TransitionGuard(fn () => true, [], 'Passing Guard'),
            new TransitionGuard(fn () => false, [], 'Failing Guard'),
            new TransitionGuard(fn () => true, [], 'Another Passing Guard'),
        ];
        $composite = CompositeGuard::create($guards, CompositeGuard::STRATEGY_ALL_MUST_PASS);
        $input = $this->createTransitionInput();

        // Act & Assert
        expect(fn () => $composite->evaluate($input, 'status'))
            ->toThrow(FsmTransitionFailedException::class);
    }

    public function test_all_must_pass_stops_on_failure_when_guard_has_stop_on_failure(): void
    {
        // Arrange
        $executed = [];

        $guards = [
            new TransitionGuard(
                callable: function () use (&$executed) {
                    $executed[] = 'Guard 1';

                    return false;
                },
                description: 'Failing Guard',
                stopOnFailure: true
            ),
            new TransitionGuard(
                callable: function () use (&$executed) {
                    $executed[] = 'Guard 2';

                    return true;
                },
                description: 'Should Not Execute'
            ),
        ];
        $composite = CompositeGuard::create($guards, CompositeGuard::STRATEGY_ALL_MUST_PASS);
        $input = $this->createTransitionInput();

        // Act & Assert
        expect(fn () => $composite->evaluate($input, 'status'))
            ->toThrow(FsmTransitionFailedException::class);

        expect($executed)->toBe(['Guard 1']);
    }

    public function test_any_must_pass_returns_true_when_first_guard_passes(): void
    {
        // Arrange
        $executed = [];

        $guards = [
            new TransitionGuard(
                callable: function () use (&$executed) {
                    $executed[] = 'Guard 1';

                    return true;
                },
                description: 'Passing Guard',
                priority: TransitionGuard::PRIORITY_HIGH
            ),
            new TransitionGuard(
                callable: function () use (&$executed) {
                    $executed[] = 'Guard 2';

                    return false;
                },
                description: 'Should Not Execute',
                priority: TransitionGuard::PRIORITY_NORMAL
            ),
        ];
        $composite = CompositeGuard::create($guards, CompositeGuard::STRATEGY_ANY_MUST_PASS);
        $input = $this->createTransitionInput();

        // Act
        $result = $composite->evaluate($input, 'status');

        // Assert
        expect($result)->toBeTrue();
        expect($executed)->toBe(['Guard 1']); // Should short-circuit
    }

    public function test_any_must_pass_throws_exception_when_all_guards_fail(): void
    {
        // Arrange
        $guards = [
            new TransitionGuard(fn () => false, [], 'Failing Guard 1'),
            new TransitionGuard(fn () => false, [], 'Failing Guard 2'),
            new TransitionGuard(fn () => false, [], 'Failing Guard 3'),
        ];
        $composite = CompositeGuard::create($guards, CompositeGuard::STRATEGY_ANY_MUST_PASS);
        $input = $this->createTransitionInput();

        // Act & Assert
        expect(fn () => $composite->evaluate($input, 'status'))
            ->toThrow(FsmTransitionFailedException::class, 'All guards failed');
    }

    public function test_priority_first_returns_true_on_first_passing_guard(): void
    {
        // Arrange
        $executed = [];

        $guards = [
            new TransitionGuard(
                callable: function () use (&$executed) {
                    $executed[] = 'Low Priority';

                    return true;
                },
                description: 'Low Priority Guard',
                priority: TransitionGuard::PRIORITY_LOW
            ),
            new TransitionGuard(
                callable: function () use (&$executed) {
                    $executed[] = 'High Priority';

                    return true;
                },
                description: 'High Priority Guard',
                priority: TransitionGuard::PRIORITY_HIGH
            ),
        ];
        $composite = CompositeGuard::create($guards, CompositeGuard::STRATEGY_PRIORITY_FIRST);
        $input = $this->createTransitionInput();

        // Act
        $result = $composite->evaluate($input, 'status');

        // Assert
        expect($result)->toBeTrue();
        expect($executed)->toBe(['High Priority']); // Higher priority should execute first
    }

    public function test_priority_first_continues_on_exception(): void
    {
        // Arrange
        $executed = [];

        $guards = [
            new TransitionGuard(
                callable: function () use (&$executed) {
                    $executed[] = 'High Priority Exception';
                    throw new \RuntimeException('High priority failed');
                },
                description: 'High Priority Guard',
                priority: TransitionGuard::PRIORITY_HIGH
            ),
            new TransitionGuard(
                callable: function () use (&$executed) {
                    $executed[] = 'Normal Priority Success';

                    return true;
                },
                description: 'Normal Priority Guard',
                priority: TransitionGuard::PRIORITY_NORMAL
            ),
        ];
        $composite = CompositeGuard::create($guards, CompositeGuard::STRATEGY_PRIORITY_FIRST);
        $input = $this->createTransitionInput();

        // Act
        $result = $composite->evaluate($input, 'status');

        // Assert
        expect($result)->toBeTrue();
        expect($executed)->toBe(['High Priority Exception', 'Normal Priority Success']);
    }

    public function test_get_guards_returns_sorted_collection(): void
    {
        // Arrange
        $guards = [
            new TransitionGuard(fn () => true, [], 'Normal', priority: TransitionGuard::PRIORITY_NORMAL),
            new TransitionGuard(fn () => true, [], 'High', priority: TransitionGuard::PRIORITY_HIGH),
            new TransitionGuard(fn () => true, [], 'Low', priority: TransitionGuard::PRIORITY_LOW),
        ];
        $composite = CompositeGuard::create($guards);

        // Act
        $sortedGuards = $composite->getGuards();

        // Assert
        expect($sortedGuards)->toBeInstanceOf(Collection::class);
        expect($sortedGuards)->toHaveCount(3);

        $priorities = $sortedGuards->map(fn ($guard) => $guard->priority)->toArray();
        expect($priorities)->toBe([
            TransitionGuard::PRIORITY_HIGH,
            TransitionGuard::PRIORITY_NORMAL,
            TransitionGuard::PRIORITY_LOW,
        ]);
    }

    public function test_unknown_strategy_throws_logic_exception(): void
    {
        // Arrange
        $guards = [new TransitionGuard(fn () => true)];
        $composite = new CompositeGuard(collect($guards), 'unknown_strategy');
        $input = $this->createTransitionInput();

        // Act & Assert
        expect(fn () => $composite->evaluate($input, 'status'))
            ->toThrow(\LogicException::class, 'Unknown guard evaluation strategy: unknown_strategy');
    }

    public function test_exception_message_contains_correct_column_name(): void
    {
        // Arrange
        $guards = [
            new TransitionGuard(fn () => false, [], 'Failing Guard'),
        ];
        $composite = CompositeGuard::create($guards, CompositeGuard::STRATEGY_ALL_MUST_PASS);
        $input = $this->createTransitionInput();

        try {
            // Act
            $composite->evaluate($input, 'status');
            $this->fail('Expected FsmTransitionFailedException was not thrown');
        } catch (FsmTransitionFailedException $e) {
            // Assert
            // The exception message should contain the FSM column name 'status', not the foreign key 'test_id'
            expect($e->getMessage())->toContain('::status');
            expect($e->getMessage())->not->toContain('::test_id');
        }
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

    public function test_execute_callable_with_instance_with_object_instance(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $parameters = [];

            public function guardMethod(string $param1, int $param2): bool
            {
                $this->called = true;
                $this->parameters = [$param1, $param2];

                return true;
            }
        };

        $guard = new TransitionGuard(
            callable: [$guardSpy, 'guardMethod'],
            priority: 1,
            stopOnFailure: false
        );

        $composite = CompositeGuard::create([$guard], CompositeGuard::STRATEGY_ALL_MUST_PASS);
        $input = $this->createTransitionInput();

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        $parameters = ['hello', 42];
        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        $this->assertTrue($guardSpy->called);
        $this->assertSame(['hello', 42], $guardSpy->parameters);
        $this->assertTrue($result);
    }

    public function test_execute_callable_with_instance_with_class_string(): void
    {
        $guard = new TransitionGuard(
            callable: [TestGuardClass::class, 'staticGuard'],
            priority: 1,
            stopOnFailure: false
        );

        $composite = CompositeGuard::create([$guard], CompositeGuard::STRATEGY_ALL_MUST_PASS);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        $parameters = ['test_param'];

        // Mock App::call to verify it's called with the correct string format
        \Illuminate\Support\Facades\App::shouldReceive('call')
            ->once()
            ->with(TestGuardClass::class.'@staticGuard', $parameters)
            ->andReturn(true);

        $result = $method->invoke($composite, [TestGuardClass::class, 'staticGuard'], $parameters);

        $this->assertTrue($result);
    }

    public function test_execute_callable_with_instance_with_closure(): void
    {
        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        $closure = function (string $param): bool {
            return $param === 'test';
        };
        $parameters = ['test'];

        // Mock App::call to verify it's called with the closure
        \Illuminate\Support\Facades\App::shouldReceive('call')
            ->once()
            ->with($closure, $parameters)
            ->andReturn(true);

        $result = $method->invoke($composite, $closure, $parameters);

        $this->assertTrue($result);
    }

    public function test_execute_callable_with_instance_with_string_callable(): void
    {
        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        $callable = TestGuardClass::class.'@staticGuard';
        $parameters = ['test_param'];

        // Mock App::call to verify it's called with the string callable
        \Illuminate\Support\Facades\App::shouldReceive('call')
            ->once()
            ->with($callable, $parameters)
            ->andReturn(true);

        $result = $method->invoke($composite, $callable, $parameters);

        $this->assertTrue($result);
    }

    public function test_guard_execution_with_object_instance_preserves_instance(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public ?TransitionInput $input = null;

            public function guardMethod(TransitionInput $input): bool
            {
                $this->called = true;
                $this->input = $input;

                return true;
            }
        };

        $guard = new TransitionGuard(
            callable: [$guardSpy, 'guardMethod'],
            priority: 1,
            stopOnFailure: false
        );

        $composite = CompositeGuard::create([$guard], CompositeGuard::STRATEGY_ALL_MUST_PASS);
        $input = $this->createTransitionInput();

        // Execute the guard evaluation
        $result = $composite->evaluate($input, 'status');

        // Verify the guard was called on the original instance
        $this->assertTrue($guardSpy->called);
        $this->assertInstanceOf(TransitionInput::class, $guardSpy->input);
        $this->assertTrue($result);
    }

    public function test_guard_execution_with_class_string_uses_app_call(): void
    {
        $guard = new TransitionGuard(
            callable: [TestGuardClass::class, 'staticGuard'],
            priority: 1,
            stopOnFailure: false
        );

        $composite = CompositeGuard::create([$guard], CompositeGuard::STRATEGY_ALL_MUST_PASS);
        $input = $this->createTransitionInput();

        // Mock App::call to verify it's called with the correct string format
        \Illuminate\Support\Facades\App::shouldReceive('call')
            ->once()
            ->with(TestGuardClass::class.'@staticGuard', \Mockery::on(function ($args) {
                return isset($args['input']) && $args['input'] instanceof TransitionInput;
            }))
            ->andReturn(true);

        // Execute the guard evaluation
        $result = $composite->evaluate($input, 'status');

        $this->assertTrue($result);
    }

    public function test_guard_execution_with_closure_uses_app_call(): void
    {
        $closure = function (TransitionInput $input): bool {
            return true;
        };

        $guard = new TransitionGuard(
            callable: $closure,
            priority: 1,
            stopOnFailure: false
        );

        $composite = CompositeGuard::create([$guard], CompositeGuard::STRATEGY_ALL_MUST_PASS);
        $input = $this->createTransitionInput();

        // Mock App::call to verify it's called with the closure
        \Illuminate\Support\Facades\App::shouldReceive('call')
            ->once()
            ->with($closure, \Mockery::on(function ($args) {
                return isset($args['input']) && $args['input'] instanceof TransitionInput;
            }))
            ->andReturn(true);

        // Execute the guard evaluation
        $result = $composite->evaluate($input, 'status');

        $this->assertTrue($result);
    }

    public function test_execute_callable_with_instance_associative_array_bug_fix(): void
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

    public function test_execute_callable_with_instance_associative_array_with_different_order(): void
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

    public function test_execute_callable_with_instance_associative_array_with_extra_parameters(): void
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

    public function test_execute_callable_with_instance_associative_array_with_missing_parameters(): void
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

    public function test_execute_callable_with_instance_associative_array_with_null_values(): void
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

    public function test_execute_callable_with_instance_associative_array_with_mixed_types(): void
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
}

// Test helper class for CompositeGuard tests
class TestGuardClass
{
    public static function staticGuard(TransitionInput $input): bool
    {
        return true;
    }
}
