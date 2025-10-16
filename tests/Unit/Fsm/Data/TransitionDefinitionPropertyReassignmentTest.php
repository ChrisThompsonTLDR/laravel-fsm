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
 * Test for TransitionDefinition property re-assignment bug fix.
 *
 * Tests that collection properties are not re-assigned after parent constructor call,
 * preventing inconsistencies and overwriting values.
 */
class TransitionDefinitionPropertyReassignmentTest extends TestCase
{
    public function test_collection_properties_not_reassigned_after_parent_constructor_with_positional_params(): void
    {
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
            event: 'activate',
            guards: $guards,
            actions: $actions,
            onTransitionCallbacks: $callbacks,
            description: 'Test transition'
        );

        // Verify collection properties are properly set and not overwritten
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        // Verify collection contents are preserved
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);

        $this->assertSame('guard1', $transition->guards->first()->name);
        $this->assertSame('action1', $transition->actions->first()->name);
        $this->assertSame('callback1', $transition->onTransitionCallbacks->first()->name);
    }

    public function test_collection_properties_not_reassigned_after_parent_constructor_with_array_params(): void
    {
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

        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'event' => 'activate',
            'guards' => $guards,
            'actions' => $actions,
            'onTransitionCallbacks' => $callbacks,
            'description' => 'Test transition',
        ]);

        // Verify collection properties are properly set and not overwritten
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        // Verify collection contents are preserved
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);

        $this->assertSame('guard1', $transition->guards->first()->name);
        $this->assertSame('action1', $transition->actions->first()->name);
        $this->assertSame('callback1', $transition->onTransitionCallbacks->first()->name);
    }

    public function test_collection_properties_initialized_as_empty_when_not_provided(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );

        // Verify collection properties are initialized as empty collections
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    public function test_collection_properties_initialized_as_empty_when_not_provided_in_array(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
        ]);

        // Verify collection properties are initialized as empty collections
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    public function test_array_collections_are_converted_to_collection_instances(): void
    {
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

        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: $guardsArray,
            actions: $actionsArray,
            onTransitionCallbacks: $callbacksArray
        );

        // Verify array collections are converted to Collection instances
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        // Verify collection contents are preserved
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);

        $this->assertSame('guard1', $transition->guards->first()->name);
        $this->assertSame('action1', $transition->actions->first()->name);
        $this->assertSame('callback1', $transition->onTransitionCallbacks->first()->name);
    }

    public function test_array_collections_are_converted_to_collection_instances_in_array_construction(): void
    {
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

        $this->assertSame('guard1', $transition->guards->first()->name);
        $this->assertSame('action1', $transition->actions->first()->name);
        $this->assertSame('callback1', $transition->onTransitionCallbacks->first()->name);
    }

    public function test_all_other_properties_are_set_correctly_without_reassignment(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            event: 'activate',
            description: 'Test transition',
            type: TransitionDefinition::TYPE_TRIGGERED,
            priority: TransitionDefinition::PRIORITY_HIGH,
            behavior: TransitionDefinition::BEHAVIOR_QUEUED,
            guardEvaluation: TransitionDefinition::GUARD_EVALUATION_ANY,
            metadata: ['key1' => 'value1', 'key2' => 'value2'],
            isReversible: true,
            timeout: 60
        );

        // Verify all properties are set correctly
        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('activate', $transition->event);
        $this->assertSame('Test transition', $transition->description);
        $this->assertSame(TransitionDefinition::TYPE_TRIGGERED, $transition->type);
        $this->assertSame(TransitionDefinition::PRIORITY_HIGH, $transition->priority);
        $this->assertSame(TransitionDefinition::BEHAVIOR_QUEUED, $transition->behavior);
        $this->assertSame(TransitionDefinition::GUARD_EVALUATION_ANY, $transition->guardEvaluation);
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $transition->metadata);
        $this->assertTrue($transition->isReversible);
        $this->assertSame(60, $transition->timeout);
    }

    public function test_array_construction_with_all_properties_set_correctly(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'event' => 'activate',
            'description' => 'Test transition',
            'type' => TransitionDefinition::TYPE_AUTOMATIC,
            'priority' => TransitionDefinition::PRIORITY_CRITICAL,
            'behavior' => TransitionDefinition::BEHAVIOR_DEFERRED,
            'guardEvaluation' => TransitionDefinition::GUARD_EVALUATION_FIRST,
            'metadata' => ['key1' => 'value1', 'key2' => 'value2'],
            'isReversible' => false,
            'timeout' => 120,
        ]);

        // Verify all properties are set correctly
        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('activate', $transition->event);
        $this->assertSame('Test transition', $transition->description);
        $this->assertSame(TransitionDefinition::TYPE_AUTOMATIC, $transition->type);
        $this->assertSame(TransitionDefinition::PRIORITY_CRITICAL, $transition->priority);
        $this->assertSame(TransitionDefinition::BEHAVIOR_DEFERRED, $transition->behavior);
        $this->assertSame(TransitionDefinition::GUARD_EVALUATION_FIRST, $transition->guardEvaluation);
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $transition->metadata);
        $this->assertFalse($transition->isReversible);
        $this->assertSame(120, $transition->timeout);
    }

    public function test_snake_case_keys_work_correctly_with_collections(): void
    {
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
        $guards = new Collection([
            new TransitionGuard(fn () => true, [], null, TransitionDefinition::PRIORITY_HIGH, false, 'guard1'),
            new TransitionGuard(fn () => false, [], null, TransitionDefinition::PRIORITY_LOW, false, 'guard2'),
        ]);

        $actions = new Collection([
            new TransitionAction(fn () => null, [], true, 'before', 50, 'action1'),
            new TransitionAction(fn () => null, [], true, 'after', 50, 'action2'),
        ]);

        $callbacks = new Collection([
            new TransitionCallback(fn () => null, [], false, 'before', 50, 'callback1'),
            new TransitionCallback(fn () => null, [], false, 'after', 50, 'callback2'),
        ]);

        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: $guards,
            actions: $actions,
            onTransitionCallbacks: $callbacks
        );

        // Test collection filtering methods work correctly
        $highPriorityGuards = $transition->getGuardsForPriority(TransitionDefinition::PRIORITY_HIGH);
        $this->assertCount(1, $highPriorityGuards);
        $this->assertSame('guard1', $highPriorityGuards->first()->name);

        $beforeActions = $transition->getActionsForTiming('before');
        $this->assertCount(1, $beforeActions);
        $this->assertSame('action1', $beforeActions->first()->name);

        $afterCallbacks = $transition->getCallbacksForTiming('after');
        $this->assertCount(1, $afterCallbacks);
        $this->assertSame('callback2', $afterCallbacks->first()->name);
    }

    public function test_wildcard_transition_with_collections(): void
    {
        $guards = new Collection([
            new TransitionGuard(fn () => true, [], null, 50, false, 'wildcard_guard'),
        ]);

        $transition = new TransitionDefinition(
            fromState: null,
            toState: TestFeatureState::Active,
            guards: $guards
        );

        // Verify wildcard transition works with collections
        $this->assertNull($transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertCount(1, $transition->guards);
        $this->assertSame('wildcard_guard', $transition->guards->first()->name);
    }

    public function test_string_states_with_collections(): void
    {
        $actions = new Collection([
            new TransitionAction(fn () => null, [], true, 'after', 50, 'string_action'),
        ]);

        $transition = new TransitionDefinition(
            fromState: 'pending',
            toState: 'active',
            actions: $actions
        );

        // Verify string states work with collections
        $this->assertSame('pending', $transition->fromState);
        $this->assertSame('active', $transition->toState);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertCount(1, $transition->actions);
        $this->assertSame('string_action', $transition->actions->first()->name);
    }
}
