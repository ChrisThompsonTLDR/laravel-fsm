<?php

declare(strict_types=1);

use Fsm\Data\TransitionGuard;
use Fsm\Data\TransitionInput;
use Fsm\Guards\CompositeGuard;
use Tests\TestCase;

/**
 * Integration tests for CompositeGuard with executeCallableWithInstance method.
 *
 * This test covers the integration between the guard evaluation and the
 * executeCallableWithInstance method to ensure the fix works end-to-end.
 */
class CompositeGuardIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that guard evaluation works with the fixed executeCallableWithInstance method.
     */
    public function test_guard_evaluation_with_fixed_parameter_resolution(): void
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
     * Test that guard evaluation works with mixed parameter types.
     */
    public function test_guard_evaluation_with_mixed_parameter_types(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(
                string $param1,
                int $param2,
                array $param3,
                TransitionInput $input,
                bool $param5 = true
            ): bool {
                $this->called = true;
                $this->receivedParams = [$param1, $param2, $param3, $input, $param5];

                return true;
            }
        };

        // Create a guard with mixed parameter types
        $guard = new TransitionGuard(
            callable: [$guardSpy, 'guardMethod'],
            parameters: [
                'param1' => 'hello',
                'param2' => 42,
                'param3' => ['nested', 'array'],
                // param5 should use default
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
        $this->assertSame(['nested', 'array'], $guardSpy->receivedParams[2]);
        $this->assertInstanceOf(TransitionInput::class, $guardSpy->receivedParams[3]);
        $this->assertTrue($guardSpy->receivedParams[4]);
        $this->assertTrue($result);
    }

    /**
     * Test that guard evaluation works with nullable parameters.
     */
    public function test_guard_evaluation_with_nullable_parameters(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(
                ?string $param1,
                ?int $param2,
                TransitionInput $input,
                ?array $param4 = null
            ): bool {
                $this->called = true;
                $this->receivedParams = [$param1, $param2, $input, $param4];

                return true;
            }
        };

        // Create a guard with nullable parameters
        $guard = new TransitionGuard(
            callable: [$guardSpy, 'guardMethod'],
            parameters: [
                'param1' => null,
                'param2' => null,
                // param4 should use default (null)
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
        $this->assertNull($guardSpy->receivedParams[0]);
        $this->assertNull($guardSpy->receivedParams[1]);
        $this->assertInstanceOf(TransitionInput::class, $guardSpy->receivedParams[2]);
        $this->assertNull($guardSpy->receivedParams[3]);
        $this->assertTrue($result);
    }

    /**
     * Test that guard evaluation fails gracefully when reflection fails.
     */
    public function test_guard_evaluation_fails_gracefully_on_reflection_error(): void
    {
        // Create a guard with a non-existent method
        $guard = new TransitionGuard(
            callable: [new \stdClass, 'nonExistentMethod'],
            parameters: ['param1' => 'hello'],
            priority: 1,
            stopOnFailure: false
        );

        $composite = CompositeGuard::create([$guard], CompositeGuard::STRATEGY_ALL_MUST_PASS);
        $input = $this->createTransitionInput();

        // Execute the guard evaluation - should throw FsmTransitionFailedException
        $this->expectException(\Fsm\Exceptions\FsmTransitionFailedException::class);

        $composite->evaluate($input, 'status');
    }

    /**
     * Test that guard evaluation works with multiple guards.
     */
    public function test_guard_evaluation_with_multiple_guards(): void
    {
        // Create spy objects to track calls
        $guardSpy1 = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod1(string $param1, TransitionInput $input): bool
            {
                $this->called = true;
                $this->receivedParams = [$param1, $input];

                return true;
            }
        };

        $guardSpy2 = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod2(int $param2, TransitionInput $input): bool
            {
                $this->called = true;
                $this->receivedParams = [$param2, $input];

                return true;
            }
        };

        // Create guards with different parameters
        $guard1 = new TransitionGuard(
            callable: [$guardSpy1, 'guardMethod1'],
            parameters: ['param1' => 'hello'],
            priority: 2,
            stopOnFailure: false
        );

        $guard2 = new TransitionGuard(
            callable: [$guardSpy2, 'guardMethod2'],
            parameters: ['param2' => 42],
            priority: 1,
            stopOnFailure: false
        );

        $composite = CompositeGuard::create([$guard1, $guard2], CompositeGuard::STRATEGY_ALL_MUST_PASS);
        $input = $this->createTransitionInput();

        // Execute the guard evaluation
        $result = $composite->evaluate($input, 'status');

        // Verify both guards were called with correct parameters
        $this->assertTrue($guardSpy1->called);
        $this->assertSame('hello', $guardSpy1->receivedParams[0]);
        $this->assertInstanceOf(TransitionInput::class, $guardSpy1->receivedParams[1]);

        $this->assertTrue($guardSpy2->called);
        $this->assertSame(42, $guardSpy2->receivedParams[0]);
        $this->assertInstanceOf(TransitionInput::class, $guardSpy2->receivedParams[1]);

        $this->assertTrue($result);
    }

    /**
     * Test that guard evaluation works with different evaluation strategies.
     */
    public function test_guard_evaluation_with_different_strategies(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(string $param1, TransitionInput $input): bool
            {
                $this->called = true;
                $this->receivedParams = [$param1, $input];

                return true;
            }
        };

        // Create a guard
        $guard = new TransitionGuard(
            callable: [$guardSpy, 'guardMethod'],
            parameters: ['param1' => 'hello'],
            priority: 1,
            stopOnFailure: false
        );

        // Test with ANY_MUST_PASS strategy
        $composite = CompositeGuard::create([$guard], CompositeGuard::STRATEGY_ANY_MUST_PASS);
        $input = $this->createTransitionInput();

        $result = $composite->evaluate($input, 'status');

        $this->assertTrue($guardSpy->called);
        $this->assertTrue($result);

        // Reset spy
        $guardSpy->called = false;
        $guardSpy->receivedParams = [];

        // Test with PRIORITY_FIRST strategy
        $composite = CompositeGuard::create([$guard], CompositeGuard::STRATEGY_PRIORITY_FIRST);
        $input = $this->createTransitionInput();

        $result = $composite->evaluate($input, 'status');

        $this->assertTrue($guardSpy->called);
        $this->assertTrue($result);
    }

    /**
     * Test that guard evaluation works with complex parameter resolution.
     */
    public function test_guard_evaluation_with_complex_parameter_resolution(): void
    {
        // Create a spy object with complex parameters
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(
                string $param1,
                int $param2,
                array $param3,
                object $param4,
                callable $param5,
                TransitionInput $input,
                bool $param7 = true
            ): bool {
                $this->called = true;
                $this->receivedParams = [$param1, $param2, $param3, $param4, $param5, $input, $param7];

                return true;
            }
        };

        // Create a guard with complex parameters
        $guard = new TransitionGuard(
            callable: [$guardSpy, 'guardMethod'],
            parameters: [
                'param1' => 'hello',
                'param2' => 42,
                'param3' => ['nested', 'array'],
                'param4' => new \stdClass,
                'param5' => 'strlen',
                // param7 should use default
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
        $this->assertSame(['nested', 'array'], $guardSpy->receivedParams[2]);
        $this->assertInstanceOf(\stdClass::class, $guardSpy->receivedParams[3]);
        $this->assertSame('strlen', $guardSpy->receivedParams[4]);
        $this->assertInstanceOf(TransitionInput::class, $guardSpy->receivedParams[5]);
        $this->assertTrue($guardSpy->receivedParams[6]);
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
