<?php

namespace Tests\Feature\TrafficLight\Enums;

use Fsm\Contracts\FsmStateEnum;

enum TrafficLightState: string implements FsmStateEnum
{
    case Red = 'red';
    case Yellow = 'yellow';
    case Green = 'green';

    public function displayName(): string
    {
        return ucfirst($this->value);
    }

    public function icon(): string
    {
        return match($this) {
            self::Red => '🔴',
            self::Yellow => '🟡',
            self::Green => '🟢',
        };
    }
}
