<?php

namespace Fsm\Contracts;

/**
 * Marker interface for FSM event enums.
 *
 * Extends PHP's built-in {@see \BackedEnum} so the type system enforces
 * what the package internally assumes: the `$value` property exists and
 * is the string event identifier registered with `TransitionBuilder::event()`.
 * Any class attempting to implement this interface that is not a backed enum
 * will fail at parse time, not at runtime.
 *
 * Example:
 *   enum MyEvent: string implements FsmEventEnum {
 *       case Start = 'start';
 *   }
 */
interface FsmEventEnum extends \BackedEnum
{
    // Marker only. Events are programmatic identifiers, not user-facing values,
    // so they need no `displayName()`/`icon()` analog to FsmStateEnum. If a future
    // debug viewer needs labels, add a separate extension interface (e.g.,
    // FsmEventDisplay) so consumers can opt in without breaking back-compat.
}
