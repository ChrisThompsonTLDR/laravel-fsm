<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\ValidateHistoryRequest;
use PHPUnit\Framework\TestCase;

/**
 * Test for ValidateHistoryRequest nullable parameters and validation.
 *
 * Tests the changes where modelId and columnName became nullable.
 */
class ValidateHistoryRequestTest extends TestCase
{
    public function test_constructor_with_all_parameters(): void
    {
        $request = new ValidateHistoryRequest(
            modelClass: 'App\\Models\\Order',
            modelId: 'order-123',
            columnName: 'status'
        );

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('status', $request->columnName);
    }

    public function test_constructor_with_only_model_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ValidateHistoryRequest(
            modelClass: 'App\\Models\\Order'
        );
    }

    public function test_constructor_with_model_class_and_model_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ValidateHistoryRequest(
            modelClass: 'App\\Models\\Order',
            modelId: 'order-123'
        );
    }

    public function test_constructor_throws_exception_when_model_class_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelClass is required and cannot be an empty string.');

        new ValidateHistoryRequest(
            modelClass: '',
            modelId: 'order-123',
            columnName: 'status'
        );
    }

    public function test_constructor_throws_exception_when_model_id_is_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ValidateHistoryRequest(
            modelClass: 'App\\Models\\Order',
            modelId: '',
            columnName: 'status'
        );
    }

    public function test_constructor_throws_exception_when_column_name_is_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ValidateHistoryRequest(
            modelClass: 'App\\Models\\Order',
            modelId: 'order-123',
            columnName: ''
        );
    }

    public function test_constructor_with_array_and_all_parameters(): void
    {
        $request = new ValidateHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'columnName' => 'status',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('status', $request->columnName);
    }

    public function test_constructor_with_array_and_only_model_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_model_class_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelClass is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'modelId' => 'order-123',
            'columnName' => 'status',
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_model_class_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelClass is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'modelClass' => '',
            'modelId' => 'order-123',
            'columnName' => 'status',
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_model_id_is_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => '',
            'columnName' => 'status',
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_column_name_is_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'columnName' => '',
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_model_id_is_null(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => null,
            'columnName' => 'status',
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_column_name_is_null(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'columnName' => null,
        ]);
    }

    public function test_validation_rules_require_model_id_and_column_name(): void
    {
        // Test static rules method directly
        $rules = ValidateHistoryRequest::rules();

        $this->assertArrayHasKey('modelClass', $rules);
        $this->assertArrayHasKey('modelId', $rules);
        $this->assertArrayHasKey('columnName', $rules);

        // modelId and columnName should be required
        $this->assertContains('required', $rules['modelId']);
        $this->assertContains('required', $rules['columnName']);
    }

    /**
     * Test that the trim() bug is fixed - null values in arrays should not cause TypeError.
     * This test specifically addresses the bug where trim() was called on null values
     * when using array-based construction. The bug was that validation happened before
     * prepareAttributes() was called, potentially causing trim(null) to be executed.
     */
    public function test_constructor_with_array_handles_null_values_properly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        // Array-based construction with null modelId - should throw InvalidArgumentException, not TypeError
        new ValidateHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => null,
            'columnName' => 'status',
        ]);
    }

    /**
     * Test that validation happens after prepareAttributes() to avoid trim(null) errors.
     */
    public function test_constructor_validates_after_prepare_attributes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        // Array-based construction with null columnName - should throw InvalidArgumentException, not TypeError
        new ValidateHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'columnName' => null,
        ]);
    }

    /**
     * Test that both null values in array construction are handled correctly.
     */
    public function test_constructor_with_array_handles_multiple_null_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        // Array-based construction with multiple null values - should throw InvalidArgumentException, not TypeError
        new ValidateHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => null,
            'columnName' => null,
        ]);
    }

    /**
     * Test that whitespace-only strings are properly handled.
     */
    public function test_constructor_handles_whitespace_only_model_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ValidateHistoryRequest(
            modelClass: 'App\\Models\\Order',
            modelId: '   ',
            columnName: 'status'
        );
    }

    /**
     * Test that whitespace-only strings are properly handled.
     */
    public function test_constructor_handles_whitespace_only_column_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ValidateHistoryRequest(
            modelClass: 'App\\Models\\Order',
            modelId: 'order-123',
            columnName: '   '
        );
    }

    /**
     * Test that whitespace-only strings in array construction are properly handled.
     */
    public function test_constructor_with_array_handles_whitespace_only_model_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => '   ',
            'columnName' => 'status',
        ]);
    }

    /**
     * Test that whitespace-only strings in array construction are properly handled.
     */
    public function test_constructor_with_array_handles_whitespace_only_column_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'columnName' => '   ',
        ]);
    }

    /**
     * Test that snake_case keys are properly converted to camelCase.
     * This verifies that prepareAttributes() is called before validation.
     */
    public function test_constructor_with_array_handles_snake_case_keys(): void
    {
        $request = new ValidateHistoryRequest([
            'model_class' => 'App\\Models\\Order',
            'model_id' => 'order-123',
            'column_name' => 'status',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('status', $request->columnName);
    }

    /**
     * Test that snake_case keys with null values are properly validated.
     * This ensures prepareAttributes() runs before validation.
     */
    public function test_constructor_with_array_handles_snake_case_keys_with_null_model_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'model_class' => 'App\\Models\\Order',
            'model_id' => null,
            'column_name' => 'status',
        ]);
    }

    /**
     * Test that snake_case keys with null values are properly validated.
     * This ensures prepareAttributes() runs before validation.
     */
    public function test_constructor_with_array_handles_snake_case_keys_with_null_column_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'model_class' => 'App\\Models\\Order',
            'model_id' => 'order-123',
            'column_name' => null,
        ]);
    }

    /**
     * Test that snake_case keys with empty strings are properly validated.
     */
    public function test_constructor_with_array_handles_snake_case_keys_with_empty_model_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'model_class' => 'App\\Models\\Order',
            'model_id' => '',
            'column_name' => 'status',
        ]);
    }

    /**
     * Test that snake_case keys with empty strings are properly validated.
     */
    public function test_constructor_with_array_handles_snake_case_keys_with_empty_column_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'model_class' => 'App\\Models\\Order',
            'model_id' => 'order-123',
            'column_name' => '',
        ]);
    }

    /**
     * Test that mixed snake_case and camelCase keys work correctly.
     */
    public function test_constructor_with_array_handles_mixed_case_keys(): void
    {
        $request = new ValidateHistoryRequest([
            'model_class' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'column_name' => 'status',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('status', $request->columnName);
    }

    /**
     * Test that non-associative arrays are rejected.
     */
    public function test_constructor_rejects_non_associative_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ValidateHistoryRequest(['App\\Models\\Order', 'order-123', 'status']);
    }

    /**
     * Test that empty arrays are rejected.
     */
    public function test_constructor_rejects_empty_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelClass is required and cannot be an empty string.');

        new ValidateHistoryRequest([]);
    }

    /**
     * Test that arrays with missing required keys are rejected.
     */
    public function test_constructor_rejects_array_missing_model_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelClass is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'model_id' => 'order-123',
            'column_name' => 'status',
        ]);
    }

    /**
     * Test that arrays with missing modelId key are rejected.
     */
    public function test_constructor_rejects_array_missing_model_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'model_class' => 'App\\Models\\Order',
            'column_name' => 'status',
        ]);
    }

    /**
     * Test that arrays with missing columnName key are rejected.
     */
    public function test_constructor_rejects_array_missing_column_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ValidateHistoryRequest([
            'model_class' => 'App\\Models\\Order',
            'model_id' => 'order-123',
        ]);
    }
}
