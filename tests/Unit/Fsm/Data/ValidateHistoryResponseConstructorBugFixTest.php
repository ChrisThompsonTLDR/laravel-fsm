<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\ValidateHistoryResponse;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class ValidateHistoryResponseConstructorBugFixTest extends TestCase
{
    public function test_constructor_with_boolean_success(): void
    {
        $response = new ValidateHistoryResponse(true, ['test' => 'data'], 'Success message');

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'data'], $response->data);
        $this->assertEquals('Success message', $response->message);
        $this->assertNull($response->error);
        $this->assertNull($response->details);
    }

    public function test_constructor_with_boolean_success_and_all_parameters(): void
    {
        $response = new ValidateHistoryResponse(
            false,
            ['error' => 'data'],
            'Error message',
            'Something went wrong',
            ['details' => 'info']
        );

        $this->assertFalse($response->success);
        $this->assertEquals(['error' => 'data'], $response->data);
        $this->assertEquals('Error message', $response->message);
        $this->assertEquals('Something went wrong', $response->error);
        $this->assertEquals(['details' => 'info'], $response->details);
    }

    public function test_constructor_with_associative_array_single_argument(): void
    {
        $data = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Array construction',
            'error' => null,
            'details' => ['info' => 'test'],
        ];

        $response = new ValidateHistoryResponse($data);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Array construction', $response->message);
        $this->assertNull($response->error);
        $this->assertEquals(['info' => 'test'], $response->details);
    }

    public function test_constructor_with_non_associative_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires an associative array.');

        new ValidateHistoryResponse(['value1', 'value2', 'value3']);
    }

    public function test_constructor_with_array_and_multiple_arguments(): void
    {
        // When an array is passed with additional parameters, the array data is used
        // and additional parameters are ignored
        $response = new ValidateHistoryResponse(
            ['success' => true, 'message' => 'Array message', 'data' => ['from' => 'array']],
            ['data' => 'test']
        );

        $this->assertTrue($response->success);
        $this->assertEquals('Array message', $response->message);
        $this->assertEquals(['from' => 'array'], $response->data); // Array data is used, additional parameter is ignored
        $this->assertNull($response->error);
        $this->assertNull($response->details);
    }

    public function test_constructor_with_empty_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires a non-empty array.');

        new ValidateHistoryResponse([]);
    }

    public function test_constructor_with_numeric_indexed_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction cannot use callable arrays.');

        new ValidateHistoryResponse([0 => 'value1', 1 => 'value2']);
    }

    public function test_constructor_with_mixed_indexed_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires at least one expected key: success, data, message, error, details');

        new ValidateHistoryResponse([0 => 'value1', 'key' => 'value2']);
    }

    public function test_constructor_with_minimal_boolean_parameters(): void
    {
        $response = new ValidateHistoryResponse(true);

        $this->assertTrue($response->success);
        $this->assertEquals([], $response->data);
        $this->assertEquals('', $response->message);
        $this->assertNull($response->error);
        $this->assertNull($response->details);
    }

    public function test_constructor_with_snake_case_keys_in_array(): void
    {
        $data = [
            'success' => false,
            'data' => ['test' => 'value'],
            'message' => 'Snake case test',
            'error' => 'Error occurred',
            'details' => ['info' => 'test'],
        ];

        $response = new ValidateHistoryResponse($data);

        $this->assertFalse($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Snake case test', $response->message);
        $this->assertEquals('Error occurred', $response->error);
        $this->assertEquals(['info' => 'test'], $response->details);
    }

    public function test_constructor_preserves_original_bug_scenario(): void
    {
        // This test ensures that the original bug scenario (array assigned to boolean property)
        // now throws an exception instead of silently failing when used as single argument
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction cannot use callable arrays.');

        // This should throw an exception because it's not an associative array
        new ValidateHistoryResponse(['not', 'associative']);
    }

    public function test_constructor_with_validation_error_scenario(): void
    {
        $data = [
            'success' => false,
            'data' => ['errors' => ['field1' => 'Required', 'field2' => 'Invalid']],
            'message' => 'Validation failed',
            'error' => 'Multiple validation errors',
            'details' => ['failed_fields' => ['field1', 'field2']],
        ];

        $response = new ValidateHistoryResponse($data);

        $this->assertFalse($response->success);
        $this->assertEquals(['errors' => ['field1' => 'Required', 'field2' => 'Invalid']], $response->data);
        $this->assertEquals('Validation failed', $response->message);
        $this->assertEquals('Multiple validation errors', $response->error);
        $this->assertEquals(['failed_fields' => ['field1', 'field2']], $response->details);
    }
}
