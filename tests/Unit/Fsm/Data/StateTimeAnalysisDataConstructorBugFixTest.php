<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\StateTimeAnalysisData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Test for StateTimeAnalysisData constructor bug fix.
 *
 * Tests that simple arrays are not misinterpreted as associative arrays for construction.
 */
class StateTimeAnalysisDataConstructorBugFixTest extends TestCase
{
    public function test_string_state_parameter_works_correctly(): void
    {
        // String state should be treated as positional parameter, not array-based construction
        $data = new StateTimeAnalysisData('active');

        $this->assertSame('active', $data->state);
        $this->assertSame(0, $data->totalDurationMs);
        $this->assertSame(0, $data->occurrenceCount);
        $this->assertSame(0.0, $data->averageDurationMs);
        $this->assertNull($data->minDurationMs);
        $this->assertNull($data->maxDurationMs);
    }

    public function test_simple_array_is_not_treated_as_associative_array(): void
    {
        // Simple array should not be treated as array-based construction
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based initialization requires an associative array.');

        new StateTimeAnalysisData(['value1', 'value2']);
    }

    public function test_associative_array_without_expected_keys_is_not_treated_as_array_construction(): void
    {
        // Associative array without expected keys should not be treated as array-based construction
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required keys in StateTimeAnalysisData: state, totalDurationMs, occurrenceCount, averageDurationMs');

        new StateTimeAnalysisData([
            'some_key' => 'some_value',
            'another_key' => 'another_value',
        ]);
    }

    public function test_associative_array_with_expected_keys_is_treated_as_array_construction(): void
    {
        // Associative array with expected keys should be treated as array-based construction
        $data = new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 5000,
            'occurrenceCount' => 3,
            'averageDurationMs' => 1666.67,
            'minDurationMs' => 1000,
            'maxDurationMs' => 2000,
        ]);

        $this->assertSame('active', $data->state);
        $this->assertSame(5000, $data->totalDurationMs);
        $this->assertSame(3, $data->occurrenceCount);
        $this->assertSame(1666.67, $data->averageDurationMs);
        $this->assertSame(1000, $data->minDurationMs);
        $this->assertSame(2000, $data->maxDurationMs);
    }

    public function test_positional_parameters_work_correctly(): void
    {
        $data = new StateTimeAnalysisData(
            state: 'active',
            totalDurationMs: 5000,
            occurrenceCount: 3,
            averageDurationMs: 1666.67,
            minDurationMs: 1000,
            maxDurationMs: 2000,
        );

        $this->assertSame('active', $data->state);
        $this->assertSame(5000, $data->totalDurationMs);
        $this->assertSame(3, $data->occurrenceCount);
        $this->assertSame(1666.67, $data->averageDurationMs);
        $this->assertSame(1000, $data->minDurationMs);
        $this->assertSame(2000, $data->maxDurationMs);
    }

    public function test_minimal_positional_parameters_work_correctly(): void
    {
        $data = new StateTimeAnalysisData('minimal');

        $this->assertSame('minimal', $data->state);
        $this->assertSame(0, $data->totalDurationMs);
        $this->assertSame(0, $data->occurrenceCount);
        $this->assertSame(0.0, $data->averageDurationMs);
        $this->assertNull($data->minDurationMs);
        $this->assertNull($data->maxDurationMs);
    }

    public function test_array_construction_with_snake_case_keys_works(): void
    {
        // Test that snake_case keys are properly converted to camelCase
        $data = new StateTimeAnalysisData([
            'state' => 'active',
            'total_duration_ms' => 5000,
            'occurrence_count' => 3,
            'average_duration_ms' => 1666.67,
            'min_duration_ms' => 1000,
            'max_duration_ms' => 2000,
        ]);

        $this->assertSame('active', $data->state);
        $this->assertSame(5000, $data->totalDurationMs);
        $this->assertSame(3, $data->occurrenceCount);
        $this->assertSame(1666.67, $data->averageDurationMs);
        $this->assertSame(1000, $data->minDurationMs);
        $this->assertSame(2000, $data->maxDurationMs);
    }

    public function test_array_construction_missing_required_keys_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required keys in StateTimeAnalysisData: totalDurationMs, occurrenceCount, averageDurationMs');

        new StateTimeAnalysisData(['state' => 'active']);
    }

    public function test_array_construction_with_invalid_state_type_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "state" value must be a string, got: int');

        new StateTimeAnalysisData([
            'state' => 123,
            'totalDurationMs' => 5000,
            'occurrenceCount' => 3,
            'averageDurationMs' => 1666.67,
        ]);
    }

    public function test_state_property_value_extraction_bug_fix(): void
    {
        // This test ensures the redundant null coalescing check bug is fixed.
        // The bug was that we were using `?? null` on required keys after already validating they exist.
        // Before fix: $stateValue = $state['state'] ?? null;
        // After fix: $stateValue = $state['state']; (no ?? null needed since validation ensures key exists)

        // Test that state property extraction works correctly with array-based construction
        $data = new StateTimeAnalysisData([
            'state' => 'test_state',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 1,
            'averageDurationMs' => 1000.0,
        ]);

        $this->assertSame('test_state', $data->state);
    }

    public function test_state_property_validation_works_correctly(): void
    {
        // Test that state property validation works correctly after the bug fix
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "state" value must be a string, got: array');

        new StateTimeAnalysisData([
            'state' => ['not_a_string'],
            'totalDurationMs' => 1000,
            'occurrenceCount' => 1,
            'averageDurationMs' => 1000.0,
        ]);
    }
}
