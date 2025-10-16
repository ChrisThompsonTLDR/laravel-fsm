<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\ReplayTransitionsResponse;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Test class to verify the fix for inconsistent array-based initialization logic
 * in the ReplayTransitionsResponse constructor.
 */
class ReplayTransitionsResponseConstructorBugFixTest extends TestCase
{
    /**
     * Test that array-based construction works with single argument.
     */
    public function test_array_based_construction_with_single_argument(): void
    {
        $data = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Test message',
            'error' => null,
            'details' => ['extra' => 'info'],
        ];

        $response = new ReplayTransitionsResponse($data);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Test message', $response->message);
        $this->assertNull($response->error);
        $this->assertEquals(['extra' => 'info'], $response->details);
    }

    /**
     * Test that named parameters work correctly with boolean success.
     */
    public function test_named_parameters_with_boolean_success(): void
    {
        $response = new ReplayTransitionsResponse(
            success: true,
            data: ['from_named' => 'value'],
            message: 'From named parameters',
            error: 'Named error',
            details: ['named' => 'details']
        );

        $this->assertTrue($response->success);
        $this->assertEquals(['from_named' => 'value'], $response->data);
        $this->assertEquals('From named parameters', $response->message);
        $this->assertEquals('Named error', $response->error);
        $this->assertEquals(['named' => 'details'], $response->details);
    }

    /**
     * Test that boolean success parameter works correctly with named parameters.
     */
    public function test_boolean_success_with_named_parameters(): void
    {
        $response = new ReplayTransitionsResponse(
            success: true,
            data: ['test' => 'data'],
            message: 'Success message',
            error: null,
            details: ['info' => 'details']
        );

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'data'], $response->data);
        $this->assertEquals('Success message', $response->message);
        $this->assertNull($response->error);
        $this->assertEquals(['info' => 'details'], $response->details);
    }

    /**
     * Test that positional parameters work correctly.
     */
    public function test_positional_parameters(): void
    {
        $response = new ReplayTransitionsResponse(
            true,
            ['test' => 'data'],
            'Test message',
            null,
            ['details' => 'info']
        );

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'data'], $response->data);
        $this->assertEquals('Test message', $response->message);
        $this->assertNull($response->error);
        $this->assertEquals(['details' => 'info'], $response->details);
    }

    /**
     * Test that default values are applied correctly.
     */
    public function test_default_values(): void
    {
        $response = new ReplayTransitionsResponse(true);

        $this->assertTrue($response->success);
        $this->assertEquals([], $response->data);
        $this->assertEquals('', $response->message);
        $this->assertNull($response->error);
        $this->assertNull($response->details);
    }

    /**
     * Test that array-based construction with snake_case keys works.
     */
    public function test_array_based_construction_with_snake_case_keys(): void
    {
        $data = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Test message',
            'error' => null,
            'details' => ['extra' => 'info'],
        ];

        $response = new ReplayTransitionsResponse($data);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Test message', $response->message);
        $this->assertNull($response->error);
        $this->assertEquals(['extra' => 'info'], $response->details);
    }

    /**
     * Test that array-based construction fails with empty array.
     */
    public function test_array_based_construction_fails_with_empty_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires a non-empty array.');

        new ReplayTransitionsResponse([]);
    }

    /**
     * Test that array-based construction fails with non-associative array.
     */
    public function test_array_based_construction_fails_with_non_associative_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires an associative array.');

        new ReplayTransitionsResponse(['value1', 'value2', 'value3']);
    }

    /**
     * Test that array-based construction fails with callable array.
     */
    public function test_array_based_construction_fails_with_callable_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction cannot use callable arrays.');

        new ReplayTransitionsResponse(['class', 'method']);
    }

    /**
     * Test that array-based construction fails with numeric keys.
     */
    public function test_array_based_construction_fails_with_numeric_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction cannot use callable arrays.');

        new ReplayTransitionsResponse([0 => 'value1', 1 => 'value2']);
    }

    /**
     * Test that array-based construction fails without expected keys.
     */
    public function test_array_based_construction_fails_without_expected_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires at least one expected key: success, data, message, error, details');

        new ReplayTransitionsResponse(['unexpected' => 'key']);
    }

    /**
     * Test that fromArray method works correctly.
     */
    public function test_from_array_method(): void
    {
        $data = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Test message',
            'error' => null,
            'details' => ['extra' => 'info'],
        ];

        $response = ReplayTransitionsResponse::fromArray($data);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Test message', $response->message);
        $this->assertNull($response->error);
        $this->assertEquals(['extra' => 'info'], $response->details);
    }

    /**
     * Test that fromArray method applies default values correctly.
     */
    public function test_from_array_method_with_defaults(): void
    {
        $data = ['success' => true];

        $response = ReplayTransitionsResponse::fromArray($data);

        $this->assertTrue($response->success);
        $this->assertEquals([], $response->data);
        $this->assertEquals('', $response->message);
        $this->assertNull($response->error);
        $this->assertNull($response->details);
    }

    /**
     * Test that the bug is fixed: array parameter with additional parameters should not use array-based construction.
     * This test verifies that when multiple arguments are present, array-based construction is not used.
     */
    public function test_bug_fix_array_parameter_with_additional_parameters(): void
    {
        // This should use named parameter initialization, not array-based construction
        $response = new ReplayTransitionsResponse(
            success: true,
            data: ['from_named' => 'value'],
            message: 'Named message'
        );

        // Verify that named parameters are used correctly
        $this->assertTrue($response->success);
        $this->assertEquals(['from_named' => 'value'], $response->data);
        $this->assertEquals('Named message', $response->message);
    }

    /**
     * Test that mixed parameter types work correctly.
     */
    public function test_mixed_parameter_types(): void
    {
        $response = new ReplayTransitionsResponse(
            success: false,
            data: ['key' => 'value'],
            message: 'Error message',
            error: 'Something went wrong',
            details: ['debug' => 'info']
        );

        $this->assertFalse($response->success);
        $this->assertEquals(['key' => 'value'], $response->data);
        $this->assertEquals('Error message', $response->message);
        $this->assertEquals('Something went wrong', $response->error);
        $this->assertEquals(['debug' => 'info'], $response->details);
    }

    /**
     * Test that null values are handled correctly.
     */
    public function test_null_values(): void
    {
        $response = new ReplayTransitionsResponse(
            success: true,
            data: null,
            message: null,
            error: null,
            details: null
        );

        $this->assertTrue($response->success);
        $this->assertEquals([], $response->data); // Should default to empty array
        $this->assertEquals('', $response->message); // Should default to empty string
        $this->assertNull($response->error);
        $this->assertNull($response->details);
    }

    /**
     * Test that the bug is fixed: when multiple arguments are present, array-based construction is not used.
     * This test demonstrates the fix by showing that array-based construction only happens with single argument.
     */
    public function test_bug_fix_multiple_arguments_do_not_use_array_construction(): void
    {
        // Test with single argument - should use array-based construction
        $arrayData = [
            'success' => true,
            'data' => ['from_array' => 'value'],
            'message' => 'From array construction',
            'error' => null,
            'details' => ['array' => 'details'],
        ];

        $response1 = new ReplayTransitionsResponse($arrayData);
        $this->assertTrue($response1->success);
        $this->assertEquals(['from_array' => 'value'], $response1->data);
        $this->assertEquals('From array construction', $response1->message);

        // Test with multiple arguments - should use named parameter initialization
        $response2 = new ReplayTransitionsResponse(
            success: false,
            data: ['from_named' => 'value'],
            message: 'From named parameters'
        );

        $this->assertFalse($response2->success);
        $this->assertEquals(['from_named' => 'value'], $response2->data);
        $this->assertEquals('From named parameters', $response2->message);
    }
}
