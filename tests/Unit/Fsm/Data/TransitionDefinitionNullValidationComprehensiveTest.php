<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionDefinition;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Fsm\Enums\TestFeatureState;

/**
 * Comprehensive test for TransitionDefinition null validation bug fix.
 *
 * This test verifies that the bug where null values for fromState and toState
 * were incorrectly rejected in wildcard transitions has been properly fixed.
 * The fix ensures that null values are allowed for both fromState and toState
 * in both constructor and fromArray methods.
 */
class TransitionDefinitionNullValidationComprehensiveTest extends TestCase
{
    /**
     * Test that constructor allows null fromState for wildcard transitions.
     */
    public function test_constructor_allows_null_from_state_for_wildcard_transitions(): void
    {
        $transition = new TransitionDefinition(
            fromState: null,
            toState: TestFeatureState::Active
        );

        $this->assertNull($transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that constructor allows null toState for wildcard transitions.
     */
    public function test_constructor_allows_null_to_state_for_wildcard_transitions(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: null
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }

    /**
     * Test that constructor allows both fromState and toState to be null.
     */
    public function test_constructor_allows_both_states_null_for_wildcard_transitions(): void
    {
        $transition = new TransitionDefinition(
            fromState: null,
            toState: null
        );

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that fromArray method allows null fromState for wildcard transitions.
     */
    public function test_from_array_allows_null_from_state_for_wildcard_transitions(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => null,
            'toState' => TestFeatureState::Active,
        ]);

        $this->assertNull($transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that fromArray method allows null toState for wildcard transitions.
     */
    public function test_from_array_allows_null_to_state_for_wildcard_transitions(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'toState' => null,
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }

    /**
     * Test that fromArray method allows both states to be null.
     */
    public function test_from_array_allows_both_states_null_for_wildcard_transitions(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => null,
            'toState' => null,
        ]);

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that fromArray method works with snake_case keys for null values.
     */
    public function test_from_array_allows_null_states_with_snake_case_keys(): void
    {
        $transition = TransitionDefinition::fromArray([
            'from_state' => null,
            'to_state' => null,
        ]);

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that validation still rejects invalid types for fromState.
     */
    public function test_validation_still_rejects_invalid_from_state_types(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "fromState" value must be a string, FsmStateEnum, or null, got: int');

        TransitionDefinition::fromArray([
            'fromState' => 123,
            'toState' => TestFeatureState::Active,
        ]);
    }

    /**
     * Test that validation still rejects invalid types for toState.
     */
    public function test_validation_still_rejects_invalid_to_state_types(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "toState" value must be a string, FsmStateEnum, or null, got: array');

        TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'toState' => ['invalid'],
        ]);
    }

    /**
     * Test that validation still rejects invalid types for fromState in constructor.
     */
    public function test_constructor_validation_still_rejects_invalid_from_state_types(): void
    {
        $this->expectException(\TypeError::class);

        new TransitionDefinition(
            fromState: 123,
            toState: TestFeatureState::Active
        );
    }

    /**
     * Test that validation still rejects invalid types for toState in constructor.
     */
    public function test_constructor_validation_still_rejects_invalid_to_state_types(): void
    {
        $this->expectException(\TypeError::class);

        new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: 123
        );
    }

    /**
     * Test that null values work with all optional parameters.
     */
    public function test_null_states_work_with_all_optional_parameters(): void
    {
        $transition = new TransitionDefinition(
            fromState: null,
            toState: null,
            event: 'wildcard_transition',
            description: 'Wildcard transition between any states',
            type: TransitionDefinition::TYPE_AUTOMATIC,
            priority: TransitionDefinition::PRIORITY_HIGH,
            behavior: TransitionDefinition::BEHAVIOR_IMMEDIATE,
            guardEvaluation: TransitionDefinition::GUARD_EVALUATION_ANY,
            metadata: ['wildcard' => true, 'from_any' => true, 'to_any' => true],
            isReversible: true,
            timeout: 60
        );

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertSame('wildcard_transition', $transition->event);
        $this->assertSame('Wildcard transition between any states', $transition->description);
        $this->assertSame(TransitionDefinition::TYPE_AUTOMATIC, $transition->type);
        $this->assertSame(TransitionDefinition::PRIORITY_HIGH, $transition->priority);
        $this->assertSame(TransitionDefinition::BEHAVIOR_IMMEDIATE, $transition->behavior);
        $this->assertSame(TransitionDefinition::GUARD_EVALUATION_ANY, $transition->guardEvaluation);
        $this->assertSame(['wildcard' => true, 'from_any' => true, 'to_any' => true], $transition->metadata);
        $this->assertTrue($transition->isReversible);
        $this->assertSame(60, $transition->timeout);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that null values work with all optional parameters in fromArray.
     */
    public function test_null_states_work_with_all_optional_parameters_in_from_array(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => null,
            'toState' => null,
            'event' => 'wildcard_transition',
            'description' => 'Wildcard transition between any states',
            'type' => TransitionDefinition::TYPE_AUTOMATIC,
            'priority' => TransitionDefinition::PRIORITY_HIGH,
            'behavior' => TransitionDefinition::BEHAVIOR_IMMEDIATE,
            'guardEvaluation' => TransitionDefinition::GUARD_EVALUATION_ANY,
            'metadata' => ['wildcard' => true, 'from_any' => true, 'to_any' => true],
            'isReversible' => true,
            'timeout' => 60,
        ]);

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertSame('wildcard_transition', $transition->event);
        $this->assertSame('Wildcard transition between any states', $transition->description);
        $this->assertSame(TransitionDefinition::TYPE_AUTOMATIC, $transition->type);
        $this->assertSame(TransitionDefinition::PRIORITY_HIGH, $transition->priority);
        $this->assertSame(TransitionDefinition::BEHAVIOR_IMMEDIATE, $transition->behavior);
        $this->assertSame(TransitionDefinition::GUARD_EVALUATION_ANY, $transition->guardEvaluation);
        $this->assertSame(['wildcard' => true, 'from_any' => true, 'to_any' => true], $transition->metadata);
        $this->assertTrue($transition->isReversible);
        $this->assertSame(60, $transition->timeout);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that null values work with collections.
     */
    public function test_null_states_work_with_collections(): void
    {
        $guards = collect(['guard1', 'guard2']);
        $actions = collect(['action1', 'action2']);
        $callbacks = collect(['callback1', 'callback2']);

        $transition = new TransitionDefinition(
            fromState: null,
            toState: null,
            guards: $guards,
            actions: $actions,
            onTransitionCallbacks: $callbacks
        );

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that null values work with collections in fromArray.
     */
    public function test_null_states_work_with_collections_in_from_array(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => null,
            'toState' => null,
            'guards' => ['guard1', 'guard2'],
            'actions' => ['action1', 'action2'],
            'onTransitionCallbacks' => ['callback1', 'callback2'],
        ]);

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that getFromStateName returns null for null fromState.
     */
    public function test_get_from_state_name_returns_null_for_null_from_state(): void
    {
        $transition = new TransitionDefinition(
            fromState: null,
            toState: TestFeatureState::Active
        );

        $this->assertNull($transition->getFromStateName());
    }

    /**
     * Test that getToStateName returns empty string for null toState.
     */
    public function test_get_to_state_name_returns_empty_string_for_null_to_state(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: null
        );

        $this->assertSame('', $transition->getToStateName());
    }

    /**
     * Test that getDisplayDescription handles null states correctly.
     */
    public function test_get_display_description_handles_null_states_correctly(): void
    {
        $transition = new TransitionDefinition(
            fromState: null,
            toState: null,
            event: 'wildcard'
        );

        $description = $transition->getDisplayDescription();
        $this->assertStringContainsString('Transition from Any State to ', $description);
        $this->assertStringContainsString('(triggered by: wildcard)', $description);
    }

    /**
     * Test that getDisplayDescription with custom description works with null states.
     */
    public function test_get_display_description_with_custom_description_works_with_null_states(): void
    {
        $transition = new TransitionDefinition(
            fromState: null,
            toState: null,
            description: 'Wildcard transition between any states'
        );

        $this->assertSame('Wildcard transition between any states', $transition->getDisplayDescription());
    }

    /**
     * Test that validation is consistent between constructor and fromArray methods.
     */
    public function test_validation_consistency_between_constructor_and_from_array(): void
    {
        // Test with null fromState
        $transition1 = new TransitionDefinition(
            fromState: null,
            toState: TestFeatureState::Active
        );

        $transition2 = TransitionDefinition::fromArray([
            'fromState' => null,
            'toState' => TestFeatureState::Active,
        ]);

        $this->assertSame($transition1->fromState, $transition2->fromState);
        $this->assertSame($transition1->toState, $transition2->toState);

        // Test with null toState
        $transition3 = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: null
        );

        $transition4 = TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'toState' => null,
        ]);

        $this->assertSame($transition3->fromState, $transition4->fromState);
        $this->assertSame($transition3->toState, $transition4->toState);

        // Test with both null
        $transition5 = new TransitionDefinition(
            fromState: null,
            toState: null
        );

        $transition6 = TransitionDefinition::fromArray([
            'fromState' => null,
            'toState' => null,
        ]);

        $this->assertSame($transition5->fromState, $transition6->fromState);
        $this->assertSame($transition5->toState, $transition6->toState);
    }

    /**
     * Test that the bug fix ensures null values are not rejected by validation.
     * This test specifically verifies that the validation logic correctly allows null values.
     */
    public function test_bug_fix_ensures_null_values_are_not_rejected(): void
    {
        // This test would have failed before the bug fix
        // because null values were incorrectly rejected by validation

        $testCases = [
            // fromState null, toState valid
            ['fromState' => null, 'toState' => TestFeatureState::Active],
            ['fromState' => null, 'toState' => 'active'],
            // fromState valid, toState null
            ['fromState' => TestFeatureState::Pending, 'toState' => null],
            ['fromState' => 'pending', 'toState' => null],
            // both null
            ['fromState' => null, 'toState' => null],
        ];

        foreach ($testCases as $testCase) {
            // Constructor should work
            $transition1 = new TransitionDefinition(
                fromState: $testCase['fromState'],
                toState: $testCase['toState']
            );

            $this->assertSame($testCase['fromState'], $transition1->fromState);
            $this->assertSame($testCase['toState'], $transition1->toState);

            // fromArray should work
            $transition2 = TransitionDefinition::fromArray($testCase);

            $this->assertSame($testCase['fromState'], $transition2->fromState);
            $this->assertSame($testCase['toState'], $transition2->toState);
        }
    }

    /**
     * Test that the bug fix works with mixed key formats (camelCase and snake_case).
     */
    public function test_bug_fix_works_with_mixed_key_formats(): void
    {
        $testCases = [
            // camelCase keys
            ['fromState' => null, 'toState' => null],
            // snake_case keys
            ['from_state' => null, 'to_state' => null],
            // mixed keys
            ['fromState' => null, 'to_state' => null],
            ['from_state' => null, 'toState' => null],
        ];

        foreach ($testCases as $testCase) {
            $transition = TransitionDefinition::fromArray($testCase);

            $this->assertNull($transition->fromState);
            $this->assertNull($transition->toState);
            $this->assertTrue($transition->isWildcardTransition());
        }
    }

    /**
     * Test that the bug fix maintains backward compatibility with valid non-null values.
     */
    public function test_bug_fix_maintains_backward_compatibility(): void
    {
        // Test that valid non-null values still work
        $transition1 = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );

        $this->assertSame(TestFeatureState::Pending, $transition1->fromState);
        $this->assertSame(TestFeatureState::Active, $transition1->toState);

        $transition2 = TransitionDefinition::fromArray([
            'fromState' => 'pending',
            'toState' => 'active',
        ]);

        $this->assertSame('pending', $transition2->fromState);
        $this->assertSame('active', $transition2->toState);
    }
}
