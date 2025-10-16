<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Exceptions;

use Fsm\Contracts\FsmStateEnum;

/**
 * Simple enum used across unit tests.
 */
enum MockState: string implements FsmStateEnum
{
    case Pending = 'pending';
    case Done = 'done';
    case Processing = 'processing';

    public function displayName(): string
    {
        return ucfirst($this->value);
    }

    public function icon(): string
    {
        return 'icon-'.$this->value;
    }
}
