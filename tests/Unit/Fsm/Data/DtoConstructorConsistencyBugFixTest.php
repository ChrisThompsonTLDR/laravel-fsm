<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\ReplayHistoryResponse;
use Fsm\Data\ReplayStatisticsResponse;
use Fsm\Data\ValidateHistoryResponse;
use PHPUnit\Framework\TestCase;

/**
 * Test class to verify the fix for inconsistent parameter handling in DTO constructors.
 *
 * This test ensures that DTO constructors handle array parameters consistently,
 * regardless of whether additional parameters are provided or not.
 */
class DtoConstructorConsistencyBugFixTest extends TestCase
{
    /**
     * Test that ReplayStatisticsResponse handles array parameters consistently.
     */
    public function test_replay_statistics_response_array_consistency(): void
    {
        $arrayData = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Test message',
            'error' => null,
            'details' => ['extra' => 'info'],
        ];

        // Test array-only construction (should work)
        $dto1 = new ReplayStatisticsResponse($arrayData);
        $this->assertTrue($dto1->success);
        $this->assertEquals(['test' => 'value'], $dto1->data);
        $this->assertEquals('Test message', $dto1->message);

        // Test array with additional parameters (should now work consistently)
        $dto2 = new ReplayStatisticsResponse($arrayData, ['other' => 'data'], 'Other message');
        $this->assertTrue($dto2->success);
        $this->assertEquals(['test' => 'value'], $dto2->data);
        $this->assertEquals('Test message', $dto2->message);

        // Test positional parameters (should work as before)
        $dto3 = new ReplayStatisticsResponse(true, ['test' => 'value'], 'Test message');
        $this->assertTrue($dto3->success);
        $this->assertEquals(['test' => 'value'], $dto3->data);
        $this->assertEquals('Test message', $dto3->message);
    }

    /**
     * Test that ValidateHistoryResponse handles array parameters consistently.
     */
    public function test_validate_history_response_array_consistency(): void
    {
        $arrayData = [
            'success' => false,
            'data' => ['errors' => ['validation failed']],
            'message' => 'Validation failed',
            'error' => 'Invalid data',
            'details' => ['field' => 'value'],
        ];

        // Test array-only construction (should work)
        $dto1 = new ValidateHistoryResponse($arrayData);
        $this->assertFalse($dto1->success);
        $this->assertEquals(['errors' => ['validation failed']], $dto1->data);
        $this->assertEquals('Validation failed', $dto1->message);
        $this->assertEquals('Invalid data', $dto1->error);

        // Test array with additional parameters (should now work consistently)
        $dto2 = new ValidateHistoryResponse($arrayData, ['other' => 'data'], 'Other message');
        $this->assertFalse($dto2->success);
        $this->assertEquals(['errors' => ['validation failed']], $dto2->data);
        $this->assertEquals('Validation failed', $dto2->message);
        $this->assertEquals('Invalid data', $dto2->error);

        // Test positional parameters (should work as before)
        $dto3 = new ValidateHistoryResponse(false, ['errors' => ['validation failed']], 'Validation failed');
        $this->assertFalse($dto3->success);
        $this->assertEquals(['errors' => ['validation failed']], $dto3->data);
        $this->assertEquals('Validation failed', $dto3->message);
    }

    /**
     * Test that ReplayHistoryResponse handles array parameters consistently.
     */
    public function test_replay_history_response_array_consistency(): void
    {
        $arrayData = [
            'success' => true,
            'data' => ['transitions' => [['from' => 'A', 'to' => 'B']]],
            'message' => 'History retrieved',
            'error' => null,
            'details' => ['count' => 1],
        ];

        // Test array-only construction (should work)
        $dto1 = new ReplayHistoryResponse($arrayData);
        $this->assertTrue($dto1->success);
        $this->assertEquals(['transitions' => [['from' => 'A', 'to' => 'B']]], $dto1->data);
        $this->assertEquals('History retrieved', $dto1->message);

        // Test array with additional parameters (should now work consistently)
        $dto2 = new ReplayHistoryResponse($arrayData, ['other' => 'data'], 'Other message');
        $this->assertTrue($dto2->success);
        $this->assertEquals(['transitions' => [['from' => 'A', 'to' => 'B']]], $dto2->data);
        $this->assertEquals('History retrieved', $dto2->message);

        // Test positional parameters (should work as before)
        $dto3 = new ReplayHistoryResponse(true, ['transitions' => [['from' => 'A', 'to' => 'B']]], 'History retrieved');
        $this->assertTrue($dto3->success);
        $this->assertEquals(['transitions' => [['from' => 'A', 'to' => 'B']]], $dto3->data);
        $this->assertEquals('History retrieved', $dto3->message);
    }

    /**
     * Test that array validation still works correctly.
     *
     * These tests verify that the Dto base class properly validates arrays
     * before allowing array-based construction. The validation ensures:
     * 1. Arrays are non-empty
     * 2. Arrays are not callable arrays (e.g., ['Class', 'method'])
     * 3. Arrays are associative (not sequential numeric arrays)
     * 4. Arrays have at least one string key (not just numeric keys)
     */
    public function test_array_validation_still_works(): void
    {
        // Test 1: Empty array (should throw exception)
        $this->expectExceptionForInvalidArray(
            [],
            'Array-based construction requires at least one expected key: success, data, message, error, details',
            'empty array'
        );

        // Test 2: Non-associative array with 2 elements (detected as callable array)
        $this->expectExceptionForInvalidArray(
            ['value1', 'value2'],
            'Array-based construction cannot use callable arrays.',
            'array that looks like callable'
        );

        // Test 3: Non-associative array with 3+ elements (should throw exception)
        $this->expectExceptionForInvalidArray(
            ['value1', 'value2', 'value3'],
            'Array-based construction requires an associative array.',
            'non-associative array'
        );

        // Test 4: Callable array with class name and method (should throw exception)
        $this->expectExceptionForInvalidArray(
            ['ClassName', 'method'],
            'Array-based construction cannot use callable arrays.',
            'callable array'
        );

        // Test 5: Array with only numeric keys (2 elements - detected as callable array)
        $this->expectExceptionForInvalidArray(
            [0 => 'value', 1 => 'value2'],
            'Array-based construction cannot use callable arrays.',
            'array with only numeric keys (2 elements)'
        );

        // Test 6: Array with only numeric keys (3+ elements, sequential - not associative)
        $this->expectExceptionForInvalidArray(
            [0 => 'value', 1 => 'value2', 2 => 'value3'],
            'Array-based construction requires an associative array.',
            'array with only numeric keys (3+ elements)'
        );

        // Test 7: Array with non-sequential numeric keys (associative, but no string keys)
        $this->expectExceptionForInvalidArray(
            [0 => 'value', 2 => 'value2', 5 => 'value3'],
            'Array-based construction requires an array with at least one string key.',
            'array without string keys'
        );

        // Test 8: Mixed keys array with at least one string key (should work)
        $dto = new ReplayStatisticsResponse([
            'success' => true,
            0 => 'ignored',
            'data' => ['test'],
            'message' => 'Success',
        ]);
        $this->assertTrue($dto->success);
        $this->assertEquals(['test'], $dto->data);
        $this->assertEquals('Success', $dto->message);
    }

    /**
     * Helper method to test that invalid arrays throw the expected exception.
     *
     * @param  array<mixed>  $invalidArray
     */
    private function expectExceptionForInvalidArray(array $invalidArray, string $expectedMessage, string $description): void
    {
        try {
            new ReplayStatisticsResponse($invalidArray);
            $this->fail("Expected InvalidArgumentException for {$description}");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals(
                $expectedMessage,
                $e->getMessage(),
                "Exception message mismatch for {$description}"
            );
        }
    }

    /**
     * Test that the fix prevents the original bug where arrays with additional parameters threw exceptions.
     */
    public function test_original_bug_is_fixed(): void
    {
        $arrayData = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Test message',
        ];

        // This should NOT throw an exception anymore (the original bug)
        $dto = new ReplayStatisticsResponse($arrayData, ['ignored' => 'data'], 'Ignored message');

        // The array data should be used, not the additional parameters
        $this->assertTrue($dto->success);
        $this->assertEquals(['test' => 'value'], $dto->data);
        $this->assertEquals('Test message', $dto->message);
    }

    /**
     * Test that snake_case keys are properly normalized to camelCase.
     */
    public function test_snake_case_key_normalization(): void
    {
        $arrayData = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Test message',
            'error' => null,
            'details' => ['extra' => 'info'],
        ];

        $dto = new ReplayStatisticsResponse($arrayData, ['ignored' => 'data']);

        $this->assertTrue($dto->success);
        $this->assertEquals(['test' => 'value'], $dto->data);
        $this->assertEquals('Test message', $dto->message);
    }

    /**
     * Test that all three response DTOs behave consistently.
     */
    public function test_all_response_dtos_behave_consistently(): void
    {
        $arrayData = [
            'success' => true,
            'data' => ['test' => 'value'],
            'message' => 'Test message',
        ];

        // All three should handle arrays with additional parameters the same way
        $dto1 = new ReplayStatisticsResponse($arrayData, ['ignored' => 'data']);
        $dto2 = new ValidateHistoryResponse($arrayData, ['ignored' => 'data']);
        $dto3 = new ReplayHistoryResponse($arrayData, ['ignored' => 'data']);

        // All should use the array data, not the additional parameters
        $this->assertTrue($dto1->success);
        $this->assertTrue($dto2->success);
        $this->assertTrue($dto3->success);

        $this->assertEquals(['test' => 'value'], $dto1->data);
        $this->assertEquals(['test' => 'value'], $dto2->data);
        $this->assertEquals(['test' => 'value'], $dto3->data);

        $this->assertEquals('Test message', $dto1->message);
        $this->assertEquals('Test message', $dto2->message);
        $this->assertEquals('Test message', $dto3->message);
    }

    /**
     * Test that all response DTOs throw consistent validation error messages.
     *
     * This ensures that all DTOs using the base Dto class validation
     * provide the same error messages for invalid array inputs.
     */
    public function test_all_response_dtos_throw_consistent_validation_errors(): void
    {
        $invalidArrays = [
            'empty' => [
                'array' => [],
                'message' => 'Array-based construction requires at least one expected key: success, data, message, error, details',
            ],
            'callable' => [
                'array' => ['ClassName', 'method'],
                'message' => 'Array-based construction cannot use callable arrays.',
            ],
            'non-associative' => [
                'array' => ['value1', 'value2', 'value3'],
                'message' => 'Array-based construction requires an associative array.',
            ],
            'no-string-keys' => [
                'array' => [0 => 'value', 2 => 'value2', 5 => 'value3'],
                'message' => 'Array-based construction requires an array with at least one string key.',
            ],
        ];

        $dtoClasses = [
            'ReplayStatisticsResponse' => ReplayStatisticsResponse::class,
            'ValidateHistoryResponse' => ValidateHistoryResponse::class,
            'ReplayHistoryResponse' => ReplayHistoryResponse::class,
        ];

        foreach ($dtoClasses as $dtoName => $dtoClass) {
            foreach ($invalidArrays as $testName => $testData) {
                try {
                    new $dtoClass($testData['array']);
                    $this->fail("Expected {$dtoName} to throw InvalidArgumentException for {$testName} array");
                } catch (\InvalidArgumentException $e) {
                    $this->assertEquals(
                        $testData['message'],
                        $e->getMessage(),
                        "Error message mismatch for {$dtoName} with {$testName} array"
                    );
                }
            }
        }
    }
}
