<?php

namespace Fsm\Contracts;

/**
 * Marker interface for FSM event enums.
 *
 * Implementations are expected to be string-backed enums whose `value`
 * matches the event identifier as registered with `TransitionBuilder::event()`.
 *
 * Example:
 *   enum MyEvent: string implements FsmEventEnum {
 *       case Start = 'start';
 *   }
 *
 * @property-read string $value The string event identifier
 */
interface FsmEventEnum
{
    // Marker only. Events are programmatic identifiers, not user-facing values,
    // so they need no `displayName()`/`icon()` analog to FsmStateEnum. If a future
    // debug viewer needs labels, add a separate extension interface (e.g.,
    // FsmEventDisplay) so consumers can opt in without breaking back-compat.
}
