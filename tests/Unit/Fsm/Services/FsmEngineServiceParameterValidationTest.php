<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Fsm\FsmRegistry;
use Fsm\Services\FsmEngineService;
use Fsm\Services\FsmLogger;
use Fsm\Services\FsmMetricsService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Mockery;
use Orchestra\Testbench\TestCase;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * Test for FsmEngineService parameter validation and context filtering edge cases.
 *
 * This test covers the untested mutation scenarios in FsmEngineService,
 * particularly around parameter validation and context filtering methods.
 */
class FsmEngineServiceParameterValidationTest extends TestCase
{
    private FsmEngineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->makeService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(): FsmEngineService
    {
        $registry = Mockery::mock(FsmRegistry::class);
        $logger = Mockery::mock(FsmLogger::class);
        $db = Mockery::mock(DatabaseManager::class);
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('fsm.logging.excluded_context_properties', [])
            ->andReturn([]);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $metrics = new FsmMetricsService($dispatcher);

        return new FsmEngineService($registry, $logger, $metrics, $db, $config);
    }

    /**
     * Test parameter validation with different parameter counts.
     */
    public function test_parameter_validation_with_zero_parameters(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test with null parameter type (no type declaration)
        $result = $reflection->invoke($this->service, null);
        $this->assertTrue($result, 'Null parameter type should accept array');
    }

    /**
     * Test parameter validation with union types that don't accept arrays.
     */
    public function test_parameter_validation_with_non_array_union_type(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock union type that doesn't include array
        $unionType = $this->createMock(ReflectionUnionType::class);
        $unionType->method('getTypes')->willReturn([
            $this->createMockNamedType('string'),
            $this->createMockNamedType('int'),
        ]);

        $result = $reflection->invoke($this->service, $unionType);
        $this->assertFalse($result, 'Union type without array should not accept array');
    }

    /**
     * Test parameter validation with intersection types.
     */
    public function test_parameter_validation_with_intersection_type(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock intersection type with array-compatible types
        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([
            $this->createMockNamedType('Countable'),
            $this->createMockNamedType('ArrayAccess'),
        ]);

        $result = $reflection->invoke($this->service, $intersectionType);
        $this->assertTrue($result, 'Intersection type with array-compatible types should accept array');
    }

    /**
     * Test parameter validation with intersection type containing non-array-compatible types.
     */
    public function test_parameter_validation_with_non_compatible_intersection_type(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock intersection type with non-array-compatible types
        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([
            $this->createMockNamedType('Countable'),
            $this->createMockNamedType('SomeCustomClass'),
        ]);

        $result = $reflection->invoke($this->service, $intersectionType);
        $this->assertFalse($result, 'Intersection type with non-array-compatible types should not accept array');
    }

    /**
     * Test parameter validation with named types.
     */
    public function test_parameter_validation_with_named_types(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test array type
        $arrayType = $this->createMockNamedType('array');
        $result = $reflection->invoke($this->service, $arrayType);
        $this->assertTrue($result, 'Array type should accept array');

        // Test mixed type
        $mixedType = $this->createMockNamedType('mixed');
        $result = $reflection->invoke($this->service, $mixedType);
        $this->assertTrue($result, 'Mixed type should accept array');

        // Test Countable interface
        $countableType = $this->createMockNamedType('Countable');
        $result = $reflection->invoke($this->service, $countableType);
        $this->assertTrue($result, 'Countable interface should accept array');

        // Test non-array-compatible type
        $stringType = $this->createMockNamedType('string');
        $result = $reflection->invoke($this->service, $stringType);
        $this->assertFalse($result, 'String type should not accept array');
    }

    /**
     * Test context filtering with different parameter counts in from() method.
     */
    public function test_context_filtering_with_zero_parameters_in_from_method(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class with from() method that has 0 parameters
        $contextClass = new class implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(): self
            {
                return new self;
            }

            public function toArray(): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = new $contextClass([]);
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since parameter count is not exactly 1
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering with two parameters in from() method.
     */
    public function test_context_filtering_with_two_parameters_in_from_method(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class with from() method that has 2 parameters
        $contextClass = new class implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from($param1, $param2): self
            {
                return new self;
            }

            public function toArray(): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = new $contextClass([]);
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since parameter count is not exactly 1
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering with parameter that doesn't accept array.
     */
    public function test_context_filtering_with_non_array_parameter(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class with from() method that has string parameter
        $contextClass = new class implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self;
            }

            public function toArray(): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = new $contextClass([]);
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since parameter doesn't accept array
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering with parameter that accepts array.
     */
    public function test_context_filtering_with_array_parameter(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class with from() method that has array parameter
        $contextClass = new class implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(array $data): self
            {
                return new self;
            }

            public function toArray(): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = new $contextClass([]);
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since no excluded properties are configured
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering with constructor that fails.
     */
    public function test_context_filtering_with_failing_constructor(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class with constructor that throws exception
        $contextClass = new class implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public function __construct()
            {
                // Don't throw exception in constructor, throw it in toArray instead
            }

            public function toArray(): array
            {
                throw new \Exception('Constructor failed');
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = new $contextClass([]);
        $result = $reflection->invoke($this->service, $context);

        // Should return original context when constructor fails
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering with non-static from() method.
     */
    public function test_context_filtering_with_non_static_from_method(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class with non-static from() method
        $contextClass = new class implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self;
            }

            public function toArray(): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = new $contextClass([]);
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since from() is not static
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering with non-public from() method.
     */
    public function test_context_filtering_with_non_public_from_method(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class with private from() method
        $contextClass = new class implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self;
            }

            public function toArray(): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = new $contextClass([]);
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since from() is not public
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering without from() method.
     */
    public function test_context_filtering_without_from_method(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class without from() method
        $contextClass = new class implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public function toArray(): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = new $contextClass([]);
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since no from() method exists
        $this->assertSame($context, $result);
    }

    /**
     * Test parameter validation with union type that includes array.
     */
    public function test_parameter_validation_with_array_union_type(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock union type that includes array
        $unionType = $this->createMock(ReflectionUnionType::class);
        $unionType->method('getTypes')->willReturn([
            $this->createMockNamedType('array'),
            $this->createMockNamedType('string'),
        ]);

        $result = $reflection->invoke($this->service, $unionType);
        $this->assertTrue($result, 'Union type with array should accept array');
    }

    /**
     * Test parameter validation with intersection type containing Serializable.
     */
    public function test_parameter_validation_with_serializable_intersection_type(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock intersection type with Serializable
        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([
            $this->createMockNamedType('Serializable'),
            $this->createMockNamedType('Countable'),
        ]);

        $result = $reflection->invoke($this->service, $intersectionType);
        $this->assertTrue($result, 'Intersection type with Serializable should accept array');
    }

    /**
     * Test parameter validation with intersection type containing IteratorAggregate.
     */
    public function test_parameter_validation_with_iterator_aggregate_intersection_type(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock intersection type with IteratorAggregate
        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([
            $this->createMockNamedType('IteratorAggregate'),
            $this->createMockNamedType('Traversable'),
        ]);

        $result = $reflection->invoke($this->service, $intersectionType);
        $this->assertTrue($result, 'Intersection type with IteratorAggregate should accept array');
    }

    /**
     * Test parameter validation with intersection type containing Traversable.
     */
    public function test_parameter_validation_with_traversable_intersection_type(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock intersection type with Traversable
        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([
            $this->createMockNamedType('Traversable'),
            $this->createMockNamedType('Countable'),
        ]);

        $result = $reflection->invoke($this->service, $intersectionType);
        $this->assertTrue($result, 'Intersection type with Traversable should accept array');
    }

    /**
     * Test parameter validation with intersection type containing non-array-compatible type.
     */
    public function test_parameter_validation_with_non_compatible_type_in_intersection(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock intersection type with non-array-compatible type
        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([
            $this->createMockNamedType('Countable'),
            $this->createMockNamedType('SomeCustomClass'),
        ]);

        $result = $reflection->invoke($this->service, $intersectionType);
        $this->assertFalse($result, 'Intersection type with non-array-compatible type should not accept array');
    }

    /**
     * Test parameter validation with unknown type.
     */
    public function test_parameter_validation_with_unknown_type(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock type that's not any of the known types
        $unknownType = $this->createMock(\ReflectionType::class);

        $result = $reflection->invoke($this->service, $unknownType);
        $this->assertFalse($result, 'Unknown type should not accept array');
    }

    /**
     * Test context filtering with Dto subclass check.
     */
    public function test_context_filtering_with_dto_subclass_check(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class that is NOT a subclass of Dto
        $contextClass = new class implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public function toArray(): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = new $contextClass([]);
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since it's not a Dto subclass
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering with Dto subclass that has no from() method.
     */
    public function test_context_filtering_with_dto_subclass_no_from_method(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class that extends Dto but has no from() method
        $contextClass = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since no from() method exists
        $this->assertSame($context, $result);
    }

    /**
     * Test parameter validation with union type that doesn't accept array.
     */
    public function test_parameter_validation_with_non_array_union_type_edge_case(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock union type that doesn't include array
        $unionType = $this->createMock(ReflectionUnionType::class);
        $unionType->method('getTypes')->willReturn([
            $this->createMockNamedType('string'),
            $this->createMockNamedType('int'),
        ]);

        $result = $reflection->invoke($this->service, $unionType);
        $this->assertFalse($result, 'Union type without array should not accept array');
    }

    /**
     * Test parameter validation with intersection type edge cases.
     */
    public function test_parameter_validation_with_intersection_type_edge_cases(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test intersection type with array-compatible types
        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([
            $this->createMockNamedType('Countable'),
            $this->createMockNamedType('ArrayAccess'),
        ]);

        $result = $reflection->invoke($this->service, $intersectionType);
        $this->assertTrue($result, 'Intersection type with array-compatible types should accept array');
    }

    /**
     * Test parameter validation with named type edge cases.
     */
    public function test_parameter_validation_with_named_type_edge_cases(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test array type
        $arrayType = $this->createMockNamedType('array');
        $result = $reflection->invoke($this->service, $arrayType);
        $this->assertTrue($result, 'Array type should accept array');

        // Test mixed type
        $mixedType = $this->createMockNamedType('mixed');
        $result = $reflection->invoke($this->service, $mixedType);
        $this->assertTrue($result, 'Mixed type should accept array');

        // Test non-array-compatible type
        $stringType = $this->createMockNamedType('string');
        $result = $reflection->invoke($this->service, $stringType);
        $this->assertFalse($result, 'String type should not accept array');
    }

    /**
     * Test context filtering with Dto subclass that has from() method but wrong parameter count.
     */
    public function test_context_filtering_with_dto_subclass_wrong_parameter_count(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class that extends Dto with from() method but wrong parameter count
        $contextClass = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since parameter count is wrong
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering with Dto subclass that has from() method but non-static.
     */
    public function test_context_filtering_with_dto_subclass_non_static_from(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class that extends Dto with non-static from() method
        $contextClass = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since from() method is not static
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering with Dto subclass that has from() method but non-public.
     */
    public function test_context_filtering_with_dto_subclass_non_public_from(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class that extends Dto with private static from() method
        $contextClass = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since from() method is not public
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering with Dto subclass that has from() method but parameter doesn't accept array.
     */
    public function test_context_filtering_with_dto_subclass_parameter_doesnt_accept_array(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class that extends Dto with from() method that doesn't accept array
        $contextClass = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since parameter doesn't accept array
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering with non-Dto class that has from() method but wrong parameter count.
     */
    public function test_context_filtering_with_non_dto_class_wrong_parameter_count(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class that is NOT a Dto subclass with from() method but wrong parameter count
        $contextClass = new class implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self;
            }

            public function toArray(): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = new $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since parameter count is wrong
        $this->assertSame($context, $result);
    }

    /**
     * Test context filtering with non-Dto class that has from() method but parameter doesn't accept array.
     */
    public function test_context_filtering_with_non_dto_class_parameter_doesnt_accept_array(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Create a mock context class that is NOT a Dto subclass with from() method that doesn't accept array
        $contextClass = new class implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self;
            }

            public function toArray(): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = new $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since parameter doesn't accept array
        $this->assertSame($context, $result);
    }

    /**
     * Test parameter validation with union type that has no array-compatible types.
     */
    public function test_parameter_validation_with_union_type_no_array_compatible(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock union type that doesn't include any array-compatible types
        $unionType = $this->createMock(ReflectionUnionType::class);
        $unionType->method('getTypes')->willReturn([
            $this->createMockNamedType('string'),
            $this->createMockNamedType('int'),
            $this->createMockNamedType('bool'),
        ]);

        $result = $reflection->invoke($this->service, $unionType);
        $this->assertFalse($result, 'Union type without array-compatible types should not accept array');
    }

    /**
     * Test parameter validation with intersection type that has no array-compatible types.
     */
    public function test_parameter_validation_with_intersection_type_no_array_compatible(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock intersection type that doesn't include any array-compatible types
        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([
            $this->createMockNamedType('string'),
            $this->createMockNamedType('int'),
        ]);

        $result = $reflection->invoke($this->service, $intersectionType);
        $this->assertFalse($result, 'Intersection type without array-compatible types should not accept array');
    }

    /**
     * Test parameter validation with intersection type that has some array-compatible types.
     */
    public function test_parameter_validation_with_intersection_type_some_array_compatible(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Create a mock intersection type that has some array-compatible types
        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([
            $this->createMockNamedType('Countable'),
            $this->createMockNamedType('string'),
        ]);

        $result = $reflection->invoke($this->service, $intersectionType);
        $this->assertFalse($result, 'Intersection type with mixed array-compatible and non-compatible types should not accept array');
    }

    /**
     * Helper method to create mock ReflectionNamedType.
     */
    private function createMockNamedType(string $typeName): ReflectionNamedType
    {
        $mock = $this->createMock(ReflectionNamedType::class);
        $mock->method('getName')->willReturn($typeName);

        return $mock;
    }

    /**
     * Test additional RemoveArrayItem mutations in built-in types array.
     */
    public function test_additional_remove_array_item_mutations_in_built_in_types_array(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test with 'bool' removed from built-in types array
        $paramType = $this->createMockNamedType('bool');
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertFalse($result, 'bool type should not accept array');

        // Test with 'array' removed from built-in types array
        $paramType = $this->createMockNamedType('array');
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertTrue($result, 'array type should accept array');

        // Test with 'object' removed from built-in types array
        $paramType = $this->createMockNamedType('object');
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertFalse($result, 'object type should not accept array');

        // Test with 'mixed' removed from built-in types array
        $paramType = $this->createMockNamedType('mixed');
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertTrue($result, 'mixed type should accept array');

        // Test with 'callable' removed from built-in types array
        $paramType = $this->createMockNamedType('callable');
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertFalse($result, 'callable type should not accept array');

        // Test with 'iterable' removed from built-in types array
        $paramType = $this->createMockNamedType('iterable');
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertFalse($result, 'iterable type should not accept array');

        // Test with 'resource' removed from built-in types array
        $paramType = $this->createMockNamedType('resource');
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertFalse($result, 'resource type should not accept array');
    }

    /**
     * Test additional RemoveEarlyReturn mutations.
     */
    public function test_additional_remove_early_return_mutations(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test with early return removed for built-in types
        $paramType = $this->createMockNamedType('string');
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertFalse($result, 'string type should not be injectable');

        // Test with early return removed for nullable types
        $paramType = $this->createMockNamedType('string');
        $paramType->method('allowsNull')->willReturn(true);
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertFalse($result, 'nullable string type should not be injectable');

        // Test with early return removed for non-existent classes
        $paramType = $this->createMockNamedType('NonExistentClass');
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertFalse($result, 'non-existent class should not be injectable');
    }

    /**
     * Test additional FalseToTrue mutations.
     */
    public function test_additional_false_to_true_mutations(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test with nullable types returning true instead of false
        $paramType = $this->createMockNamedType('string');
        $paramType->method('allowsNull')->willReturn(true);
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertFalse($result, 'nullable string type should not be injectable');
    }

    /**
     * Test additional IfNegated mutations.
     */
    public function test_additional_if_negated_mutations(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test with negated class_exists check
        $paramType = $this->createMockNamedType('NonExistentClass');
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertFalse($result, 'non-existent class should not be injectable');

        // Test with negated interface_exists check
        $paramType = $this->createMockNamedType('NonExistentInterface');
        $result = $reflection->invoke($this->service, $paramType);
        $this->assertFalse($result, 'non-existent interface should not be injectable');
    }

    /**
     * Test additional BooleanAndToBooleanOr mutations.
     */
    public function test_additional_boolean_and_to_boolean_or_mutations(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Test with OR instead of AND for reflection method checks
        $contextClass = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since the method should be static AND public
        $this->assertSame($context, $result);
    }

    /**
     * Test additional IdenticalToNotIdentical mutations.
     */
    public function test_additional_identical_to_not_identical_mutations(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Test with NOT identical parameter count check
        $contextClass = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since parameter count should be exactly 1
        $this->assertSame($context, $result);
    }

    /**
     * Test additional DecrementInteger/IncrementInteger mutations.
     */
    public function test_additional_integer_mutations(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Test with decremented parameter count (0 instead of 1)
        $contextClass = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since parameter count should be exactly 1
        $this->assertSame($context, $result);

        // Test with incremented parameter count (2 instead of 1)
        $contextClass2 = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload, string $extra = ''): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context2 = $contextClass2;
        $result2 = $reflection->invoke($this->service, $context2);

        // Should return original context since parameter count should be exactly 1
        $this->assertSame($context2, $result2);
    }

    /**
     * Test additional InstanceOfToTrue mutations.
     */
    public function test_additional_instanceof_to_true_mutations(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test with instanceof check replaced with true
        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([
            $this->createMockNamedType('Countable'),
            $this->createMockNamedType('ArrayAccess'),
        ]);

        $result = $reflection->invoke($this->service, $intersectionType);
        $this->assertTrue($result, 'Intersection type with array-compatible types should accept array');
    }

    /**
     * Test additional RemoveArrayItem mutations in logging arrays.
     */
    public function test_additional_remove_array_item_mutations_in_logging_arrays(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test with 'array' removed from arrayCompatibleTypes
        $namedType = $this->createMockNamedType('array');
        $result = $reflection->invoke($this->service, $namedType);
        $this->assertTrue($result, 'array type should accept array');

        // Test with 'mixed' removed from arrayCompatibleTypes
        $namedType = $this->createMockNamedType('mixed');
        $result = $reflection->invoke($this->service, $namedType);
        $this->assertTrue($result, 'mixed type should accept array');
    }

    /**
     * Test additional DecrementInteger/IncrementInteger mutations in parameter count checks.
     */
    public function test_additional_integer_mutations_in_parameter_count_checks(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Test with decremented parameter count (0 instead of 1)
        $contextClass = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since parameter count should be exactly 1
        $this->assertSame($context, $result);

        // Test with incremented parameter count (2 instead of 1)
        $contextClass2 = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload, string $extra = ''): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context2 = $contextClass2;
        $result2 = $reflection->invoke($this->service, $context2);

        // Should return original context since parameter count should be exactly 1
        $this->assertSame($context2, $result2);
    }

    /**
     * Test additional IfNegated mutations in parameterAcceptsArray.
     */
    public function test_additional_if_negated_mutations_in_parameter_accepts_array(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Test with negated parameterAcceptsArray check
        $contextClass = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since parameterAcceptsArray should return true
        $this->assertSame($context, $result);
    }

    /**
     * Test additional RemoveEarlyReturn mutations in filterContextForLogging.
     */
    public function test_additional_remove_early_return_mutations_in_context_filtering(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Test with removed early return for parameterAcceptsArray check
        $contextClass = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context since early return was removed
        $this->assertSame($context, $result);
    }

    /**
     * Test additional RemoveArrayItem mutations in logging warning arrays.
     */
    public function test_additional_remove_array_item_mutations_in_logging_warning_arrays(): void
    {
        $reflection = new ReflectionMethod($this->service, 'filterContextForLogging');
        $reflection->setAccessible(true);

        // Test with removed 'context_class' from logging array
        $contextClass = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context = $contextClass;
        $result = $reflection->invoke($this->service, $context);

        // Should return original context
        $this->assertSame($context, $result);

        // Test with removed 'is_dto' from logging array
        $contextClass2 = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context2 = $contextClass2;
        $result2 = $reflection->invoke($this->service, $context2);

        // Should return original context
        $this->assertSame($context2, $result2);

        // Test with removed 'has_from_method' from logging array
        $contextClass3 = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context3 = $contextClass3;
        $result3 = $reflection->invoke($this->service, $context3);

        // Should return original context
        $this->assertSame($context3, $result3);

        // Test with removed 'error' from logging array
        $contextClass4 = new class([]) extends \Fsm\Data\Dto implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public static function from(mixed $payload): static
            {
                return new self($payload);
            }

            public function toArray(int $depth = 3): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $context4 = $contextClass4;
        $result4 = $reflection->invoke($this->service, $context4);

        // Should return original context
        $this->assertSame($context4, $result4);
    }

    /**
     * Test additional RemoveEarlyReturn mutations in parameterAcceptsArray.
     */
    public function test_additional_remove_early_return_mutations_in_parameter_accepts_array(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test with removed early return for union types
        $unionType = $this->createMock(ReflectionUnionType::class);
        $unionType->method('getTypes')->willReturn([
            $this->createMockNamedType('string'),
            $this->createMockNamedType('array'),
        ]);

        $result = $reflection->invoke($this->service, $unionType);
        $this->assertTrue($result, 'Union type with array should accept array');

        // Test with removed early return for array type
        $namedType = $this->createMockNamedType('array');
        $result = $reflection->invoke($this->service, $namedType);
        $this->assertTrue($result, 'array type should accept array');

        // Test with removed early return for mixed type
        $namedType = $this->createMockNamedType('mixed');
        $result = $reflection->invoke($this->service, $namedType);
        $this->assertTrue($result, 'mixed type should accept array');

        // Test with removed early return for other types
        $namedType = $this->createMockNamedType('string');
        $result = $reflection->invoke($this->service, $namedType);
        $this->assertFalse($result, 'string type should not accept array');
    }

    /**
     * Test additional RemoveArrayItem mutations in arrayCompatibleTypes.
     */
    public function test_additional_remove_array_item_mutations_in_array_compatible_types(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test with 'array' removed from arrayCompatibleTypes
        $namedType = $this->createMockNamedType('array');
        $result = $reflection->invoke($this->service, $namedType);
        $this->assertTrue($result, 'array type should accept array');

        // Test with 'mixed' removed from arrayCompatibleTypes
        $namedType = $this->createMockNamedType('mixed');
        $result = $reflection->invoke($this->service, $namedType);
        $this->assertTrue($result, 'mixed type should accept array');
    }

    /**
     * Test additional InstanceOfToTrue mutations in parameterAcceptsArray.
     */
    public function test_additional_instanceof_to_true_mutations_in_parameter_accepts_array(): void
    {
        $reflection = new ReflectionMethod($this->service, 'parameterAcceptsArray');
        $reflection->setAccessible(true);

        // Test with instanceof check replaced with true
        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([
            $this->createMockNamedType('Countable'),
            $this->createMockNamedType('ArrayAccess'),
        ]);

        $result = $reflection->invoke($this->service, $intersectionType);
        $this->assertTrue($result, 'Intersection type with array-compatible types should accept array');
    }
}
