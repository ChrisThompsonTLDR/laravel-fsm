<?php

declare(strict_types=1);

namespace Fsm\Verbs;

use Fsm\Constants;
use Fsm\Contracts\FsmStateEnum;
use Fsm\Data\TransitionInput;
use Fsm\Traits\StateNameStringConversion;
use Illuminate\Database\Eloquent\Model;
use Thunk\Verbs\Event;
use Thunk\Verbs\SerializedByVerbs;
use Thunk\Verbs\Support\Normalization\NormalizeToPropertiesAndClassName;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * Verb to represent that an FSM transition has successfully occurred on a model.
 *
 * Enhanced with readonly properties for immutability and typed constants
 * for better event sourcing integration and type safety.
 */
class FsmTransitioned extends Event implements SerializedByVerbs // Or StatefulEvent if it needs to apply to a state
{
    use NormalizeToPropertiesAndClassName;
    use StateNameStringConversion;

    /**
     * Transition result constants with proper typing for event sourcing.
     */
    public const string RESULT_SUCCESS = Constants::TRANSITION_SUCCESS;

    public const string RESULT_BLOCKED = Constants::TRANSITION_BLOCKED;

    public const string RESULT_FAILED = Constants::TRANSITION_FAILED;

    /**
     * Event source constants for enhanced type safety.
     */
    public const string SOURCE_USER_ACTION = TransitionInput::SOURCE_USER;

    public const string SOURCE_SYSTEM_PROCESS = TransitionInput::SOURCE_SYSTEM;

    public const string SOURCE_API_CALL = TransitionInput::SOURCE_API;

    public const string SOURCE_SCHEDULED_TASK = TransitionInput::SOURCE_SCHEDULER;

    public const string SOURCE_DATA_MIGRATION = TransitionInput::SOURCE_MIGRATION;

    /**
     * Event type constants for Verbs integration.
     */
    public const string EVENT_TYPE_TRANSITION = Constants::VERBS_EVENT_TYPE;

    public const string AGGREGATE_TYPE = Constants::VERBS_AGGREGATE_TYPE;

    /**
     * Transition priority constants matching system constants.
     */
    public const int PRIORITY_HIGH = Constants::PRIORITY_HIGH;

    public const int PRIORITY_NORMAL = Constants::PRIORITY_NORMAL;

    public const int PRIORITY_LOW = Constants::PRIORITY_LOW;

    /**
     * FsmTransitioned constructor.
     *
     * @param  string  $modelId  The model ID for better event sourcing serialization.
     * @param  string  $modelType  The model class name for polymorphic reconstruction.
     * @param  string  $fsmColumn  The name of the FSM column on the model.
     * @param  FsmStateEnum|string|null  $fromState  The original state.
     * @param  FsmStateEnum|string|null  $toState  The new state.
     * @param  string  $result  The transition result using typed constants.
     * @param  ArgonautDTOContract|null  $context  The context DTO provided during the transition, if any.
     * @param  string|null  $transitionEvent  The specific event name that triggered the transition.
     * @param  string  $source  The source that initiated the transition.
     * @param  array<string, mixed>  $metadata  Additional metadata for the transition.
     * @param  \DateTimeInterface|null  $occurredAt  When the transition occurred.
     * @param  int  $priority  Priority level for event processing.
     * @param  string|null  $correlationId  Optional correlation ID for tracing.
     * @param  string|null  $causationId  Optional causation ID for event chains.
     */
    public function __construct(
        public readonly string $modelId,
        public readonly string $modelType,
        public readonly string $fsmColumn,
        public readonly FsmStateEnum|string|null $fromState,
        public readonly FsmStateEnum|string|null $toState,
        public readonly string $result = self::RESULT_SUCCESS,
        public readonly ?ArgonautDTOContract $context = null,
        public readonly ?string $transitionEvent = null,
        public readonly string $source = self::SOURCE_USER_ACTION,
        public readonly array $metadata = [],
        public readonly ?\DateTimeInterface $occurredAt = null,
        public readonly int $priority = self::PRIORITY_NORMAL,
        public readonly ?string $correlationId = null,
        public readonly ?string $causationId = null,
    ) {}

    /**
     * Create from model instance - convenience factory method.
     *
     * @param  Model  $model  The model instance that transitioned.
     * @param  string  $fsmColumn  The name of the FSM column on the model.
     * @param  FsmStateEnum|string  $fromState  The original state.
     * @param  FsmStateEnum|string  $toState  The new state.
     * @param  string  $result  The transition result.
     * @param  ArgonautDTOContract|null  $context  The context DTO provided during the transition.
     * @param  string|null  $transitionEvent  The specific event name that triggered the transition.
     * @param  string  $source  The source that initiated the transition.
     * @param  array<string, mixed>  $metadata  Additional metadata.
     */
    public static function fromModel(
        Model $model,
        string $fsmColumn,
        FsmStateEnum|string $fromState,
        FsmStateEnum|string $toState,
        string $result = self::RESULT_SUCCESS,
        ?ArgonautDTOContract $context = null,
        ?string $transitionEvent = null,
        string $source = self::SOURCE_USER_ACTION,
        array $metadata = [],
    ): self {
        return new self(
            modelId: (string) $model->getKey(),
            modelType: get_class($model),
            fsmColumn: $fsmColumn,
            fromState: $fromState,
            toState: $toState,
            result: $result,
            context: $context,
            transitionEvent: $transitionEvent,
            source: $source,
            metadata: $metadata,
            occurredAt: now(),
        );
    }

    /**
     * Create from TransitionInput - demonstrating how typed constants flow through.
     */
    public static function fromTransitionInput(
        TransitionInput $input,
        string $fsmColumn,
        string $result = self::RESULT_SUCCESS,
    ): self {
        return new self(
            modelId: (string) $input->model->getKey(),
            modelType: get_class($input->model),
            fsmColumn: $fsmColumn,
            fromState: $input->fromState,
            toState: $input->toState,
            result: $result,
            context: $input->context,
            transitionEvent: $input->event,
            source: $input->getSource(), // Uses typed constants from TransitionInput
            metadata: $input->metadata,
            occurredAt: $input->getTimestamp(),
        );
    }

    /**
     * Record this verb using the Verbs broker.
     *
     * This provides a stable API rather than relying on the dynamic
     * {@code commit()} method inherited from {@see Event}.
     *
     * @param  mixed  ...$args
     */
    public static function record(...$args): self
    {
        /** @var self $verb */
        $verb = new self(...$args);
        $verb->commit();

        return $verb;
    }

    /**
     * Get the from state as a string for consistent serialization.
     */
    public function getFromStateName(): string
    {
        return self::stateToString($this->fromState) ?? '';
    }

    /**
     * Get the to state as a string for consistent serialization.
     */
    public function getToStateName(): string
    {
        return self::stateToString($this->toState) ?? '';
    }

    /**
     * Check if the transition was successful using typed constants.
     */
    public function wasSuccessful(): bool
    {
        return $this->result === self::RESULT_SUCCESS;
    }

    /**
     * Check if the transition was blocked using typed constants.
     */
    public function wasBlocked(): bool
    {
        return $this->result === self::RESULT_BLOCKED;
    }

    /**
     * Check if the transition failed using typed constants.
     */
    public function hasFailed(): bool
    {
        return $this->result === self::RESULT_FAILED;
    }

    /**
     * Get the priority level with type safety.
     */
    public function getPriorityLevel(): int
    {
        return $this->priority;
    }

    /**
     * Check if this is a high priority transition.
     */
    public function isHighPriority(): bool
    {
        return $this->priority >= self::PRIORITY_HIGH;
    }

    /**
     * Get metadata value with optional default.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Get the event type for Verbs aggregate handling.
     */
    public function getEventType(): string
    {
        return self::EVENT_TYPE_TRANSITION;
    }

    /**
     * Get the aggregate type for Verbs state management.
     */
    public function getAggregateType(): string
    {
        return self::AGGREGATE_TYPE;
    }

    /**
     * Get the aggregate ID for this transition event.
     */
    public function getAggregateId(): string
    {
        return "{$this->modelType}:{$this->modelId}:{$this->fsmColumn}";
    }

    /**
     * Transform to array for event sourcing storage with typed constants.
     *
     * @return array<string, mixed>
     */
    public function toEventSourcingArray(): array
    {
        return [
            'event_type' => $this->getEventType(),
            'aggregate_type' => $this->getAggregateType(),
            'aggregate_id' => $this->getAggregateId(),
            'model_id' => $this->modelId,
            'model_type' => $this->modelType,
            'fsm_column' => $this->fsmColumn,
            'from_state' => $this->getFromStateName(),
            'to_state' => $this->getToStateName(),
            'result' => $this->result,
            'transition_event' => $this->transitionEvent,
            'source' => $this->source,
            'priority' => $this->priority,
            'context' => $this->context?->toArray(),
            'metadata' => $this->metadata,
            'occurred_at' => $this->occurredAt?->format('c'),
            'correlation_id' => $this->correlationId,
            'causation_id' => $this->causationId,
        ];
    }

    // Optional authorization method for Verbs
    // public function authorize(): bool
    // {
    //     return true; // Or your specific authorization logic
    // }

    // Optional apply method if this verb changes state
    // public function apply(SomeState $state): void
    // {
    //     // Apply state changes based on typed constants
    // }
}
