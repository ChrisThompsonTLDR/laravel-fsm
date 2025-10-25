<?php

declare(strict_types=1);

namespace Fsm\Services;

use Fsm\Models\FsmEventLog;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

/**
 * Service for replaying FSM state transitions for deterministic reconstruction.
 *
 * This service enables powerful auditing and debugging capabilities by providing
 * methods to reconstruct state history, validate transition consistency, and
 * generate analytics from the event log. It's particularly useful for:
 *
 * - Debugging complex state transition flows
 * - Auditing state changes for compliance
 * - Generating analytics on state machine usage patterns
 * - Reconstructing state after data corruption or rollbacks
 * - Testing state transition scenarios
 */
class FsmReplayService
{
    /**
     * Get the transition history for a model and column.
     *
     * @param  class-string<Model>  $modelClass
     *
     * @phpstan-return EloquentCollection<int, FsmEventLog>
     */
    public function getTransitionHistory(string $modelClass, string $modelId, string $columnName): EloquentCollection
    {
        if (trim($modelId) === '') {
            throw new \InvalidArgumentException('The modelId cannot be an empty string.');
        }

        if ($columnName === '') {
            throw new \InvalidArgumentException('The columnName cannot be an empty string.');
        }

        return FsmEventLog::forModel($modelClass, $modelId, $columnName)->get();
    }

    /**
     * Replay all transitions for a model to determine the current state.
     *
     * This is useful for debugging or rebuilding state from event logs.
     *
     * @param  class-string<Model>  $modelClass
     * @return array{initial_state: string|null, final_state: string|null, transition_count: int, transitions: array<array{from_state: string|null, to_state: string, transition_name: string|null, occurred_at: string, context: array<string, mixed>|null, metadata: array<string, mixed>|null}>}
     */
    public function replayTransitions(string $modelClass, string $modelId, string $columnName): array
    {
        // Validate input parameters to prevent empty strings
        if (trim($modelId) === '') {
            throw new \InvalidArgumentException('The modelId cannot be an empty string.');
        }

        if (trim($columnName) === '') {
            throw new \InvalidArgumentException('The columnName cannot be an empty string.');
        }

        $transitions = $this->getTransitionHistory($modelClass, $modelId, $columnName);

        if ($transitions->isEmpty()) {
            return [
                'initial_state' => null,
                'final_state' => null,
                'transition_count' => 0,
                'transitions' => [],
            ];
        }

        $initialState = $transitions->first()->from_state;
        $finalState = $transitions->last()->to_state;

        return [
            'initial_state' => $initialState,
            'final_state' => $finalState,
            'transition_count' => $transitions->count(),
            'transitions' => $transitions->map(fn (FsmEventLog $log) => $log->getReplayData())->toArray(),
        ];
    }

    /**
     * Validate that the transition history is consistent (no gaps or invalid sequences).
     *
     * This method checks that each transition's from_state matches the previous
     * transition's to_state, ensuring a valid sequence of state changes. This is
     * crucial for detecting data corruption or concurrent modification issues.
     *
     * @param  class-string<Model>  $modelClass
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateTransitionHistory(string $modelClass, string $modelId, string $columnName): array
    {
        // Validate input parameters to prevent empty strings
        if (trim($modelId) === '') {
            throw new \InvalidArgumentException('The modelId cannot be an empty string.');
        }

        if (trim($columnName) === '') {
            throw new \InvalidArgumentException('The columnName cannot be an empty string.');
        }

        $transitions = $this->getTransitionHistory($modelClass, $modelId, $columnName);
        $errors = [];

        $previousToState = null;

        foreach ($transitions as $index => $transition) {
            // Check for consistency between transitions
            // Only check consistency for transitions after the first one
            if ($index > 0 && $previousToState !== $transition->from_state) {
                $errors[] = "Transition {$index}: from_state '{$transition->from_state}' doesn't match previous to_state '{$previousToState}'";
            }

            $previousToState = $transition->to_state;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get transition statistics for a model and column.
     *
     * Provides analytical data about state machine usage including:
     * - Total number of transitions
     * - Count of unique states encountered
     * - Frequency of each state (how often it was entered)
     * - Frequency of each transition type
     *
     * Useful for performance optimization, identifying bottlenecks, and
     * understanding user behavior patterns in your application.
     *
     * @param  class-string<Model>  $modelClass
     * @return array{total_transitions: int, unique_states: int, state_frequency: array<string, int>, transition_frequency: array<string, int>}
     */
    public function getTransitionStatistics(string $modelClass, string $modelId, string $columnName): array
    {
        // Validate input parameters to prevent empty strings
        if (trim($modelId) === '') {
            throw new \InvalidArgumentException('The modelId cannot be an empty string.');
        }

        if (trim($columnName) === '') {
            throw new \InvalidArgumentException('The columnName cannot be an empty string.');
        }

        $transitions = $this->getTransitionHistory($modelClass, $modelId, $columnName);

        $stateFrequency = [];
        $transitionFrequency = [];

        foreach ($transitions as $transition) {
            // Count from_state frequency
            if ($transition->from_state !== null) {
                $stateFrequency[$transition->from_state] = ($stateFrequency[$transition->from_state] ?? 0) + 1;
            }

            // Count to_state frequency (always count, even for initial null state)
            $stateFrequency[$transition->to_state] = ($stateFrequency[$transition->to_state] ?? 0) + 1;

            // Count transition frequency
            $transitionKey = ($transition->from_state ?? 'null').' → '.$transition->to_state;
            $transitionFrequency[$transitionKey] = ($transitionFrequency[$transitionKey] ?? 0) + 1;
        }

        return [
            'total_transitions' => $transitions->count(),
            'unique_states' => count($stateFrequency),
            'state_frequency' => $stateFrequency,
            'transition_frequency' => $transitionFrequency,
        ];
    }
}
