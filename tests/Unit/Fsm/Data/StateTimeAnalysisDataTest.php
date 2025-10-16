<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\StateTimeAnalysisData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class StateTimeAnalysisDataTest extends TestCase
{
    public function test_constructor_with_valid_array_data(): void
    {
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

    public function test_constructor_with_minimal_array_data(): void
    {
        $data = new StateTimeAnalysisData([
            'state' => 'pending',
            'totalDurationMs' => 0,
            'occurrenceCount' => 0,
            'averageDurationMs' => 0.0,
        ]);

        $this->assertSame('pending', $data->state);
        $this->assertSame(0, $data->totalDurationMs);
        $this->assertSame(0, $data->occurrenceCount);
        $this->assertSame(0.0, $data->averageDurationMs);
        $this->assertNull($data->minDurationMs);
        $this->assertNull($data->maxDurationMs);
    }

    public function test_constructor_with_named_parameters(): void
    {
        $data = new StateTimeAnalysisData(
            state: 'completed',
            totalDurationMs: 10000,
            occurrenceCount: 5,
            averageDurationMs: 2000.0,
            minDurationMs: 1500,
            maxDurationMs: 3000
        );

        $this->assertSame('completed', $data->state);
        $this->assertSame(10000, $data->totalDurationMs);
        $this->assertSame(5, $data->occurrenceCount);
        $this->assertSame(2000.0, $data->averageDurationMs);
        $this->assertSame(1500, $data->minDurationMs);
        $this->assertSame(3000, $data->maxDurationMs);
    }

    public function test_constructor_with_positional_parameters(): void
    {
        $data = new StateTimeAnalysisData(
            'processing',
            7500,
            2,
            3750.0,
            3000,
            4500
        );

        $this->assertSame('processing', $data->state);
        $this->assertSame(7500, $data->totalDurationMs);
        $this->assertSame(2, $data->occurrenceCount);
        $this->assertSame(3750.0, $data->averageDurationMs);
        $this->assertSame(3000, $data->minDurationMs);
        $this->assertSame(4500, $data->maxDurationMs);
    }

    public function test_constructor_throws_exception_when_missing_required_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required keys in StateTimeAnalysisData: state, totalDurationMs');

        new StateTimeAnalysisData([
            'occurrenceCount' => 1,
            'averageDurationMs' => 1000.0,
        ]);
    }

    public function test_constructor_throws_exception_when_state_is_not_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "state" value must be a string, got: int');

        new StateTimeAnalysisData([
            'state' => 123,
            'totalDurationMs' => 1000,
            'occurrenceCount' => 1,
            'averageDurationMs' => 1000.0,
        ]);
    }

    public function test_constructor_throws_exception_when_total_duration_ms_is_not_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "totalDurationMs" value must be an integer, got: string');

        new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => '1000',
            'occurrenceCount' => 1,
            'averageDurationMs' => 1000.0,
        ]);
    }

    public function test_constructor_throws_exception_when_occurrence_count_is_not_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "occurrenceCount" value must be an integer, got: float');

        new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 1.5,
            'averageDurationMs' => 1000.0,
        ]);
    }

    public function test_constructor_throws_exception_when_average_duration_ms_is_not_numeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "averageDurationMs" value must be a float or integer, got: string');

        new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 1,
            'averageDurationMs' => 'not_a_number',
        ]);
    }

    public function test_constructor_accepts_integer_average_duration_ms(): void
    {
        $data = new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 1,
            'averageDurationMs' => 1000, // integer instead of float
        ]);

        $this->assertSame(1000.0, $data->averageDurationMs); // DTO converts to float
    }

    public function test_constructor_throws_exception_when_min_duration_ms_is_not_integer_or_null(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "minDurationMs" value must be an integer or null, got: string');

        new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 1,
            'averageDurationMs' => 1000.0,
            'minDurationMs' => 'not_an_integer',
        ]);
    }

    public function test_constructor_accepts_null_min_duration_ms(): void
    {
        $data = new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 1,
            'averageDurationMs' => 1000.0,
            'minDurationMs' => null,
        ]);

        $this->assertNull($data->minDurationMs);
    }

    public function test_constructor_throws_exception_when_max_duration_ms_is_not_integer_or_null(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "maxDurationMs" value must be an integer or null, got: array');

        new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 1,
            'averageDurationMs' => 1000.0,
            'maxDurationMs' => ['not_an_integer'],
        ]);
    }

    public function test_constructor_accepts_null_max_duration_ms(): void
    {
        $data = new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 1,
            'averageDurationMs' => 1000.0,
            'maxDurationMs' => null,
        ]);

        $this->assertNull($data->maxDurationMs);
    }

    public function test_constructor_throws_exception_when_non_associative_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based initialization requires an associative array.');

        new StateTimeAnalysisData(['active', 1000, 1, 1000.0]);
    }

    public function test_constructor_throws_exception_when_empty_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Empty arrays are not allowed for StateTimeAnalysisData initialization');

        new StateTimeAnalysisData([]);
    }

    public function test_constructor_with_extra_keys_ignores_them(): void
    {
        $data = new StateTimeAnalysisData([
            'state' => 'active',
            'totalDurationMs' => 1000,
            'occurrenceCount' => 1,
            'averageDurationMs' => 1000.0,
            'extraKey' => 'ignored',
            'anotherExtra' => 123,
        ]);

        $this->assertSame('active', $data->state);
        $this->assertSame(1000, $data->totalDurationMs);
        $this->assertSame(1, $data->occurrenceCount);
        $this->assertSame(1000.0, $data->averageDurationMs);
    }

    public function test_constructor_type_validation_error_messages_are_descriptive(): void
    {
        $testCases = [
            [
                'data' => ['state' => 123, 'totalDurationMs' => 1000, 'occurrenceCount' => 1, 'averageDurationMs' => 1000.0],
                'expectedMessage' => 'The "state" value must be a string, got: int',
            ],
            [
                'data' => ['state' => 'active', 'totalDurationMs' => '1000', 'occurrenceCount' => 1, 'averageDurationMs' => 1000.0],
                'expectedMessage' => 'The "totalDurationMs" value must be an integer, got: string',
            ],
            [
                'data' => ['state' => 'active', 'totalDurationMs' => 1000, 'occurrenceCount' => 1.5, 'averageDurationMs' => 1000.0],
                'expectedMessage' => 'The "occurrenceCount" value must be an integer, got: float',
            ],
            [
                'data' => ['state' => 'active', 'totalDurationMs' => 1000, 'occurrenceCount' => 1, 'averageDurationMs' => 'not_a_number'],
                'expectedMessage' => 'The "averageDurationMs" value must be a float or integer, got: string',
            ],
            [
                'data' => ['state' => 'active', 'totalDurationMs' => 1000, 'occurrenceCount' => 1, 'averageDurationMs' => 1000.0, 'minDurationMs' => 'invalid'],
                'expectedMessage' => 'The "minDurationMs" value must be an integer or null, got: string',
            ],
            [
                'data' => ['state' => 'active', 'totalDurationMs' => 1000, 'occurrenceCount' => 1, 'averageDurationMs' => 1000.0, 'maxDurationMs' => 'invalid'],
                'expectedMessage' => 'The "maxDurationMs" value must be an integer or null, got: string',
            ],
        ];

        foreach ($testCases as $testCase) {
            try {
                new StateTimeAnalysisData($testCase['data']);
                $this->fail('Expected exception was not thrown for data: '.json_encode($testCase['data']));
            } catch (InvalidArgumentException $e) {
                $this->assertSame($testCase['expectedMessage'], $e->getMessage());
            }
        }
    }
}
