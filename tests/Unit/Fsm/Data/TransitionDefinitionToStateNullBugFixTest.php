<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionDefinition;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Fsm\Enums\TestFeatureState;

/**
 * Test for TransitionDefinition toState null bug fix.
 *
 * This test verifies that the bug where toState parameter was typed as nullable
 * but immediately threw an InvalidArgumentException if null has been fixed.
 * The fix allows legitimate null values for wildcard transitions.
 */
class TransitionDefinitionToStateNullBugFixTest extends TestCase
{
    public function test_constructor_allows_null_to_state_for_wildcard_transitions(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: null
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_constructor_allows_null_to_state_with_null_from_state(): void
    {
        $transition = new TransitionDefinition(
            fromState: null,
            toState: null
        );

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    public function test_constructor_allows_null_to_state_with_all_parameters(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: null,
            event: 'wildcard_transition',
            description: 'Wildcard transition to any state',
            type: TransitionDefinition::TYPE_AUTOMATIC,
            priority: TransitionDefinition::PRIORITY_HIGH,
            behavior: TransitionDefinition::BEHAVIOR_IMMEDIATE,
            guardEvaluation: TransitionDefinition::GUARD_EVALUATION_ANY,
            metadata: ['wildcard' => true],
            isReversible: true,
            timeout: 60
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertSame('wildcard_transition', $transition->event);
        $this->assertSame('Wildcard transition to any state', $transition->description);
        $this->assertSame(TransitionDefinition::TYPE_AUTOMATIC, $transition->type);
        $this->assertSame(TransitionDefinition::PRIORITY_HIGH, $transition->priority);
        $this->assertSame(TransitionDefinition::BEHAVIOR_IMMEDIATE, $transition->behavior);
        $this->assertSame(TransitionDefinition::GUARD_EVALUATION_ANY, $transition->guardEvaluation);
        $this->assertSame(['wildcard' => true], $transition->metadata);
        $this->assertTrue($transition->isReversible);
        $this->assertSame(60, $transition->timeout);
    }

    public function test_from_array_allows_null_to_state_for_wildcard_transitions(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'toState' => null,
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_from_array_allows_null_to_state_with_snake_case_key(): void
    {
        $transition = TransitionDefinition::fromArray([
            'from_state' => TestFeatureState::Pending,
            'to_state' => null,
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_from_array_allows_null_to_state_with_null_from_state(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => null,
            'toState' => null,
        ]);

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    public function test_from_array_allows_null_to_state_with_all_parameters(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'toState' => null,
            'event' => 'wildcard_transition',
            'description' => 'Wildcard transition to any state',
            'type' => TransitionDefinition::TYPE_AUTOMATIC,
            'priority' => TransitionDefinition::PRIORITY_HIGH,
            'behavior' => TransitionDefinition::BEHAVIOR_IMMEDIATE,
            'guardEvaluation' => TransitionDefinition::GUARD_EVALUATION_ANY,
            'metadata' => ['wildcard' => true],
            'isReversible' => true,
            'timeout' => 60,
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertSame('wildcard_transition', $transition->event);
        $this->assertSame('Wildcard transition to any state', $transition->description);
        $this->assertSame(TransitionDefinition::TYPE_AUTOMATIC, $transition->type);
        $this->assertSame(TransitionDefinition::PRIORITY_HIGH, $transition->priority);
        $this->assertSame(TransitionDefinition::BEHAVIOR_IMMEDIATE, $transition->behavior);
        $this->assertSame(TransitionDefinition::GUARD_EVALUATION_ANY, $transition->guardEvaluation);
        $this->assertSame(['wildcard' => true], $transition->metadata);
        $this->assertTrue($transition->isReversible);
        $this->assertSame(60, $transition->timeout);
    }

    public function test_constructor_still_throws_exception_for_invalid_to_state_types(): void
    {
        $this->expectException(\TypeError::class);

        new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: 123
        );
    }

    public function test_from_array_still_throws_exception_for_invalid_to_state_types(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "toState" value must be a string, FsmStateEnum, or null, got: array');

        TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'toState' => ['invalid'],
        ]);
    }

    public function test_constructor_still_throws_exception_when_to_state_key_is_missing_in_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based initialization requires an associative array with a "toState" or "to_state" key.');

        TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'event' => 'test',
        ]);
    }

    public function test_constructor_still_accepts_valid_string_to_state(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: 'active'
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame('active', $transition->toState);
    }

    public function test_constructor_still_accepts_valid_enum_to_state(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    public function test_from_array_still_accepts_valid_string_to_state(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'toState' => 'active',
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame('active', $transition->toState);
    }

    public function test_from_array_still_accepts_valid_enum_to_state(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    public function test_get_to_state_name_returns_empty_string_for_null_to_state(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: null
        );

        $this->assertSame('', $transition->getToStateName());
    }

    public function test_get_display_description_handles_null_to_state(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: null,
            event: 'wildcard'
        );

        $description = $transition->getDisplayDescription();
        $this->assertStringContainsString('Transition from', $description);
        $this->assertStringContainsString('to ', $description);
        $this->assertStringContainsString('(triggered by: wildcard)', $description);
    }

    public function test_get_display_description_with_custom_description_and_null_to_state(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: null,
            description: 'Wildcard transition to any state'
        );

        $this->assertSame('Wildcard transition to any state', $transition->getDisplayDescription());
    }

    public function test_constructor_without_arguments_works(): void
    {
        // This should work - both fromState and toState default to null for wildcard transitions
        $transition = new TransitionDefinition;

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_constructor_with_only_from_state_works(): void
    {
        // This should work - toState defaults to null for wildcard transitions
        $transition = new TransitionDefinition(fromState: TestFeatureState::Pending);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_constructor_validation_consistency_between_positional_and_array_construction(): void
    {
        // Test that both construction methods handle null toState consistently
        $transition1 = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: null
        );

        $transition2 = TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'toState' => null,
        ]);

        $this->assertSame($transition1->fromState, $transition2->fromState);
        $this->assertSame($transition1->toState, $transition2->toState);
        $this->assertSame($transition1->type, $transition2->type);
        $this->assertSame($transition1->priority, $transition2->priority);
        $this->assertSame($transition1->behavior, $transition2->behavior);
        $this->assertSame($transition1->guardEvaluation, $transition2->guardEvaluation);
        $this->assertSame($transition1->isReversible, $transition2->isReversible);
        $this->assertSame($transition1->timeout, $transition2->timeout);
    }

    public function test_constructor_accepts_null_to_state_with_collections(): void
    {
        $guards = collect(['guard1', 'guard2']);
        $actions = collect(['action1', 'action2']);
        $callbacks = collect(['callback1', 'callback2']);

        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: null,
            guards: $guards,
            actions: $actions,
            onTransitionCallbacks: $callbacks
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);
    }

    public function test_from_array_accepts_null_to_state_with_collections(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'toState' => null,
            'guards' => ['guard1', 'guard2'],
            'actions' => ['action1', 'action2'],
            'onTransitionCallbacks' => ['callback1', 'callback2'],
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);
    }
}
