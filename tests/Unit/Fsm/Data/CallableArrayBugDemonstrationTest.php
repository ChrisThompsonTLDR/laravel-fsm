<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionAction;
use Fsm\Data\TransitionCallback;
use Fsm\Data\TransitionGuard;
use PHPUnit\Framework\TestCase;

/**
 * Demonstration test showing the specific bug that was fixed.
 *
 * This test documents the core issue: callable arrays like ['MyClass', 'method']
 * were being misinterpreted as DTO property arrays in some cases.
 */
class CallableArrayBugDemonstrationTest extends TestCase
{
    /**
     * THE CORE BUG: Callable arrays should be assigned to the callable parameter,
     * not misinterpreted as DTO property arrays.
     */
    public function test_callable_array_should_not_be_misinterpreted_as_dto_properties(): void
    {
        // This is a callable array - it should be treated as the callable parameter
        $callableArray = ['MyService', 'handleAction'];

        $action = new TransitionAction($callableArray);

        // ✅ EXPECTED: The callable array should be assigned to the callable property
        $this->assertSame($callableArray, $action->callable);

        // ✅ EXPECTED: Other properties should have their default values
        $this->assertSame([], $action->parameters);
        $this->assertTrue($action->runAfterTransition);
        $this->assertSame(TransitionAction::TIMING_AFTER, $action->timing);
        $this->assertSame(TransitionAction::PRIORITY_NORMAL, $action->priority);
        $this->assertNull($action->name);
        $this->assertFalse($action->queued);

        // ❌ BUG (now fixed): The old logic might have tried to use 'MyService' and 'handleAction'
        // as DTO property keys, leading to:
        // - callable = null (or undefined)
        // - Unexpected property assignments
        // - Potential errors during instantiation
    }

    /**
     * Verify the fix works for TransitionCallback too.
     */
    public function test_callback_callable_array_is_treated_correctly(): void
    {
        $callableArray = ['MyService', 'handleCallback'];

        $callback = new TransitionCallback($callableArray);

        $this->assertSame($callableArray, $callback->callable);
        $this->assertSame([], $callback->parameters);
        $this->assertFalse($callback->runAfterTransition);
        $this->assertSame(TransitionCallback::TIMING_AFTER_SAVE, $callback->timing);
    }

    /**
     * Verify the fix works for TransitionGuard too (which already had the correct logic).
     */
    public function test_guard_callable_array_is_treated_correctly(): void
    {
        $callableArray = ['MyService', 'checkGuard'];

        $guard = new TransitionGuard($callableArray);

        $this->assertSame($callableArray, $guard->callable);
        $this->assertSame([], $guard->parameters);
    }

    /**
     * Verify that legitimate DTO property arrays still work correctly.
     */
    public function test_dto_property_arrays_still_work_correctly(): void
    {
        // This IS a DTO property array - it should be used for construction
        $action = new TransitionAction([
            'callable' => 'MyService@handle',
            'priority' => TransitionAction::PRIORITY_HIGH,
            'name' => 'My Action',
        ]);

        $this->assertSame('MyService@handle', $action->callable);
        $this->assertSame(TransitionAction::PRIORITY_HIGH, $action->priority);
        $this->assertSame('My Action', $action->name);
    }

    /**
     * The distinguishing factor: DTO property arrays have known property keys,
     * while callable arrays have numeric keys [0, 1].
     */
    public function test_distinguishing_callable_arrays_from_dto_arrays(): void
    {
        // Callable array: numeric keys [0, 1]
        $callableArray = ['MyService', 'method'];
        $action1 = new TransitionAction($callableArray);
        $this->assertSame($callableArray, $action1->callable);

        // DTO array: string keys matching property names
        $dtoArray = [
            'callable' => 'MyService@method',
            'priority' => 100,
        ];
        $action2 = new TransitionAction($dtoArray);
        $this->assertSame('MyService@method', $action2->callable);
        $this->assertSame(100, $action2->priority);

        // The fix ensures these are never confused!
    }

    /**
     * Edge case: Empty arrays and single-element arrays should also work.
     */
    public function test_edge_cases_are_handled_correctly(): void
    {
        // Empty array as callable
        $action1 = new TransitionAction([]);
        $this->assertSame([], $action1->callable);

        // Single element array as callable
        $action2 = new TransitionAction(['SingleValue']);
        $this->assertSame(['SingleValue'], $action2->callable);

        // These should NOT be misinterpreted as DTO property arrays
    }
}
