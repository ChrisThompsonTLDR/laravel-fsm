<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\ReplayHistoryRequest;
use PHPUnit\Framework\TestCase;

/**
 * Test for ReplayHistoryRequest constructor and validation.
 */
class ReplayHistoryRequestTest extends TestCase
{
    public function test_constructor_with_all_parameters(): void
    {
        $request = new ReplayHistoryRequest(
            modelClass: 'App\\Models\\Order',
            modelId: 'order-123',
            columnName: 'status'
        );

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('status', $request->columnName);
    }

    public function test_constructor_with_array_and_all_parameters(): void
    {
        $request = new ReplayHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'columnName' => 'status',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('status', $request->columnName);
    }

    public function test_constructor_with_array_and_missing_model_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelClass is required and cannot be an empty string.');

        new ReplayHistoryRequest([
            'modelId' => 'order-123',
            'columnName' => 'status',
        ]);
    }

    public function test_constructor_with_array_and_missing_model_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ReplayHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'columnName' => 'status',
        ]);
    }

    public function test_constructor_with_array_and_missing_column_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ReplayHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => 'order-123',
        ]);
    }

    public function test_constructor_with_array_and_empty_model_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelClass is required and cannot be an empty string.');

        new ReplayHistoryRequest([
            'modelClass' => '',
            'modelId' => 'order-123',
            'columnName' => 'status',
        ]);
    }

    public function test_constructor_with_array_and_empty_model_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ReplayHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => '',
            'columnName' => 'status',
        ]);
    }

    public function test_constructor_with_array_and_empty_column_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ReplayHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'columnName' => '',
        ]);
    }

    public function test_constructor_with_array_and_null_model_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelClass is required and cannot be an empty string.');

        new ReplayHistoryRequest([
            'modelClass' => null,
            'modelId' => 'order-123',
            'columnName' => 'status',
        ]);
    }

    public function test_constructor_with_array_and_null_model_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ReplayHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => null,
            'columnName' => 'status',
        ]);
    }

    public function test_constructor_with_array_and_null_column_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ReplayHistoryRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'columnName' => null,
        ]);
    }

    public function test_validation_rules_require_model_id_and_column_name(): void
    {
        // Test static rules method directly
        $rules = ReplayHistoryRequest::rules();

        $this->assertArrayHasKey('modelClass', $rules);
        $this->assertArrayHasKey('modelId', $rules);
        $this->assertArrayHasKey('columnName', $rules);

        // modelId and columnName should be required
        $this->assertContains('required', $rules['modelId']);
        $this->assertContains('required', $rules['columnName']);
    }

    public function test_validation_rules_model_class_validation(): void
    {
        $rules = ReplayHistoryRequest::rules();

        $this->assertArrayHasKey('modelClass', $rules);
        $this->assertContains('required', $rules['modelClass']);
        $this->assertContains('string', $rules['modelClass']);

        // Check that there's a custom validation closure
        $this->assertCount(3, $rules['modelClass']);
        $this->assertIsCallable($rules['modelClass'][2]);
    }

    public function test_constructor_with_array_converts_snake_case_to_camel_case(): void
    {
        // Test that snake_case keys are converted to camelCase properties
        $request = new ReplayHistoryRequest([
            'model_class' => 'App\\Models\\Order',
            'model_id' => 'order-123',
            'column_name' => 'status',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('status', $request->columnName);
    }

    public function test_constructor_with_array_handles_mixed_case_keys(): void
    {
        // Test that mixed snake_case and camelCase keys work together
        $request = new ReplayHistoryRequest([
            'model_class' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'column_name' => 'status',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('status', $request->columnName);
    }

    public function test_constructor_with_snake_case_validates_empty_strings(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelClass is required and cannot be an empty string.');

        new ReplayHistoryRequest([
            'model_class' => '',
            'model_id' => 'order-123',
            'column_name' => 'status',
        ]);
    }

    public function test_constructor_with_snake_case_validates_missing_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId is required and cannot be an empty string.');

        new ReplayHistoryRequest([
            'model_class' => 'App\\Models\\Order',
            'column_name' => 'status',
        ]);
    }

    public function test_constructor_with_snake_case_validates_whitespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName is required and cannot be an empty string.');

        new ReplayHistoryRequest([
            'model_class' => 'App\\Models\\Order',
            'model_id' => 'order-123',
            'column_name' => '   ',
        ]);
    }
}
