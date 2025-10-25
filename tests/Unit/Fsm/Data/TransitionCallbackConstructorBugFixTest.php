<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionCallback;
use PHPUnit\Framework\TestCase;

/**
 * Test for TransitionCallback constructor bug fix.
 *
 * Tests that array callables and simple arrays are not misinterpreted as associative arrays.
 */
class TransitionCallbackConstructorBugFixTest extends TestCase
{
    public function test_array_callable_is_treated_as_callable_not_associative_array(): void
    {
        // Array callable should be treated as a callable, not as array-based construction
        $callback = new TransitionCallback(['MyClass', 'method']);

        $this->assertSame(['MyClass', 'method'], $callback->callable);
        $this->assertSame([], $callback->parameters);
        $this->assertFalse($callback->runAfterTransition);
        $this->assertSame(TransitionCallback::TIMING_AFTER_SAVE, $callback->timing);
        $this->assertSame(TransitionCallback::PRIORITY_NORMAL, $callback->priority);
        $this->assertNull($callback->name);
        $this->assertTrue($callback->continueOnFailure);
        $this->assertFalse($callback->queued);
    }

    public function test_simple_array_is_treated_as_callable_not_associative_array(): void
    {
        // Simple array should be treated as a callable, not as array-based construction
        $callback = new TransitionCallback(['value1', 'value2']);

        $this->assertSame(['value1', 'value2'], $callback->callable);
        $this->assertSame([], $callback->parameters);
        $this->assertFalse($callback->runAfterTransition);
        $this->assertSame(TransitionCallback::TIMING_AFTER_SAVE, $callback->timing);
        $this->assertSame(TransitionCallback::PRIORITY_NORMAL, $callback->priority);
        $this->assertNull($callback->name);
        $this->assertTrue($callback->continueOnFailure);
        $this->assertFalse($callback->queued);
    }

    public function test_associative_array_with_expected_keys_is_treated_as_array_construction(): void
    {
        // Associative array with expected keys should be treated as array-based construction
        $callback = new TransitionCallback([
            'callable' => 'MyClass@method',
            'parameters' => ['param1' => 'value1'],
            'runAfterTransition' => true,
            'timing' => TransitionCallback::TIMING_ON_ENTRY,
            'priority' => TransitionCallback::PRIORITY_HIGH,
            'name' => 'Test Callback',
            'continueOnFailure' => false,
            'queued' => true,
        ]);

        $this->assertSame('MyClass@method', $callback->callable);
        $this->assertSame(['param1' => 'value1'], $callback->parameters);
        $this->assertTrue($callback->runAfterTransition);
        $this->assertSame(TransitionCallback::TIMING_ON_ENTRY, $callback->timing);
        $this->assertSame(TransitionCallback::PRIORITY_HIGH, $callback->priority);
        $this->assertSame('Test Callback', $callback->name);
        $this->assertFalse($callback->continueOnFailure);
        $this->assertTrue($callback->queued);
    }

    public function test_associative_array_without_expected_keys_is_treated_as_callable(): void
    {
        // Associative array without expected keys should be treated as a callable
        $callback = new TransitionCallback([
            'some_key' => 'some_value',
            'another_key' => 'another_value',
        ]);

        $this->assertSame([
            'some_key' => 'some_value',
            'another_key' => 'another_value',
        ], $callback->callable);
        $this->assertSame([], $callback->parameters);
        $this->assertFalse($callback->runAfterTransition);
        $this->assertSame(TransitionCallback::TIMING_AFTER_SAVE, $callback->timing);
        $this->assertSame(TransitionCallback::PRIORITY_NORMAL, $callback->priority);
        $this->assertNull($callback->name);
        $this->assertTrue($callback->continueOnFailure);
        $this->assertFalse($callback->queued);
    }

    public function test_string_callable_works_correctly(): void
    {
        $callback = new TransitionCallback('MyClass@method');

        $this->assertSame('MyClass@method', $callback->callable);
        $this->assertSame([], $callback->parameters);
        $this->assertFalse($callback->runAfterTransition);
        $this->assertSame(TransitionCallback::TIMING_AFTER_SAVE, $callback->timing);
        $this->assertSame(TransitionCallback::PRIORITY_NORMAL, $callback->priority);
        $this->assertNull($callback->name);
        $this->assertTrue($callback->continueOnFailure);
        $this->assertFalse($callback->queued);
    }

    public function test_closure_callable_works_correctly(): void
    {
        $closure = function () {
            return 'test';
        };

        $callback = new TransitionCallback($closure);

        $this->assertSame($closure, $callback->callable);
        $this->assertSame([], $callback->parameters);
        $this->assertFalse($callback->runAfterTransition);
        $this->assertSame(TransitionCallback::TIMING_AFTER_SAVE, $callback->timing);
        $this->assertSame(TransitionCallback::PRIORITY_NORMAL, $callback->priority);
        $this->assertNull($callback->name);
        $this->assertTrue($callback->continueOnFailure);
        $this->assertFalse($callback->queued);
    }

    public function test_positional_parameters_work_correctly(): void
    {
        $callback = new TransitionCallback(
            callable: 'MyClass@method',
            parameters: ['param1' => 'value1'],
            runAfterTransition: true,
            timing: TransitionCallback::TIMING_ON_ENTRY,
            priority: TransitionCallback::PRIORITY_HIGH,
            name: 'Test Callback',
            continueOnFailure: false,
            queued: true,
        );

        $this->assertSame('MyClass@method', $callback->callable);
        $this->assertSame(['param1' => 'value1'], $callback->parameters);
        $this->assertTrue($callback->runAfterTransition);
        $this->assertSame(TransitionCallback::TIMING_ON_ENTRY, $callback->timing);
        $this->assertSame(TransitionCallback::PRIORITY_HIGH, $callback->priority);
        $this->assertSame('Test Callback', $callback->name);
        $this->assertFalse($callback->continueOnFailure);
        $this->assertTrue($callback->queued);
    }
}
