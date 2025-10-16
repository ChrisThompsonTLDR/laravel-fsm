<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm;

use Fsm\Contracts\FsmStateEnum;
use Fsm\Traits\StateNameStringConversion;
use PHPUnit\Framework\TestCase;

// Dummy enum for testing
enum DummyState: string implements FsmStateEnum
{
    case Foo = 'foo';
    case Bar = 'bar';

    public function displayName(): string
    {
        return $this->value;
    }

    public function icon(): string
    {
        return '';
    }
}

class StateNameStringConversionTest extends TestCase
{
    use StateNameStringConversion;

    public function test_state_to_string_with_enum(): void
    {
        $this->assertSame('foo', self::stateToString(DummyState::Foo));
        $this->assertSame('bar', self::stateToString(DummyState::Bar));
    }

    public function test_state_to_string_with_string(): void
    {
        $this->assertSame('baz', self::stateToString('baz'));
        $this->assertSame('', self::stateToString(''));
    }

    public function test_state_to_string_with_null(): void
    {
        $this->assertNull(self::stateToString(null));
    }
}
