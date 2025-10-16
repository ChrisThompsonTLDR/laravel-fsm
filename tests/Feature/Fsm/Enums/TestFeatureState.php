<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Enums;

use Fsm\Contracts\FsmStateEnum;

enum TestFeatureState: string implements FsmStateEnum
{
    case Idle = 'idle';
    case Pending = 'pending';
    case Processing = 'processing';
    case Active = 'active';
    case Running = 'running';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case Shipped = 'shipped';

    public function displayName(): string
    {
        return ucfirst($this->value);
    }

    public function icon(): string
    {
        return 'icon-'.$this->value;
    }
}
