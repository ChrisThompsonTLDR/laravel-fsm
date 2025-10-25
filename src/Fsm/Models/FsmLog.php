<?php

declare(strict_types=1);

namespace Fsm\Models;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Fsm\Models\FsmLog
 *
 * @property string $id
 * @property string|null $subject_id
 * @property string|null $subject_type
 * @property string $model_id
 * @property string $model_type
 * @property string $fsm_column
 * @property string $from_state
 * @property string $to_state
 * @property string|null $transition_event
 * @property array<string, mixed>|null $context_snapshot
 * @property string|null $exception_details
 * @property int|null $duration_ms
 * @property \Illuminate\Support\Carbon $happened_at
 * @property-read Model|null $subject
 * @property-read Model $model
 */
class FsmLog extends Model
{
    use HasUuids;

    /**
     * Extracts user_id from a state object, regardless of property visibility.
     *
     * @param  object|null  $state
     */
    private static function extractUserId($state): ?string
    {
        if (! is_object($state)) {
            return null;
        }
        // 1. Try public property
        if (isset($state->user_id)) {
            return (string) $state->user_id;
        }
        // 2. Try getter
        if (method_exists($state, 'getUserId')) {
            $id = $state->getUserId();
            if ($id !== null) {
                return (string) $id;
            }
        }
        // 3. Try reflection
        try {
            $ref = new \ReflectionObject($state);
            if ($ref->hasProperty('user_id')) {
                $prop = $ref->getProperty('user_id');
                $prop->setAccessible(true);
                $id = $prop->getValue($state);
                if ($id !== null) {
                    return (string) $id;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }

    public $timestamps = false; // handled by happened_at

    protected $table = 'fsm_logs';

    protected $fillable = [
        'id',
        'subject_id',
        'subject_type',
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
    ];

    protected $casts = [
        'happened_at' => 'immutable_datetime',
        'context_snapshot' => 'array',
        'exception_details' => 'string',
        'duration_ms' => 'integer',
    ];

    /**
     * The subject that triggered this FSM transition (e.g., User).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            if (empty($log->happened_at)) {
                $log->happened_at = now();
            }
            // Attempt to set subject from Verbs state if configured and available
            if (
                empty($log->subject_id)
                && app(ConfigRepository::class)->get('fsm.verbs.log_user_subject', true)
                && class_exists(\Thunk\Verbs\Facades\Verbs::class)
            ) {
                try {
                    $verbsInstance = \Thunk\Verbs\Facades\Verbs::getFacadeRoot();
                    if (method_exists($verbsInstance, 'state')) {
                        $state = \Thunk\Verbs\Facades\Verbs::state();
                        $userId = self::extractUserId($state);
                        if ($userId) {
                            $log->subject_id = $userId;
                            $log->subject_type = app(ConfigRepository::class)->get('auth.providers.users.model');
                        }
                    }
                } catch (\Throwable $e) {
                    // Silently ignore if Verbs context is not available or method doesn't exist
                }
            }
        });
    }
}
