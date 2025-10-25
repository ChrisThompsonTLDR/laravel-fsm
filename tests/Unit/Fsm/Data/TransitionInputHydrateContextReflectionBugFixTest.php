<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\Dto;
use Fsm\Data\TransitionInput;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * Tests for the bug fix in TransitionInput::hydrateContext method's reflection logic.
 *
 * This test ensures that the reflection logic properly handles union types, intersection types,
 * and other complex type declarations when validating DTO's from() method parameter types.
 */

// Test DTO with union type parameter that includes array
class UnionTypeWithArrayContext extends Dto
{
    public string $message;

    public int $count;

    public function __construct(string|array $message, int $count = 0)
    {
        if (is_array($message) && func_num_args() === 1 && static::isAssociative($message)) {
            parent::__construct($message);

            return;
        }

        parent::__construct(['message' => $message, 'count' => $count]);
    }
}

// Test DTO with mixed type parameter
class MixedTypeContext extends Dto
{
    public mixed $data;

    public string $message;

    public function __construct(mixed $data, string $message = 'default')
    {
        if (is_array($data) && func_num_args() === 1 && static::isAssociative($data)) {
            parent::__construct($data);

            return;
        }

        parent::__construct(['data' => $data, 'message' => $message]);
    }
}

// Test DTO with array-only parameter
class ArrayOnlyContext extends Dto
{
    public array $data;

    public string $message;

    public function __construct(array $data, string $message = 'default')
    {
        if (is_array($data) && func_num_args() === 1 && static::isAssociative($data)) {
            parent::__construct($data);

            return;
        }

        parent::__construct(['data' => $data, 'message' => $message]);
    }
}

// Test DTO with no type declaration (should work)
class NoTypeContext extends Dto
{
    public $data;

    public string $message;

    public function __construct($data, string $message = 'default')
    {
        if (is_array($data) && func_num_args() === 1 && static::isAssociative($data)) {
            parent::__construct($data);

            return;
        }

        parent::__construct(['data' => $data, 'message' => $message]);
    }
}

// Test DTO that implements ArgonautDTOContract with union type
class ArgonautUnionTypeContext implements ArgonautDTOContract
{
    public string $message;

    public int $count;

    public function __construct(string|array $message, int $count = 0)
    {
        if (is_array($message)) {
            $this->message = $message['message'] ?? '';
            $this->count = $message['count'] ?? 0;
        } else {
            $this->message = $message;
            $this->count = $count;
        }
    }

    public static function from(array $data): self
    {
        return new self($data);
    }

    public function toArray(int $depth = 3): array
    {
        return [
            'message' => $this->message,
            'count' => $this->count,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}

// Test DTO that implements ArgonautDTOContract with array-only parameter
class ArgonautArrayOnlyContext implements ArgonautDTOContract
{
    public array $data;

    public string $message;

    public function __construct(array $data, string $message = 'default')
    {
        $this->data = $data;
        $this->message = $message;
    }

    public static function from(array $data): self
    {
        return new self($data);
    }

    public function toArray(int $depth = 3): array
    {
        return [
            'data' => $this->data,
            'message' => $this->message,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}

class TransitionInputHydrateContextReflectionBugFixTest extends TestCase
{
    /**
     * Test that union types with array are properly handled for Dto subclasses.
     */
    public function test_union_type_with_array_works_for_dto_subclasses(): void
    {
        $context = [
            'class' => UnionTypeWithArrayContext::class,
            'payload' => ['message' => 'test union', 'count' => 5],
        ];

        $input = new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );

        $this->assertInstanceOf(UnionTypeWithArrayContext::class, $input->context);
        $this->assertSame('test union', $input->context->message);
        $this->assertSame(5, $input->context->count);
    }

    /**
     * Test that mixed type parameters work for Dto subclasses.
     */
    public function test_mixed_type_parameter_works_for_dto_subclasses(): void
    {
        $context = [
            'class' => MixedTypeContext::class,
            'payload' => ['data' => ['test' => 'data'], 'message' => 'test mixed'],
        ];

        $input = new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );

        $this->assertInstanceOf(MixedTypeContext::class, $input->context);
        $this->assertSame(['test' => 'data'], $input->context->data);
        $this->assertSame('test mixed', $input->context->message);
    }

    /**
     * Test that array-only parameters work for Dto subclasses.
     */
    public function test_array_only_parameter_works_for_dto_subclasses(): void
    {
        $context = [
            'class' => ArrayOnlyContext::class,
            'payload' => ['data' => ['test' => 'data'], 'message' => 'test array only'],
        ];

        $input = new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );

        $this->assertInstanceOf(ArrayOnlyContext::class, $input->context);
        $this->assertSame(['test' => 'data'], $input->context->data);
        $this->assertSame('test array only', $input->context->message);
    }

    /**
     * Test that no type declaration parameters work for Dto subclasses.
     */
    public function test_no_type_declaration_parameter_works_for_dto_subclasses(): void
    {
        $context = [
            'class' => NoTypeContext::class,
            'payload' => ['data' => ['test' => 'data'], 'message' => 'test no type'],
        ];

        $input = new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );

        $this->assertInstanceOf(NoTypeContext::class, $input->context);
        $this->assertSame(['test' => 'data'], $input->context->data);
        $this->assertSame('test no type', $input->context->message);
    }

    /**
     * Test that union types with array work for ArgonautDTOContract implementations.
     */
    public function test_union_type_with_array_works_for_argonaut_dto_contract(): void
    {
        $context = [
            'class' => ArgonautUnionTypeContext::class,
            'payload' => ['message' => 'test argonaut union', 'count' => 7],
        ];

        $input = new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );

        $this->assertInstanceOf(ArgonautUnionTypeContext::class, $input->context);
        $this->assertSame('test argonaut union', $input->context->message);
        $this->assertSame(7, $input->context->count);
    }

    /**
     * Test that array-only parameters work for ArgonautDTOContract implementations.
     */
    public function test_array_only_parameter_works_for_argonaut_dto_contract(): void
    {
        $context = [
            'class' => ArgonautArrayOnlyContext::class,
            'payload' => ['data' => ['test' => 'data'], 'message' => 'test argonaut array only'],
        ];

        $input = new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );

        $this->assertInstanceOf(ArgonautArrayOnlyContext::class, $input->context);
        $this->assertSame(['data' => ['test' => 'data'], 'message' => 'test argonaut array only'], $input->context->data);
        $this->assertSame('default', $input->context->message);
    }
}
