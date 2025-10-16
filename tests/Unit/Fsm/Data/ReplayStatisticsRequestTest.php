<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\ReplayStatisticsRequest;
use PHPUnit\Framework\TestCase;

/**
 * Test for ReplayStatisticsRequest constructor and validation.
 *
 * Tests the constructor with various parameters and validation rules.
 */
class ReplayStatisticsRequestTest extends TestCase
{
    public function test_constructor_with_all_parameters(): void
    {
        $request = new ReplayStatisticsRequest(
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
        $request = new ReplayStatisticsRequest(
            modelClass: 'App\\Models\\Order'
        );

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('', $request->modelId);
        $this->assertSame('', $request->columnName);
    }

    public function test_constructor_with_model_class_and_model_id(): void
    {
        $request = new ReplayStatisticsRequest(
            modelClass: 'App\\Models\\Order',
            modelId: 'order-123'
        );

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('', $request->columnName);
    }

    public function test_constructor_with_array_and_all_parameters(): void
    {
        $request = new ReplayStatisticsRequest([
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
        $request = new ReplayStatisticsRequest([
            'modelClass' => 'App\\Models\\Order',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('', $request->modelId);
        $this->assertSame('', $request->columnName);
    }

    public function test_constructor_with_array_and_partial_parameters(): void
    {
        $request = new ReplayStatisticsRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => 'order-123',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('', $request->columnName);
    }

    public function test_validation_rules_are_static(): void
    {
        // Test static rules method directly
        $rules = ReplayStatisticsRequest::rules();

        $this->assertArrayHasKey('modelClass', $rules);
        $this->assertArrayHasKey('modelId', $rules);
        $this->assertArrayHasKey('columnName', $rules);

        // All fields should be required
        $this->assertContains('required', $rules['modelClass']);
        $this->assertContains('required', $rules['modelId']);
        $this->assertContains('required', $rules['columnName']);

        // All fields should be strings
        $this->assertContains('string', $rules['modelClass']);
        $this->assertContains('string', $rules['modelId']);
        $this->assertContains('string', $rules['columnName']);
    }

    public function test_validation_rules_model_class_has_custom_validation(): void
    {
        $rules = ReplayStatisticsRequest::rules();

        $this->assertArrayHasKey('modelClass', $rules);
        $modelClassRules = $rules['modelClass'];

        // Should have required, string, and custom validation
        $this->assertContains('required', $modelClassRules);
        $this->assertContains('string', $modelClassRules);

        // Should have a custom validation function
        $customValidation = array_filter($modelClassRules, function ($rule) {
            return is_callable($rule);
        });

        $this->assertCount(1, $customValidation);
    }

    public function test_validation_rules_model_id_and_column_name_are_simple(): void
    {
        $rules = ReplayStatisticsRequest::rules();

        // modelId should only have required and string rules
        $this->assertEquals(['required', 'string'], $rules['modelId']);

        // columnName should only have required and string rules
        $this->assertEquals(['required', 'string'], $rules['columnName']);
    }

    public function test_constructor_with_empty_array(): void
    {
        // Empty array should be treated as positional parameters
        $request = new ReplayStatisticsRequest([]);

        $this->assertSame('', $request->modelClass);
        $this->assertSame('', $request->modelId);
        $this->assertSame('', $request->columnName);
    }

    public function test_constructor_handles_empty_strings(): void
    {
        $request = new ReplayStatisticsRequest(
            modelClass: '',
            modelId: '',
            columnName: ''
        );

        $this->assertSame('', $request->modelClass);
        $this->assertSame('', $request->modelId);
        $this->assertSame('', $request->columnName);
    }

    public function test_constructor_with_array_handles_empty_strings(): void
    {
        $request = new ReplayStatisticsRequest([
            'modelClass' => '',
            'modelId' => '',
            'columnName' => '',
        ]);

        $this->assertSame('', $request->modelClass);
        $this->assertSame('', $request->modelId);
        $this->assertSame('', $request->columnName);
    }

    public function test_constructor_with_array_handles_extra_keys(): void
    {
        $request = new ReplayStatisticsRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'columnName' => 'status',
            'extraKey' => 'extraValue',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('status', $request->columnName);
    }

    public function test_constructor_with_array_handles_whitespace_strings(): void
    {
        $request = new ReplayStatisticsRequest([
            'modelClass' => '  App\\Models\\Order  ',
            'modelId' => '  order-123  ',
            'columnName' => '  status  ',
        ]);

        $this->assertSame('  App\\Models\\Order  ', $request->modelClass);
        $this->assertSame('  order-123  ', $request->modelId);
        $this->assertSame('  status  ', $request->columnName);
    }

    public function test_constructor_with_array_handles_missing_keys(): void
    {
        $request = new ReplayStatisticsRequest([
            'modelClass' => 'App\\Models\\Order',
            // modelId and columnName are missing
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('', $request->modelId);
        $this->assertSame('', $request->columnName);
    }

    public function test_constructor_with_array_handles_whitespace_strings_mixed(): void
    {
        $request = new ReplayStatisticsRequest([
            'modelClass' => '  App\\Models\\Order  ',
            'modelId' => 'order-123',
            'columnName' => '  status  ',
        ]);

        $this->assertSame('  App\\Models\\Order  ', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('  status  ', $request->columnName);
    }

    public function test_constructor_with_array_handles_empty_strings_mixed(): void
    {
        $request = new ReplayStatisticsRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => '',
            'columnName' => 'status',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('', $request->modelId);
        $this->assertSame('status', $request->columnName);
    }

    public function test_constructor_with_array_handles_whitespace_strings_mixed_2(): void
    {
        $request = new ReplayStatisticsRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => '  order-123  ',
            'columnName' => 'status',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('  order-123  ', $request->modelId);
        $this->assertSame('status', $request->columnName);
    }

    public function test_constructor_with_array_handles_empty_strings_mixed_2(): void
    {
        $request = new ReplayStatisticsRequest([
            'modelClass' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'columnName' => '',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('', $request->columnName);
    }

    public function test_constructor_with_array_converts_snake_case_to_camel_case(): void
    {
        // Test that snake_case keys are converted to camelCase properties
        $request = new ReplayStatisticsRequest([
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
        $request = new ReplayStatisticsRequest([
            'model_class' => 'App\\Models\\Order',
            'modelId' => 'order-123',
            'column_name' => 'status',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('status', $request->columnName);
    }

    public function test_constructor_with_snake_case_and_partial_parameters(): void
    {
        $request = new ReplayStatisticsRequest([
            'model_class' => 'App\\Models\\Order',
            'model_id' => 'order-123',
        ]);

        $this->assertSame('App\\Models\\Order', $request->modelClass);
        $this->assertSame('order-123', $request->modelId);
        $this->assertSame('', $request->columnName);
    }
}
