<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\ReplayHistoryResponse;
use Fsm\Data\ReplayStatisticsResponse;
use Fsm\Data\ReplayTransitionsResponse;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class ResponseDtoConstructorBugFixTest extends TestCase
{
    // ===== ReplayStatisticsResponse Tests =====

    public function test_replay_statistics_response_constructor_with_boolean_success(): void
    {
        $response = new ReplayStatisticsResponse(true, ['test' => 'data'], 'Success message');

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'data'], $response->data);
        $this->assertEquals('Success message', $response->message);
        $this->assertNull($response->error);
        $this->assertNull($response->details);
    }

    public function test_replay_statistics_response_constructor_with_boolean_success_and_all_parameters(): void
    {
        $response = new ReplayStatisticsResponse(
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

    public function test_replay_statistics_response_constructor_with_associative_array_single_argument(): void
    {
        $data = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Array construction',
            'error' => null,
            'details' => ['info' => 'test'],
        ];

        $response = new ReplayStatisticsResponse($data);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Array construction', $response->message);
        $this->assertNull($response->error);
        $this->assertEquals(['info' => 'test'], $response->details);
    }

    public function test_replay_statistics_response_constructor_with_array_and_multiple_arguments_uses_array_data(): void
    {
        // Array with additional parameters should use the array data and ignore extra parameters
        $response = new ReplayStatisticsResponse(['success' => true, 'data' => ['test' => 'value']], ['ignored' => 'data']);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('', $response->message); // Default value since not in array
    }

    public function test_replay_statistics_response_constructor_with_empty_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires at least one expected key: success, data, message, error, details');

        new ReplayStatisticsResponse([]);
    }

    public function test_replay_statistics_response_constructor_with_non_associative_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires an associative array.');

        new ReplayStatisticsResponse(['value1', 'value2', 'value3']);
    }

    public function test_replay_statistics_response_constructor_with_mixed_indexed_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires at least one expected key: success, data, message, error, details');

        new ReplayStatisticsResponse([0 => 'value1', 'key' => 'value2']);
    }

    public function test_replay_statistics_response_constructor_with_callable_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction cannot use callable arrays.');

        new ReplayStatisticsResponse(['ClassName', 'method']);
    }

    public function test_replay_statistics_response_constructor_with_snake_case_keys_in_array(): void
    {
        $data = [
            'success' => false,
            'data' => ['test' => 'value'],
            'message' => 'Snake case test',
            'error' => 'Error occurred',
            'details' => ['info' => 'test'],
        ];

        $response = new ReplayStatisticsResponse($data);

        $this->assertFalse($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Snake case test', $response->message);
        $this->assertEquals('Error occurred', $response->error);
        $this->assertEquals(['info' => 'test'], $response->details);
    }

    // ===== ReplayHistoryResponse Tests =====

    public function test_replay_history_response_constructor_with_boolean_success(): void
    {
        $response = new ReplayHistoryResponse(true, ['test' => 'data'], 'Success message');

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'data'], $response->data);
        $this->assertEquals('Success message', $response->message);
        $this->assertNull($response->error);
        $this->assertNull($response->details);
    }

    public function test_replay_history_response_constructor_with_boolean_success_and_all_parameters(): void
    {
        $response = new ReplayHistoryResponse(
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

    public function test_replay_history_response_constructor_with_associative_array_single_argument(): void
    {
        $data = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Array construction',
            'error' => null,
            'details' => ['info' => 'test'],
        ];

        $response = new ReplayHistoryResponse($data);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Array construction', $response->message);
        $this->assertNull($response->error);
        $this->assertEquals(['info' => 'test'], $response->details);
    }

    public function test_replay_history_response_constructor_with_array_and_multiple_arguments_uses_array_data(): void
    {
        // Array with additional parameters should use the array data and ignore extra parameters
        $response = new ReplayHistoryResponse(['success' => true, 'data' => ['test' => 'value']], ['ignored' => 'data']);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('', $response->message); // Default value since not in array
    }

    public function test_replay_history_response_constructor_with_empty_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires at least one expected key: success, data, message, error, details');

        new ReplayHistoryResponse([]);
    }

    public function test_replay_history_response_constructor_with_non_associative_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires an associative array.');

        new ReplayHistoryResponse(['value1', 'value2', 'value3']);
    }

    public function test_replay_history_response_constructor_with_mixed_indexed_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires at least one expected key: success, data, message, error, details');

        new ReplayHistoryResponse([0 => 'value1', 'key' => 'value2']);
    }

    public function test_replay_history_response_constructor_with_callable_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction cannot use callable arrays.');

        new ReplayHistoryResponse(['ClassName', 'method']);
    }

    public function test_replay_history_response_constructor_with_snake_case_keys_in_array(): void
    {
        $data = [
            'success' => false,
            'data' => ['test' => 'value'],
            'message' => 'Snake case test',
            'error' => 'Error occurred',
            'details' => ['info' => 'test'],
        ];

        $response = new ReplayHistoryResponse($data);

        $this->assertFalse($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Snake case test', $response->message);
        $this->assertEquals('Error occurred', $response->error);
        $this->assertEquals(['info' => 'test'], $response->details);
    }

    // ===== ReplayTransitionsResponse Tests =====

    public function test_replay_transitions_response_constructor_with_boolean_success(): void
    {
        $response = new ReplayTransitionsResponse(true, ['test' => 'data'], 'Success message');

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'data'], $response->data);
        $this->assertEquals('Success message', $response->message);
        $this->assertNull($response->error);
        $this->assertNull($response->details);
    }

    public function test_replay_transitions_response_constructor_with_boolean_success_and_all_parameters(): void
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

    public function test_replay_transitions_response_constructor_with_associative_array_single_argument(): void
    {
        $data = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Array construction',
            'error' => null,
            'details' => ['info' => 'test'],
        ];

        $response = new ReplayTransitionsResponse($data);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Array construction', $response->message);
        $this->assertNull($response->error);
        $this->assertEquals(['info' => 'test'], $response->details);
    }

    public function test_replay_transitions_response_constructor_with_array_and_multiple_arguments_uses_array_data(): void
    {
        // Array with additional parameters should use the array data and ignore extra parameters
        $response = new ReplayTransitionsResponse(['success' => true, 'data' => ['test' => 'value']], ['ignored' => 'data']);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('', $response->message); // Default value since not in array
    }

    public function test_replay_transitions_response_constructor_with_empty_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires at least one expected key: success, data, message, error, details');

        new ReplayTransitionsResponse([]);
    }

    public function test_replay_transitions_response_constructor_with_non_associative_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires an associative array.');

        new ReplayTransitionsResponse(['value1', 'value2', 'value3']);
    }

    public function test_replay_transitions_response_constructor_with_mixed_indexed_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction requires at least one expected key: success, data, message, error, details');

        new ReplayTransitionsResponse([0 => 'value1', 'key' => 'value2']);
    }

    public function test_replay_transitions_response_constructor_with_callable_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction cannot use callable arrays.');

        new ReplayTransitionsResponse(['ClassName', 'method']);
    }

    public function test_replay_transitions_response_constructor_with_snake_case_keys_in_array(): void
    {
        $data = [
            'success' => false,
            'data' => ['test' => 'value'],
            'message' => 'Snake case test',
            'error' => 'Error occurred',
            'details' => ['info' => 'test'],
        ];

        $response = new ReplayTransitionsResponse($data);

        $this->assertFalse($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
        $this->assertEquals('Snake case test', $response->message);
        $this->assertEquals('Error occurred', $response->error);
        $this->assertEquals(['info' => 'test'], $response->details);
    }

    // ===== Bug Fix Verification Tests =====

    public function test_replay_history_response_handles_array_with_additional_parameters_by_using_array_data(): void
    {
        // ReplayHistoryResponse with array and additional parameters should use the array data
        $response = new ReplayHistoryResponse(['success' => true, 'data' => ['test' => 'value']], ['ignored' => 'data']);

        $this->assertTrue($response->success);
        $this->assertEquals(['test' => 'value'], $response->data);
    }

    public function test_all_response_dtos_validate_array_structure(): void
    {
        $testCases = [
            ReplayStatisticsResponse::class,
            ReplayHistoryResponse::class,
            ReplayTransitionsResponse::class,
        ];

        $invalidArrays = [
            'empty' => [],
            'non_associative' => ['value1', 'value2'],
            'mixed_keys' => [0 => 'value1', 'key' => 'value2'],
            'callable' => ['ClassName', 'method'],
        ];

        foreach ($testCases as $dtoClass) {
            foreach ($invalidArrays as $type => $array) {
                $this->expectException(InvalidArgumentException::class);
                new $dtoClass($array);
            }
        }
    }

    public function test_all_response_dtos_accept_valid_array_construction(): void
    {
        $testCases = [
            ReplayStatisticsResponse::class,
            ReplayHistoryResponse::class,
            ReplayTransitionsResponse::class,
        ];

        $validArray = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Test message',
            'error' => null,
            'details' => ['info' => 'test'],
        ];

        foreach ($testCases as $dtoClass) {
            $response = new $dtoClass($validArray);

            $this->assertTrue($response->success);
            $this->assertEquals(['test' => 'value'], $response->data);
            $this->assertEquals('Test message', $response->message);
            $this->assertNull($response->error);
            $this->assertEquals(['info' => 'test'], $response->details);
        }
    }

    public function test_all_response_dtos_accept_valid_named_parameter_construction(): void
    {
        $testCases = [
            ReplayStatisticsResponse::class,
            ReplayHistoryResponse::class,
            ReplayTransitionsResponse::class,
        ];

        foreach ($testCases as $dtoClass) {
            $response = new $dtoClass(
                success: true,
                data: ['test' => 'value'],
                message: 'Test message',
                error: null,
                details: ['info' => 'test']
            );

            $this->assertTrue($response->success);
            $this->assertEquals(['test' => 'value'], $response->data);
            $this->assertEquals('Test message', $response->message);
            $this->assertNull($response->error);
            $this->assertEquals(['info' => 'test'], $response->details);
        }
    }

    public function test_original_bug_scenario_now_works_with_array_data(): void
    {
        // Test ReplayStatisticsResponse - non-associative arrays should still throw exception
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction cannot use callable arrays.');
        new ReplayStatisticsResponse(['not', 'associative'], ['some' => 'data']);
    }

    public function test_original_bug_scenario_now_throws_clear_exception_for_replay_history(): void
    {
        // Test ReplayHistoryResponse - non-associative arrays should still throw exception
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction cannot use callable arrays.');
        new ReplayHistoryResponse(['not', 'associative'], ['some' => 'data']);
    }

    public function test_original_bug_scenario_now_throws_clear_exception_for_replay_transitions(): void
    {
        // Test ReplayTransitionsResponse - non-associative arrays should still throw exception
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based construction cannot use callable arrays.');
        new ReplayTransitionsResponse(['not', 'associative'], ['some' => 'data']);
    }
}
