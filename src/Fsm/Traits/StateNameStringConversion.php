<?php

declare(strict_types=1);

namespace Fsm\Traits;

use Fsm\Contracts\FsmStateEnum;

trait StateNameStringConversion
{
    /**
     * Convert a state (enum|string|null) to string (or null).
     */
    protected static function stateToString(FsmStateEnum|string|null $state): ?string
    {
        if ($state === null) {
            return null;
        }
        if ($state instanceof FsmStateEnum) {
            return $state->value;
        }

        return $state; // $state is guaranteed to be string here
    }
}
