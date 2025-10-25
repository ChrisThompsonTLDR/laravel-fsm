<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Fsm\Services\FsmEngineService;
use Orchestra\Testbench\TestCase;
use ReflectionMethod;

/**
 * Tests for the bug fix in FsmEngineService::parameterAcceptsArray method.
 *
 * This test ensures that the parameterAcceptsArray method correctly handles
 * intersection types by checking if PHP's built-in array type satisfies ALL
 * types in the intersection, rather than incorrectly checking if each individual
 * type "accepts array".
 *
 * The bug: For intersection types like Countable&ArrayAccess, the old logic
 * would check if Countable accepts array AND if ArrayAccess accepts array,
 * both of which would return false (since they're interfaces), causing the
 * method to incorrectly return false.
 *
 * The fix: Check if the PHP array type satisfies ALL types in the intersection.
 * Since PHP arrays implement Countable, ArrayAccess, Traversable, IteratorAggregate,
 * and Serializable, intersection types using these interfaces should return true.
 */
class ParameterAcceptsArrayIntersectionTypeBugFixTest extends TestCase
{
    /**
     * Get the parameterAcceptsArray method via reflection for testing.
     */
    private function getParameterAcceptsArrayMethod(): ReflectionMethod
    {
        $service = $this->app->make(FsmEngineService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('parameterAcceptsArray');
        $method->setAccessible(true);

        return $method;
    }

    /**
     * Test that intersection types with array-compatible types return true.
     *
     * This is the core bug fix: intersection types like Countable&ArrayAccess
     * should return true because PHP arrays implement both interfaces.
     */
    public function test_intersection_type_countable_and_array_access_accepts_array(): void
    {
        $method = $this->getParameterAcceptsArrayMethod();

        // Create a function with Countable&ArrayAccess parameter to get the ReflectionType
        $testFunction = function (mixed $param): void {
            // This parameter will be checked in the test
        };

        // PHP 8.1+ supports intersection types
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Intersection types require PHP 8.1+');
        }

        // Create a test function with an intersection type parameter
        $testClass = new class
        {
            public function test_method(\Countable&\ArrayAccess $param): void
            {
                // Parameter for testing
            }
        };

        $reflectionMethod = new \ReflectionMethod($testClass, 'test_method');
        $parameters = $reflectionMethod->getParameters();
        $paramType = $parameters[0]->getType();

        $this->assertInstanceOf(\ReflectionIntersectionType::class, $paramType);

        $result = $method->invoke(null, $paramType);

        $this->assertTrue(
            $result,
            'Intersection type Countable&ArrayAccess should accept array since PHP arrays implement both'
        );
    }

    /**
     * Test that intersection types with non-array-compatible types return false.
     */
    public function test_intersection_type_with_non_compatible_types_returns_false(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Intersection types require PHP 8.1+');
        }

        $method = $this->getParameterAcceptsArrayMethod();

        // Create a test with an intersection type that arrays don't satisfy
        $testClass = new class
        {
            public function test_method(\Countable&\DateTimeInterface $param): void
            {
                // This intersection cannot be satisfied by an array
            }
        };

        $reflectionMethod = new \ReflectionMethod($testClass, 'test_method');
        $parameters = $reflectionMethod->getParameters();
        $paramType = $parameters[0]->getType();

        $this->assertInstanceOf(\ReflectionIntersectionType::class, $paramType);

        $result = $method->invoke(null, $paramType);

        $this->assertFalse(
            $result,
            'Intersection type with DateTimeInterface should not accept array'
        );
    }

    /**
     * Test that intersection types with Traversable work correctly.
     */
    public function test_intersection_type_with_traversable_accepts_array(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Intersection types require PHP 8.1+');
        }

        $method = $this->getParameterAcceptsArrayMethod();

        $testClass = new class
        {
            public function test_method(\Traversable&\Countable $param): void
            {
                // Arrays are Traversable and Countable
            }
        };

        $reflectionMethod = new \ReflectionMethod($testClass, 'test_method');
        $parameters = $reflectionMethod->getParameters();
        $paramType = $parameters[0]->getType();

        $this->assertInstanceOf(\ReflectionIntersectionType::class, $paramType);

        $result = $method->invoke(null, $paramType);

        $this->assertTrue(
            $result,
            'Intersection type Traversable&Countable should accept array'
        );
    }

    /**
     * Test that intersection types with IteratorAggregate work correctly.
     */
    public function test_intersection_type_with_iterator_aggregate_accepts_array(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Intersection types require PHP 8.1+');
        }

        $method = $this->getParameterAcceptsArrayMethod();

        $testClass = new class
        {
            public function test_method(\IteratorAggregate&\ArrayAccess $param): void
            {
                // Arrays implement both IteratorAggregate and ArrayAccess
            }
        };

        $reflectionMethod = new \ReflectionMethod($testClass, 'test_method');
        $parameters = $reflectionMethod->getParameters();
        $paramType = $parameters[0]->getType();

        $this->assertInstanceOf(\ReflectionIntersectionType::class, $paramType);

        $result = $method->invoke(null, $paramType);

        $this->assertTrue(
            $result,
            'Intersection type IteratorAggregate&ArrayAccess should accept array'
        );
    }

    /**
     * Test that named array type still works correctly.
     */
    public function test_named_array_type_accepts_array(): void
    {
        $method = $this->getParameterAcceptsArrayMethod();

        $testClass = new class
        {
            public function test_method(array $param): void
            {
                // Direct array parameter
            }
        };

        $reflectionMethod = new \ReflectionMethod($testClass, 'test_method');
        $parameters = $reflectionMethod->getParameters();
        $paramType = $parameters[0]->getType();

        $this->assertInstanceOf(\ReflectionNamedType::class, $paramType);

        $result = $method->invoke(null, $paramType);

        $this->assertTrue($result, 'Named type array should accept array');
    }

    /**
     * Test that mixed type accepts array.
     */
    public function test_mixed_type_accepts_array(): void
    {
        $method = $this->getParameterAcceptsArrayMethod();

        $testClass = new class
        {
            public function test_method(mixed $param): void
            {
                // Mixed accepts everything including array
            }
        };

        $reflectionMethod = new \ReflectionMethod($testClass, 'test_method');
        $parameters = $reflectionMethod->getParameters();
        $paramType = $parameters[0]->getType();

        $this->assertInstanceOf(\ReflectionNamedType::class, $paramType);

        $result = $method->invoke(null, $paramType);

        $this->assertTrue($result, 'Mixed type should accept array');
    }

    /**
     * Test that union types with array work correctly.
     */
    public function test_union_type_with_array_accepts_array(): void
    {
        $method = $this->getParameterAcceptsArrayMethod();

        $testClass = new class
        {
            public function test_method(array|string $param): void
            {
                // Union type including array
            }
        };

        $reflectionMethod = new \ReflectionMethod($testClass, 'test_method');
        $parameters = $reflectionMethod->getParameters();
        $paramType = $parameters[0]->getType();

        $this->assertInstanceOf(\ReflectionUnionType::class, $paramType);

        $result = $method->invoke(null, $paramType);

        $this->assertTrue($result, 'Union type with array should accept array');
    }

    /**
     * Test that union types without array return false.
     */
    public function test_union_type_without_array_rejects_array(): void
    {
        $method = $this->getParameterAcceptsArrayMethod();

        $testClass = new class
        {
            public function test_method(string|int $param): void
            {
                // Union type without array
            }
        };

        $reflectionMethod = new \ReflectionMethod($testClass, 'test_method');
        $parameters = $reflectionMethod->getParameters();
        $paramType = $parameters[0]->getType();

        $this->assertInstanceOf(\ReflectionUnionType::class, $paramType);

        $result = $method->invoke(null, $paramType);

        $this->assertFalse($result, 'Union type without array should not accept array');
    }

    /**
     * Test that null parameter type accepts array (no type declaration).
     */
    public function test_null_parameter_type_accepts_array(): void
    {
        $method = $this->getParameterAcceptsArrayMethod();

        $result = $method->invoke(null, null);

        $this->assertTrue(
            $result,
            'Null parameter type (no type declaration) should accept array'
        );
    }

    /**
     * Test that string type does not accept array.
     */
    public function test_string_type_rejects_array(): void
    {
        $method = $this->getParameterAcceptsArrayMethod();

        $testClass = new class
        {
            public function test_method(string $param): void
            {
                // String parameter
            }
        };

        $reflectionMethod = new \ReflectionMethod($testClass, 'test_method');
        $parameters = $reflectionMethod->getParameters();
        $paramType = $parameters[0]->getType();

        $this->assertInstanceOf(\ReflectionNamedType::class, $paramType);

        $result = $method->invoke(null, $paramType);

        $this->assertFalse($result, 'String type should not accept array');
    }

    /**
     * Test that object type does not accept array.
     */
    public function test_object_type_rejects_array(): void
    {
        $method = $this->getParameterAcceptsArrayMethod();

        $testClass = new class
        {
            public function test_method(object $param): void
            {
                // Object parameter
            }
        };

        $reflectionMethod = new \ReflectionMethod($testClass, 'test_method');
        $parameters = $reflectionMethod->getParameters();
        $paramType = $parameters[0]->getType();

        $this->assertInstanceOf(\ReflectionNamedType::class, $paramType);

        $result = $method->invoke(null, $paramType);

        $this->assertFalse($result, 'Object type should not accept array');
    }

    /**
     * Test that intersection types with all array-compatible interfaces return true.
     */
    public function test_intersection_type_all_array_compatible_interfaces(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Intersection types require PHP 8.1+');
        }

        $method = $this->getParameterAcceptsArrayMethod();

        // Test with multiple array-compatible interfaces
        $testClass = new class
        {
            public function test_method(\Countable&\ArrayAccess&\Traversable $param): void
            {
                // Arrays implement all three interfaces
            }
        };

        $reflectionMethod = new \ReflectionMethod($testClass, 'test_method');
        $parameters = $reflectionMethod->getParameters();
        $paramType = $parameters[0]->getType();

        $this->assertInstanceOf(\ReflectionIntersectionType::class, $paramType);

        $result = $method->invoke(null, $paramType);

        $this->assertTrue(
            $result,
            'Intersection type with multiple array-compatible interfaces should accept array'
        );
    }

    /**
     * Test that intersection types with one incompatible type return false.
     */
    public function test_intersection_type_with_one_incompatible_type_returns_false(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Intersection types require PHP 8.1+');
        }

        $method = $this->getParameterAcceptsArrayMethod();

        // Mix compatible and incompatible types
        $testClass = new class
        {
            public function test_method(\Countable&\Stringable $param): void
            {
                // Arrays don't implement Stringable
            }
        };

        $reflectionMethod = new \ReflectionMethod($testClass, 'test_method');
        $parameters = $reflectionMethod->getParameters();
        $paramType = $parameters[0]->getType();

        $this->assertInstanceOf(\ReflectionIntersectionType::class, $paramType);

        $result = $method->invoke(null, $paramType);

        $this->assertFalse(
            $result,
            'Intersection type with Stringable should not accept array since arrays are not Stringable'
        );
    }
}
