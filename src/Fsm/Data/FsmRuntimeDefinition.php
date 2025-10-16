<?php

declare(strict_types=1);

namespace Fsm\Data;

use Fsm\Constants;
use Fsm\Contracts\FsmStateEnum;

/**
 * Represents the complete, "compiled" runtime definition of an FSM for a specific model and column.
 */
class FsmRuntimeDefinition
{
    /**
     * All states defined in this FSM, keyed by their string value.
     *
     * @var array<string, StateDefinition>
     */
    public readonly array $states;

    /**
     * All transitions defined in this FSM, as a list.
     *
     * @var TransitionDefinition[]
     */
    public readonly array $transitions;

    /**
     * Initial state of the FSM.
     */
    public readonly FsmStateEnum|string|null $initialState;

    /**
     * @param  class-string  $modelClass  The Eloquent model class this FSM is for.
     * @param  string  $columnName  The column on the model that stores the state.
     * @param  array<int, StateDefinition>  $stateDefinitions
     * @param  array<int, TransitionDefinition>  $transitionDefinitions
     */
    public function __construct(
        public readonly string $modelClass,
        public readonly string $columnName,
        array $stateDefinitions,
        array $transitionDefinitions,
        FsmStateEnum|string|null $initialState = null,
        public readonly ?string $contextDtoClass = null,
        public readonly ?string $description = null,
    ) {
        $this->initialState = $initialState;

        $states = [];
        foreach ($stateDefinitions as $stateDef) {
            $states[self::getStateValue($stateDef->name)] = $stateDef;
        }
        $this->states = $states;
        $this->transitions = array_values($transitionDefinitions);
    }

    public function getStateDefinition(FsmStateEnum|string|null $state): ?StateDefinition
    {
        if ($state === null) {
            return null;
        }

        return $this->states[self::getStateValue($state)] ?? null;
    }

    /**
     * @return TransitionDefinition[]
     */
    public function getTransitionsFor(FsmStateEnum|string|null $fromState, ?string $event): array
    {
        $searchFromValueAsString = ($fromState === null) ? null : self::getStateValue($fromState);

        return array_values(array_filter(
            $this->transitions,
            function (TransitionDefinition $t) use ($searchFromValueAsString, $event) {
                $definedTransitionEvent = $t->event;

                $eventMatches = ($event === Constants::EVENT_WILDCARD)
                    ? $definedTransitionEvent === Constants::EVENT_WILDCARD
                    : ($definedTransitionEvent === $event || $definedTransitionEvent === Constants::EVENT_WILDCARD);

                if (! $eventMatches) {
                    return false;
                }

                $definedTransitionFromStateValueAsString = ($t->fromState === null) ? null : self::getStateValue($t->fromState);

                return $definedTransitionFromStateValueAsString === Constants::STATE_WILDCARD ||
                    $definedTransitionFromStateValueAsString === $searchFromValueAsString;
            }
        ));
    }

    private static function getStateValue(FsmStateEnum|string $state): string
    {
        return $state instanceof FsmStateEnum ? $state->value : $state;
    }

    /**
     * Export this FSM definition to a simplified array structure.
     *
     * This is useful when passing the FSM to an LLM for NPC dialogue logic or
     * other AI driven features where only high level information is required.
     *
     * @return array<string, mixed>
     */
    public function export(): array
    {
        $states = [];
        foreach ($this->states as $state) {
            $states[] = [
                'name' => self::getStateValue($state->name),
                'description' => $state->description,
            ];
        }

        $transitions = [];
        foreach ($this->transitions as $transition) {
            $transitions[] = [
                'from' => $transition->fromState === null
                    ? null
                    : self::getStateValue($transition->fromState),
                'to' => self::getStateValue($transition->toState),
                'event' => $transition->event,
                'description' => $transition->description,
            ];
        }

        return [
            'model' => $this->modelClass,
            'column' => $this->columnName,
            'initial_state' => $this->initialState === null
                ? null
                : self::getStateValue($this->initialState),
            'description' => $this->description,
            'states' => $states,
            'transitions' => $transitions,
        ];
    }
}
