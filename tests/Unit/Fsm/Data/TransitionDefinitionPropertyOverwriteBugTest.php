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
 * Test for TransitionDefinition property overwrite bug fix.
 *
 * Tests that the guards, actions, and onTransitionCallbacks properties are not
 * unintentionally overwritten with empty collections after the parent Dto constructor
 * execution, which should have already populated these properties via its casting system.
 */
class TransitionDefinitionPropertyOverwriteBugTest extends TestCase
{
    public function test_collection_properties_not_overwritten_with_positional_params(): void
    {
        $guards = collect([
            new TransitionGuard(fn () => true, [], null, 50, false, 'guard1'),
            new TransitionGuard(fn () => true, [], null, 50, false, 'guard2'),
        ]);

        $actions = collect([
            new TransitionAction(fn () => null, [], true, 'after', 50, 'action1'),
            new TransitionAction(fn () => null, [], true, 'after', 50, 'action2'),
        ]);

        $callbacks = collect([
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

        // Verify the collections contain the expected items
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);

        // Verify the actual content is preserved
        $this->assertSame('guard1', $transition->guards->first()->name);
        $this->assertSame('guard2', $transition->guards->last()->name);
        $this->assertSame('action1', $transition->actions->first()->name);
        $this->assertSame('action2', $transition->actions->last()->name);
        $this->assertSame('callback1', $transition->onTransitionCallbacks->first()->name);
        $this->assertSame('callback2', $transition->onTransitionCallbacks->last()->name);
    }

    public function test_collection_properties_not_overwritten_with_array_params(): void
    {
        $guards = [
            new TransitionGuard(fn () => true, [], null, 50, false, 'guard1'),
            new TransitionGuard(fn () => true, [], null, 50, false, 'guard2'),
        ];

        $actions = [
            new TransitionAction(fn () => null, [], true, 'after', 50, 'action1'),
            new TransitionAction(fn () => null, [], true, 'after', 50, 'action2'),
        ];

        $callbacks = [
            new TransitionCallback(fn () => null, [], false, 'after_save', 50, 'callback1'),
            new TransitionCallback(fn () => null, [], false, 'after_save', 50, 'callback2'),
        ];

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

        // Verify the collections contain the expected items
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);

        // Verify the actual content is preserved
        $this->assertSame('guard1', $transition->guards->first()->name);
        $this->assertSame('guard2', $transition->guards->last()->name);
        $this->assertSame('action1', $transition->actions->first()->name);
        $this->assertSame('action2', $transition->actions->last()->name);
        $this->assertSame('callback1', $transition->onTransitionCallbacks->first()->name);
        $this->assertSame('callback2', $transition->onTransitionCallbacks->last()->name);
    }

    public function test_collection_properties_not_overwritten_with_mixed_array_and_collection_params(): void
    {
        $guards = collect([
            new TransitionGuard(fn () => true, [], null, 50, false, 'guard1'),
        ]);

        $actions = [
            new TransitionAction(fn () => null, [], true, 'after', 50, 'action1'),
        ];

        $callbacks = collect([
            new TransitionCallback(fn () => null, [], false, 'after_save', 50, 'callback1'),
        ]);

        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => $guards,
            'actions' => $actions,
            'onTransitionCallbacks' => $callbacks,
        ]);

        // Verify collection properties are properly set and not overwritten
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        // Verify the collections contain the expected items
        $this->assertCount(1, $transition->guards);
        $this->assertCount(1, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);

        // Verify the actual content is preserved
        $this->assertSame('guard1', $transition->guards->first()->name);
        $this->assertSame('action1', $transition->actions->first()->name);
        $this->assertSame('callback1', $transition->onTransitionCallbacks->first()->name);
    }

    public function test_collection_properties_default_to_empty_when_not_provided(): void
    {
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

    public function test_collection_properties_default_to_empty_when_not_provided_in_array(): void
    {
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

    public function test_collection_properties_not_overwritten_with_empty_arrays(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => [],
            'actions' => [],
            'onTransitionCallbacks' => [],
        ]);

        // Verify collection properties are properly initialized as empty collections
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    public function test_collection_properties_not_overwritten_with_empty_collections(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: collect([]),
            actions: collect([]),
            onTransitionCallbacks: collect([])
        );

        // Verify collection properties are properly initialized as empty collections
        $this->assertInstanceOf(Collection::class, $transition->guards);
        $this->assertInstanceOf(Collection::class, $transition->actions);
        $this->assertInstanceOf(Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    public function test_other_properties_are_set_correctly_without_affecting_collections(): void
    {
        $guards = collect([new TransitionGuard(fn () => true, [], null, 50, false, 'guard1')]);
        $actions = collect([new TransitionAction(fn () => null, [], true, 'after', 50, 'action1')]);
        $callbacks = collect([new TransitionCallback(fn () => null, [], false, 'after_save', 50, 'callback1')]);

        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'event' => 'test_event',
            'description' => 'Test description',
            'type' => TransitionDefinition::TYPE_TRIGGERED,
            'priority' => TransitionDefinition::PRIORITY_HIGH,
            'behavior' => TransitionDefinition::BEHAVIOR_QUEUED,
            'guardEvaluation' => TransitionDefinition::GUARD_EVALUATION_ANY,
            'metadata' => ['key' => 'value'],
            'isReversible' => true,
            'timeout' => 60,
            'guards' => $guards,
            'actions' => $actions,
            'onTransitionCallbacks' => $callbacks,
        ]);

        // Verify all properties are set correctly
        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('test_event', $transition->event);
        $this->assertSame('Test description', $transition->description);
        $this->assertSame(TransitionDefinition::TYPE_TRIGGERED, $transition->type);
        $this->assertSame(TransitionDefinition::PRIORITY_HIGH, $transition->priority);
        $this->assertSame(TransitionDefinition::BEHAVIOR_QUEUED, $transition->behavior);
        $this->assertSame(TransitionDefinition::GUARD_EVALUATION_ANY, $transition->guardEvaluation);
        $this->assertSame(['key' => 'value'], $transition->metadata);
        $this->assertTrue($transition->isReversible);
        $this->assertSame(60, $transition->timeout);

        // Verify collection properties are still properly set
        $this->assertCount(1, $transition->guards);
        $this->assertCount(1, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);
        $this->assertSame('guard1', $transition->guards->first()->name);
        $this->assertSame('action1', $transition->actions->first()->name);
        $this->assertSame('callback1', $transition->onTransitionCallbacks->first()->name);
    }

    public function test_wildcard_transition_with_collections_not_overwritten(): void
    {
        $guards = collect([new TransitionGuard(fn () => true, [], null, 50, false, 'wildcard_guard')]);
        $actions = collect([new TransitionAction(fn () => null, [], true, 'after', 50, 'wildcard_action')]);

        $transition = new TransitionDefinition([
            'fromState' => null, // Wildcard transition
            'toState' => TestFeatureState::Active,
            'guards' => $guards,
            'actions' => $actions,
        ]);

        // Verify wildcard transition properties
        $this->assertNull($transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertTrue($transition->isWildcardTransition());

        // Verify collection properties are properly set
        $this->assertCount(1, $transition->guards);
        $this->assertCount(1, $transition->actions);
        $this->assertSame('wildcard_guard', $transition->guards->first()->name);
        $this->assertSame('wildcard_action', $transition->actions->first()->name);
    }

    public function test_string_states_with_collections_not_overwritten(): void
    {
        $guards = collect([new TransitionGuard(fn () => true, [], null, 50, false, 'string_guard')]);
        $callbacks = collect([new TransitionCallback(fn () => null, [], false, 'after_save', 50, 'string_callback')]);

        $transition = new TransitionDefinition([
            'fromState' => 'pending',
            'toState' => 'active',
            'guards' => $guards,
            'onTransitionCallbacks' => $callbacks,
        ]);

        // Verify string state properties
        $this->assertSame('pending', $transition->fromState);
        $this->assertSame('active', $transition->toState);

        // Verify collection properties are properly set
        $this->assertCount(1, $transition->guards);
        $this->assertCount(1, $transition->onTransitionCallbacks);
        $this->assertSame('string_guard', $transition->guards->first()->name);
        $this->assertSame('string_callback', $transition->onTransitionCallbacks->first()->name);
    }
}
