<?php

declare(strict_types=1);

use Fsm\Data\TransitionInput;
use Fsm\Guards\CompositeGuard;
use Tests\TestCase;

/**
 * Tests for edge cases in parameter resolution in executeCallableWithInstance method.
 *
 * This test covers various edge cases that could cause issues with parameter
 * resolution, including mixed parameter types, complex default values, and
 * edge cases in parameter mapping.
 */
class CompositeGuardParameterResolutionEdgeCasesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that parameters with complex default values work correctly.
     */
    public function test_parameters_with_complex_default_values(): void
    {
        // Create a spy object with complex default values
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(
                string $param1 = 'default_string',
                array $param2 = ['default', 'array'],
                ?object $param3 = null,
                int $param4 = 42
            ): bool {
                $this->called = true;
                $this->receivedParams = [$param1, $param2, $param3, $param4];

                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with empty parameters (should use all defaults)
        $parameters = [];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with default values
        $this->assertTrue($guardSpy->called);
        $this->assertSame('default_string', $guardSpy->receivedParams[0]);
        $this->assertSame(['default', 'array'], $guardSpy->receivedParams[1]);
        $this->assertNull($guardSpy->receivedParams[2]);
        $this->assertSame(42, $guardSpy->receivedParams[3]);
        $this->assertTrue($result);
    }

    /**
     * Test that parameters with mixed default and required values work correctly.
     */
    public function test_parameters_with_mixed_default_and_required_values(): void
    {
        // Create a spy object with mixed default and required values
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(
                string $required1,
                int $required2,
                string $optional1 = 'default1',
                int $optional2 = 100
            ): bool {
                $this->called = true;
                $this->receivedParams = [$required1, $required2, $optional1, $optional2];

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
            'required1' => 'hello',
            'required2' => 42,
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with correct values
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertSame(42, $guardSpy->receivedParams[1]);
        $this->assertSame('default1', $guardSpy->receivedParams[2]);
        $this->assertSame(100, $guardSpy->receivedParams[3]);
        $this->assertTrue($result);
    }

    /**
     * Test that parameters with positional and named parameters work correctly.
     */
    public function test_parameters_with_positional_and_named_mixed(): void
    {
        // Create a spy object
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(
                string $param1,
                int $param2,
                string $param3 = 'default3',
                int $param4 = 200
            ): bool {
                $this->called = true;
                $this->receivedParams = [$param1, $param2, $param3, $param4];

                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with mixed positional and named parameters
        $parameters = [
            'param1' => 'hello',  // named
            1 => 42,              // positional at index 1
            'param3' => 'world',  // named
            // param4 should use default
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with correct values
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertSame(42, $guardSpy->receivedParams[1]);
        $this->assertSame('world', $guardSpy->receivedParams[2]);
        $this->assertSame(200, $guardSpy->receivedParams[3]);
        $this->assertTrue($result);
    }

    /**
     * Test that parameters with duplicate keys are handled correctly.
     */
    public function test_parameters_with_duplicate_keys(): void
    {
        // Create a spy object
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

        // Test with duplicate keys (named should take precedence)
        $parameters = [
            'param1' => 'hello',
            'param2' => 42,
            0 => 'overridden_by_named',  // This should be ignored
            1 => 999,                    // This should be ignored
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with named parameters (not positional)
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertSame(42, $guardSpy->receivedParams[1]);
        $this->assertTrue($result);
    }

    /**
     * Test that parameters with null values work correctly.
     */
    public function test_parameters_with_null_values(): void
    {
        // Create a spy object with nullable parameters
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(
                ?string $param1,
                ?int $param2,
                ?array $param3 = null
            ): bool {
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

        // Test with null values
        $parameters = [
            'param1' => null,
            'param2' => null,
            // param3 should use default (null)
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with null values
        $this->assertTrue($guardSpy->called);
        $this->assertNull($guardSpy->receivedParams[0]);
        $this->assertNull($guardSpy->receivedParams[1]);
        $this->assertNull($guardSpy->receivedParams[2]);
        $this->assertTrue($result);
    }

    /**
     * Test that parameters with mixed types work correctly.
     */
    public function test_parameters_with_mixed_types(): void
    {
        // Create a spy object with mixed types
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(
                string $param1,
                int $param2,
                array $param3,
                object $param4,
                bool $param5 = true
            ): bool {
                $this->called = true;
                $this->receivedParams = [$param1, $param2, $param3, $param4, $param5];

                return true;
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with mixed types
        $parameters = [
            'param1' => 'hello',
            'param2' => 42,
            'param3' => ['nested', 'array'],
            'param4' => new \stdClass,
            // param5 should use default
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with correct types
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertSame(42, $guardSpy->receivedParams[1]);
        $this->assertSame(['nested', 'array'], $guardSpy->receivedParams[2]);
        $this->assertInstanceOf(\stdClass::class, $guardSpy->receivedParams[3]);
        $this->assertTrue($guardSpy->receivedParams[4]);
        $this->assertTrue($result);
    }

    /**
     * Test that parameters with callable types work correctly.
     */
    public function test_parameters_with_callable_types(): void
    {
        // Create a spy object with callable parameters
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(
                callable $param1,
                \Closure $param2,
                ?callable $param3 = null
            ): bool {
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

        // Test with callable types
        $closure = fn () => 'test';
        $parameters = [
            'param1' => 'strlen',
            'param2' => $closure,
            // param3 should use default (null)
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with correct callable types
        $this->assertTrue($guardSpy->called);
        $this->assertSame('strlen', $guardSpy->receivedParams[0]);
        $this->assertSame($closure, $guardSpy->receivedParams[1]);
        $this->assertNull($guardSpy->receivedParams[2]);
        $this->assertTrue($result);
    }

    /**
     * Test that parameters with resource types work correctly.
     */
    public function test_parameters_with_resource_types(): void
    {
        // Create a spy object with resource parameters
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(
                $param1,  // resource
                $param2 = null  // resource with default
            ): bool {
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

        // Test with resource types
        $resource = fopen('php://memory', 'r');
        $parameters = [
            'param1' => $resource,
            // param2 should use default (null)
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with correct resource types
        $this->assertTrue($guardSpy->called);
        $this->assertSame($resource, $guardSpy->receivedParams[0]);
        $this->assertNull($guardSpy->receivedParams[1]);
        $this->assertTrue($result);

        // Clean up
        fclose($resource);
    }

    /**
     * Test that parameters with very large arrays work correctly.
     */
    public function test_parameters_with_large_arrays(): void
    {
        // Create a spy object
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(array $param1): bool
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

        // Test with large array
        $largeArray = range(1, 1000);
        $parameters = [
            'param1' => $largeArray,
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with the large array
        $this->assertTrue($guardSpy->called);
        $this->assertSame($largeArray, $guardSpy->receivedParams[0]);
        $this->assertCount(1000, $guardSpy->receivedParams[0]);
        $this->assertTrue($result);
    }

    /**
     * Test that parameters with deeply nested arrays work correctly.
     */
    public function test_parameters_with_deeply_nested_arrays(): void
    {
        // Create a spy object
        $guardSpy = new class
        {
            public bool $called = false;

            public array $receivedParams = [];

            public function guardMethod(array $param1): bool
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

        // Test with deeply nested array
        $nestedArray = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'level5' => 'deep_value',
                        ],
                    ],
                ],
            ],
        ];
        $parameters = [
            'param1' => $nestedArray,
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called with the nested array
        $this->assertTrue($guardSpy->called);
        $this->assertSame($nestedArray, $guardSpy->receivedParams[0]);
        $this->assertSame('deep_value', $guardSpy->receivedParams[0]['level1']['level2']['level3']['level4']['level5']);
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
