<?php

declare(strict_types=1);

namespace Fsm\Contracts;

/**
 * Contract for modular state definitions that can be overridden or extended.
 */
interface ModularStateDefinition
{
    /**
     * Get the state name or enum that this definition applies to.
     */
    public function getStateName(): string|FsmStateEnum;

    /**
     * Get the state definition data.
     *
     * @return array<string, mixed>
     */
    public function getDefinition(): array;

    /**
     * Determines if this state definition should override an existing one.
     */
    public function shouldOverride(): bool;

    /**
     * Get the priority of this state definition. Higher numbers take precedence.
     */
    public function getPriority(): int;
}
