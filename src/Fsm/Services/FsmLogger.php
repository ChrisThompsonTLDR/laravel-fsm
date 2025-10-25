<?php

declare(strict_types=1);

namespace Fsm\Services;

use Fsm\Contracts\FsmStateEnum;
// For context filtering later
use Fsm\Models\FsmLog;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Thunk\Verbs\Facades\Verbs;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

class FsmLogger
{
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
                // Ensure property is accessible for reading
                if (! $prop->isPublic()) {
                    $prop->setAccessible(true);
                }
                $id = $prop->getValue($state);
                if ($id !== null) {
                    return (string) $id;
                }
            }
        } catch (\Throwable $e) {
            // ignore reflection errors
        }

        return null;
    }

    public function __construct(
        private readonly ConfigRepository $config
    ) {}

    /**
     * Write log information to the configured log channel.
     *
     * @param  array<string, mixed>  $data
     */
    protected function logToChannel(array $data, bool $isFailure = false): void
    {
        $channel = $this->config->get('fsm.logging.channel');
        if (! $channel) {
            return;
        }

        $message = $isFailure ? 'FSM transition failed' : 'FSM transition succeeded';
        $logger = Log::channel($channel);

        if ($this->config->get('fsm.logging.structured', false)) {
            $logger->{$isFailure ? 'error' : 'info'}($message, $data);
        } else {
            // Flatten log data to a string message for non-structured logging
            $fields = [
                'model_type', 'model_id', 'fsm_column', 'from_state', 'to_state', 'transition_event', 'duration_ms', 'happened_at', 'subject_id', 'subject_type', 'exception_details',
            ];
            $parts = [];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $parts[] = $field.'='.(is_scalar($data[$field]) ? $data[$field] : json_encode($data[$field]));
                }
            }
            if (isset($data['context_snapshot'])) {
                $parts[] = 'context_snapshot='.json_encode($data['context_snapshot']);
            }
            $flatMessage = $message.': '.(empty($parts) ? '' : implode(' | ', $parts));
            $logger->{$isFailure ? 'error' : 'info'}($flatMessage);
        }
    }

    protected function normalizeState(FsmStateEnum|string $state): string
    {
        return $state instanceof FsmStateEnum ? $state->value : $state;
    }

    /**
     * Prepare context data for storage.
     *
     * @return array<string, mixed>|null
     */
    protected function filterContextForLogging(?ArgonautDTOContract $context): ?array
    {
        if (! $context) {
            return null;
        }

        try {
            $contextArray = $context->toArray();
        } catch (\Throwable $e) {
            $contextArray = get_object_vars($context);
        }

        $sensitiveKeys = $this->config->get('fsm.logging.excluded_context_properties', []);
        if (empty($sensitiveKeys)) {
            return $contextArray;
        }

        // Ensure we have an array to work with - defensive check
        if (! is_array($contextArray)) {
            return $contextArray;
        }

        return $this->recursivelyRemoveSensitiveKeys($contextArray, $sensitiveKeys);
    }

    /**
     * Recursively removes sensitive keys from an array.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $sensitiveKeys
     * @return array<string, mixed>
     */
    protected function recursivelyRemoveSensitiveKeys(array $data, array $sensitiveKeys, string $prefix = ''): array
    {
        $filteredData = [];

        foreach ($data as $key => $value) {
            $currentKey = $prefix ? "{$prefix}.{$key}" : $key;

            // Check if this key should be filtered out
            if (in_array($currentKey, $sensitiveKeys, true)) {
                continue;
            }

            // Also check for wildcard matches, e.g., 'extra.*'
            $wildcardKey = "{$prefix}.*";
            if ($prefix && in_array($wildcardKey, $sensitiveKeys, true)) {
                continue;
            }

            // Convert DTO objects to arrays if needed
            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }

            if (is_array($value)) {
                $filteredData[$key] = $this->recursivelyRemoveSensitiveKeys($value, $sensitiveKeys, $currentKey);
            } else {
                $filteredData[$key] = $value;
            }
        }

        return $filteredData;
    }

    /**
     * Retrieve subject information from the current Verbs state if available.
     *
     * @return array{subject_id: string, subject_type: string}|null
     */
    protected function subjectFromVerbs(): ?array
    {
        if (! $this->config->get('fsm.verbs.log_user_subject', false)) {
            return null;
        }

        if (! class_exists(Verbs::class)) {
            return null;
        }

        try {
            $verbsInstance = Verbs::getFacadeRoot();

            if (method_exists($verbsInstance, 'state')) {
                $state = Verbs::state();
                $userId = self::extractUserId($state);
                if ($userId) {
                    return [
                        'subject_id' => $userId,
                        'subject_type' => $this->config->get('auth.providers.users.model'),
                    ];
                }
            }
        } catch (Throwable $e) {
            // Ignore issues retrieving Verbs context
        }

        return null;
    }

    /**
     * Logs a successful FSM transition.
     */
    public function logSuccess(
        Model $model,
        string $columnName,
        FsmStateEnum|string|null $fromState,
        FsmStateEnum|string $toState,
        ?string $transitionEvent,
        ?ArgonautDTOContract $context,
        ?int $durationMs = null
    ): void {
        if (! $this->config->get('fsm.logging.enabled', true)) {
            return;
        }

        $contextData = $this->filterContextForLogging($context);
        $fromStateValue = $fromState === null ? null : $this->normalizeState($fromState);

        $logData = ['model_id' => $model->getKey(), 'model_type' => $model->getMorphClass(), 'fsm_column' => $columnName, 'from_state' => $fromStateValue, 'to_state' => $this->normalizeState($toState), 'transition_event' => $transitionEvent, 'context_snapshot' => $contextData, 'duration_ms' => $durationMs];

        if ($subject = $this->subjectFromVerbs()) {
            $logData = array_merge($logData, $subject);
        }

        FsmLog::create($logData);
        $this->logToChannel($logData, false);
    }

    /**
     * Logs a failed FSM transition.
     */
    public function logFailure(
        Model $model,
        string $columnName,
        FsmStateEnum|string|null $fromState,
        FsmStateEnum|string $toState,
        ?string $transitionEvent,
        ?ArgonautDTOContract $context,
        Throwable $exception,
        ?int $durationMs = null
    ): void {
        if (! $this->config->get('fsm.logging.enabled', true) || ! $this->config->get('fsm.logging.log_failures', true)) {
            return;
        }

        $contextData = $this->filterContextForLogging($context);
        $fromStateValue = $fromState === null ? null : $this->normalizeState($fromState);

        $exceptionDetails = Str::limit((string) $exception, $this->config->get('fsm.logging.exception_character_limit', 65535));

        $logData = [
            'model_id' => $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'fsm_column' => $columnName,
            'from_state' => $fromStateValue,
            'to_state' => $this->normalizeState($toState),
            'transition_event' => $transitionEvent, // Event name that was attempted
            'context_snapshot' => $contextData,
            'exception_details' => $exceptionDetails,
            'duration_ms' => $durationMs,
        ];

        if ($subject = $this->subjectFromVerbs()) {
            $logData = array_merge($logData, $subject);
        }

        FsmLog::create($logData);
        $this->logToChannel($logData, true);
    }

    public function logTransition(
        Model $model,
        string $fsmColumn,
        FsmStateEnum|string|null $fromState,
        FsmStateEnum|string $toState,
        ?ArgonautDTOContract $context,
        ?string $transitionEventName = null,
        // If the transition was triggered by a Verb, its ID can be passed here
        ?string $verbEventId = null,
        ?int $durationMs = null
    ): void {
        $fromValue = $fromState === null ? null : ($fromState instanceof FsmStateEnum ? $fromState->value : $fromState);
        $toValue = $toState instanceof FsmStateEnum ? $toState->value : $toState;
        $contextData = $this->filterContextForLogging($context);

        $subjectId = $verbEventId;
        $subjectType = null;

        if ($verbEventId) {
            $subject = $this->subjectFromVerbs();
            if ($subject) {
                $subjectId = $subject['subject_id'];
                $subjectType = $subject['subject_type'];
            }
        }

        $logData = [
            'id' => Str::uuid(),
            'subject_id' => $subjectId,
            'subject_type' => $subjectType,
            'model_id' => $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'fsm_column' => $fsmColumn,
            'from_state' => $fromValue,
            'to_state' => $toValue,
            'transition_event' => $transitionEventName,
            'context_snapshot' => $contextData,
            'duration_ms' => $durationMs,
            'happened_at' => Date::now(),
        ];

        FsmLog::create($logData);
        $this->logToChannel($logData, true);
    }
}
