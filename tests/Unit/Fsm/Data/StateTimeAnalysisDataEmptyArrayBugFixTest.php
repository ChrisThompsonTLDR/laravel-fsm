<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\StateTimeAnalysisData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the bug fix in StateTimeAnalysisData constructor
 * that incorrectly allowed empty arrays to pass validation.
 */
class StateTimeAnalysisDataEmptyArrayBugFixTest extends TestCase
{
    /**
     * Test that empty arrays are now properly rejected.
     */
    public function test_empty_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Empty arrays are not allowed for StateTimeAnalysisData initialization');

        new StateTimeAnalysisData([]);
    }

    /**
     * Test that arrays with missing required keys throw exception.
     */
    public function test_missing_required_keys_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required keys in StateTimeAnalysisData: state, totalDurationMs, occurrenceCount, averageDurationMs');

        new StateTimeAnalysisData(['someKey' => 'someValue']);
    }

    /**
     * Test that arrays with partial required keys throw exception.
     */
    public function test_partial_required_keys_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required keys in StateTimeAnalysisData: totalDurationMs, occurrenceCount, averageDurationMs');

        new StateTimeAnalysisData(['state' => 'active']);
    }

    /**
     * Test that arrays with all required keys work correctly.
     */
    public function test_valid_array_construction_works(): void
    {
        $data = new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 5,
            'averageDurationMs' => 200.0,
        ]);

        $this->assertEquals('active', $data->state);
        $this->assertEquals(1000, $data->totalDurationMs);
        $this->assertEquals(5, $data->occurrenceCount);
        $this->assertEquals(200.0, $data->averageDurationMs);
    }

    /**
     * Test that arrays with snake_case keys work correctly.
     */
    public function test_snake_case_keys_work(): void
    {
        $data = new StateTimeAnalysisData([
            'state' => 'inactive',
            'total_duration_ms' => 2000,
            'occurrence_count' => 3,
            'average_duration_ms' => 666.67,
        ]);

        $this->assertEquals('inactive', $data->state);
        $this->assertEquals(2000, $data->totalDurationMs);
        $this->assertEquals(3, $data->occurrenceCount);
        $this->assertEquals(666.67, $data->averageDurationMs);
    }

    /**
     * Test that arrays with mixed camelCase and snake_case keys work correctly.
     */
    public function test_mixed_case_keys_work(): void
    {
        $data = new StateTimeAnalysisData([
            'state' => 'pending',
            'totalDurationMs' => 1500,
            'occurrence_count' => 2,
            'average_duration_ms' => 750.0,
        ]);

        $this->assertEquals('pending', $data->state);
        $this->assertEquals(1500, $data->totalDurationMs);
        $this->assertEquals(2, $data->occurrenceCount);
        $this->assertEquals(750.0, $data->averageDurationMs);
    }

    /**
     * Test that optional keys work correctly.
     */
    public function test_optional_keys_work(): void
    {
        $data = new StateTimeAnalysisData([
            'state' => 'processing',
            'totalDurationMs' => 3000,
            'occurrenceCount' => 1,
            'averageDurationMs' => 3000.0,
            'minDurationMs' => 2500,
            'maxDurationMs' => 3500,
        ]);

        $this->assertEquals('processing', $data->state);
        $this->assertEquals(3000, $data->totalDurationMs);
        $this->assertEquals(1, $data->occurrenceCount);
        $this->assertEquals(3000.0, $data->averageDurationMs);
        $this->assertEquals(2500, $data->minDurationMs);
        $this->assertEquals(3500, $data->maxDurationMs);
    }

    /**
     * Test that optional keys with snake_case work correctly.
     */
    public function test_optional_keys_snake_case_work(): void
    {
        $data = new StateTimeAnalysisData([
            'state' => 'completed',
            'totalDurationMs' => 5000,
            'occurrenceCount' => 2,
            'averageDurationMs' => 2500.0,
            'min_duration_ms' => 2000,
            'max_duration_ms' => 3000,
        ]);

        $this->assertEquals('completed', $data->state);
        $this->assertEquals(5000, $data->totalDurationMs);
        $this->assertEquals(2, $data->occurrenceCount);
        $this->assertEquals(2500.0, $data->averageDurationMs);
        $this->assertEquals(2000, $data->minDurationMs);
        $this->assertEquals(3000, $data->maxDurationMs);
    }

    /**
     * Test that positional parameters still work correctly.
     */
    public function test_positional_parameters_work(): void
    {
        $data = new StateTimeAnalysisData(
            'active',
            1000,
            5,
            200.0,
            100,
            300
        );

        $this->assertEquals('active', $data->state);
        $this->assertEquals(1000, $data->totalDurationMs);
        $this->assertEquals(5, $data->occurrenceCount);
        $this->assertEquals(200.0, $data->averageDurationMs);
        $this->assertEquals(100, $data->minDurationMs);
        $this->assertEquals(300, $data->maxDurationMs);
    }

    /**
     * Test that positional parameters with defaults work correctly.
     */
    public function test_positional_parameters_with_defaults_work(): void
    {
        $data = new StateTimeAnalysisData('inactive');

        $this->assertEquals('inactive', $data->state);
        $this->assertEquals(0, $data->totalDurationMs);
        $this->assertEquals(0, $data->occurrenceCount);
        $this->assertEquals(0.0, $data->averageDurationMs);
        $this->assertNull($data->minDurationMs);
        $this->assertNull($data->maxDurationMs);
    }

    /**
     * Test that non-associative arrays are rejected.
     */
    public function test_non_associative_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based initialization requires an associative array.');

        new StateTimeAnalysisData(['value1', 'value2', 'value3']);
    }

    /**
     * Test that invalid state type throws exception.
     */
    public function test_invalid_state_type_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "state" value must be a string, got: int');

        new StateTimeAnalysisData([
            'state' => 123,
            'totalDurationMs' => 1000,
            'occurrenceCount' => 5,
            'averageDurationMs' => 200.0,
        ]);
    }

    /**
     * Test that invalid totalDurationMs type throws exception.
     */
    public function test_invalid_total_duration_ms_type_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "totalDurationMs" value must be an integer, got: string');

        new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 'not_a_number',
            'occurrenceCount' => 5,
            'averageDurationMs' => 200.0,
        ]);
    }

    /**
     * Test that invalid occurrenceCount type throws exception.
     */
    public function test_invalid_occurrence_count_type_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "occurrenceCount" value must be an integer, got: float');

        new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 5.5,
            'averageDurationMs' => 200.0,
        ]);
    }

    /**
     * Test that invalid averageDurationMs type throws exception.
     */
    public function test_invalid_average_duration_ms_type_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "averageDurationMs" value must be a float or integer, got: string');

        new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 5,
            'averageDurationMs' => 'not_a_number',
        ]);
    }

    /**
     * Test that invalid minDurationMs type throws exception.
     */
    public function test_invalid_min_duration_ms_type_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "minDurationMs" value must be an integer or null, got: string');

        new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 5,
            'averageDurationMs' => 200.0,
            'minDurationMs' => 'not_a_number',
        ]);
    }

    /**
     * Test that invalid maxDurationMs type throws exception.
     */
    public function test_invalid_max_duration_ms_type_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "maxDurationMs" value must be an integer or null, got: string');

        new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 5,
            'averageDurationMs' => 200.0,
            'maxDurationMs' => 'not_a_number',
        ]);
    }

    /**
     * Test that null values for optional keys work correctly.
     */
    public function test_null_optional_values_work(): void
    {
        $data = new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 5,
            'averageDurationMs' => 200.0,
            'minDurationMs' => null,
            'maxDurationMs' => null,
        ]);

        $this->assertEquals('active', $data->state);
        $this->assertEquals(1000, $data->totalDurationMs);
        $this->assertEquals(5, $data->occurrenceCount);
        $this->assertEquals(200.0, $data->averageDurationMs);
        $this->assertNull($data->minDurationMs);
        $this->assertNull($data->maxDurationMs);
    }

    /**
     * Test that arrays with extra keys work correctly (extra keys should be ignored).
     */
    public function test_extra_keys_are_ignored(): void
    {
        $data = new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 5,
            'averageDurationMs' => 200.0,
            'extraKey1' => 'extraValue1',
            'extraKey2' => 'extraValue2',
        ]);

        $this->assertEquals('active', $data->state);
        $this->assertEquals(1000, $data->totalDurationMs);
        $this->assertEquals(5, $data->occurrenceCount);
        $this->assertEquals(200.0, $data->averageDurationMs);
    }
}
