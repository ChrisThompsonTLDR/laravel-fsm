<?php

declare(strict_types=1);

namespace Fsm\Data;

use Fsm\Contracts\FsmStateEnum;
use Illuminate\Support\Collection;

/**
 * Extends StateDefinition to allow nesting child state machines.
 */
class HierarchicalStateDefinition extends StateDefinition
{
    /**
     * @param  array<int, TransitionCallback>|\Illuminate\Support\Collection<int, TransitionCallback>  $onEntryCallbacks
     * @param  array<int, TransitionCallback>|\Illuminate\Support\Collection<int, TransitionCallback>  $onExitCallbacks
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        FsmStateEnum|string $name,
        array|Collection $onEntryCallbacks = [],
        array|Collection $onExitCallbacks = [],
        ?string $description = null,
        string $type = self::TYPE_INTERMEDIATE,
        ?string $category = null,
        string $behavior = self::BEHAVIOR_PERSISTENT,
        array $metadata = [],
        bool $isTerminal = false,
        int $priority = 50,
        public readonly ?FsmRuntimeDefinition $childStateMachine = null,
        public readonly ?string $parentState = null,
    ) {
        parent::__construct(
            name: $name,
            onEntryCallbacks: $onEntryCallbacks,
            onExitCallbacks: $onExitCallbacks,
            description: $description,
            type: $type,
            category: $category,
            behavior: $behavior,
            metadata: $metadata,
            isTerminal: $isTerminal,
            priority: $priority,
        );
    }
}
