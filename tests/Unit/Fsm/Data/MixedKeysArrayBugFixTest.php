<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\ValidateHistoryRequest;
use Fsm\Data\ValidateHistoryResponse;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use Tests\Feature\Fsm\Data\TestContextData;

class MixedKeysArrayBugFixTest extends TestCase
{
    public function test_test_context_data_accepts_mixed_keys_arrays(): void
    {
        // This was the bug: mixed keys arrays were being rejected when they should be allowed
        // if they contain expected DTO property keys
        $data = [
            'message' => 'Test message',
            0 => 'ignored numeric value',
            'userId' => 123,
            1 => 'another ignored value',
            'triggerFailure' => true,
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Test message', $context->message);
        $this->assertEquals(123, $context->userId);
        $this->assertTrue($context->triggerFailure);
    }

    public function test_validate_history_request_accepts_mixed_keys_arrays(): void
    {
        $data = [
            'modelClass' => 'App\\Models\\User',
            0 => 'ignored value',
            'modelId' => '123',
            1 => 'another ignored value',
            'columnName' => 'status',
        ];

        $request = new ValidateHistoryRequest($data);

        $this->assertEquals('App\\Models\\User', $request->modelClass);
        $this->assertEquals('123', $request->modelId);
        $this->assertEquals('status', $request->columnName);
    }

    public function test_validate_history_response_accepts_mixed_keys_arrays(): void
    {
        $data = [
            'success' => true,
            0 => 'ignored value',
            'message' => 'Success',
            1 => 'another ignored value',
            'data' => ['result' => 'test'],
        ];

        $response = new ValidateHistoryResponse($data);

        $this->assertTrue($response->success);
        $this->assertEquals('Success', $response->message);
        $this->assertEquals(['result' => 'test'], $response->data);
    }

    public function test_mixed_keys_array_with_snake_case_keys(): void
    {
        $data = [
            'message' => 'Test message',
            0 => 'ignored value',
            'user_id' => 456, // snake_case should be converted to camelCase
            'trigger_failure' => true,
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Test message', $context->message);
        $this->assertEquals(456, $context->userId); // Should be converted to camelCase
        $this->assertTrue($context->triggerFailure); // Should be converted to camelCase
    }

    public function test_mixed_keys_array_with_partial_dto_properties(): void
    {
        // Should work even if not all DTO properties are present
        $data = [
            'message' => 'Partial data',
            0 => 'ignored value',
            'userId' => 789,
            // triggerFailure not provided, should use default
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Partial data', $context->message);
        $this->assertEquals(789, $context->userId);
        $this->assertFalse($context->triggerFailure); // Default value
    }

    public function test_mixed_keys_array_rejects_arrays_without_expected_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array parameter must be an associative array with DTO property keys used as single argument for array-based construction.');

        new TestContextData([
            'unexpected' => 'key',
            0 => 'ignored value',
            'other' => 'value',
        ]);
    }

    public function test_mixed_keys_array_rejects_callable_arrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Callable arrays are not valid for TestContextData construction.');

        new TestContextData(['MyClass', 'method']);
    }

    public function test_mixed_keys_array_rejects_empty_arrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array parameter must be an associative array with DTO property keys used as single argument for array-based construction.');

        new TestContextData([]);
    }

    public function test_mixed_keys_array_rejects_purely_numeric_arrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array parameter must be an associative array with DTO property keys used as single argument for array-based construction.');

        new TestContextData([0 => 'value1', 1 => 'value2', 2 => 'value3']);
    }

    public function test_mixed_keys_array_with_complex_data_types(): void
    {
        $data = [
            'message' => 'Complex test',
            0 => 'ignored',
            'userId' => 999,
            'triggerFailure' => true,
            1 => ['nested' => 'array'],
            2 => new \stdClass,
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Complex test', $context->message);
        $this->assertEquals(999, $context->userId);
        $this->assertTrue($context->triggerFailure);
    }

    public function test_mixed_keys_array_preserves_original_behavior_for_valid_cases(): void
    {
        // Ensure that valid cases that worked before still work
        $data = [
            'message' => 'Valid case',
            'userId' => 123,
            'triggerFailure' => true,
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Valid case', $context->message);
        $this->assertEquals(123, $context->userId);
        $this->assertTrue($context->triggerFailure);
    }

    public function test_mixed_keys_array_with_null_values(): void
    {
        $data = [
            'message' => 'Test with nulls',
            0 => 'ignored',
            'userId' => null,
            'triggerFailure' => false,
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Test with nulls', $context->message);
        $this->assertNull($context->userId);
        $this->assertFalse($context->triggerFailure);
    }

    public function test_mixed_keys_array_with_zero_values(): void
    {
        $data = [
            'message' => 'Test with zeros',
            0 => 'ignored',
            'userId' => 0,
            'triggerFailure' => false,
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Test with zeros', $context->message);
        $this->assertEquals(0, $context->userId);
        $this->assertFalse($context->triggerFailure);
    }
}
