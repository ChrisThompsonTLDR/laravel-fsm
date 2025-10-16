<?php

declare(strict_types=1);

namespace Fsm\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Fsm\Data\StateTimeAnalysisData;
use Fsm\Data\StateTimelineEntryData;
use Fsm\Models\FsmLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Service for querying FSM transition history and analyzing state durations.
 *
 * Provides methods to retrieve detailed timeline of state changes and analyze
 * time spent in each state for any entity with FSM columns.
 */
class FsmHistoryService
{
    /**
     * Get the complete timeline of state transitions for a model.
     *
     * @param  Model  $model  The model instance to get history for
     * @param  string  $columnName  The FSM column name
     * @param  DateTimeInterface|null  $from  Optional start date filter
     * @param  DateTimeInterface|null  $to  Optional end date filter
     * @return Collection<int, StateTimelineEntryData>
     */
    public function getStateTimeline(
        Model $model,
        string $columnName,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null
    ): Collection {
        $query = FsmLog::query()
            ->select([
                'id',
                'model_id',
                'model_type',
                'fsm_column',
                'from_state',
                'to_state',
                'transition_event',
                'context_snapshot',
                'exception_details',
                'duration_ms',
                'happened_at',
                'subject_id',
                'subject_type',
            ])
            ->where('model_id', $model->getKey())
            ->where('model_type', $model->getMorphClass())
            ->where('fsm_column', $columnName)
            ->orderBy('happened_at', 'asc');

        if ($from !== null) {
            $query->where('happened_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('happened_at', '<=', $to);
        }

        return $query->get()->map(function (FsmLog $log) {
            return StateTimelineEntryData::from([
                'id' => $log->id,
                'model_id' => $log->model_id,
                'model_type' => $log->model_type,
                'fsm_column' => $log->fsm_column,
                'from_state' => $log->from_state,
                'to_state' => $log->to_state,
                'transition_event' => $log->transition_event,
                'context_snapshot' => $log->context_snapshot,
                'exception_details' => $log->exception_details,
                'duration_ms' => $log->duration_ms,
                'happened_at' => $log->happened_at,
                'subject_id' => $log->subject_id,
                'subject_type' => $log->subject_type,
            ]);
        });
    }

    /**
     * Analyze time spent in each state for a model.
     *
     * Calculates duration statistics by pairing timeline entries to determine
     * how long the entity spent in each state.
     *
     * @param  Model  $model  The model instance to analyze
     * @param  string  $columnName  The FSM column name
     * @return Collection<int, StateTimeAnalysisData>
     */
    public function getStateTimeAnalysis(Model $model, string $columnName): Collection
    {
        $timeline = $this->getStateTimeline($model, $columnName);

        if ($timeline->isEmpty()) {
            return collect();
        }

        $stateAnalysis = [];
        $timelineEntries = $timeline->values();

        // Process each timeline entry to calculate durations
        for ($i = 0; $i < $timelineEntries->count(); $i++) {
            /** @var StateTimelineEntryData $currentEntry */
            $currentEntry = $timelineEntries[$i];
            $nextEntry = $timelineEntries[$i + 1] ?? null;

            // Calculate duration for the 'from' state if this is not the first entry
            if ($i > 0) {
                /** @var StateTimelineEntryData $previousEntry */
                $previousEntry = $timelineEntries[$i - 1];

                $fromState = $currentEntry->fromState;
                $currentHappenedAt = CarbonImmutable::make($currentEntry->happenedAt);
                $previousHappenedAt = CarbonImmutable::make($previousEntry->happenedAt);

                if ($fromState !== null && $currentHappenedAt instanceof CarbonImmutable && $previousHappenedAt instanceof CarbonImmutable) {
                    $durationMs = $previousHappenedAt->diffInMilliseconds($currentHappenedAt);

                    if (! isset($stateAnalysis[$fromState])) {
                        $stateAnalysis[$fromState] = [
                            'durations' => [],
                            'count' => 0,
                        ];
                    }

                    $stateAnalysis[$fromState]['durations'][] = $durationMs;
                    $stateAnalysis[$fromState]['count']++;
                }
            }

            // For the last entry, include the 'to' state if no more transitions follow
            if ($nextEntry === null) {
                $toState = $currentEntry->toState;
                if ($toState !== null) {
                    if (! isset($stateAnalysis[$toState])) {
                        $stateAnalysis[$toState] = [
                            'durations' => [],
                            'count' => 0,
                        ];
                    }
                    // We can't calculate duration for the final state without an end time
                    // So we'll just record its occurrence
                    $stateAnalysis[$toState]['count']++;
                }
            }
        }

        // Convert to DTOs
        return collect($stateAnalysis)->map(function ($data, $state) {
            $durations = $data['durations'];
            $totalDuration = (int) array_sum($durations);
            $count = max($data['count'], count($durations)); // Ensure count includes occurrences
            $averageDuration = $count > 0 && ! empty($durations)
                ? $totalDuration / count($durations)
                : 0.0;
            $minDuration = ! empty($durations) ? (int) min($durations) : null;
            $maxDuration = ! empty($durations) ? (int) max($durations) : null;

            return StateTimeAnalysisData::from([
                'state' => $state,
                'total_duration_ms' => $totalDuration,
                'occurrence_count' => $count,
                'average_duration_ms' => $averageDuration,
                'min_duration_ms' => $minDuration,
                'max_duration_ms' => $maxDuration,
            ]);
        })->values();
    }
}
