<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Fsm\Models\FsmEventLog;
use Fsm\Services\FsmReplayService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

/**
 * Test for FsmReplayService functionality.
 *
 * Tests the core replay service methods for retrieving transition history,
 * replaying transitions, validating history consistency, and generating statistics.
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
}

class TestReplayModel extends Model
{
    protected $table = 'test_models';

    protected $fillable = ['status'];
}
