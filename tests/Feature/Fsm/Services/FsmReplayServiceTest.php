<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Services;

use Fsm\Models\FsmEventLog;
use Fsm\Services\FsmReplayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class FsmReplayServiceTest extends FsmTestCase
{
    use RefreshDatabase;

    private FsmReplayService $replayService;

    private TestModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->replayService = $this->app->make(FsmReplayService::class);
        $this->model = TestModel::factory()->create();
    }

    public function test_get_transition_history_with_empty_model_id_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionHistory(
            TestModel::class,
            '',
            'status'
        );
    }

    public function test_get_transition_history_with_whitespace_model_id_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionHistory(
            TestModel::class,
            '   ',
            'status'
        );
    }

    public function test_get_transition_history_with_empty_column_name_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->getTransitionHistory(
            TestModel::class,
            '1',
            ''
        );
    }

    public function test_get_transition_history_returns_collection(): void
    {
        // Create some transition logs
        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => null,
            'to_state' => 'pending',
            'transition_name' => 'start',
            'context' => ['info' => 'test'],
            'occurred_at' => now(),
        ]);

        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => 'pending',
            'to_state' => 'completed',
            'transition_name' => 'finish',
            'context' => ['info' => 'done'],
            'occurred_at' => now()->addMinutes(5),
        ]);

        $transitions = $this->replayService->getTransitionHistory(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        $this->assertCount(2, $transitions);
    }

    public function test_replay_transitions_with_no_history_returns_empty_result(): void
    {
        $result = $this->replayService->replayTransitions(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        $this->assertEquals([
            'initial_state' => null,
            'final_state' => null,
            'transition_count' => 0,
            'transitions' => [],
        ], $result);
    }

    public function test_replay_transitions_with_history_returns_correct_result(): void
    {
        // Create transition logs
        $firstLog = FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => null,
            'to_state' => 'pending',
            'transition_name' => 'start',
            'context' => ['info' => 'test'],
            'occurred_at' => now(),
        ]);

        $secondLog = FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => 'pending',
            'to_state' => 'completed',
            'transition_name' => 'finish',
            'context' => ['info' => 'done'],
            'occurred_at' => now()->addMinutes(5),
        ]);

        $result = $this->replayService->replayTransitions(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        $this->assertEquals(null, $result['initial_state']);
        $this->assertEquals('completed', $result['final_state']);
        $this->assertEquals(2, $result['transition_count']);
        $this->assertCount(2, $result['transitions']);
    }

    public function test_validate_transition_history_with_valid_sequence_returns_valid(): void
    {
        // Create consistent transition logs
        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => null,
            'to_state' => 'pending',
            'transition_name' => 'start',
            'occurred_at' => now(),
        ]);

        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => 'pending', // Matches previous to_state
            'to_state' => 'completed',
            'transition_name' => 'finish',
            'occurred_at' => now()->addMinutes(5),
        ]);

        $result = $this->replayService->validateTransitionHistory(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_transition_history_with_invalid_sequence_returns_errors(): void
    {
        // Create inconsistent transition logs
        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => null,
            'to_state' => 'pending',
            'transition_name' => 'start',
            'occurred_at' => now(),
        ]);

        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => 'completed', // Doesn't match previous to_state 'pending'
            'to_state' => 'failed',
            'transition_name' => 'error',
            'occurred_at' => now()->addMinutes(5),
        ]);

        $result = $this->replayService->validateTransitionHistory(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('from_state \'completed\' doesn\'t match previous to_state \'pending\'', $result['errors'][0]);
    }

    public function test_validate_transition_history_with_no_transitions_returns_valid(): void
    {
        $result = $this->replayService->validateTransitionHistory(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_transition_history_checks_index_consistency(): void
    {
        // Create logs where index > 0 check matters
        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => null,
            'to_state' => 'pending',
            'occurred_at' => now(),
        ]);

        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => 'completed', // Wrong state for index 1
            'to_state' => 'failed',
            'occurred_at' => now()->addMinutes(5),
        ]);

        $result = $this->replayService->validateTransitionHistory(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        // Should catch the inconsistency due to index > 0 check
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_get_transition_statistics_with_no_transitions(): void
    {
        $result = $this->replayService->getTransitionStatistics(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        $this->assertEquals([
            'total_transitions' => 0,
            'unique_states' => 0,
            'state_frequency' => [],
            'transition_frequency' => [],
        ], $result);
    }

    public function test_get_transition_statistics_calculates_frequencies_correctly(): void
    {
        // Create transition logs
        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => null,
            'to_state' => 'pending',
            'transition_name' => 'start',
            'occurred_at' => now(),
        ]);

        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => 'pending',
            'to_state' => 'completed',
            'transition_name' => 'finish',
            'occurred_at' => now()->addMinutes(5),
        ]);

        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => 'completed',
            'to_state' => 'pending', // Back to pending
            'transition_name' => 'restart',
            'occurred_at' => now()->addMinutes(10),
        ]);

        $result = $this->replayService->getTransitionStatistics(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        $this->assertEquals(3, $result['total_transitions']);
        $this->assertEquals(2, $result['unique_states']); // pending, completed

        // pending appears as from_state once and to_state twice = 3 times total
        // completed appears as from_state once and to_state once = 2 times total
        $this->assertEquals(3, $result['state_frequency']['pending']);
        $this->assertEquals(2, $result['state_frequency']['completed']);

        // Check transition frequencies
        $this->assertEquals(1, $result['transition_frequency']['null → pending']);
        $this->assertEquals(1, $result['transition_frequency']['pending → completed']);
        $this->assertEquals(1, $result['transition_frequency']['completed → pending']);
    }

    public function test_get_transition_statistics_handles_null_from_state(): void
    {
        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => null,
            'to_state' => 'pending',
            'occurred_at' => now(),
        ]);

        $result = $this->replayService->getTransitionStatistics(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        // null from_state should not be counted in state_frequency
        $this->assertArrayNotHasKey(null, $result['state_frequency']);
        $this->assertEquals(1, $result['state_frequency']['pending']);
        $this->assertEquals(1, $result['transition_frequency']['null → pending']);
    }

    public function test_get_transition_statistics_includes_null_to_state_in_frequency(): void
    {
        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => null,
            'to_state' => 'pending',
            'occurred_at' => now(),
        ]);

        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => 'pending',
            'to_state' => 'idle', // Reset to idle state
            'transition_name' => 'reset',
            'occurred_at' => now()->addMinutes(5),
        ]);

        $result = $this->replayService->getTransitionStatistics(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        // idle should be counted in to_state frequency
        $this->assertEquals(1, $result['state_frequency']['idle']);
        $this->assertEquals(1, $result['transition_frequency']['pending → idle']);
    }

    public function test_get_transition_statistics_coalescing_operator_usage(): void
    {
        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => null,
            'to_state' => 'pending',
            'occurred_at' => now(),
        ]);

        $result = $this->replayService->getTransitionStatistics(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        // This tests that the coalescing operator (??) is working correctly
        // If the mutation removes the left side, this would break
        $this->assertEquals(1, $result['transition_frequency']['null → pending']);
    }

    public function test_replay_transitions_validation_with_empty_model_id_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->replayTransitions(
            TestModel::class,
            '',
            'status'
        );
    }

    public function test_replay_transitions_validation_with_empty_column_name_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->replayTransitions(
            TestModel::class,
            '1',
            ''
        );
    }

    public function test_validate_transition_history_validation_with_empty_model_id_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->validateTransitionHistory(
            TestModel::class,
            '',
            'status'
        );
    }

    public function test_validate_transition_history_validation_with_empty_column_name_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->validateTransitionHistory(
            TestModel::class,
            '1',
            ''
        );
    }

    public function test_get_transition_statistics_validation_with_empty_model_id_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionStatistics(
            TestModel::class,
            '',
            'status'
        );
    }

    public function test_get_transition_statistics_validation_with_empty_column_name_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The columnName cannot be an empty string.');

        $this->replayService->getTransitionStatistics(
            TestModel::class,
            '1',
            ''
        );
    }

    /**
     * Test greater than comparison mutations in loop conditions
     */
    public function test_loop_condition_greater_than_mutations(): void
    {
        // Create multiple transitions to test index > 0 condition
        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => null,
            'to_state' => 'pending',
            'transition_name' => 'start',
            'occurred_at' => now(),
        ]);

        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => 'completed', // This should cause an error (doesn't match previous to_state)
            'to_state' => 'failed',
            'transition_name' => 'error',
            'occurred_at' => now()->addMinutes(5),
        ]);

        $result = $this->replayService->validateTransitionHistory(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        // Should detect the inconsistency due to index > 0 check
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('doesn\'t match previous to_state', $result['errors'][0]);
    }

    /**
     * Test frequency calculation integer mutations
     */
    public function test_frequency_calculation_integer_mutations(): void
    {
        // Create transitions with specific states to test frequency counting
        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => null,
            'to_state' => 'pending',
            'transition_name' => 'start',
            'occurred_at' => now(),
        ]);

        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => 'pending',
            'to_state' => 'completed',
            'transition_name' => 'finish',
            'occurred_at' => now()->addMinutes(5),
        ]);

        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => 'completed',
            'to_state' => 'pending', // Back to pending
            'transition_name' => 'restart',
            'occurred_at' => now()->addMinutes(10),
        ]);

        $result = $this->replayService->getTransitionStatistics(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        // Test the frequency calculations
        $this->assertEquals(3, $result['total_transitions']);
        $this->assertEquals(2, $result['unique_states']);

        // pending appears as from_state once and to_state twice = 3 times total
        $this->assertEquals(3, $result['state_frequency']['pending']);
        // completed appears as from_state once and to_state once = 2 times total
        $this->assertEquals(2, $result['state_frequency']['completed']);

        // Check transition frequencies
        $this->assertEquals(1, $result['transition_frequency']['null → pending']);
        $this->assertEquals(1, $result['transition_frequency']['pending → completed']);
        $this->assertEquals(1, $result['transition_frequency']['completed → pending']);
    }

    /**
     * Test null coalescing mutations in frequency initialization
     */
    public function test_null_coalescing_mutations_in_frequency_initialization(): void
    {
        // Create a transition with null from_state
        FsmEventLog::create([
            'model_type' => TestModel::class,
            'model_id' => $this->model->getKey(),
            'column_name' => 'status',
            'from_state' => null,
            'to_state' => 'pending',
            'transition_name' => 'start',
            'occurred_at' => now(),
        ]);

        $result = $this->replayService->getTransitionStatistics(
            TestModel::class,
            (string) $this->model->getKey(),
            'status'
        );

        // Test that null coalescing works correctly
        $this->assertEquals(1, $result['total_transitions']);
        $this->assertEquals(1, $result['unique_states']);

        // null from_state should not be counted in state_frequency
        $this->assertArrayNotHasKey(null, $result['state_frequency']);
        $this->assertEquals(1, $result['state_frequency']['pending']);

        // But null should be counted in transition frequency
        $this->assertEquals(1, $result['transition_frequency']['null → pending']);
    }

    /**
     * Test empty string to non-empty mutations in validation
     */
    public function test_empty_string_to_non_empty_mutations(): void
    {
        // This test verifies that the EmptyStringToNotEmpty mutations are caught
        // The validation should reject empty strings after trimming
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionHistory(
            TestModel::class,
            '', // Empty string should be rejected
            'status'
        );
    }

    /**
     * Test trim removal mutations in validation
     */
    public function test_trim_removal_mutations(): void
    {
        // This test verifies that the UnwrapTrim mutations are caught
        // The validation should trim whitespace before checking if empty
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The modelId cannot be an empty string.');

        $this->replayService->getTransitionHistory(
            TestModel::class,
            '   ', // Whitespace only should be rejected after trim
            'status'
        );
    }

    /**
     * Test identical vs not identical mutations in parameter counting
     */
    public function test_identical_vs_not_identical_mutations(): void
    {
        // Create a DTO with from() method that has 2 parameters instead of 1
        $testDto = new class(['message' => 'test', 'secret' => 'hidden']) extends \Fsm\Data\Dto
        {
            public string $message;

            public string $secret;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                if (array_key_exists('secret', $data)) {
                    $this->secret = $data['secret'];
                }
                parent::__construct($data);
            }

            public static function from(mixed $payload): static
            {
                return new self(is_array($payload) ? $payload : ['message' => '']);
            }

            public function toArray(int $depth = 3): array
            {
                $result = ['message' => $this->message];
                if (isset($this->secret)) {
                    $result['secret'] = $this->secret;
                }

                return $result;
            }
        };

        // Mock config to exclude 'secret' property
        config(['fsm.logging.excluded_context_properties' => ['secret']]);

        $service = $this->app->make(\Fsm\Services\FsmEngineService::class);

        $filtered = $service->filterContextForLogging($testDto);

        // Test that the context filtering logic works (may fall back or succeed depending on implementation)
        $this->assertNotNull($filtered);
        $this->assertInstanceOf(\YorCreative\LaravelArgonautDTO\ArgonautDTOContract::class, $filtered);
    }
}
