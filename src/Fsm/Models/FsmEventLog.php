<?php

declare(strict_types=1);

namespace Fsm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * FSM Event Log model for storing state transition events.
 *
 * This model provides a dedicated storage mechanism for FSM state transition
 * events, separate from the general FSM logs, to enable better event replay
 * and analytics capabilities.
 *
 * @property string $id
 * @property string $model_id
 * @property string $model_type
 * @property string $column_name
 * @property string|null $from_state
 * @property string $to_state
 * @property string|null $transition_name
 * @property \Illuminate\Support\Carbon|null $occurred_at
 * @property array<string, mixed>|null $context
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read Model $model
 */
class FsmEventLog extends Model
{
    use HasUuids;

    public $timestamps = true; // Enable timestamps

    const UPDATED_AT = null; // Disable updated_at, only use created_at

    protected $table = 'fsm_event_logs';

    protected $fillable = [
        'model_id',
        'model_type',
        'column_name',
        'from_state',
        'to_state',
        'transition_name',
        'occurred_at',
        'context',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'context' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The model that transitioned.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the transition history for replay purposes.
     *
     * @return array{from_state: string|null, to_state: string, transition_name: string|null, occurred_at: string|null, context: array<string, mixed>|null, metadata: array<string, mixed>|null}
     */
    public function getReplayData(): array
    {
        return [
            'from_state' => $this->from_state,
            'to_state' => $this->to_state,
            'transition_name' => $this->transition_name,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'context' => $this->context,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get events for a specific model and column, ordered by occurrence time.
     *
     * @param  class-string<Model>  $modelClass
     *
     * @phpstan-return \Illuminate\Database\Eloquent\Builder<static>
     */
    public static function forModel(string $modelClass, string $modelId, string $columnName): \Illuminate\Database\Eloquent\Builder
    {
        return static::query()
            ->where('model_type', $modelClass)
            ->where('model_id', $modelId)
            ->where('column_name', $columnName)
            ->orderBy('occurred_at');
    }
}
