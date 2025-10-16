<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use Tests\Feature\Fsm\Data\TestContextData;

class TestContextDataConstructorBugFixTest extends TestCase
{
    public function test_constructor_with_string_message(): void
    {
        $context = new TestContextData('Hello world', 123, true);

        $this->assertEquals('Hello world', $context->message);
        $this->assertEquals(123, $context->userId);
        $this->assertTrue($context->triggerFailure);
    }

    public function test_constructor_with_string_message_minimal_parameters(): void
    {
        $context = new TestContextData('Test message');

        $this->assertEquals('Test message', $context->message);
        $this->assertNull($context->userId);
        $this->assertFalse($context->triggerFailure);
    }

    public function test_constructor_with_associative_array_single_argument(): void
    {
        $data = [
            'message' => 'Array construction',
            'userId' => 456,
            'triggerFailure' => true,
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Array construction', $context->message);
        $this->assertEquals(456, $context->userId);
        $this->assertTrue($context->triggerFailure);
    }

    public function test_constructor_with_associative_array_partial_data(): void
    {
        $data = [
            'message' => 'Partial data',
            'userId' => 789,
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Partial data', $context->message);
        $this->assertEquals(789, $context->userId);
        $this->assertFalse($context->triggerFailure); // Default value
    }

    public function test_constructor_with_non_associative_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array parameter must be an associative array with DTO property keys used as single argument for array-based construction.');

        new TestContextData(['value1', 'value2', 'value3']);
    }

    public function test_constructor_with_array_and_multiple_arguments_throws_exception(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Cannot assign array to property Tests\Feature\Fsm\Data\TestContextData::$message of type string');

        new TestContextData(['message' => 'test'], 123);
    }

    public function test_constructor_with_empty_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array parameter must be an associative array with DTO property keys used as single argument for array-based construction.');

        new TestContextData([]);
    }

    public function test_constructor_with_numeric_indexed_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array parameter must be an associative array with DTO property keys used as single argument for array-based construction.');

        new TestContextData([0 => 'value1', 1 => 'value2', 2 => 'value3']);
    }

    public function test_constructor_with_mixed_indexed_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array parameter must be an associative array with DTO property keys used as single argument for array-based construction.');

        new TestContextData([0 => 'value1', 'key' => 'value2']);
    }

    public function test_constructor_with_snake_case_keys_in_array(): void
    {
        $data = [
            'message' => 'Snake case test',
            'user_id' => 999,
            'trigger_failure' => true,
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Snake case test', $context->message);
        $this->assertEquals(999, $context->userId);
        $this->assertTrue($context->triggerFailure);
    }

    public function test_constructor_preserves_original_bug_scenario(): void
    {
        // This test ensures that the original bug scenario (array assigned to string property)
        // now throws an exception instead of silently failing
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Cannot assign array to property Tests\Feature\Fsm\Data\TestContextData::$message of type string');

        // This would have previously assigned the array to the message property
        new TestContextData(['not', 'associative'], 123);
    }

    public function test_constructor_with_null_user_id(): void
    {
        $context = new TestContextData('Message with null user', null, false);

        $this->assertEquals('Message with null user', $context->message);
        $this->assertNull($context->userId);
        $this->assertFalse($context->triggerFailure);
    }

    public function test_constructor_with_zero_user_id(): void
    {
        $context = new TestContextData('Message with zero user', 0, true);

        $this->assertEquals('Message with zero user', $context->message);
        $this->assertEquals(0, $context->userId);
        $this->assertTrue($context->triggerFailure);
    }

    public function test_constructor_with_empty_string_message(): void
    {
        $context = new TestContextData('', 456, false);

        $this->assertEquals('', $context->message);
        $this->assertEquals(456, $context->userId);
        $this->assertFalse($context->triggerFailure);
    }

    public function test_constructor_with_array_containing_null_values(): void
    {
        $data = [
            'message' => 'Test with nulls',
            'userId' => null,
            'triggerFailure' => false,
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Test with nulls', $context->message);
        $this->assertNull($context->userId);
        $this->assertFalse($context->triggerFailure);
    }
}
