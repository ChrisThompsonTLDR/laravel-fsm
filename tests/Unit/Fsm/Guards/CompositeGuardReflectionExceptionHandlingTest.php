<?php

declare(strict_types=1);

use Fsm\Data\TransitionInput;
use Fsm\Guards\CompositeGuard;
use Tests\TestCase;

/**
 * Tests for ReflectionException handling in executeCallableWithInstance method.
 *
 * This test covers the new error handling for ReflectionException that was
 * added to prevent unexpected runtime failures when reflection fails.
 */
class CompositeGuardReflectionExceptionHandlingTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that ReflectionException is properly caught and wrapped.
     */
    public function test_reflection_exception_is_caught_and_wrapped(): void
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

        // Test with non-existent method (should cause ReflectionException)
        $parameters = ['param1' => 'hello'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Failed to create reflection for method 'nonExistentMethod' on class");

        $method->invoke($composite, [$guardSpy, 'nonExistentMethod'], $parameters);
    }

    /**
     * Test that ReflectionException preserves the original exception as cause.
     */
    public function test_reflection_exception_preserves_original_exception(): void
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

        // Test with non-existent method (should cause ReflectionException)
        $parameters = ['param1' => 'hello'];

        try {
            $method->invoke($composite, [$guardSpy, 'nonExistentMethod'], $parameters);
            $this->fail('Expected InvalidArgumentException to be thrown');
        } catch (\InvalidArgumentException $e) {
            // Verify the original ReflectionException is preserved as cause
            $this->assertInstanceOf(\ReflectionException::class, $e->getPrevious());
            $this->assertStringContainsString('nonExistentMethod', $e->getPrevious()->getMessage());
        }
    }

    /**
     * Test that ReflectionException is thrown for invalid class names.
     */
    public function test_reflection_exception_for_invalid_class(): void
    {
        // Create a mock object that will cause reflection issues
        $guardSpy = new class
        {
            public function __call(string $name, array $arguments)
            {
                // This will cause reflection to fail when trying to get method info
                throw new \ReflectionException("Method {$name} does not exist");
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        $parameters = ['param1' => 'hello'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Failed to create reflection for method 'testMethod' on class");

        $method->invoke($composite, [$guardSpy, 'testMethod'], $parameters);
    }

    /**
     * Test that ReflectionException is handled for private methods.
     */
    public function test_reflection_exception_for_private_method(): void
    {
        // Create an object with a private method
        $guardSpy = new class
        {
            private function privateMethod(): bool
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

        // Reflection will fail when trying to access private method
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot access private method 'privateMethod' on class");

        $method->invoke($composite, [$guardSpy, 'privateMethod'], $parameters);
    }

    /**
     * Test that ReflectionException is handled for protected methods.
     */
    public function test_reflection_exception_for_protected_method(): void
    {
        // Create an object with a protected method
        $guardSpy = new class
        {
            protected function protectedMethod(): bool
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

        // Reflection will fail when trying to access protected method
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot access protected method 'protectedMethod' on class");

        $method->invoke($composite, [$guardSpy, 'protectedMethod'], $parameters);
    }

    /**
     * Test that ReflectionException is handled for abstract methods.
     */
    public function test_reflection_exception_for_abstract_method(): void
    {
        // Create an abstract class with an abstract method
        $abstractClass = new class
        {
            public function concreteMethod(): bool
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Failed to create reflection for method 'abstractMethod' on class");

        $method->invoke($composite, [$abstractClass, 'abstractMethod'], $parameters);
    }

    /**
     * Test that valid method calls still work after adding error handling.
     */
    public function test_valid_method_calls_still_work(): void
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

        // Test with valid method call
        $parameters = [
            'param1' => 'hello',
            'param2' => 42,
        ];

        $result = $method->invoke($composite, [$guardSpy, 'guardMethod'], $parameters);

        // Verify the method was called correctly
        $this->assertTrue($guardSpy->called);
        $this->assertSame('hello', $guardSpy->receivedParams[0]);
        $this->assertSame(42, $guardSpy->receivedParams[1]);
        $this->assertTrue($result);
    }

    /**
     * Test that the error message includes the correct class and method names.
     */
    public function test_error_message_includes_correct_class_and_method_names(): void
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

        try {
            $method->invoke($composite, [$guardSpy, 'nonExistentMethod'], $parameters);
            $this->fail('Expected InvalidArgumentException to be thrown');
        } catch (\InvalidArgumentException $e) {
            // Verify the error message includes the correct class and method names
            $this->assertStringContainsString('nonExistentMethod', $e->getMessage());
            $this->assertStringContainsString(get_class($guardSpy), $e->getMessage());
            $this->assertStringContainsString('Failed to create reflection', $e->getMessage());
        }
    }

    /**
     * Test that the error handling works with complex class hierarchies.
     */
    public function test_error_handling_with_complex_class_hierarchies(): void
    {
        // Create a class that extends another class
        $parentClass = new class
        {
            public function parentMethod(): bool
            {
                return true;
            }
        };

        $childClass = new class($parentClass) extends \stdClass
        {
            public function childMethod(): bool
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

        // Test with non-existent method on child class
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Failed to create reflection for method 'nonExistentMethod' on class");

        $method->invoke($composite, [$childClass, 'nonExistentMethod'], $parameters);
    }

    /**
     * Test that the error handling works with anonymous classes.
     */
    public function test_error_handling_with_anonymous_classes(): void
    {
        // Create an anonymous class
        $anonymousClass = new class
        {
            public function validMethod(): bool
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

        // Test with non-existent method on anonymous class
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Failed to create reflection for method 'invalidMethod' on class");

        $method->invoke($composite, [$anonymousClass, 'invalidMethod'], $parameters);
    }

    /**
     * Test that the error handling works with interfaces.
     */
    public function test_error_handling_with_interfaces(): void
    {
        // Create a class that implements an interface
        $interfaceClass = new class implements \Iterator
        {
            public function current(): mixed
            {
                return null;
            }

            public function next(): void
            {
                // Implementation
            }

            public function key(): mixed
            {
                return null;
            }

            public function valid(): bool
            {
                return false;
            }

            public function rewind(): void
            {
                // Implementation
            }
        };

        $composite = CompositeGuard::create([]);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($composite);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        $parameters = ['param1' => 'hello'];

        // Test with non-existent method on interface implementing class
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Failed to create reflection for method 'nonExistentMethod' on class");

        $method->invoke($composite, [$interfaceClass, 'nonExistentMethod'], $parameters);
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
