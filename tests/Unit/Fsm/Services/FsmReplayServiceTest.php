<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Fsm\Models\FsmEventLog;
use Fsm\Services\FsmReplayService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

mutates(\Fsm\Services\FsmReplayService::class);

/**
 * Test for FsmReplayService functionality.
 *
 * Tests the core replay service methods for retrieving transition history,
 * replaying transitions, validating history consistency, and generating statistics.
 */
/**
 * @covers Fsm\Services\FsmReplayService
 */
class FsmReplayServiceTest extends TestCase
{
    use RefreshDatabase;

    private FsmReplayService $replayService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->replayService = new FsmReplayService;

        // Create tables for testing
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
            $table->timestamp('created_at')->nullable();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('test_models', function ($table) {
            $table->id();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function test_get_transition_history_returns_empty_collection_when_no_events()
    {
        $history = $this->replayService->getTransitionHistory(
            TestReplayModel::class,
            '1',
            'status'
        );

        $this->assertInstanceOf(Collection::class, $history);
        $this->assertCount(0, $history);
    }

    public function test_get_transition_history_returns_events_in_chronological_order()
    {
        // Create test events out of chronological order
        $this->createTestEvent('1', 'pending', 'processing', '2024-01-01 10:00:00');
        $this->createTestEvent('1', 'processing', 'completed', '2024-01-01 12:00:00');
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00'); // Initial state

        $history = $this->replayService->getTransitionHistory(
            TestReplayModel::class,
            '1',
            'status'
        );

        $this->assertCount(3, $history);

        // Should be ordered by occurred_at
        $this->assertNull($history[0]->from_state);
        $this->assertEquals('pending', $history[0]->to_state);

        $this->assertEquals('pending', $history[1]->from_state);
        $this->assertEquals('processing', $history[1]->to_state);

        $this->assertEquals('processing', $history[2]->from_state);
        $this->assertEquals('completed', $history[2]->to_state);
    }

    public function test_replay_transitions_returns_correct_structure_for_empty_history()
    {
        $result = $this->replayService->replayTransitions(
            TestReplayModel::class,
            '1',
            'status'
        );

        $expected = [
            'initial_state' => null,
            'final_state' => null,
            'transition_count' => 0,
            'transitions' => [],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_replay_transitions_reconstructs_state_correctly()
    {
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00');
        $this->createTestEvent('1', 'pending', 'processing', '2024-01-01 10:00:00');
        $this->createTestEvent('1', 'processing', 'completed', '2024-01-01 12:00:00');

        $result = $this->replayService->replayTransitions(
            TestReplayModel::class,
            '1',
            'status'
        );

        $this->assertNull($result['initial_state']);
        $this->assertEquals('completed', $result['final_state']);
        $this->assertEquals(3, $result['transition_count']);
        $this->assertCount(3, $result['transitions']);

        // Check structure of transitions
        $firstTransition = $result['transitions'][0];
        $this->assertArrayHasKey('from_state', $firstTransition);
        $this->assertArrayHasKey('to_state', $firstTransition);
        $this->assertArrayHasKey('transition_name', $firstTransition);
        $this->assertArrayHasKey('occurred_at', $firstTransition);
        $this->assertArrayHasKey('context', $firstTransition);
        $this->assertArrayHasKey('metadata', $firstTransition);
    }

    public function test_validate_transition_history_returns_valid_for_empty_history()
    {
        $result = $this->replayService->validateTransitionHistory(
            TestReplayModel::class,
            '1',
            'status'
        );

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_transition_history_detects_inconsistencies()
    {
        // Create invalid sequence: pending -> processing, then completed -> failed (gap)
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00');
        $this->createTestEvent('1', 'pending', 'processing', '2024-01-01 10:00:00');
        $this->createTestEvent('1', 'completed', 'failed', '2024-01-01 12:00:00'); // Invalid: should be from processing

        $result = $this->replayService->validateTransitionHistory(
            TestReplayModel::class,
            '1',
            'status'
        );

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('doesn\'t match previous to_state', $result['errors'][0]);
    }

    public function test_validate_transition_history_passes_for_valid_sequence()
    {
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00');
        $this->createTestEvent('1', 'pending', 'processing', '2024-01-01 10:00:00');
        $this->createTestEvent('1', 'processing', 'completed', '2024-01-01 12:00:00');

        $result = $this->replayService->validateTransitionHistory(
            TestReplayModel::class,
            '1',
            'status'
        );

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_get_transition_statistics_returns_correct_structure()
    {
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00');
        $this->createTestEvent('1', 'pending', 'processing', '2024-01-01 10:00:00');
        $this->createTestEvent('1', 'processing', 'completed', '2024-01-01 12:00:00');

        $result = $this->replayService->getTransitionStatistics(
            TestReplayModel::class,
            '1',
            'status'
        );

        $this->assertArrayHasKey('total_transitions', $result);
        $this->assertArrayHasKey('unique_states', $result);
        $this->assertArrayHasKey('state_frequency', $result);
        $this->assertArrayHasKey('transition_frequency', $result);

        $this->assertEquals(3, $result['total_transitions']);
        $this->assertEquals(3, $result['unique_states']); // pending, processing, completed

        // Check state frequencies (counts both entries and exits)
        $this->assertEquals(2, $result['state_frequency']['pending']); // entered once (to_state), exited once (from_state) = 2
        $this->assertEquals(2, $result['state_frequency']['processing']); // entered once (to_state), exited once (from_state) = 2
        $this->assertEquals(1, $result['state_frequency']['completed']); // entered once (to_state), never exited = 1

        // Check transition frequencies
        $this->assertEquals(1, $result['transition_frequency']['null → pending']);
        $this->assertEquals(1, $result['transition_frequency']['pending → processing']);
        $this->assertEquals(1, $result['transition_frequency']['processing → completed']);
    }

    public function test_get_transition_history_throws_exception_when_model_id_is_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionHistory(
            TestReplayModel::class,
            '',
            'status'
        );
    }

    public function test_get_transition_history_throws_exception_when_column_name_is_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->getTransitionHistory(
            TestReplayModel::class,
            '1',
            ''
        );
    }

    public function test_replay_transitions_throws_exception_when_model_id_is_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->replayTransitions(
            TestReplayModel::class,
            '',
            'status'
        );
    }

    public function test_validate_transition_history_throws_exception_when_column_name_is_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->validateTransitionHistory(
            TestReplayModel::class,
            '1',
            ''
        );
    }

    public function test_get_transition_statistics_throws_exception_when_model_id_is_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionStatistics(
            TestReplayModel::class,
            '',
            'status'
        );
    }

    public function test_get_transition_statistics_throws_exception_when_column_name_is_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->getTransitionStatistics(
            TestReplayModel::class,
            '1',
            ''
        );
    }

    public function test_get_transition_history_throws_exception_when_model_id_is_whitespace_only()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionHistory(
            TestReplayModel::class,
            '   ',
            'status'
        );
    }

    public function test_validate_transition_history_throws_exception_when_column_name_is_whitespace_only()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->validateTransitionHistory(
            TestReplayModel::class,
            '1',
            '   '
        );
    }

    private function createTestEvent(
        string $modelId,
        ?string $fromState,
        string $toState,
        string $occurredAt,
        ?string $transitionName = null,
        ?array $context = null,
        ?array $metadata = null
    ): void {
        FsmEventLog::create([
            'model_id' => $modelId,
            'model_type' => TestReplayModel::class,
            'column_name' => 'status',
            'from_state' => $fromState,
            'to_state' => $toState,
            'transition_name' => $transitionName,
            'occurred_at' => $occurredAt,
            'context' => $context,
            'metadata' => $metadata,
        ]);
    }

    public function test_get_transition_history_with_empty_model_id_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionHistory(TestReplayModel::class, '', 'status');
        // Tests trim validation in getTransitionHistory
    }

    public function test_get_transition_history_with_empty_column_name_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->getTransitionHistory(TestReplayModel::class, '1', '');
        // Tests trim validation in getTransitionHistory
    }

    public function test_validate_transition_history_with_empty_transitions(): void
    {
        // Assuming no logs exist for this model
        $result = $this->replayService->validateTransitionHistory(TestReplayModel::class, '999', 'status');

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        // Tests empty collection handling in validateTransitionHistory
    }

    public function test_get_transition_statistics_with_no_transitions(): void
    {
        $stats = $this->replayService->getTransitionStatistics(TestReplayModel::class, '999', 'status');

        $this->assertEquals(0, $stats['total_transitions']);
        $this->assertEquals(0, $stats['unique_states']);
        $this->assertEmpty($stats['state_frequency']);
        $this->assertEmpty($stats['transition_frequency']);
        // Tests array operations with empty data
    }

    public function test_validate_transition_history_first_transition_is_not_validated(): void
    {
        // Create only a first transition (no previous transition to validate against)
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00');

        $result = $this->replayService->validateTransitionHistory(
            TestReplayModel::class,
            '1',
            'status'
        );

        // Should be valid since there's no previous transition to validate against
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_transition_history_only_validates_subsequent_transitions(): void
    {
        // Create a valid sequence where first transition should not be validated
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00'); // First transition - no validation
        $this->createTestEvent('1', 'pending', 'processing', '2024-01-01 10:00:00'); // Second transition - validates against first

        $result = $this->replayService->validateTransitionHistory(
            TestReplayModel::class,
            '1',
            'status'
        );

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_get_transition_statistics_handles_null_from_state_correctly(): void
    {
        // Create a transition with null from_state (initial state)
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00');

        $stats = $this->replayService->getTransitionStatistics(
            TestReplayModel::class,
            '1',
            'status'
        );

        $this->assertEquals(1, $stats['total_transitions']);
        $this->assertEquals(1, $stats['unique_states']);
        // null from_state should not increment state frequency, but to_state should
        $this->assertArrayNotHasKey(null, $stats['state_frequency']);
        $this->assertArrayHasKey('pending', $stats['state_frequency']);
        $this->assertEquals(1, $stats['state_frequency']['pending']);
    }

    public function test_get_transition_statistics_state_frequency_calculation(): void
    {
        // Create transitions to test frequency calculations
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00');
        $this->createTestEvent('1', 'pending', 'processing', '2024-01-01 10:00:00');

        $stats = $this->replayService->getTransitionStatistics(
            TestReplayModel::class,
            '1',
            'status'
        );

        // Ensure state frequency is calculated correctly
        $this->assertEquals(2, $stats['state_frequency']['pending']); // entered and exited
        $this->assertEquals(1, $stats['state_frequency']['processing']); // only entered
    }

    /**
     * Test validation mutations - empty string checks should be properly validated
     */
    public function test_validation_mutations_empty_string_detection(): void
    {
        // Test that empty model_id is properly rejected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string');

        $this->replayService->replayTransitions(
            TestReplayModel::class,
            '',
            'status'
        );
    }

    /**
     * Test validation mutations - empty column name checks should be properly validated
     */
    public function test_validation_mutations_empty_column_detection(): void
    {
        // Test that empty column_name is properly rejected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string');

        $this->replayService->replayTransitions(
            TestReplayModel::class,
            '1',
            ''
        );
    }

    /**
     * Test validation mutations - trim operations should be properly handled
     */
    public function test_validation_mutations_trim_operations(): void
    {
        // Test that whitespace-only strings are properly rejected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string');

        $this->replayService->replayTransitions(
            TestReplayModel::class,
            '   ',
            'status'
        );
    }

    /**
     * Test transition validation mutations - index comparison should be properly validated
     */
    public function test_transition_validation_mutations(): void
    {
        // Create transitions that would be invalid if mutations broke the validation
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00');
        $this->createTestEvent('1', 'processing', 'completed', '2024-01-01 10:00:00'); // Wrong from_state

        $result = $this->replayService->validateTransitionHistory(
            TestReplayModel::class,
            '1',
            'status'
        );

        // Should detect the inconsistency
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('doesn\'t match previous to_state', $result['errors'][0]);
    }

    /**
     * Test statistics mutations - state frequency calculation should handle null states correctly
     */
    public function test_statistics_mutations_null_state_handling(): void
    {
        // Create transitions with null from_state (initial transitions)
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00');
        $this->createTestEvent('1', 'pending', 'processing', '2024-01-01 10:00:00');

        $stats = $this->replayService->getTransitionStatistics(
            TestReplayModel::class,
            '1',
            'status'
        );

        // null from_state should not be counted in state_frequency
        $this->assertArrayNotHasKey(null, $stats['state_frequency']);
        // But to_state should be counted
        $this->assertArrayHasKey('pending', $stats['state_frequency']);
        $this->assertArrayHasKey('processing', $stats['state_frequency']);

        // Transition frequency should be calculated correctly
        $this->assertArrayHasKey('null → pending', $stats['transition_frequency']);
        $this->assertArrayHasKey('pending → processing', $stats['transition_frequency']);
        $this->assertEquals(1, $stats['transition_frequency']['null → pending']);
        $this->assertEquals(1, $stats['transition_frequency']['pending → processing']);
    }

    /**
     * Test protection against empty string validation vulnerabilities.
     */
    public function test_replay_transitions_protects_against_empty_strings(): void
    {
        // Test empty modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');
        $this->replayService->replayTransitions('TestModel', '', 'status');

        // Test empty columnName
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');
        $this->replayService->replayTransitions('TestModel', '123', '');

        // Test whitespace-only modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');
        $this->replayService->replayTransitions('TestModel', '   ', 'status');

        // Test whitespace-only columnName
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');
        $this->replayService->replayTransitions('TestModel', '123', '   ');
    }

    /**
     * Test protection against empty string validation vulnerabilities in validateTransitionHistory.
     */
    public function test_validate_transition_history_protects_against_empty_strings(): void
    {
        // Test empty modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');
        $this->replayService->validateTransitionHistory('TestModel', '', 'status');

        // Test empty columnName
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');
        $this->replayService->validateTransitionHistory('TestModel', '123', '');

        // Test whitespace-only modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');
        $this->replayService->validateTransitionHistory('TestModel', '   ', 'status');

        // Test whitespace-only columnName
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');
        $this->replayService->validateTransitionHistory('TestModel', '123', '   ');
    }

    /**
     * Test protection against empty string validation vulnerabilities in getTransitionStatistics.
     */
    public function test_get_transition_statistics_protects_against_empty_strings(): void
    {
        // Test empty modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');
        $this->replayService->getTransitionStatistics('TestModel', '', 'status');

        // Test empty columnName
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');
        $this->replayService->getTransitionStatistics('TestModel', '123', '');

        // Test whitespace-only modelId
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');
        $this->replayService->getTransitionStatistics('TestModel', '   ', 'status');

        // Test whitespace-only columnName
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');
        $this->replayService->getTransitionStatistics('TestModel', '123', '   ');
    }

    /**
     * Test protection against index comparison vulnerability in transition validation.
     */
    public function test_validate_transition_history_protects_against_index_vulnerability(): void
    {
        // Create transitions that would trigger the index > 0 check
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00');
        $this->createTestEvent('1', 'pending', 'processing', '2024-01-01 10:00:00');

        $result = $this->replayService->validateTransitionHistory(
            TestReplayModel::class,
            '1',
            'status'
        );

        // Should validate successfully with proper index checking (index > 0)
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test protection against integer arithmetic vulnerabilities in statistics.
     */
    public function test_statistics_protects_against_integer_arithmetic_vulnerabilities(): void
    {
        // Create multiple transitions to test frequency calculations
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00');
        $this->createTestEvent('1', 'pending', 'processing', '2024-01-01 10:00:00');
        $this->createTestEvent('1', 'processing', 'completed', '2024-01-01 12:00:00');

        $stats = $this->replayService->getTransitionStatistics(
            TestReplayModel::class,
            '1',
            'status'
        );

        // Test that frequency calculations are correct and protected against mutations
        // Note: 'pending' appears twice - once as from_state and once as to_state
        $this->assertEquals(2, $stats['state_frequency']['pending']);
        // Note: 'processing' appears twice - once as from_state and once as to_state
        $this->assertEquals(2, $stats['state_frequency']['processing']);
        $this->assertEquals(1, $stats['state_frequency']['completed']);

        // Test transition frequency calculations
        $this->assertEquals(1, $stats['transition_frequency']['null → pending']);
        $this->assertEquals(1, $stats['transition_frequency']['pending → processing']);
        $this->assertEquals(1, $stats['transition_frequency']['processing → completed']);
    }

    /**
     * Test protection against null coalescing vulnerabilities in statistics.
     */
    public function test_statistics_protects_against_null_coalescing_vulnerabilities(): void
    {
        // Create transitions with same states to test null coalescing
        $this->createTestEvent('1', null, 'pending', '2024-01-01 08:00:00');
        $this->createTestEvent('1', 'pending', 'pending', '2024-01-01 10:00:00'); // Same state transition

        $stats = $this->replayService->getTransitionStatistics(
            TestReplayModel::class,
            '1',
            'status'
        );

        // Test that null coalescing works correctly for frequency calculations
        // Note: 'pending' appears 3 times - once as from_state (null→pending) and twice as to_state (null→pending, pending→pending)
        $this->assertEquals(3, $stats['state_frequency']['pending']); // Should be 3, not 2
        $this->assertEquals(1, $stats['transition_frequency']['null → pending']);
        $this->assertEquals(1, $stats['transition_frequency']['pending → pending']);
    }
}

class TestReplayModel extends Model
{
    protected $table = 'test_models';

    protected $fillable = ['status'];
}
