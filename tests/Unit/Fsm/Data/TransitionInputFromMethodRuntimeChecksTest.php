<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\Dto;
use Fsm\Data\TransitionInput;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * Test for TransitionInput robust runtime checks for static from() method.
 *
 * Tests the fix where context hydration now includes robust runtime checks
 * for the existence, accessibility, and parameter compatibility of the static from() method.
 */
class TransitionInputFromMethodRuntimeChecksTest extends TestCase
{
    /**
     * Test that DTO with compatible from() method works correctly.
     */
    public function test_dto_with_compatible_from_method_works(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that extends Dto with compatible from() method
        $testDtoClass = new class(['message' => 'test']) extends Dto
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                parent::__construct($data);
            }
        };

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that DTO with no from() method falls back to constructor.
     */
    public function test_dto_with_no_from_method_falls_back_to_constructor(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO with no from() method
        $testDtoClass = new class(['message' => 'test']) extends Dto
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                parent::__construct($data);
            }
        };

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that non-Dto ArgonautDTOContract with compatible from() method works correctly.
     */
    public function test_non_dto_argonaut_dto_with_compatible_from_method_works(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract but doesn't extend Dto
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
            }

            public static function from(array $data): self
            {
                return new self($data);
            }

            public function toArray(): array
            {
                return ['message' => $this->message];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that non-Dto ArgonautDTOContract with incompatible from() method falls back to constructor.
     */
    public function test_non_dto_argonaut_dto_with_incompatible_from_method_falls_back_to_constructor(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract but doesn't extend Dto
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
            }

            public static function from(string $data): self
            {
                return new self(['message' => $data]);
            }

            public function toArray(): array
            {
                return ['message' => $this->message];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that non-Dto ArgonautDTOContract with private from() method falls back to constructor.
     */
    public function test_non_dto_argonaut_dto_with_private_from_method_falls_back_to_constructor(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract but doesn't extend Dto
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
            }

            private static function from(array $data): self
            {
                return new self($data);
            }

            public function toArray(): array
            {
                return ['message' => $this->message];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that non-Dto ArgonautDTOContract with non-static from() method falls back to constructor.
     */
    public function test_non_dto_argonaut_dto_with_non_static_from_method_falls_back_to_constructor(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract but doesn't extend Dto
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
            }

            public function from(array $data): self
            {
                return new self($data);
            }

            public function toArray(): array
            {
                return ['message' => $this->message];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that non-Dto ArgonautDTOContract with multiple parameters in from() method falls back to constructor.
     */
    public function test_non_dto_argonaut_dto_with_multiple_parameters_in_from_method_falls_back_to_constructor(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract but doesn't extend Dto
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
            }

            public static function from(array $data, string $extra): self
            {
                return new self($data);
            }

            public function toArray(): array
            {
                return ['message' => $this->message];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that non-Dto ArgonautDTOContract with nullable array parameter in from() method works correctly.
     */
    public function test_non_dto_argonaut_dto_with_nullable_array_parameter_in_from_method_works(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract but doesn't extend Dto
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
            }

            public static function from(?array $data): self
            {
                return new self($data ?? []);
            }

            public function toArray(): array
            {
                return ['message' => $this->message];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that non-Dto ArgonautDTOContract with mixed parameter type in from() method works correctly.
     */
    public function test_non_dto_argonaut_dto_with_mixed_parameter_type_in_from_method_works(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract but doesn't extend Dto
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
            }

            public static function from(mixed $data): self
            {
                return new self(is_array($data) ? $data : []);
            }

            public function toArray(): array
            {
                return ['message' => $this->message];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that non-Dto ArgonautDTOContract with untyped parameter in from() method works correctly.
     */
    public function test_non_dto_argonaut_dto_with_untyped_parameter_in_from_method_works(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract but doesn't extend Dto
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
            }

            public static function from($data): self
            {
                return new self(is_array($data) ? $data : []);
            }

            public function toArray(): array
            {
                return ['message' => $this->message];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that DTO with constructor that doesn't accept array throws exception.
     */
    public function test_dto_with_constructor_that_doesnt_accept_array_throws_exception(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO with constructor that doesn't accept array
        $testDtoClass = new class('test') implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(string $message)
            {
                $this->message = $message;
            }

            public static function from(string $data): self
            {
                return new self($data);
            }

            public function toArray(): array
            {
                return ['message' => $this->message];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to instantiate DTO class');

        new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);
    }

    /**
     * Test that DTO with from() method that throws exception throws the exception.
     */
    public function test_dto_with_from_method_that_throws_exception_throws_exception(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO with from() method that throws exception
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
            }

            public static function from(array $data): self
            {
                throw new \RuntimeException('from() method failed');
            }

            public function toArray(): array
            {
                return ['message' => $this->message];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('from() method failed');

        new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);
    }

    /**
     * Test that DTO with both from() method and constructor failing throws exception.
     */
    public function test_dto_with_both_from_method_and_constructor_failing_throws_exception(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO with both from() method and constructor failing
        $testDtoClass = new class('test') implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(string $message)
            {
                $this->message = $message;
            }

            public static function from(array $data): self
            {
                throw new \RuntimeException('from() method failed');
            }

            public function toArray(): array
            {
                return ['message' => $this->message];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('from() method failed');

        new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
        ]);
    }
}
