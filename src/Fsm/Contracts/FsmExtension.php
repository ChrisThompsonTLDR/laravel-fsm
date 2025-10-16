<?php

declare(strict_types=1);

namespace Fsm\Contracts;

use Fsm\TransitionBuilder;

/**
 * Contract for FSM extensions that can modify existing FSM definitions.
 *
 * Extensions allow for modular enhancement of FSMs by adding states,
 * transitions, or modifying existing ones without touching the original
 * definition class.
 */
interface FsmExtension
{
    /**
     * Apply the extension to the FSM definition.
     *
     * @param  string  $modelClass  The model class this FSM is for
     * @param  string  $columnName  The column name this FSM manages
     * @param  TransitionBuilder  $builder  The transition builder to modify
     */
    public function extend(string $modelClass, string $columnName, TransitionBuilder $builder): void;

    /**
     * Determines if this extension should be applied to the given FSM.
     *
     * @param  string  $modelClass  The model class this FSM is for
     * @param  string  $columnName  The column name this FSM manages
     */
    public function appliesTo(string $modelClass, string $columnName): bool;

    /**
     * Get the priority of this extension. Higher numbers execute first.
     */
    public function getPriority(): int;

    /**
     * Get a unique identifier for this extension.
     */
    public function getName(): string;
}
