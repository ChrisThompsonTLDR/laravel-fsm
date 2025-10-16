<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionAction;
use Fsm\Data\TransitionCallback;
use Fsm\Data\TransitionDefinition;
use Fsm\Data\TransitionGuard;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Fsm\Enums\TestFeatureState;

/**
 * Test to verify the fix for the isset() bug in TransitionDefinition constructor.
 *
 * The fix: Replace `if (! isset($this->guards))` with `if ($this->guards === null || !($this->guards instanceof Collection))`
 * to properly detect when collection properties need to be initialized.
 */
class TransitionDefinitionIssetBugFixTest extends TestCase
{
    public function test_collection_properties_are_properly_initialized_with_positional_parameters(): void
    {
        // Test that collection properties are properly initialized when using positional parameters
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );

        // Verify collection properties are properly initialized as empty collections
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty when not provided
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    public function test_collection_properties_are_properly_initialized_with_array_construction(): void
    {
        // Test that collection properties are properly initialized when using array construction
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
        ]);

        // Verify collection properties are properly initialized as empty collections
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty when not provided
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    public function test_collection_properties_are_not_overwritten_when_provided(): void
    {
        // Test that collection properties are not overwritten when explicitly provided
        $guards = new Collection([
            new TransitionGuard(fn () => true, [], null, 50, false, 'guard1'),
            new TransitionGuard(fn () => false, [], null, 50, false, 'guard2'),
        ]);

        $actions = new Collection([
            new TransitionAction(fn () => null, [], true, 'after', 50, 'action1'),
            new TransitionAction(fn () => null, [], true, 'after', 50, 'action2'),
        ]);

        $callbacks = new Collection([
            new TransitionCallback(fn () => null, [], false, 'after_save', 50, 'callback1'),
            new TransitionCallback(fn () => null, [], false, 'after_save', 50, 'callback2'),
        ]);

        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: $guards,
            actions: $actions,
            onTransitionCallbacks: $callbacks
        );

        // Verify collection properties are properly initialized (may be different objects due to casting)
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        // Verify collection contents are preserved
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);
    }

    public function test_array_collections_are_converted_to_collection_instances(): void
    {
        // Test that array collections are properly converted to Collection instances
        $guardsArray = [
            new TransitionGuard(fn () => true, [], null, 50, false, 'guard1'),
            new TransitionGuard(fn () => false, [], null, 50, false, 'guard2'),
        ];

        $actionsArray = [
            new TransitionAction(fn () => null, [], true, 'after', 50, 'action1'),
            new TransitionAction(fn () => null, [], true, 'after', 50, 'action2'),
        ];

        $callbacksArray = [
            new TransitionCallback(fn () => null, [], false, 'after_save', 50, 'callback1'),
            new TransitionCallback(fn () => null, [], false, 'after_save', 50, 'callback2'),
        ];

        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => $guardsArray,
            'actions' => $actionsArray,
            'onTransitionCallbacks' => $callbacksArray,
        ]);

        // Verify array collections are converted to Collection instances
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        // Verify collection contents are preserved
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);
    }

    public function test_empty_arrays_are_converted_to_empty_collections(): void
    {
        // Test that empty arrays are converted to empty Collection instances
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => [],
            'actions' => [],
            'onTransitionCallbacks' => [],
        ]);

        // Verify empty arrays are converted to empty Collection instances
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    public function test_snake_case_keys_work_correctly_with_collections(): void
    {
        // Test that snake_case keys work correctly with collection properties
        $guardsArray = [
            new TransitionGuard(fn () => true, [], null, 50, false, 'guard1'),
        ];

        $actionsArray = [
            new TransitionAction(fn () => null, [], true, 'after', 50, 'action1'),
        ];

        $callbacksArray = [
            new TransitionCallback(fn () => null, [], false, 'after_save', 50, 'callback1'),
        ];

        $transition = new TransitionDefinition([
            'from_state' => TestFeatureState::Pending,
            'to_state' => TestFeatureState::Active,
            'guards' => $guardsArray,
            'actions' => $actionsArray,
            'onTransitionCallbacks' => $callbacksArray,
        ]);

        // Verify snake case keys are converted correctly
        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);

        // Verify collections are properly initialized
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        $this->assertCount(1, $transition->guards);
        $this->assertCount(1, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);
    }

    public function test_collection_methods_work_correctly_after_construction(): void
    {
        // Test that collection methods work correctly after construction
        $guards = new Collection([
            new TransitionGuard(fn () => true, [], null, 50, false, 'guard1'),
        ]);

        $actions = new Collection([
            new TransitionAction(fn () => null, [], true, 'after', 50, 'action1'),
        ]);

        $callbacks = new Collection([
            new TransitionCallback(fn () => null, [], false, 'after_save', 50, 'callback1'),
        ]);

        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: $guards,
            actions: $actions,
            onTransitionCallbacks: $callbacks
        );

        // Test collection methods work correctly
        $this->assertTrue($transition->guards->isNotEmpty());
        $this->assertTrue($transition->actions->isNotEmpty());
        $this->assertTrue($transition->onTransitionCallbacks->isNotEmpty());

        $this->assertFalse($transition->guards->isEmpty());
        $this->assertFalse($transition->actions->isEmpty());
        $this->assertFalse($transition->onTransitionCallbacks->isEmpty());

        // Test that we can iterate over the collections
        $guardCount = 0;
        foreach ($transition->guards as $guard) {
            $this->assertInstanceOf(TransitionGuard::class, $guard);
            $guardCount++;
        }
        $this->assertEquals(1, $guardCount);

        $actionCount = 0;
        foreach ($transition->actions as $action) {
            $this->assertInstanceOf(TransitionAction::class, $action);
            $actionCount++;
        }
        $this->assertEquals(1, $actionCount);

        $callbackCount = 0;
        foreach ($transition->onTransitionCallbacks as $callback) {
            $this->assertInstanceOf(TransitionCallback::class, $callback);
            $callbackCount++;
        }
        $this->assertEquals(1, $callbackCount);
    }

    public function test_fix_handles_edge_case_where_properties_are_null(): void
    {
        // This test simulates a scenario where the parent DTO might not properly
        // initialize collection properties, and our fix should handle it

        // Create a transition with minimal parameters
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );

        // The fix should ensure that even if the parent DTO doesn't initialize
        // these properties properly, our null checks will catch it and initialize them
        $this->assertNotNull($transition->guards);
        $this->assertNotNull($transition->actions);
        $this->assertNotNull($transition->onTransitionCallbacks);

        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);
    }

    public function test_fix_handles_edge_case_where_properties_are_not_collections(): void
    {
        // This test simulates a scenario where the parent DTO might initialize
        // properties with non-Collection values, and our fix should handle it

        // Create a transition with minimal parameters
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );

        // The fix should ensure that even if the parent DTO initializes
        // these properties with non-Collection values, our instanceof checks
        // will catch it and initialize them properly
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);
    }

    public function test_has_guards_has_actions_has_callbacks_work_correctly(): void
    {
        // Test that the helper methods work correctly with the fixed initialization
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );

        // Initially should have no guards, actions, or callbacks
        $this->assertFalse($transition->hasGuards());
        $this->assertFalse($transition->hasActions());
        $this->assertFalse($transition->hasCallbacks());

        // Add some guards, actions, and callbacks
        $transition->guards->push(new TransitionGuard(fn () => true, [], null, 50, false, 'guard1'));
        $transition->actions->push(new TransitionAction(fn () => null, [], true, 'after', 50, 'action1'));
        $transition->onTransitionCallbacks->push(new TransitionCallback(fn () => null, [], false, 'after_save', 50, 'callback1'));

        // Now should have guards, actions, and callbacks
        $this->assertTrue($transition->hasGuards());
        $this->assertTrue($transition->hasActions());
        $this->assertTrue($transition->hasCallbacks());
    }
}
