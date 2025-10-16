<?php

declare(strict_types=1);

namespace Fsm\Data;

use InvalidArgumentException;

/**
 * DTO representing time analysis data for a specific state.
 *
 * Contains aggregated information about how long an entity spent
 * in a particular state, including total duration and occurrence count.
 */
class StateTimeAnalysisData extends Dto
{
    public string $state;

    public int $totalDurationMs;

    public int $occurrenceCount;

    public float $averageDurationMs;

    public ?int $minDurationMs = null;

    public ?int $maxDurationMs = null;

    /**
     * @param  array<string, mixed>|string  $state
     */
    public function __construct(
        string|array $state,
        int $totalDurationMs = 0,
        int $occurrenceCount = 0,
        float $averageDurationMs = 0.0,
        ?int $minDurationMs = null,
        ?int $maxDurationMs = null,
    ) {
        // Array-based initialization: new StateTimeAnalysisData(['state' => ..., 'totalDurationMs' => ...])
        if (is_array($state) && func_num_args() === 1) {
            // Reject empty arrays first
            if (empty($state)) {
                throw new InvalidArgumentException(
                    'Empty arrays are not allowed for StateTimeAnalysisData initialization'
                );
            }

            // Check if it's associative
            if (static::isAssociative($state)) {
                // Check if it has expected keys for DTO construction
                if (array_key_exists('state', $state) || array_key_exists('totalDurationMs', $state) ||
                    array_key_exists('occurrenceCount', $state) || array_key_exists('averageDurationMs', $state)) {

                    // Validate that all required keys are present (check both camelCase and snake_case)
                    $requiredKeys = [
                        'state',
                        'totalDurationMs',
                        'occurrenceCount',
                        'averageDurationMs',
                    ];

                    $snakeCaseKeys = [
                        'state',
                        'total_duration_ms',
                        'occurrence_count',
                        'average_duration_ms',
                    ];

                    $hasRequiredKeys = false;
                    foreach ($requiredKeys as $key) {
                        if (array_key_exists($key, $state)) {
                            $hasRequiredKeys = true;
                            break;
                        }
                    }

                    if (! $hasRequiredKeys) {
                        foreach ($snakeCaseKeys as $key) {
                            if (array_key_exists($key, $state)) {
                                $hasRequiredKeys = true;
                                break;
                            }
                        }
                    }

                    if (! $hasRequiredKeys) {
                        throw new InvalidArgumentException(
                            'Missing required keys in StateTimeAnalysisData: '.implode(', ', $requiredKeys)
                        );
                    }

                    // Check if all required keys are present (either camelCase or snake_case)
                    $missingKeys = [];
                    foreach ($requiredKeys as $key) {
                        $snakeKey = match ($key) {
                            'totalDurationMs' => 'total_duration_ms',
                            'occurrenceCount' => 'occurrence_count',
                            'averageDurationMs' => 'average_duration_ms',
                            default => $key,
                        };

                        if (! array_key_exists($key, $state) && ! array_key_exists($snakeKey, $state)) {
                            $missingKeys[] = $key;
                        }
                    }

                    if (! empty($missingKeys)) {
                        throw new InvalidArgumentException(
                            'Missing required keys in StateTimeAnalysisData: '.implode(', ', $missingKeys)
                        );
                    }

                    // Validate value types for required keys
                    // Note: All required keys are guaranteed to exist at this point due to validation above
                    $stateValue = $state['state'];
                    if (! is_string($stateValue)) {
                        throw new InvalidArgumentException(
                            'The "state" value must be a string, got: '.get_debug_type($stateValue)
                        );
                    }

                    $totalDurationMsValue = $state['totalDurationMs'] ?? $state['total_duration_ms'];
                    if (! is_int($totalDurationMsValue)) {
                        throw new InvalidArgumentException(
                            'The "totalDurationMs" value must be an integer, got: '.get_debug_type($totalDurationMsValue)
                        );
                    }

                    $occurrenceCountValue = $state['occurrenceCount'] ?? $state['occurrence_count'];
                    if (! is_int($occurrenceCountValue)) {
                        throw new InvalidArgumentException(
                            'The "occurrenceCount" value must be an integer, got: '.get_debug_type($occurrenceCountValue)
                        );
                    }

                    $averageDurationMsValue = $state['averageDurationMs'] ?? $state['average_duration_ms'];
                    if (! is_float($averageDurationMsValue) && ! is_int($averageDurationMsValue)) {
                        throw new InvalidArgumentException(
                            'The "averageDurationMs" value must be a float or integer, got: '.get_debug_type($averageDurationMsValue)
                        );
                    }

                    // Validate optional keys if present
                    $minDurationMsValue = $state['minDurationMs'] ?? $state['min_duration_ms'] ?? null;
                    if ($minDurationMsValue !== null && ! is_int($minDurationMsValue)) {
                        throw new InvalidArgumentException(
                            'The "minDurationMs" value must be an integer or null, got: '.get_debug_type($minDurationMsValue)
                        );
                    }

                    $maxDurationMsValue = $state['maxDurationMs'] ?? $state['max_duration_ms'] ?? null;
                    if ($maxDurationMsValue !== null && ! is_int($maxDurationMsValue)) {
                        throw new InvalidArgumentException(
                            'The "maxDurationMs" value must be an integer or null, got: '.get_debug_type($maxDurationMsValue)
                        );
                    }

                    parent::__construct(static::prepareAttributes($state));

                    return;
                } else {
                    // Associative array but no expected keys - throw missing keys exception
                    throw new InvalidArgumentException(
                        'Missing required keys in StateTimeAnalysisData: state, totalDurationMs, occurrenceCount, averageDurationMs'
                    );
                }
            }
        }

        // Reject non-associative arrays for clarity
        if (is_array($state)) {
            throw new InvalidArgumentException('Array-based initialization requires an associative array.');
        }

        // Named parameter initialization: new StateTimeAnalysisData(state: ..., totalDurationMs: ...)
        parent::__construct([
            'state' => $state,
            'totalDurationMs' => $totalDurationMs,
            'occurrenceCount' => $occurrenceCount,
            'averageDurationMs' => $averageDurationMs,
            'minDurationMs' => $minDurationMs,
            'maxDurationMs' => $maxDurationMs,
        ]);
    }
}
