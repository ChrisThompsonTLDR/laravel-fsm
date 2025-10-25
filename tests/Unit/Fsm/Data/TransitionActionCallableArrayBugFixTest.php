<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionAction;
use PHPUnit\Framework\TestCase;

class TransitionActionCallableArrayBugFixTest extends TestCase
{
    /**
     * Test that callable arrays are not misinterpreted as associative arrays for DTO construction.
     */
    public function test_callable_array_is_not_misinterpreted_as_associative_array(): void
    {
        // This should work correctly - callable array should be treated as callable parameter
        $action = new TransitionAction(['MyClass', 'method']);

        $this->assertInstanceOf(TransitionAction::class, $action);
        $this->assertEquals(['MyClass', 'method'], $action->callable);
        $this->assertEquals([], $action->parameters);
        $this->assertTrue($action->runAfterTransition);
        $this->assertEquals(TransitionAction::TIMING_AFTER, $action->timing);
        $this->assertEquals(TransitionAction::PRIORITY_NORMAL, $action->priority);
        $this->assertNull($action->name);
        $this->assertFalse($action->queued);
    }

    /**
     * Test that simple arrays with sequential keys are not misinterpreted as associative arrays.
     */
    public function test_simple_array_with_sequential_keys_is_not_misinterpreted(): void
    {
        // This should work correctly - simple array should be treated as callable parameter
        $action = new TransitionAction(['A', 'B', 'C']);

        $this->assertInstanceOf(TransitionAction::class, $action);
        $this->assertEquals(['A', 'B', 'C'], $action->callable);
        $this->assertEquals([], $action->parameters);
    }

    /**
     * Test that associative arrays with DTO property keys are correctly handled for DTO construction.
     */
    public function test_associative_array_with_dto_property_keys_is_handled_correctly(): void
    {
        // This should work correctly - associative array with DTO properties should be treated as DTO construction
        $action = new TransitionAction([
            'callable' => 'MyClass@method',
            'parameters' => ['param1' => 'value1'],
            'runAfterTransition' => false,
            'timing' => TransitionAction::TIMING_BEFORE,
            'priority' => TransitionAction::PRIORITY_HIGH,
            'name' => 'test-action',
            'queued' => true,
        ]);

        $this->assertInstanceOf(TransitionAction::class, $action);
        $this->assertEquals('MyClass@method', $action->callable);
        $this->assertEquals(['param1' => 'value1'], $action->parameters);
        $this->assertFalse($action->runAfterTransition);
        $this->assertEquals(TransitionAction::TIMING_BEFORE, $action->timing);
        $this->assertEquals(TransitionAction::PRIORITY_HIGH, $action->priority);
        $this->assertEquals('test-action', $action->name);
        $this->assertTrue($action->queued);
    }

    /**
     * Test that associative arrays with snake_case keys are correctly handled.
     */
    public function test_associative_array_with_snake_case_keys_is_handled_correctly(): void
    {
        // This should work correctly - snake_case keys should be normalized to camelCase
        $action = new TransitionAction([
            'callable' => 'MyClass@method',
            'parameters' => ['param1' => 'value1'],
            'run_after_transition' => false, // snake_case
            'timing' => TransitionAction::TIMING_BEFORE,
            'priority' => TransitionAction::PRIORITY_HIGH,
            'name' => 'test-action',
            'queued' => true,
        ]);

        $this->assertInstanceOf(TransitionAction::class, $action);
        $this->assertEquals('MyClass@method', $action->callable);
        $this->assertEquals(['param1' => 'value1'], $action->parameters);
        $this->assertFalse($action->runAfterTransition); // Should be normalized to camelCase
        $this->assertEquals(TransitionAction::TIMING_BEFORE, $action->timing);
        $this->assertEquals(TransitionAction::PRIORITY_HIGH, $action->priority);
        $this->assertEquals('test-action', $action->name);
        $this->assertTrue($action->queued);
    }

    /**
     * Test that positional parameters work correctly.
     */
    public function test_positional_parameters_work_correctly(): void
    {
        $action = new TransitionAction(
            'MyClass@method',
            ['param1' => 'value1'],
            false,
            TransitionAction::TIMING_BEFORE,
            TransitionAction::PRIORITY_HIGH,
            'test-action',
            true
        );

        $this->assertInstanceOf(TransitionAction::class, $action);
        $this->assertEquals('MyClass@method', $action->callable);
        $this->assertEquals(['param1' => 'value1'], $action->parameters);
        $this->assertFalse($action->runAfterTransition);
        $this->assertEquals(TransitionAction::TIMING_BEFORE, $action->timing);
        $this->assertEquals(TransitionAction::PRIORITY_HIGH, $action->priority);
        $this->assertEquals('test-action', $action->name);
        $this->assertTrue($action->queued);
    }

    /**
     * Test that closure callables work correctly.
     */
    public function test_closure_callable_works_correctly(): void
    {
        $closure = function () {
            return true;
        };

        $action = new TransitionAction($closure);

        $this->assertInstanceOf(TransitionAction::class, $action);
        $this->assertSame($closure, $action->callable);
        $this->assertEquals([], $action->parameters);
    }

    /**
     * Test that string callables work correctly.
     */
    public function test_string_callable_works_correctly(): void
    {
        $action = new TransitionAction('MyClass@method');

        $this->assertInstanceOf(TransitionAction::class, $action);
        $this->assertEquals('MyClass@method', $action->callable);
        $this->assertEquals([], $action->parameters);
    }

    /**
     * Test that associative arrays without DTO property keys are treated as callable parameters.
     */
    public function test_associative_array_without_dto_property_keys_is_treated_as_callable_parameter(): void
    {
        // This should work - associative array without DTO property keys should be treated as callable parameter
        $action = new TransitionAction([
            'some_other_key' => 'value',
            'another_key' => 'another_value',
        ]);

        $this->assertInstanceOf(TransitionAction::class, $action);
        $this->assertEquals([
            'some_other_key' => 'value',
            'another_key' => 'another_value',
        ], $action->callable);
        $this->assertEquals([], $action->parameters);
    }

    /**
     * Test edge case: empty array should be treated as callable parameter.
     */
    public function test_empty_array_is_treated_as_callable_parameter(): void
    {
        $action = new TransitionAction([]);

        $this->assertInstanceOf(TransitionAction::class, $action);
        $this->assertEquals([], $action->callable);
        $this->assertEquals([], $action->parameters);
    }

    /**
     * Test edge case: array with single element should be treated as callable parameter.
     */
    public function test_array_with_single_element_is_treated_as_callable_parameter(): void
    {
        $action = new TransitionAction(['MyClass']);

        $this->assertInstanceOf(TransitionAction::class, $action);
        $this->assertEquals(['MyClass'], $action->callable);
        $this->assertEquals([], $action->parameters);
    }

    /**
     * Test that verb class strings work correctly.
     */
    public function test_verb_class_string_works_correctly(): void
    {
        // Use a class that actually exists
        $action = new TransitionAction(\stdClass::class);

        $this->assertInstanceOf(TransitionAction::class, $action);
        $this->assertEquals(\stdClass::class, $action->callable);
        $this->assertEquals([], $action->parameters);
        $this->assertEquals(TransitionAction::TYPE_VERB, $action->getType());
    }
}
