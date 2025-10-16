<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionAction;
use PHPUnit\Framework\TestCase;

/**
 * Test for TransitionAction constructor bug fix.
 *
 * Tests that array callables and simple arrays are not misinterpreted as associative arrays.
 */
class TransitionActionConstructorBugFixTest extends TestCase
{
    public function test_array_callable_is_treated_as_callable_not_associative_array(): void
    {
        // Array callable should be treated as a callable, not as array-based construction
        $action = new TransitionAction(['MyClass', 'method']);

        $this->assertSame(['MyClass', 'method'], $action->callable);
        $this->assertSame([], $action->parameters);
        $this->assertTrue($action->runAfterTransition);
        $this->assertSame(TransitionAction::TIMING_AFTER, $action->timing);
        $this->assertSame(TransitionAction::PRIORITY_NORMAL, $action->priority);
        $this->assertNull($action->name);
        $this->assertFalse($action->queued);
    }

    public function test_simple_array_is_treated_as_callable_not_associative_array(): void
    {
        // Simple array should be treated as a callable, not as array-based construction
        $action = new TransitionAction(['value1', 'value2']);

        $this->assertSame(['value1', 'value2'], $action->callable);
        $this->assertSame([], $action->parameters);
        $this->assertTrue($action->runAfterTransition);
        $this->assertSame(TransitionAction::TIMING_AFTER, $action->timing);
        $this->assertSame(TransitionAction::PRIORITY_NORMAL, $action->priority);
        $this->assertNull($action->name);
        $this->assertFalse($action->queued);
    }

    public function test_associative_array_with_expected_keys_is_treated_as_array_construction(): void
    {
        // Associative array with expected keys should be treated as array-based construction
        $action = new TransitionAction([
            'callable' => 'MyClass@method',
            'parameters' => ['param1' => 'value1'],
            'runAfterTransition' => false,
            'timing' => TransitionAction::TIMING_BEFORE,
            'priority' => TransitionAction::PRIORITY_HIGH,
            'name' => 'Test Action',
            'queued' => true,
        ]);

        $this->assertSame('MyClass@method', $action->callable);
        $this->assertSame(['param1' => 'value1'], $action->parameters);
        $this->assertFalse($action->runAfterTransition);
        $this->assertSame(TransitionAction::TIMING_BEFORE, $action->timing);
        $this->assertSame(TransitionAction::PRIORITY_HIGH, $action->priority);
        $this->assertSame('Test Action', $action->name);
        $this->assertTrue($action->queued);
    }

    public function test_associative_array_without_expected_keys_is_treated_as_callable(): void
    {
        // Associative array without expected keys should be treated as a callable
        $action = new TransitionAction([
            'some_key' => 'some_value',
            'another_key' => 'another_value',
        ]);

        $this->assertSame([
            'some_key' => 'some_value',
            'another_key' => 'another_value',
        ], $action->callable);
        $this->assertSame([], $action->parameters);
        $this->assertTrue($action->runAfterTransition);
        $this->assertSame(TransitionAction::TIMING_AFTER, $action->timing);
        $this->assertSame(TransitionAction::PRIORITY_NORMAL, $action->priority);
        $this->assertNull($action->name);
        $this->assertFalse($action->queued);
    }

    public function test_string_callable_works_correctly(): void
    {
        $action = new TransitionAction('MyClass@method');

        $this->assertSame('MyClass@method', $action->callable);
        $this->assertSame([], $action->parameters);
        $this->assertTrue($action->runAfterTransition);
        $this->assertSame(TransitionAction::TIMING_AFTER, $action->timing);
        $this->assertSame(TransitionAction::PRIORITY_NORMAL, $action->priority);
        $this->assertNull($action->name);
        $this->assertFalse($action->queued);
    }

    public function test_closure_callable_works_correctly(): void
    {
        $closure = function () {
            return 'test';
        };

        $action = new TransitionAction($closure);

        $this->assertSame($closure, $action->callable);
        $this->assertSame([], $action->parameters);
        $this->assertTrue($action->runAfterTransition);
        $this->assertSame(TransitionAction::TIMING_AFTER, $action->timing);
        $this->assertSame(TransitionAction::PRIORITY_NORMAL, $action->priority);
        $this->assertNull($action->name);
        $this->assertFalse($action->queued);
    }

    public function test_positional_parameters_work_correctly(): void
    {
        $action = new TransitionAction(
            callable: 'MyClass@method',
            parameters: ['param1' => 'value1'],
            runAfterTransition: false,
            timing: TransitionAction::TIMING_BEFORE,
            priority: TransitionAction::PRIORITY_HIGH,
            name: 'Test Action',
            queued: true,
        );

        $this->assertSame('MyClass@method', $action->callable);
        $this->assertSame(['param1' => 'value1'], $action->parameters);
        $this->assertFalse($action->runAfterTransition);
        $this->assertSame(TransitionAction::TIMING_BEFORE, $action->timing);
        $this->assertSame(TransitionAction::PRIORITY_HIGH, $action->priority);
        $this->assertSame('Test Action', $action->name);
        $this->assertTrue($action->queued);
    }
}
