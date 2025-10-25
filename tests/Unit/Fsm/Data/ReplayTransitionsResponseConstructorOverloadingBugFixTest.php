<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\ReplayTransitionsResponse;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

/**
 * Test for ReplayTransitionsResponse constructor overloading bug fix.
 *
 * Tests that the constructor no longer overloads the first parameter for both
 * specific field values and array-based DTO construction, making the API clearer.
 */
class ReplayTransitionsResponseConstructorOverloadingBugFixTest extends TestCase
{
    public function test_constructor_with_boolean_success_parameter(): void
    {
        $response = new ReplayTransitionsResponse(true, ['test' => 'data'], 'Success message');

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'data'], $response->data);
        $this->assertEquals('Success message', $response->message);
        $this->assertNull($response->error);
        $this->assertNull($response->details);
    }

    public function test_constructor_with_boolean_success_and_all_parameters(): void
    {
        $response = new ReplayTransitionsResponse(
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

    public function test_constructor_with_minimal_boolean_parameters(): void
    {
        $response = new ReplayTransitionsResponse(true);

        $this->assertTrue($response->success);
        $this->assertEquals([], $response->data);
        $this->assertEquals('', $response->message);
        $this->assertNull($response->error);
        $this->assertNull($response->details);
    }

    public function test_constructor_accepts_array_as_first_parameter(): void
    {
        // This should work with array-based construction
        $response = new ReplayTransitionsResponse(['success' => true, 'data' => ['test' => 'value'], 'message' => 'Array construction']);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Array construction', $response->message);
    }

    public function test_from_array_static_method_works_correctly(): void
    {
        $data = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Array construction',
            'error' => null,
            'details' => ['info' => 'test'],
        ];

        $response = ReplayTransitionsResponse::fromArray($data);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Array construction', $response->message);
        $this->assertNull($response->error);
        $this->assertEquals(['info' => 'test'], $response->details);
    }

    public function test_from_array_with_minimal_data(): void
    {
        $data = [
            'success' => false,
        ];

        $response = ReplayTransitionsResponse::fromArray($data);

        $this->assertFalse($response->success);
        $this->assertEquals([], $response->data);
        $this->assertEquals('', $response->message);
        $this->assertNull($response->error);
        $this->assertNull($response->details);
    }

    public function test_from_array_with_snake_case_keys(): void
    {
        $data = [
            'success' => false,
            'data' => ['test' => 'value'],
            'message' => 'Snake case test',
            'error' => 'Error occurred',
            'details' => ['info' => 'test'],
        ];

        $response = ReplayTransitionsResponse::fromArray($data);

        $this->assertFalse($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Snake case test', $response->message);
        $this->assertEquals('Error occurred', $response->error);
        $this->assertEquals(['info' => 'test'], $response->details);
    }

    public function test_from_array_throws_exception_for_invalid_data(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires at least one expected key: success, data, message, error, details');

        ReplayTransitionsResponse::fromArray([]);
    }

    public function test_from_array_throws_exception_for_non_associative_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires an associative array.');

        ReplayTransitionsResponse::fromArray(['value1', 'value2', 'value3']);
    }

    public function test_from_array_throws_exception_for_mixed_indexed_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires at least one expected key: success, data, message, error, details');

        ReplayTransitionsResponse::fromArray([0 => 'value1', 'key' => 'value2']);
    }

    public function test_from_array_throws_exception_for_numeric_indexed_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction cannot use callable arrays.');

        ReplayTransitionsResponse::fromArray([0 => 'value1', 1 => 'value2']);
    }

    public function test_constructor_api_clarity_improvement(): void
    {
        // Test that the API is now clear - first parameter is always bool
        $response1 = new ReplayTransitionsResponse(true);
        $this->assertTrue($response1->success);

        $response2 = new ReplayTransitionsResponse(false, ['data' => 'test']);
        $this->assertFalse($response2->success);
        $this->assertEquals(['data' => 'test'], $response2->data);

        // Test that array-based construction is now explicit via fromArray
        $response3 = ReplayTransitionsResponse::fromArray(['success' => true, 'data' => ['test' => 'value']]);
        $this->assertTrue($response3->success);
        $this->assertEquals(['test' => 'value'], $response3->data);
    }

    public function test_constructor_type_safety_improvement(): void
    {
        // Test that type safety is improved - no more ambiguous parameter types
        $response = new ReplayTransitionsResponse(
            success: true,
            data: ['key' => 'value'],
            message: 'Type safe',
            error: null,
            details: ['info' => 'test']
        );

        $this->assertTrue($response->success);
        $this->assertEquals(['key' => 'value'], $response->data);
        $this->assertEquals('Type safe', $response->message);
        $this->assertNull($response->error);
        $this->assertEquals(['info' => 'test'], $response->details);
    }

    public function test_constructor_parameter_order_clarity(): void
    {
        // Test that parameter order is now clear and consistent
        $response = new ReplayTransitionsResponse(
            success: false,
            data: ['error' => 'data'],
            message: 'Error occurred',
            error: 'Something went wrong',
            details: ['trace' => 'stack']
        );

        $this->assertFalse($response->success);
        $this->assertEquals(['error' => 'data'], $response->data);
        $this->assertEquals('Error occurred', $response->message);
        $this->assertEquals('Something went wrong', $response->error);
        $this->assertEquals(['trace' => 'stack'], $response->details);
    }

    public function test_from_array_handles_all_properties(): void
    {
        $data = [
            'success' => true,
            'data' => ['complex' => ['nested' => 'data']],
            'message' => 'Complex response',
            'error' => null,
            'details' => ['metadata' => ['version' => '1.0']],
        ];

        $response = ReplayTransitionsResponse::fromArray($data);

        $this->assertTrue($response->success);
        $this->assertEquals(['complex' => ['nested' => 'data']], $response->data);
        $this->assertEquals('Complex response', $response->message);
        $this->assertNull($response->error);
        $this->assertEquals(['metadata' => ['version' => '1.0']], $response->details);
    }

    public function test_from_array_uses_defaults_for_missing_properties(): void
    {
        $data = [
            'success' => true,
        ];

        $response = ReplayTransitionsResponse::fromArray($data);

        $this->assertTrue($response->success);
        $this->assertEquals([], $response->data); // Default empty array
        $this->assertEquals('', $response->message); // Default empty string
        $this->assertNull($response->error); // Default null
        $this->assertNull($response->details); // Default null
    }
}
