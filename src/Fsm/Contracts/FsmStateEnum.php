<?php

namespace Fsm\Contracts;

/**
 * Interface for FSM state enums
 *
 * All implementations must be string-backed enums that automatically
 * provide the $value property.
 *
 * @property-read string $value The string value of the enum case
 */
interface FsmStateEnum
{
    // This interface marks an enum as an FSM state type.
    // All concrete implementations are expected to be string-backed enums.
    // Example: enum MyState: string implements FsmStateEnum

    public function displayName(): string;

    public function icon(): string;
}
