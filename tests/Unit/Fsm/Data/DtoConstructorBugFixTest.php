<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\Dto;
use Fsm\Data\TransitionGuard;
use Fsm\Data\ValidateHistoryRequest;
use Fsm\Data\ValidateHistoryResponse;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use Tests\Feature\Fsm\Data\TestContextData;

class DtoConstructorBugFixTest extends TestCase
{
    public function test_is_callable_array_detects_valid_callable_arrays(): void
    {
        // Valid callable arrays
        $this->assertTrue(Dto::isCallableArray(['ClassName', 'method']));
        $this->assertTrue(Dto::isCallableArray([new \stdClass, 'method']));
        $this->assertTrue(Dto::isCallableArray(['App\\Services\\MyService', 'handle']));

        // Invalid callable arrays
        $this->assertFalse(Dto::isCallableArray(['ClassName'])); // Only one element
        $this->assertFalse(Dto::isCallableArray(['ClassName', 'method', 'extra'])); // Three elements
        $this->assertFalse(Dto::isCallableArray([0 => 'ClassName', 1 => 'method', 2 => 'extra'])); // Three elements
        $this->assertFalse(Dto::isCallableArray(['ClassName', 123])); // Second element not string
        $this->assertFalse(Dto::isCallableArray([123, 'method'])); // First element not string/object
        $this->assertFalse(Dto::isCallableArray(['key' => 'ClassName', 'method' => 'method'])); // Associative
        $this->assertFalse(Dto::isCallableArray([])); // Empty
    }

    public function test_is_dto_property_array_detects_valid_dto_arrays(): void
    {
        $expectedKeys = ['callable', 'parameters', 'description'];

        // Valid DTO property arrays
        $this->assertTrue(Dto::isDtoPropertyArray(['callable' => 'test', 'parameters' => []], $expectedKeys));
        $this->assertTrue(Dto::isDtoPropertyArray(['description' => 'test'], $expectedKeys));
        $this->assertTrue(Dto::isDtoPropertyArray(['callable' => 'test', 'extra' => 'value'], $expectedKeys));

        // Mixed keys arrays should be valid if they have expected keys
        $this->assertTrue(Dto::isDtoPropertyArray(['callable' => 'test', 0 => 'value'], $expectedKeys));

        // Invalid DTO property arrays
        $this->assertFalse(Dto::isDtoPropertyArray([], $expectedKeys)); // Empty
        $this->assertFalse(Dto::isDtoPropertyArray(['ClassName', 'method'], $expectedKeys)); // Callable array
        $this->assertFalse(Dto::isDtoPropertyArray([0, 1, 2], $expectedKeys)); // Numeric only
        $this->assertFalse(Dto::isDtoPropertyArray(['other' => 'value'], $expectedKeys)); // No expected keys
    }

    public function test_transition_guard_handles_callable_arrays_correctly(): void
    {
        // Callable array should be treated as positional parameter
        $guard = new TransitionGuard(['MyClass', 'method']);

        $this->assertEquals(['MyClass', 'method'], $guard->callable);
        $this->assertEquals([], $guard->parameters);
        $this->assertNull($guard->description);
        $this->assertEquals(TransitionGuard::PRIORITY_NORMAL, $guard->priority);
        $this->assertFalse($guard->stopOnFailure);
        $this->assertNull($guard->name);
    }

    public function test_transition_guard_handles_dto_property_arrays_correctly(): void
    {
        // DTO property array should be used for construction
        $data = [
            'callable' => 'MyClass@method',
            'parameters' => ['param1' => 'value1'],
            'description' => 'Test guard',
            'priority' => 75,
            'stopOnFailure' => true,
            'name' => 'test-guard',
        ];

        $guard = new TransitionGuard($data);

        $this->assertEquals('MyClass@method', $guard->callable);
        $this->assertEquals(['param1' => 'value1'], $guard->parameters);
        $this->assertEquals('Test guard', $guard->description);
        $this->assertEquals(75, $guard->priority);
        $this->assertTrue($guard->stopOnFailure);
        $this->assertEquals('test-guard', $guard->name);
    }

    public function test_transition_guard_rejects_invalid_arrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array parameter must be either a callable array [class, method] or an associative array with DTO property keys.');

        new TransitionGuard(['invalid', 'array', 'with', 'too', 'many', 'elements']);
    }

    public function test_test_context_data_handles_mixed_keys_arrays(): void
    {
        // Mixed keys array with expected keys should work
        $data = [
            'message' => 'Test message',
            0 => 'ignored value',
            'userId' => 123,
            'triggerFailure' => true,
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Test message', $context->message);
        $this->assertEquals(123, $context->userId);
        $this->assertTrue($context->triggerFailure);
    }

    public function test_test_context_data_rejects_callable_arrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Callable arrays are not valid for TestContextData construction.');

        new TestContextData(['MyClass', 'method']);
    }

    public function test_test_context_data_rejects_arrays_without_expected_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array parameter must be an associative array with DTO property keys used as single argument for array-based construction.');

        new TestContextData(['other' => 'value', 'unexpected' => 'key']);
    }

    public function test_validate_history_request_handles_mixed_keys_arrays(): void
    {
        // Mixed keys array with expected keys should work
        $data = [
            'modelClass' => 'App\\Models\\User',
            0 => 'ignored value',
            'modelId' => '123',
            'columnName' => 'status',
        ];

        $request = new ValidateHistoryRequest($data);

        $this->assertEquals('App\\Models\\User', $request->modelClass);
        $this->assertEquals('123', $request->modelId);
        $this->assertEquals('status', $request->columnName);
    }

    public function test_validate_history_request_rejects_callable_arrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelClass is required and cannot be an empty string.');

        // Callable arrays are not associative, so they fall through to positional validation
        // where the array is treated as the modelClass parameter and fails type validation
        new ValidateHistoryRequest(['MyClass', 'method']);
    }

    public function test_validate_history_response_handles_mixed_keys_arrays(): void
    {
        // Mixed keys array with expected keys should work
        $data = [
            'success' => true,
            0 => 'ignored value',
            'message' => 'Success',
            'data' => ['result' => 'test'],
        ];

        $response = new ValidateHistoryResponse($data);

        $this->assertTrue($response->success);
        $this->assertEquals('Success', $response->message);
        $this->assertEquals(['result' => 'test'], $response->data);
    }

    public function test_validate_history_response_rejects_callable_arrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction cannot use callable arrays.');

        new ValidateHistoryResponse(['MyClass', 'method']);
    }

    public function test_positional_construction_still_works(): void
    {
        // Test that positional construction still works for all classes
        $guard = new TransitionGuard('MyClass@method', ['param' => 'value'], 'Test guard', 75, true, 'test');
        $this->assertEquals('MyClass@method', $guard->callable);
        $this->assertEquals(['param' => 'value'], $guard->parameters);
        $this->assertEquals('Test guard', $guard->description);

        $context = new TestContextData('Hello', 123, true);
        $this->assertEquals('Hello', $context->message);
        $this->assertEquals(123, $context->userId);
        $this->assertTrue($context->triggerFailure);

        $request = new ValidateHistoryRequest('App\\Models\\User', '123', 'status');
        $this->assertEquals('App\\Models\\User', $request->modelClass);
        $this->assertEquals('123', $request->modelId);
        $this->assertEquals('status', $request->columnName);

        $response = new ValidateHistoryResponse(true, ['data'], 'Success');
        $this->assertTrue($response->success);
        $this->assertEquals(['data'], $response->data);
        $this->assertEquals('Success', $response->message);
    }

    public function test_edge_cases(): void
    {
        // Empty array should be rejected
        $this->expectException(InvalidArgumentException::class);
        new TestContextData([]);

        // Array with only numeric keys should be rejected
        $this->expectException(InvalidArgumentException::class);
        new TestContextData([0 => 'value1', 1 => 'value2']);

        // Array with mixed keys but no expected keys should be rejected
        $this->expectException(InvalidArgumentException::class);
        new TestContextData(['unexpected' => 'value', 0 => 'numeric']);
    }

    public function test_snake_case_key_conversion(): void
    {
        // Test that snake_case keys are converted to camelCase
        $data = [
            'user_id' => 456,
            'trigger_failure' => true,
            'message' => 'Snake case test',
        ];

        $context = new TestContextData($data);

        $this->assertEquals('Snake case test', $context->message);
        $this->assertEquals(456, $context->userId);
        $this->assertTrue($context->triggerFailure);
    }
}
