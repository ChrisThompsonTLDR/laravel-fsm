<?php

declare(strict_types=1);

namespace Fsm\Contracts;

/**
 * Contract for modular transition definitions that can be overridden or extended.
 */
interface ModularTransitionDefinition
{
    /**
     * Get the source state for this transition.
     */
    public function getFromState(): string|\Fsm\Contracts\FsmStateEnum|null;

    /**
     * Get the target state for this transition.
     */
    public function getToState(): string|\Fsm\Contracts\FsmStateEnum;

    /**
     * Get the event that triggers this transition.
     */
    public function getEvent(): string;

    /**
     * Get the transition definition data.
     *
     * @return array<string, mixed>
     */
    public function getDefinition(): array;

    /**
     * Determines if this transition definition should override an existing one.
     */
    public function shouldOverride(): bool;

    /**
     * Get the priority of this transition definition. Higher numbers take precedence.
     */
    public function getPriority(): int;
}
