<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm;

use Fsm\Contracts\FsmStateEnum;

enum TestStates: string implements FsmStateEnum
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case OnHold = 'on_hold';

    public function displayName(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }

    public function icon(): string
    {
        return 'icon-'.$this->value;
    }
}
