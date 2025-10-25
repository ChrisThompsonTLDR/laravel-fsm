<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionGuard;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class TransitionGuardCallableArrayBugFixTest extends TestCase
{
    public function test_callable_array_is_treated_as_positional_parameter(): void
    {
        // This was the bug: callable arrays like ['ClassName', 'method'] were being
        // misinterpreted as associative arrays for DTO construction
        $guard = new TransitionGuard(['MyService', 'handle']);

        $this->assertEquals(['MyService', 'handle'], $guard->callable);
        $this->assertEquals([], $guard->parameters);
        $this->assertNull($guard->description);
        $this->assertEquals(TransitionGuard::PRIORITY_NORMAL, $guard->priority);
        $this->assertFalse($guard->stopOnFailure);
        $this->assertNull($guard->name);
        $this->assertEquals(TransitionGuard::TYPE_CALLABLE, $guard->getType());
    }

    public function test_callable_array_with_object_instance(): void
    {
        $object = new \stdClass;
        $guard = new TransitionGuard([$object, 'method']);

        $this->assertEquals([$object, 'method'], $guard->callable);
        $this->assertEquals(TransitionGuard::TYPE_CALLABLE, $guard->getType());
    }

    public function test_dto_property_array_is_used_for_construction(): void
    {
        // This should work as before - associative arrays with DTO property keys
        $data = [
            'callable' => 'MyService@handle',
            'parameters' => ['param1' => 'value1'],
            'description' => 'Test guard',
            'priority' => 75,
            'stopOnFailure' => true,
            'name' => 'test-guard',
        ];

        $guard = new TransitionGuard($data);

        $this->assertEquals('MyService@handle', $guard->callable);
        $this->assertEquals(['param1' => 'value1'], $guard->parameters);
        $this->assertEquals('Test guard', $guard->description);
        $this->assertEquals(75, $guard->priority);
        $this->assertTrue($guard->stopOnFailure);
        $this->assertEquals('test-guard', $guard->name);
    }

    public function test_mixed_keys_array_with_dto_properties_works(): void
    {
        // Mixed keys array that has DTO property keys should work
        $data = [
            'callable' => 'MyService@handle',
            0 => 'ignored value',
            'description' => 'Test guard',
            'priority' => 50,
        ];

        $guard = new TransitionGuard($data);

        $this->assertEquals('MyService@handle', $guard->callable);
        $this->assertEquals('Test guard', $guard->description);
        $this->assertEquals(50, $guard->priority);
    }

    public function test_invalid_array_throws_exception(): void
    {
        // Arrays that are neither callable arrays nor DTO property arrays should throw
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array parameter must be either a callable array [class, method] or an associative array with DTO property keys.');

        new TransitionGuard(['invalid', 'array', 'with', 'too', 'many', 'elements']);
    }

    public function test_empty_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array parameter must be either a callable array [class, method] or an associative array with DTO property keys.');

        new TransitionGuard([]);
    }

    public function test_array_without_dto_properties_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array parameter must be either a callable array [class, method] or an associative array with DTO property keys.');

        new TransitionGuard(['unexpected' => 'key', 'other' => 'value']);
    }

    public function test_positional_construction_still_works(): void
    {
        // Ensure positional construction still works
        $guard = new TransitionGuard(
            'MyService@handle',
            ['param' => 'value'],
            'Test guard',
            75,
            true,
            'test-guard'
        );

        $this->assertEquals('MyService@handle', $guard->callable);
        $this->assertEquals(['param' => 'value'], $guard->parameters);
        $this->assertEquals('Test guard', $guard->description);
        $this->assertEquals(75, $guard->priority);
        $this->assertTrue($guard->stopOnFailure);
        $this->assertEquals('test-guard', $guard->name);
    }

    public function test_closure_construction_still_works(): void
    {
        $closure = function () {
            return true;
        };

        $guard = new TransitionGuard($closure);

        $this->assertSame($closure, $guard->callable);
        $this->assertEquals(TransitionGuard::TYPE_CLOSURE, $guard->getType());
    }

    public function test_string_callable_construction_still_works(): void
    {
        $guard = new TransitionGuard('MyService@handle');

        $this->assertEquals('MyService@handle', $guard->callable);
        $this->assertEquals(TransitionGuard::TYPE_SERVICE, $guard->getType());
    }

    public function test_callable_array_with_extra_parameters(): void
    {
        // When using callable array as single argument, other parameters should be ignored
        $guard = new TransitionGuard(['MyService', 'handle']);

        $this->assertEquals(['MyService', 'handle'], $guard->callable);
        $this->assertEquals([], $guard->parameters);
        $this->assertNull($guard->description);
    }

    public function test_callable_array_with_positional_parameters(): void
    {
        // When using callable array with positional parameters, they should be used
        $guard = new TransitionGuard(['MyService', 'handle'], ['param' => 'value'], 'Description');

        $this->assertEquals(['MyService', 'handle'], $guard->callable);
        $this->assertEquals(['param' => 'value'], $guard->parameters);
        $this->assertEquals('Description', $guard->description);
    }

    public function test_display_name_for_callable_array(): void
    {
        $guard = new TransitionGuard(['MyService', 'handle']);

        $this->assertEquals('MyService::handle', $guard->getDisplayName());
    }

    public function test_display_name_for_callable_array_with_object(): void
    {
        $object = new \stdClass;
        $guard = new TransitionGuard([$object, 'method']);

        $this->assertEquals('stdClass::method', $guard->getDisplayName());
    }
}
