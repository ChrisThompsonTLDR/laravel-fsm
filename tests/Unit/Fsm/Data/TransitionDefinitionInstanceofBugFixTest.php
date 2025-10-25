<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionDefinition;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Fsm\Enums\TestFeatureState;

/**
 * Test for TransitionDefinition instanceof bug fix.
 *
 * This test specifically verifies that the bug where instanceof checks
 * used \Fsm\Contracts\FsmStateEnum instead of the imported FsmStateEnum
 * has been fixed. The bug caused valid FsmStateEnum instances to be
 * incorrectly rejected during type validation.
 */
class TransitionDefinitionInstanceofBugFixTest extends TestCase
{
    /**
     * Test that FsmStateEnum instances are properly recognized in array-based constructor.
     * This test would have failed before the bug fix because the instanceof check
     * was using the fully qualified namespace instead of the imported class.
     */
    public function test_array_constructor_accepts_fsm_state_enum_from_state(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => 'active',
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame('active', $transition->toState);
    }

    /**
     * Test that FsmStateEnum instances are properly recognized in array-based constructor.
     * This test would have failed before the bug fix because the instanceof check
     * was using the fully qualified namespace instead of the imported class.
     */
    public function test_array_constructor_accepts_fsm_state_enum_to_state(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => 'pending',
            'toState' => TestFeatureState::Active,
        ]);

        $this->assertSame('pending', $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    /**
     * Test that FsmStateEnum instances are properly recognized in array-based constructor
     * with both fromState and toState as enums.
     */
    public function test_array_constructor_accepts_fsm_state_enum_both_states(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    /**
     * Test that FsmStateEnum instances are properly recognized in array-based constructor
     * with null fromState and enum toState.
     */
    public function test_array_constructor_accepts_fsm_state_enum_with_null_from_state(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => null,
            'toState' => TestFeatureState::Active,
        ]);

        $this->assertNull($transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that FsmStateEnum instances are properly recognized in positional constructor.
     * This test would have failed before the bug fix because the instanceof check
     * was using the fully qualified namespace instead of the imported class.
     */
    public function test_positional_constructor_accepts_fsm_state_enum_from_state(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: 'active'
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame('active', $transition->toState);
    }

    /**
     * Test that FsmStateEnum instances are properly recognized in positional constructor.
     * This test would have failed before the bug fix because the instanceof check
     * was using the fully qualified namespace instead of the imported class.
     */
    public function test_positional_constructor_accepts_fsm_state_enum_to_state(): void
    {
        $transition = new TransitionDefinition(
            fromState: 'pending',
            toState: TestFeatureState::Active
        );

        $this->assertSame('pending', $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    /**
     * Test that FsmStateEnum instances are properly recognized in positional constructor
     * with both fromState and toState as enums.
     */
    public function test_positional_constructor_accepts_fsm_state_enum_both_states(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    /**
     * Test that FsmStateEnum instances are properly recognized in positional constructor
     * with null fromState and enum toState.
     */
    public function test_positional_constructor_accepts_fsm_state_enum_with_null_from_state(): void
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
     * Test that invalid types are still properly rejected.
     * This ensures that the fix doesn't break existing validation.
     */
    public function test_array_constructor_still_rejects_invalid_from_state_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "fromState" value must be a string, FsmStateEnum, or null, got: int');

        new TransitionDefinition([
            'fromState' => 123,
            'toState' => TestFeatureState::Active,
        ]);
    }

    /**
     * Test that invalid types are still properly rejected.
     * This ensures that the fix doesn't break existing validation.
     */
    public function test_array_constructor_still_rejects_invalid_to_state_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "toState" value must be a string, FsmStateEnum, or null, got: int');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => 456,
        ]);
    }

    /**
     * Test that string states are still properly accepted.
     * This ensures that the fix doesn't break existing functionality.
     */
    public function test_array_constructor_still_accepts_string_states(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => 'pending',
            'toState' => 'active',
        ]);

        $this->assertSame('pending', $transition->fromState);
        $this->assertSame('active', $transition->toState);
    }

    /**
     * Test that string states are still properly accepted in positional constructor.
     * This ensures that the fix doesn't break existing functionality.
     */
    public function test_positional_constructor_still_accepts_string_states(): void
    {
        $transition = new TransitionDefinition(
            fromState: 'pending',
            toState: 'active'
        );

        $this->assertSame('pending', $transition->fromState);
        $this->assertSame('active', $transition->toState);
    }

    /**
     * Test that mixed string and enum states work correctly.
     * This ensures comprehensive coverage of the fix.
     */
    public function test_mixed_string_and_enum_states_work_correctly(): void
    {
        // Array constructor with mixed types
        $transition1 = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => 'active',
        ]);
        $this->assertSame(TestFeatureState::Pending, $transition1->fromState);
        $this->assertSame('active', $transition1->toState);

        // Array constructor with reverse mixed types
        $transition2 = new TransitionDefinition([
            'fromState' => 'pending',
            'toState' => TestFeatureState::Active,
        ]);
        $this->assertSame('pending', $transition2->fromState);
        $this->assertSame(TestFeatureState::Active, $transition2->toState);

        // Positional constructor with mixed types
        $transition3 = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: 'active'
        );
        $this->assertSame(TestFeatureState::Pending, $transition3->fromState);
        $this->assertSame('active', $transition3->toState);

        // Positional constructor with reverse mixed types
        $transition4 = new TransitionDefinition(
            fromState: 'pending',
            toState: TestFeatureState::Active
        );
        $this->assertSame('pending', $transition4->fromState);
        $this->assertSame(TestFeatureState::Active, $transition4->toState);
    }
}
