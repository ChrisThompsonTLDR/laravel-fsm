<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Carbon\CarbonImmutable;
use Fsm\Data\StateTimelineEntryData;
use PHPUnit\Framework\TestCase;

/**
 * Test for StateTimelineEntryData nullable parameters and validation.
 *
 * Tests the changes where some parameters became nullable and validation logic was updated.
 */
class StateTimelineEntryDataTest extends TestCase
{
    public function test_constructor_with_all_required_parameters(): void
    {
        $happenedAt = CarbonImmutable::parse('2024-01-01 12:00:00');

        $data = new StateTimelineEntryData(
            id: 'test-id-123',
            model_id: 'model-456',
            model_type: 'App\\Models\\Order',
            fsm_column: 'status',
            from_state: 'pending',
            to_state: 'processing',
            happened_at: $happenedAt
        );

        $this->assertSame('test-id-123', $data->id);
        $this->assertSame('model-456', $data->modelId);
        $this->assertSame('App\\Models\\Order', $data->modelType);
        $this->assertSame('status', $data->fsmColumn);
        $this->assertSame('pending', $data->fromState);
        $this->assertSame('processing', $data->toState);
        $this->assertEquals($happenedAt, $data->happenedAt);
    }

    public function test_constructor_accepts_null_model_id(): void
    {
        $data = new StateTimelineEntryData(
            id: 'test-id-123',
            model_id: null,
            model_type: 'App\\Models\\Order',
            fsm_column: 'status',
            from_state: 'pending',
            to_state: 'processing',
            happened_at: CarbonImmutable::now()
        );

        $this->assertSame('test-id-123', $data->id);
        $this->assertNull($data->modelId);
        $this->assertSame('App\\Models\\Order', $data->modelType);
    }

    public function test_constructor_accepts_null_model_type(): void
    {
        $data = new StateTimelineEntryData(
            id: 'test-id-123',
            model_id: 'model-456',
            model_type: null,
            fsm_column: 'status',
            from_state: 'pending',
            to_state: 'processing',
            happened_at: CarbonImmutable::now()
        );

        $this->assertSame('test-id-123', $data->id);
        $this->assertSame('model-456', $data->modelId);
        $this->assertNull($data->modelType);
    }

    public function test_constructor_accepts_null_fsm_column(): void
    {
        $data = new StateTimelineEntryData(
            id: 'test-id-123',
            model_id: 'model-456',
            model_type: 'App\\Models\\Order',
            fsm_column: null,
            from_state: 'pending',
            to_state: 'processing',
            happened_at: CarbonImmutable::now()
        );

        $this->assertSame('test-id-123', $data->id);
        $this->assertSame('model-456', $data->modelId);
        $this->assertNull($data->fsmColumn);
    }

    public function test_constructor_accepts_null_from_state(): void
    {
        $data = new StateTimelineEntryData(
            id: 'test-id-123',
            model_id: 'model-456',
            model_type: 'App\\Models\\Order',
            fsm_column: 'status',
            from_state: null,
            to_state: 'processing',
            happened_at: CarbonImmutable::now()
        );

        $this->assertSame('test-id-123', $data->id);
        $this->assertSame('model-456', $data->modelId);
        $this->assertNull($data->fromState);
    }

    public function test_constructor_accepts_null_to_state(): void
    {
        $data = new StateTimelineEntryData(
            id: 'test-id-123',
            model_id: 'model-456',
            model_type: 'App\\Models\\Order',
            fsm_column: 'status',
            from_state: 'pending',
            to_state: null,
            happened_at: CarbonImmutable::now()
        );

        $this->assertSame('test-id-123', $data->id);
        $this->assertSame('model-456', $data->modelId);
        $this->assertNull($data->toState);
    }

    public function test_constructor_accepts_null_happened_at(): void
    {
        $data = new StateTimelineEntryData(
            id: 'test-id-123',
            model_id: 'model-456',
            model_type: 'App\\Models\\Order',
            fsm_column: 'status',
            from_state: 'pending',
            to_state: 'processing',
            happened_at: null
        );

        $this->assertSame('test-id-123', $data->id);
        $this->assertSame('model-456', $data->modelId);
        $this->assertNull($data->happenedAt);
    }

    public function test_constructor_with_array_and_all_required_keys(): void
    {
        $happenedAt = CarbonImmutable::parse('2024-01-01 12:00:00');

        $data = new StateTimelineEntryData([
            'id' => 'test-id-123',
            'model_id' => 'model-456',
            'model_type' => 'App\\Models\\Order',
            'fsm_column' => 'status',
            'from_state' => 'pending',
            'to_state' => 'processing',
            'happened_at' => $happenedAt,
        ]);

        $this->assertSame('test-id-123', $data->id);
        $this->assertSame('model-456', $data->modelId);
        $this->assertSame('App\\Models\\Order', $data->modelType);
        $this->assertSame('status', $data->fsmColumn);
        $this->assertSame('pending', $data->fromState);
        $this->assertSame('processing', $data->toState);
        $this->assertEquals($happenedAt, $data->happenedAt);
    }

    public function test_constructor_with_array_using_camel_case_keys(): void
    {
        $happenedAt = CarbonImmutable::parse('2024-01-01 12:00:00');

        $data = new StateTimelineEntryData([
            'id' => 'test-id-123',
            'modelId' => 'model-456',
            'modelType' => 'App\\Models\\Order',
            'fsmColumn' => 'status',
            'fromState' => 'pending',
            'toState' => 'processing',
            'happenedAt' => $happenedAt,
        ]);

        $this->assertSame('test-id-123', $data->id);
        $this->assertSame('model-456', $data->modelId);
        $this->assertSame('App\\Models\\Order', $data->modelType);
        $this->assertSame('status', $data->fsmColumn);
        $this->assertSame('pending', $data->fromState);
        $this->assertSame('processing', $data->toState);
        $this->assertEquals($happenedAt, $data->happenedAt);
    }

    public function test_constructor_with_array_throws_exception_when_missing_required_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required keys for array construction: id');

        new StateTimelineEntryData([
            'fsmColumn' => 'status',
            'fromState' => 'pending',
            'toState' => 'processing',
            'happenedAt' => CarbonImmutable::now(),
        ]);
    }

    public function test_constructor_with_array_accepts_optional_parameters(): void
    {
        $happenedAt = CarbonImmutable::parse('2024-01-01 12:00:00');

        $data = new StateTimelineEntryData([
            'id' => 'test-id-123',
            'model_id' => 'model-456',
            'model_type' => 'App\\Models\\Order',
            'fsm_column' => 'status',
            'from_state' => 'pending',
            'to_state' => 'processing',
            'happened_at' => $happenedAt,
            'transition_event' => 'process',
            'context_snapshot' => ['user_id' => 123],
            'subject_id' => 'user-789',
            'subject_type' => 'App\\Models\\User',
        ]);

        $this->assertSame('process', $data->transitionEvent);
        $this->assertSame(['user_id' => 123], $data->contextSnapshot);
        $this->assertSame('user-789', $data->subjectId);
        $this->assertSame('App\\Models\\User', $data->subjectType);
    }

    public function test_constructor_with_snake_case_array_keys_works_correctly(): void
    {
        $happenedAt = CarbonImmutable::parse('2024-01-01 12:00:00');

        $data = new StateTimelineEntryData([
            'id' => 'test-id-123',
            'model_id' => 'model-456',
            'model_type' => 'App\\Models\\Order',
            'fsm_column' => 'status',
            'from_state' => 'pending',
            'to_state' => 'processing',
            'happened_at' => $happenedAt,
            'transition_event' => 'process',
            'context_snapshot' => ['user_id' => 123],
            'exception_details' => 'Some error',
            'duration_ms' => 1500,
            'subject_id' => 'user-789',
            'subject_type' => 'App\\Models\\User',
        ]);

        $this->assertSame('test-id-123', $data->id);
        $this->assertSame('model-456', $data->modelId);
        $this->assertSame('App\\Models\\Order', $data->modelType);
        $this->assertSame('status', $data->fsmColumn);
        $this->assertSame('pending', $data->fromState);
        $this->assertSame('processing', $data->toState);
        $this->assertEquals($happenedAt, $data->happenedAt);
        $this->assertSame('process', $data->transitionEvent);
        $this->assertSame(['user_id' => 123], $data->contextSnapshot);
        $this->assertSame('Some error', $data->exceptionDetails);
        $this->assertSame(1500, $data->durationMs);
        $this->assertSame('user-789', $data->subjectId);
        $this->assertSame('App\\Models\\User', $data->subjectType);
    }

    public function test_constructor_with_snake_case_array_throws_exception_when_missing_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required keys for array construction: id');

        new StateTimelineEntryData([
            'model_id' => 'model-456',
            'model_type' => 'App\\Models\\Order',
            'fsm_column' => 'status',
            'from_state' => 'pending',
            'to_state' => 'processing',
            'happened_at' => CarbonImmutable::now(),
        ]);
    }

    public function test_constructor_with_mixed_case_array_keys_works_correctly(): void
    {
        $happenedAt = CarbonImmutable::parse('2024-01-01 12:00:00');

        $data = new StateTimelineEntryData([
            'id' => 'test-id-123',
            'modelId' => 'model-456',
            'model_type' => 'App\\Models\\Order',
            'fsmColumn' => 'status',
            'from_state' => 'pending',
            'toState' => 'processing',
            'happenedAt' => $happenedAt,
        ]);

        $this->assertSame('test-id-123', $data->id);
        $this->assertSame('model-456', $data->modelId);
        $this->assertSame('App\\Models\\Order', $data->modelType);
        $this->assertSame('status', $data->fsmColumn);
        $this->assertSame('pending', $data->fromState);
        $this->assertSame('processing', $data->toState);
        $this->assertEquals($happenedAt, $data->happenedAt);
    }

    public function test_constructor_with_array_handles_type_correctly(): void
    {
        // Test that when an array is passed, the id property gets the string value, not the entire array
        $data = new StateTimelineEntryData([
            'id' => 'test-id-123',
            'model_id' => 'model-456',
        ]);

        $this->assertSame('test-id-123', $data->id);
        $this->assertSame('model-456', $data->modelId);
        $this->assertIsString($data->id);
        $this->assertFalse(is_array($data->id));
    }
}
