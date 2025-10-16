<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionCallback;
use PHPUnit\Framework\TestCase;

class TransitionCallbackCallableArrayBugFixTest extends TestCase
{
    /**
     * Test that callable arrays are not misinterpreted as associative arrays for DTO construction.
     */
    public function test_callable_array_is_not_misinterpreted_as_associative_array(): void
    {
        // This should work correctly - callable array should be treated as callable parameter
        $callback = new TransitionCallback(['MyClass', 'method']);

        $this->assertInstanceOf(TransitionCallback::class, $callback);
        $this->assertEquals(['MyClass', 'method'], $callback->callable);
        $this->assertEquals([], $callback->parameters);
        $this->assertFalse($callback->runAfterTransition);
        $this->assertEquals(TransitionCallback::TIMING_AFTER_SAVE, $callback->timing);
        $this->assertEquals(TransitionCallback::PRIORITY_NORMAL, $callback->priority);
        $this->assertNull($callback->name);
        $this->assertTrue($callback->continueOnFailure);
        $this->assertFalse($callback->queued);
    }

    /**
     * Test that simple arrays with sequential keys are not misinterpreted as associative arrays.
     */
    public function test_simple_array_with_sequential_keys_is_not_misinterpreted(): void
    {
        // This should work correctly - simple array should be treated as callable parameter
        $callback = new TransitionCallback(['A', 'B', 'C']);

        $this->assertInstanceOf(TransitionCallback::class, $callback);
        $this->assertEquals(['A', 'B', 'C'], $callback->callable);
        $this->assertEquals([], $callback->parameters);
    }

    /**
     * Test that associative arrays with DTO property keys are correctly handled for DTO construction.
     */
    public function test_associative_array_with_dto_property_keys_is_handled_correctly(): void
    {
        // This should work correctly - associative array with DTO properties should be treated as DTO construction
        $callback = new TransitionCallback([
            'callable' => 'MyClass@method',
            'parameters' => ['param1' => 'value1'],
            'runAfterTransition' => true,
            'timing' => TransitionCallback::TIMING_ON_ENTRY,
            'priority' => TransitionCallback::PRIORITY_HIGH,
            'name' => 'test-callback',
            'continueOnFailure' => false,
            'queued' => true,
        ]);

        $this->assertInstanceOf(TransitionCallback::class, $callback);
        $this->assertEquals('MyClass@method', $callback->callable);
        $this->assertEquals(['param1' => 'value1'], $callback->parameters);
        $this->assertTrue($callback->runAfterTransition);
        $this->assertEquals(TransitionCallback::TIMING_ON_ENTRY, $callback->timing);
        $this->assertEquals(TransitionCallback::PRIORITY_HIGH, $callback->priority);
        $this->assertEquals('test-callback', $callback->name);
        $this->assertFalse($callback->continueOnFailure);
        $this->assertTrue($callback->queued);
    }

    /**
     * Test that associative arrays with snake_case keys are correctly handled.
     */
    public function test_associative_array_with_snake_case_keys_is_handled_correctly(): void
    {
        // This should work correctly - snake_case keys should be normalized to camelCase
        $callback = new TransitionCallback([
            'callable' => 'MyClass@method',
            'parameters' => ['param1' => 'value1'],
            'run_after_transition' => true, // snake_case
            'timing' => TransitionCallback::TIMING_ON_ENTRY,
            'priority' => TransitionCallback::PRIORITY_HIGH,
            'name' => 'test-callback',
            'continue_on_failure' => false, // snake_case
            'queued' => true,
        ]);

        $this->assertInstanceOf(TransitionCallback::class, $callback);
        $this->assertEquals('MyClass@method', $callback->callable);
        $this->assertEquals(['param1' => 'value1'], $callback->parameters);
        $this->assertTrue($callback->runAfterTransition); // Should be normalized to camelCase
        $this->assertEquals(TransitionCallback::TIMING_ON_ENTRY, $callback->timing);
        $this->assertEquals(TransitionCallback::PRIORITY_HIGH, $callback->priority);
        $this->assertEquals('test-callback', $callback->name);
        $this->assertFalse($callback->continueOnFailure); // Should be normalized to camelCase
        $this->assertTrue($callback->queued);
    }

    /**
     * Test that positional parameters work correctly.
     */
    public function test_positional_parameters_work_correctly(): void
    {
        $callback = new TransitionCallback(
            'MyClass@method',
            ['param1' => 'value1'],
            true,
            TransitionCallback::TIMING_ON_ENTRY,
            TransitionCallback::PRIORITY_HIGH,
            'test-callback',
            false,
            true
        );

        $this->assertInstanceOf(TransitionCallback::class, $callback);
        $this->assertEquals('MyClass@method', $callback->callable);
        $this->assertEquals(['param1' => 'value1'], $callback->parameters);
        $this->assertTrue($callback->runAfterTransition);
        $this->assertEquals(TransitionCallback::TIMING_ON_ENTRY, $callback->timing);
        $this->assertEquals(TransitionCallback::PRIORITY_HIGH, $callback->priority);
        $this->assertEquals('test-callback', $callback->name);
        $this->assertFalse($callback->continueOnFailure);
        $this->assertTrue($callback->queued);
    }

    /**
     * Test that closure callables work correctly.
     */
    public function test_closure_callable_works_correctly(): void
    {
        $closure = function () {
            return true;
        };

        $callback = new TransitionCallback($closure);

        $this->assertInstanceOf(TransitionCallback::class, $callback);
        $this->assertSame($closure, $callback->callable);
        $this->assertEquals([], $callback->parameters);
    }

    /**
     * Test that string callables work correctly.
     */
    public function test_string_callable_works_correctly(): void
    {
        $callback = new TransitionCallback('MyClass@method');

        $this->assertInstanceOf(TransitionCallback::class, $callback);
        $this->assertEquals('MyClass@method', $callback->callable);
        $this->assertEquals([], $callback->parameters);
    }

    /**
     * Test that associative arrays without DTO property keys are treated as callable parameters.
     */
    public function test_associative_array_without_dto_property_keys_is_treated_as_callable_parameter(): void
    {
        // This should work - associative array without DTO property keys should be treated as callable parameter
        $callback = new TransitionCallback([
            'some_other_key' => 'value',
            'another_key' => 'another_value',
        ]);

        $this->assertInstanceOf(TransitionCallback::class, $callback);
        $this->assertEquals([
            'some_other_key' => 'value',
            'another_key' => 'another_value',
        ], $callback->callable);
        $this->assertEquals([], $callback->parameters);
    }

    /**
     * Test edge case: empty array should be treated as callable parameter.
     */
    public function test_empty_array_is_treated_as_callable_parameter(): void
    {
        $callback = new TransitionCallback([]);

        $this->assertInstanceOf(TransitionCallback::class, $callback);
        $this->assertEquals([], $callback->callable);
        $this->assertEquals([], $callback->parameters);
    }

    /**
     * Test edge case: array with single element should be treated as callable parameter.
     */
    public function test_array_with_single_element_is_treated_as_callable_parameter(): void
    {
        $callback = new TransitionCallback(['MyClass']);

        $this->assertInstanceOf(TransitionCallback::class, $callback);
        $this->assertEquals(['MyClass'], $callback->callable);
        $this->assertEquals([], $callback->parameters);
    }

    /**
     * Test that invokable class strings work correctly.
     */
    public function test_invokable_class_string_works_correctly(): void
    {
        // Use a class that actually exists
        $callback = new TransitionCallback(\stdClass::class);

        $this->assertInstanceOf(TransitionCallback::class, $callback);
        $this->assertEquals(\stdClass::class, $callback->callable);
        $this->assertEquals([], $callback->parameters);
        $this->assertEquals(TransitionCallback::TYPE_INVOKABLE, $callback->getType());
    }
}
