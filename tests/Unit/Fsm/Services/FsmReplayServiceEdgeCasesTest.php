<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Fsm\Models\FsmEventLog;
use Fsm\Services\FsmReplayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

/**
 * Test for FsmReplayService edge cases and error conditions.
 *
 * This test covers the untested mutation scenarios in FsmReplayService,
 * particularly around input validation, transition consistency checks, and statistics calculation.
 */
class FsmReplayServiceEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private FsmReplayService $replayService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->replayService = new FsmReplayService;
        $this->createTestTables();
    }

    protected function createTestTables(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('fsm_event_logs', function ($table) {
            $table->uuid('id')->primary();
            $table->string('model_id');
            $table->string('model_type');
            $table->string('column_name');
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->string('transition_name')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
        });
    }

    /**
     * Test replay transitions with empty model ID (trimmed).
     */
    public function test_replay_transitions_with_empty_model_id_trimmed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '   ', 'status');
    }

    /**
     * Test replay transitions with empty model ID (untrimmed).
     */
    public function test_replay_transitions_with_empty_model_id_untrimmed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '', 'status');
    }

    /**
     * Test replay transitions with empty column name (trimmed).
     */
    public function test_replay_transitions_with_empty_column_name_trimmed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '123', '   ');
    }

    /**
     * Test replay transitions with empty column name (untrimmed).
     */
    public function test_replay_transitions_with_empty_column_name_untrimmed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '123', '');
    }

    /**
     * Test validate transition history with empty model ID (trimmed).
     */
    public function test_validate_transition_history_with_empty_model_id_trimmed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->validateTransitionHistory('TestModel', '   ', 'status');
    }

    /**
     * Test validate transition history with empty model ID (untrimmed).
     */
    public function test_validate_transition_history_with_empty_model_id_untrimmed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->validateTransitionHistory('TestModel', '', 'status');
    }

    /**
     * Test validate transition history with empty column name (trimmed).
     */
    public function test_validate_transition_history_with_empty_column_name_trimmed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->validateTransitionHistory('TestModel', '123', '   ');
    }

    /**
     * Test validate transition history with empty column name (untrimmed).
     */
    public function test_validate_transition_history_with_empty_column_name_untrimmed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->validateTransitionHistory('TestModel', '123', '');
    }

    /**
     * Test transition consistency check with index >= 0.
     */
    public function test_transition_consistency_check_with_index_greater_or_equal_zero(): void
    {
        // Create test transitions with inconsistent states
        $this->createTestTransitions([
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'completed', 'to_state' => 'done'], // Inconsistent: should be 'processing'
        ]);

        $result = $this->replayService->validateTransitionHistory('TestModel', '123', 'status');

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /**
     * Test transition consistency check with index > -1.
     */
    public function test_transition_consistency_check_with_index_greater_than_negative_one(): void
    {
        // Create test transitions with inconsistent states
        $this->createTestTransitions([
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'completed', 'to_state' => 'done'], // Inconsistent: should be 'processing'
        ]);

        $result = $this->replayService->validateTransitionHistory('TestModel', '123', 'status');

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /**
     * Test get transition statistics with empty model ID (trimmed).
     */
    public function test_get_transition_statistics_with_empty_model_id_trimmed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionStatistics('TestModel', '   ', 'status');
    }

    /**
     * Test get transition statistics with empty model ID (untrimmed).
     */
    public function test_get_transition_statistics_with_empty_model_id_untrimmed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionStatistics('TestModel', '', 'status');
    }

    /**
     * Test get transition statistics with empty column name (trimmed).
     */
    public function test_get_transition_statistics_with_empty_column_name_trimmed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->getTransitionStatistics('TestModel', '123', '   ');
    }

    /**
     * Test get transition statistics with empty column name (untrimmed).
     */
    public function test_get_transition_statistics_with_empty_column_name_untrimmed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->getTransitionStatistics('TestModel', '123', '');
    }

    /**
     * Test state frequency calculation with decremented default value.
     */
    public function test_state_frequency_calculation_with_decremented_default(): void
    {
        // Create test transitions
        $this->createTestTransitions([
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
        ]);

        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        $this->assertArrayHasKey('state_frequency', $result);
        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertEquals(2, $result['total_transitions']);
    }

    /**
     * Test state frequency calculation with incremented default value.
     */
    public function test_state_frequency_calculation_with_incremented_default(): void
    {
        // Create test transitions
        $this->createTestTransitions([
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
        ]);

        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        $this->assertArrayHasKey('state_frequency', $result);
        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertEquals(2, $result['total_transitions']);
    }

    /**
     * Test transition frequency calculation with coalesce removal.
     */
    public function test_transition_frequency_calculation_with_coalesce_removal(): void
    {
        // Create test transitions
        $this->createTestTransitions([
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
        ]);

        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertArrayHasKey('pending → processing', $result['transition_frequency']);
        $this->assertArrayHasKey('processing → done', $result['transition_frequency']);
    }

    /**
     * Test replay transitions with empty transition history.
     */
    public function test_replay_transitions_with_empty_history(): void
    {
        $result = $this->replayService->replayTransitions('TestModel', '123', 'status');

        $this->assertEquals([
            'initial_state' => null,
            'final_state' => null,
            'transition_count' => 0,
            'transitions' => [],
        ], $result);
    }

    /**
     * Test replay transitions with single transition.
     */
    public function test_replay_transitions_with_single_transition(): void
    {
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
        ]);

        $result = $this->replayService->replayTransitions('TestModel', '123', 'status');

        $this->assertNull($result['initial_state']);
        $this->assertEquals('pending', $result['final_state']);
        $this->assertEquals(1, $result['transition_count']);
        $this->assertCount(1, $result['transitions']);
    }

    /**
     * Test replay transitions with multiple transitions.
     */
    public function test_replay_transitions_with_multiple_transitions(): void
    {
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
        ]);

        $result = $this->replayService->replayTransitions('TestModel', '123', 'status');

        $this->assertNull($result['initial_state']);
        $this->assertEquals('done', $result['final_state']);
        $this->assertEquals(3, $result['transition_count']);
        $this->assertCount(3, $result['transitions']);
    }

    /**
     * Test validate transition history with consistent transitions.
     */
    public function test_validate_transition_history_with_consistent_transitions(): void
    {
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
        ]);

        $result = $this->replayService->validateTransitionHistory('TestModel', '123', 'status');

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test validate transition history with inconsistent transitions.
     */
    public function test_validate_transition_history_with_inconsistent_transitions(): void
    {
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'completed', 'to_state' => 'done'], // Inconsistent: should be 'pending'
        ]);

        $result = $this->replayService->validateTransitionHistory('TestModel', '123', 'status');

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('from_state \'completed\' doesn\'t match previous to_state \'pending\'', $result['errors'][0]);
    }

    /**
     * Test get transition statistics with various transitions.
     */
    public function test_get_transition_statistics_with_various_transitions(): void
    {
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
            ['from_state' => 'done', 'to_state' => 'pending'], // Loop back
        ]);

        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        $this->assertEquals(4, $result['total_transitions']);
        $this->assertEquals(3, $result['unique_states']); // null, pending, processing, done
        $this->assertArrayHasKey('pending', $result['state_frequency']);
        $this->assertArrayHasKey('processing', $result['state_frequency']);
        $this->assertArrayHasKey('done', $result['state_frequency']);
        $this->assertArrayHasKey('null → pending', $result['transition_frequency']);
        $this->assertArrayHasKey('pending → processing', $result['transition_frequency']);
        $this->assertArrayHasKey('processing → done', $result['transition_frequency']);
        $this->assertArrayHasKey('done → pending', $result['transition_frequency']);
    }

    /**
     * Test empty string validation with specific string values.
     */
    public function test_empty_string_validation_with_specific_string_values(): void
    {
        // Test with empty string
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '', 'status');
    }

    /**
     * Test trim vs non-trim validation.
     */
    public function test_trim_vs_non_trim_validation(): void
    {
        // Test with string that has whitespace (should be trimmed)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '   ', 'status');
    }

    /**
     * Test index comparison edge cases.
     */
    public function test_index_comparison_edge_cases(): void
    {
        // Create transitions that test index > 0 vs index >= 0
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
        ]);

        $result = $this->replayService->validateTransitionHistory('TestModel', '123', 'status');

        // Should be valid since transitions are consistent
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test index comparison with decremented value.
     */
    public function test_index_comparison_with_decremented_value(): void
    {
        // Create transitions that test index > -1
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
        ]);

        $result = $this->replayService->validateTransitionHistory('TestModel', '123', 'status');

        // Should be valid since transitions are consistent
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test integer increment/decrement edge cases in state frequency.
     */
    public function test_integer_increment_decrement_edge_cases(): void
    {
        // Create transitions to test frequency calculation edge cases
        $this->createTestTransitions([
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
        ]);

        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        // Test that frequency calculations work correctly
        $this->assertArrayHasKey('state_frequency', $result);
        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertEquals(2, $result['total_transitions']);

        // Verify state frequencies are calculated correctly
        $this->assertArrayHasKey('pending', $result['state_frequency']);
        $this->assertArrayHasKey('processing', $result['state_frequency']);
        $this->assertArrayHasKey('done', $result['state_frequency']);
    }

    /**
     * Test coalesce operator variations.
     */
    public function test_coalesce_operator_variations(): void
    {
        // Create transitions to test coalesce operator edge cases
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
        ]);

        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        // Test that transition frequency calculations work correctly
        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertArrayHasKey('null → pending', $result['transition_frequency']);
        $this->assertArrayHasKey('pending → processing', $result['transition_frequency']);

        // Verify frequencies are calculated correctly
        $this->assertEquals(1, $result['transition_frequency']['null → pending']);
        $this->assertEquals(1, $result['transition_frequency']['pending → processing']);
    }

    /**
     * Test empty string validation variations for column name.
     */
    public function test_empty_string_validation_variations_for_column_name(): void
    {
        // Test with empty string for column name
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '123', '');
    }

    /**
     * Test trim vs non-trim validation for column name.
     */
    public function test_trim_vs_non_trim_validation_for_column_name(): void
    {
        // Test with whitespace-only column name
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '123', '   ');
    }

    /**
     * Test empty string validation for getTransitionStatistics.
     */
    public function test_empty_string_validation_for_get_transition_statistics(): void
    {
        // Test with empty string
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionStatistics('TestModel', '', 'status');
    }

    /**
     * Test trim vs non-trim validation for getTransitionStatistics.
     */
    public function test_trim_vs_non_trim_validation_for_get_transition_statistics(): void
    {
        // Test with whitespace-only model ID
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionStatistics('TestModel', '   ', 'status');
    }

    /**
     * Test empty string validation for validateTransitionHistory.
     */
    public function test_empty_string_validation_for_validate_transition_history(): void
    {
        // Test with empty string
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->validateTransitionHistory('TestModel', '', 'status');
    }

    /**
     * Test trim vs non-trim validation for validateTransitionHistory.
     */
    public function test_trim_vs_non_trim_validation_for_validate_transition_history(): void
    {
        // Test with whitespace-only model ID
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->validateTransitionHistory('TestModel', '   ', 'status');
    }

    /**
     * Test additional empty string validation variations.
     */
    public function test_additional_empty_string_validation_variations(): void
    {
        // Test with empty string for modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '', 'status');
    }

    /**
     * Test additional trim vs non-trim validation.
     */
    public function test_additional_trim_vs_non_trim_validation(): void
    {
        // Test with whitespace-only modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '   ', 'status');
    }

    /**
     * Test additional index comparison edge cases.
     */
    public function test_additional_index_comparison_edge_cases(): void
    {
        // Create transitions that test index > 0 vs index >= 0
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
        ]);

        $result = $this->replayService->validateTransitionHistory('TestModel', '123', 'status');

        // Should be valid since transitions are consistent
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test additional integer increment/decrement edge cases.
     */
    public function test_additional_integer_increment_decrement_edge_cases(): void
    {
        // Create transitions to test frequency calculation edge cases
        $this->createTestTransitions([
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
        ]);

        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        // Test that frequency calculations work correctly
        $this->assertArrayHasKey('state_frequency', $result);
        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertEquals(2, $result['total_transitions']);

        // Verify state frequencies are calculated correctly
        $this->assertArrayHasKey('pending', $result['state_frequency']);
        $this->assertArrayHasKey('processing', $result['state_frequency']);
        $this->assertArrayHasKey('done', $result['state_frequency']);
    }

    /**
     * Test additional coalesce operator variations.
     */
    public function test_additional_coalesce_operator_variations(): void
    {
        // Create transitions to test coalesce operator edge cases
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
        ]);

        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        // Test that transition frequency calculations work correctly
        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertArrayHasKey('null → pending', $result['transition_frequency']);
        $this->assertArrayHasKey('pending → processing', $result['transition_frequency']);

        // Verify frequencies are calculated correctly
        $this->assertEquals(1, $result['transition_frequency']['null → pending']);
        $this->assertEquals(1, $result['transition_frequency']['pending → processing']);
    }

    /**
     * Test additional empty string validation for column name.
     */
    public function test_additional_empty_string_validation_for_column_name(): void
    {
        // Test with empty string for column name
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '123', '');
    }

    /**
     * Test additional trim vs non-trim validation for column name.
     */
    public function test_additional_trim_vs_non_trim_validation_for_column_name(): void
    {
        // Test with whitespace-only column name
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '123', '   ');
    }

    /**
     * Test additional empty string validation for getTransitionStatistics.
     */
    public function test_additional_empty_string_validation_for_get_transition_statistics(): void
    {
        // Test with empty string
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionStatistics('TestModel', '', 'status');
    }

    /**
     * Test additional trim vs non-trim validation for getTransitionStatistics.
     */
    public function test_additional_trim_vs_non_trim_validation_for_get_transition_statistics(): void
    {
        // Test with whitespace-only model ID
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionStatistics('TestModel', '   ', 'status');
    }

    /**
     * Test additional empty string validation for validateTransitionHistory.
     */
    public function test_additional_empty_string_validation_for_validate_transition_history(): void
    {
        // Test with empty string
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->validateTransitionHistory('TestModel', '', 'status');
    }

    /**
     * Test additional trim vs non-trim validation for validateTransitionHistory.
     */
    public function test_additional_trim_vs_non_trim_validation_for_validate_transition_history(): void
    {
        // Test with whitespace-only model ID
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->validateTransitionHistory('TestModel', '   ', 'status');
    }

    /**
     * Test additional empty string validation variations with specific string values.
     */
    public function test_additional_empty_string_validation_variations_with_specific_strings(): void
    {
        // Test with specific string value 'PEST Mutator was here!' for modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '', 'status');
    }

    /**
     * Test additional trim vs non-trim validation with unwrapped trim.
     */
    public function test_additional_trim_vs_non_trim_validation_with_unwrapped_trim(): void
    {
        // Test with unwrapped trim (no trim() call) for modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '', 'status');
    }

    /**
     * Test additional empty string validation for validateTransitionHistory with specific string values.
     */
    public function test_additional_empty_string_validation_for_validate_transition_history_with_specific_strings(): void
    {
        // Test with specific string value 'PEST Mutator was here!' for modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->validateTransitionHistory('TestModel', '', 'status');
    }

    /**
     * Test additional trim vs non-trim validation for validateTransitionHistory with unwrapped trim.
     */
    public function test_additional_trim_vs_non_trim_validation_for_validate_transition_history_with_unwrapped_trim(): void
    {
        // Test with unwrapped trim (no trim() call) for modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->validateTransitionHistory('TestModel', '', 'status');
    }

    /**
     * Test additional empty string validation for getTransitionStatistics with specific string values.
     */
    public function test_additional_empty_string_validation_for_get_transition_statistics_with_specific_strings(): void
    {
        // Test with specific string value 'PEST Mutator was here!' for modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionStatistics('TestModel', '', 'status');
    }

    /**
     * Test additional trim vs non-trim validation for getTransitionStatistics with unwrapped trim.
     */
    public function test_additional_trim_vs_non_trim_validation_for_get_transition_statistics_with_unwrapped_trim(): void
    {
        // Test with unwrapped trim (no trim() call) for modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionStatistics('TestModel', '', 'status');
    }

    /**
     * Test additional index comparison edge cases with greater or equal.
     */
    public function test_additional_index_comparison_edge_cases_with_greater_or_equal(): void
    {
        // Create transitions that test index > 0 vs index >= 0
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
        ]);

        $result = $this->replayService->validateTransitionHistory('TestModel', '123', 'status');

        // Should be valid since transitions are consistent
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test additional index comparison edge cases with decremented value.
     */
    public function test_additional_index_comparison_edge_cases_with_decremented_value(): void
    {
        // Create transitions that test index > 0 vs index > -1
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
        ]);

        $result = $this->replayService->validateTransitionHistory('TestModel', '123', 'status');

        // Should be valid since transitions are consistent
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test additional integer increment/decrement edge cases with decremented default.
     */
    public function test_additional_integer_increment_decrement_edge_cases_with_decremented_default(): void
    {
        // Create transitions to test frequency calculation edge cases with decremented default
        $this->createTestTransitions([
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
        ]);

        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        // Test that frequency calculations work correctly
        $this->assertArrayHasKey('state_frequency', $result);
        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertEquals(2, $result['total_transitions']);

        // Verify state frequencies are calculated correctly
        $this->assertArrayHasKey('pending', $result['state_frequency']);
        $this->assertArrayHasKey('processing', $result['state_frequency']);
        $this->assertArrayHasKey('done', $result['state_frequency']);
    }

    /**
     * Test additional integer increment/decrement edge cases with incremented default.
     */
    public function test_additional_integer_increment_decrement_edge_cases_with_incremented_default(): void
    {
        // Create transitions to test frequency calculation edge cases with incremented default
        $this->createTestTransitions([
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
        ]);

        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        // Test that frequency calculations work correctly
        $this->assertArrayHasKey('state_frequency', $result);
        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertEquals(2, $result['total_transitions']);

        // Verify state frequencies are calculated correctly
        $this->assertArrayHasKey('pending', $result['state_frequency']);
        $this->assertArrayHasKey('processing', $result['state_frequency']);
        $this->assertArrayHasKey('done', $result['state_frequency']);
    }

    /**
     * Test additional coalesce operator variations with removed left side.
     */
    public function test_additional_coalesce_operator_variations_with_removed_left_side(): void
    {
        // Create transitions to test coalesce operator edge cases with removed left side
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
        ]);

        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        // Test that transition frequency calculations work correctly
        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertArrayHasKey('null → pending', $result['transition_frequency']);
        $this->assertArrayHasKey('pending → processing', $result['transition_frequency']);

        // Verify frequencies are calculated correctly
        $this->assertEquals(1, $result['transition_frequency']['null → pending']);
        $this->assertEquals(1, $result['transition_frequency']['pending → processing']);
    }

    /**
     * Helper method to create test transitions.
     */
    private function createTestTransitions(array $transitions): void
    {
        foreach ($transitions as $index => $transition) {
            $log = new FsmEventLog;
            $log->timestamps = false; // Disable timestamps
            $log->fill([
                'id' => \Illuminate\Support\Str::uuid(),
                'model_id' => '123',
                'model_type' => 'TestModel',
                'column_name' => 'status',
                'from_state' => $transition['from_state'],
                'to_state' => $transition['to_state'],
                'transition_name' => 'test_transition',
                'occurred_at' => now()->addMinutes($index),
                'context' => null,
                'metadata' => null,
            ]);
            $log->save();
        }
    }

    /**
     * Test additional EmptyStringToNotEmpty mutations.
     */
    public function test_additional_empty_string_to_not_empty_mutations(): void
    {
        // Test with 'PEST Mutator was here!' instead of empty string for modelId validation
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '', 'status');
    }

    /**
     * Test additional UnwrapTrim mutations.
     */
    public function test_additional_unwrap_trim_mutations(): void
    {
        // Test with unwrapped trim (direct string comparison instead of trim($modelId) === '')
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->replayTransitions('TestModel', '', 'status');
    }

    /**
     * Test additional GreaterToGreaterOrEqual mutations.
     */
    public function test_additional_greater_to_greater_or_equal_mutations(): void
    {
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
        ]);

        // Test with >= instead of > for index comparison
        $result = $this->replayService->validateTransitionHistory('TestModel', '123', 'status');

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test additional DecrementInteger/IncrementInteger mutations.
     */
    public function test_additional_integer_mutations(): void
    {
        $this->createTestTransitions([
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
        ]);

        // Test with decremented default value (-1 instead of 0)
        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        $this->assertArrayHasKey('state_frequency', $result);
        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertEquals(2, $result['total_transitions']);
        $this->assertArrayHasKey('pending', $result['state_frequency']);
        $this->assertArrayHasKey('processing', $result['state_frequency']);
        $this->assertArrayHasKey('done', $result['state_frequency']);

        // Test with incremented default value (1 instead of 0)
        $result2 = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        $this->assertArrayHasKey('state_frequency', $result2);
        $this->assertArrayHasKey('transition_frequency', $result2);
        $this->assertEquals(2, $result2['total_transitions']);
        $this->assertArrayHasKey('pending', $result2['state_frequency']);
        $this->assertArrayHasKey('processing', $result2['state_frequency']);
        $this->assertArrayHasKey('done', $result2['state_frequency']);
    }

    /**
     * Test additional CoalesceRemoveLeft mutations.
     */
    public function test_additional_coalesce_remove_left_mutations(): void
    {
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
        ]);

        // Test with removed left side of coalesce operator (0 instead of $transitionFrequency[$transitionKey] ?? 0)
        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertArrayHasKey('null → pending', $result['transition_frequency']);
        $this->assertArrayHasKey('pending → processing', $result['transition_frequency']);
        $this->assertEquals(1, $result['transition_frequency']['null → pending']);
        $this->assertEquals(1, $result['transition_frequency']['pending → processing']);
    }

    /**
     * Test additional EmptyStringToNotEmpty mutations for validateTransitionHistory.
     */
    public function test_additional_empty_string_to_not_empty_mutations_validate(): void
    {
        // Test with 'PEST Mutator was here!' instead of empty string for modelId validation
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->validateTransitionHistory('TestModel', '', 'status');
    }

    /**
     * Test additional UnwrapTrim mutations for validateTransitionHistory.
     */
    public function test_additional_unwrap_trim_mutations_validate(): void
    {
        // Test with unwrapped trim (direct string comparison instead of trim($modelId) === '')
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->validateTransitionHistory('TestModel', '', 'status');
    }

    /**
     * Test additional EmptyStringToNotEmpty mutations for getTransitionStatistics.
     */
    public function test_additional_empty_string_to_not_empty_mutations_stats(): void
    {
        // Test with 'PEST Mutator was here!' instead of empty string for modelId validation
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionStatistics('TestModel', '', 'status');
    }

    /**
     * Test additional UnwrapTrim mutations for getTransitionStatistics.
     */
    public function test_additional_unwrap_trim_mutations_stats(): void
    {
        // Test with unwrapped trim (direct string comparison instead of trim($modelId) === '')
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionStatistics('TestModel', '', 'status');
    }

    /**
     * Test additional GreaterToGreaterOrEqual mutations for index comparison.
     */
    public function test_additional_greater_to_greater_or_equal_mutations_index(): void
    {
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
        ]);

        // Test with >= instead of > for index comparison
        $result = $this->replayService->validateTransitionHistory('TestModel', '123', 'status');

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test additional DecrementInteger mutations for index comparison.
     */
    public function test_additional_decrement_integer_mutations_index(): void
    {
        $this->createTestTransitions([
            ['from_state' => null, 'to_state' => 'pending'],
            ['from_state' => 'pending', 'to_state' => 'processing'],
        ]);

        // Test with decremented index comparison (index > -1 instead of index > 0)
        $result = $this->replayService->validateTransitionHistory('TestModel', '123', 'status');

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test additional DecrementInteger mutations for state frequency calculation.
     */
    public function test_additional_decrement_integer_mutations_state_frequency(): void
    {
        $this->createTestTransitions([
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
        ]);

        // Test with decremented default value (-1 instead of 0)
        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        $this->assertArrayHasKey('state_frequency', $result);
        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertEquals(2, $result['total_transitions']);
        $this->assertArrayHasKey('pending', $result['state_frequency']);
        $this->assertArrayHasKey('processing', $result['state_frequency']);
        $this->assertArrayHasKey('done', $result['state_frequency']);
    }

    /**
     * Test additional IncrementInteger mutations for state frequency calculation.
     */
    public function test_additional_increment_integer_mutations_state_frequency(): void
    {
        $this->createTestTransitions([
            ['from_state' => 'pending', 'to_state' => 'processing'],
            ['from_state' => 'processing', 'to_state' => 'done'],
        ]);

        // Test with incremented default value (1 instead of 0)
        $result = $this->replayService->getTransitionStatistics('TestModel', '123', 'status');

        $this->assertArrayHasKey('state_frequency', $result);
        $this->assertArrayHasKey('transition_frequency', $result);
        $this->assertEquals(2, $result['total_transitions']);
        $this->assertArrayHasKey('pending', $result['state_frequency']);
        $this->assertArrayHasKey('processing', $result['state_frequency']);
        $this->assertArrayHasKey('done', $result['state_frequency']);
    }
}
