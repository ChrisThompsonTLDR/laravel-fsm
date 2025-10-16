<?php

declare(strict_types=1);

namespace Fsm\Data;

use Carbon\CarbonImmutable;

/**
 * DTO representing a single state transition entry in the timeline.
 *
 * Contains all relevant information about a state transition event,
 * including the transition details, timing, and optional context.
 */
class StateTimelineEntryData extends Dto
{
    public string $id;

    public ?string $modelId = null;

    public ?string $modelType = null;

    public ?string $fsmColumn = null;

    public ?string $fromState = null;

    public ?string $toState = null;

    public ?string $transitionEvent = null;

    /** @var ?array<string, mixed> */
    public ?array $contextSnapshot = null;

    public ?string $exceptionDetails = null;

    public ?int $durationMs = null;

    public ?CarbonImmutable $happenedAt = null;

    public ?string $subjectId = null;

    public ?string $subjectType = null;

    /** @var array<string, string> */
    protected array $casts = [
        'happened_at' => CarbonImmutable::class,
    ];

    /**
     * @param  array<string, mixed>|string  $id
     * @param  array<string, mixed>|null  $context_snapshot
     */
    public function __construct(
        string|array $id,
        ?string $model_id = null,
        ?string $model_type = null,
        ?string $fsm_column = null,
        ?string $from_state = null,
        ?string $to_state = null,
        ?string $transition_event = null,
        ?array $context_snapshot = null,
        ?string $exception_details = null,
        ?int $duration_ms = null,
        ?CarbonImmutable $happened_at = null,
        ?string $subject_id = null,
        ?string $subject_type = null,
    ) {
        // Array-based initialization: new StateTimelineEntryData(['id' => ..., 'model_id' => ...])
        if (is_array($id) && func_num_args() === 1 && static::isAssociative($id)) {
            // First prepare attributes to convert snake_case to camelCase
            $prepared = static::prepareAttributes($id);

            // Then validate required keys using the prepared (camelCase) keys
            $requiredKeys = ['id']; // Only id is truly required
            $missingKeys = array_diff($requiredKeys, array_keys($prepared));

            if (! empty($missingKeys)) {
                throw new \InvalidArgumentException(
                    'Missing required keys for array construction: '.implode(', ', $missingKeys)
                );
            }

            parent::__construct($prepared);

            return;
        }

        // Reject non-associative arrays for clarity
        if (is_array($id)) {
            throw new \InvalidArgumentException('Array-based initialization requires an associative array.');
        }

        // Named parameter initialization: new StateTimelineEntryData(id: ..., model_id: ...)
        parent::__construct(static::prepareAttributes([
            'id' => $id,
            'model_id' => $model_id,
            'model_type' => $model_type,
            'fsm_column' => $fsm_column,
            'from_state' => $from_state,
            'to_state' => $to_state,
            'transition_event' => $transition_event,
            'context_snapshot' => $context_snapshot,
            'exception_details' => $exception_details,
            'duration_ms' => $duration_ms,
            'happened_at' => $happened_at,
            'subject_id' => $subject_id,
            'subject_type' => $subject_type,
        ]));
    }
}
