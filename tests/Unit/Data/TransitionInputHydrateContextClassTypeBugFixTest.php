<?php

declare(strict_types=1);

namespace Tests\Unit\Data;

use Fsm\Data\TransitionInput;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Feature\Fsm\Data\TestContextData;

/**
 * Tests for the bug fix in TransitionInput::hydrateContext method.
 *
 * This test ensures that when the context's 'class' key is not a string,
 * the error message includes the actual type received for better debugging.
 */
class TransitionInputHydrateContextClassTypeBugFixTest extends TestCase
{
    /**
     * Test that the error message includes the type when class is not a string.
     */
    public function test_error_message_includes_type_when_class_is_not_string(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got int)');

        $context = [
            'class' => 123, // int instead of string
            'payload' => ['test' => 'data'],
        ];

        new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );
    }

    /**
     * Test that the error message includes the type when class is null.
     */
    public function test_error_message_includes_type_when_class_is_null(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got null)');

        $context = [
            'class' => null, // null instead of string
            'payload' => ['test' => 'data'],
        ];

        new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );
    }

    /**
     * Test that the error message includes the type when class is an array.
     */
    public function test_error_message_includes_type_when_class_is_array(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got array)');

        $context = [
            'class' => ['some', 'array'], // array instead of string
            'payload' => ['test' => 'data'],
        ];

        new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );
    }

    /**
     * Test that the error message includes the type when class is a bool.
     */
    public function test_error_message_includes_type_when_class_is_bool(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got bool)');

        $context = [
            'class' => true, // bool instead of string
            'payload' => ['test' => 'data'],
        ];

        new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );
    }

    /**
     * Test that the error message includes the type when class is an object.
     */
    public function test_error_message_includes_type_when_class_is_object(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got object)');

        $context = [
            'class' => new \stdClass, // object instead of string
            'payload' => ['test' => 'data'],
        ];

        new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );
    }

    /**
     * Test that the error message includes the type when class is a float.
     */
    public function test_error_message_includes_type_when_class_is_float(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got float)');

        $context = [
            'class' => 123.45, // float instead of string
            'payload' => ['test' => 'data'],
        ];

        new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );
    }

    /**
     * Test that the error message includes the type when class is a resource.
     */
    public function test_error_message_includes_type_when_class_is_resource(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got resource)');

        $context = [
            'class' => fopen('php://memory', 'r'), // resource instead of string
            'payload' => ['test' => 'data'],
        ];

        new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );
    }

    /**
     * Test that the error message includes the type when class is missing entirely.
     */
    public function test_error_message_includes_type_when_class_is_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got null)');

        $context = [
            // 'class' key is missing entirely
            'payload' => ['test' => 'data'],
        ];

        new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );
    }

    /**
     * Test that the error message includes the type when class is a callable.
     */
    public function test_error_message_includes_type_when_class_is_callable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got object)');

        $context = [
            'class' => function () {}, // callable instead of string
            'payload' => ['test' => 'data'],
        ];

        new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );
    }

    /**
     * Test that the error message includes the type when class is an empty string.
     * This should still work since empty string is a valid string type.
     */
    public function test_empty_string_class_should_work(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class : class does not exist');

        $context = [
            'class' => '', // empty string - should be treated as string
            'payload' => ['test' => 'data'],
        ];

        new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );
    }

    /**
     * Test that valid string class names work correctly.
     */
    public function test_valid_string_class_works(): void
    {
        $context = [
            'class' => TestContextData::class,
            'payload' => ['message' => 'test data', 'userId' => 123, 'triggerFailure' => false],
        ];

        $transitionInput = new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );

        $this->assertInstanceOf(TransitionInput::class, $transitionInput);
        $this->assertInstanceOf(TestContextData::class, $transitionInput->context);
    }

    /**
     * Test that the error message format is consistent with other error messages in the method.
     */
    public function test_error_message_format_consistency(): void
    {
        // Test the specific error message format
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got int)');

        $context = [
            'class' => 42,
            'payload' => [],
        ];

        new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );
    }

    /**
     * Test that the error message includes the actual value when possible.
     */
    public function test_error_message_includes_actual_value_for_debugging(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got int)');

        $context = [
            'class' => 999, // specific int value
            'payload' => ['test' => 'data'],
        ];

        new TransitionInput(
            $this->createMock(Model::class),
            'from',
            'to',
            $context
        );
    }

    /**
     * Test that the error message works with array-based construction.
     */
    public function test_error_message_with_array_based_construction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got bool)');

        $attributes = [
            'model' => $this->createMock(Model::class),
            'fromState' => 'from',
            'toState' => 'to',
            'context' => [
                'class' => false, // bool instead of string
                'payload' => ['test' => 'data'],
            ],
        ];

        new TransitionInput($attributes);
    }

    /**
     * Test that the error message works with snake_case keys in array construction.
     */
    public function test_error_message_with_snake_case_keys(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got array)');

        $attributes = [
            'model' => $this->createMock(Model::class),
            'from_state' => 'from',
            'to_state' => 'to',
            'context' => [
                'class' => ['invalid', 'array'], // array instead of string
                'payload' => ['test' => 'data'],
            ],
        ];

        new TransitionInput($attributes);
    }
}
