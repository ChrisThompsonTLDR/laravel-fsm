<?php

declare(strict_types=1);

namespace Fsm\Contracts;

use Fsm\FsmBuilder;

/**
 * Contract for defining FSM configurations.
 */
interface FsmDefinition
{
    /**
     * Define the FSM configuration.
     * This method should use FsmBuilder::for() to define states and transitions.
     */
    public function define(): void;
}
