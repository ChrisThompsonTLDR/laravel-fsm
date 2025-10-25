<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionAction;
use Fsm\Data\TransitionCallback;
use Fsm\Data\TransitionDefinition;
use Fsm\Data\TransitionGuard;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Fsm\Enums\TestFeatureState;

/**
 * Test for TransitionDefinition constructor bug fix.
 *
 * Tests that the constructor's func_num_args() check is fixed and that
 * initializeCollectionProperties() runs before the parent constructor to prevent data loss.
 */
class TransitionDefinitionConstructorBugFixTest extends TestCase
{
    public function test_constructor_with_named_parameters_works_correctly(): void
    {
        // This should work with named parameters - the old func_num_args() check was unreliable
        $transition = new TransitionDefinition(
            toState: TestFeatureState::Active,
            fromState: TestFeatureState::Pending
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    public function test_constructor_with_named_parameters_and_defaults(): void
    {
        // Test that named parameters work with default values
        $transition = new TransitionDefinition(
            toState: TestFeatureState::Active,
            fromState: TestFeatureState::Pending,
            event: 'activate',
            description: 'Test transition'
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('activate', $transition->event);
        $this->assertSame('Test transition', $transition->description);
    }

    public function test_constructor_allows_null_to_state_for_wildcard(): void
    {
        // This should work - toState can be null for wildcard transitions
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: null
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_constructor_works_with_missing_to_state(): void
    {
        // This should work - toState has a default value of null
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_constructor_works_with_no_arguments(): void
    {
        // This should work - both fromState and toState have default values
        $transition = new TransitionDefinition;

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_constructor_allows_null_from_state_for_wildcard(): void
    {
        // This should work - fromState can be null for wildcard transitions
        $transition = new TransitionDefinition(
            fromState: null,
            toState: TestFeatureState::Active
        );

        $this->assertNull($transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    public function test_constructor_preserves_collection_data(): void
    {
        // Test that collection properties are not reset after parent constructor
        $guards = collect([new TransitionGuard('guard1'), new TransitionGuard('guard2')]);
        $actions = collect([new TransitionAction('action1'), new TransitionAction('action2')]);
        $callbacks = collect([new TransitionCallback('callback1'), new TransitionCallback('callback2')]);

        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: $guards,
            actions: $actions,
            onTransitionCallbacks: $callbacks
        );

        // Verify that the collections are preserved and not reset to empty
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);

        $this->assertSame($guards->toArray(), $transition->guards->toArray());
        $this->assertSame($actions->toArray(), $transition->actions->toArray());
        $this->assertSame($callbacks->toArray(), $transition->onTransitionCallbacks->toArray());
    }

    public function test_constructor_preserves_array_collection_data(): void
    {
        // Test that array collections are properly converted and preserved
        $guards = [new TransitionGuard('guard1'), new TransitionGuard('guard2')];
        $actions = [new TransitionAction('action1'), new TransitionAction('action2')];
        $callbacks = [new TransitionCallback('callback1'), new TransitionCallback('callback2')];

        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: $guards,
            actions: $actions,
            onTransitionCallbacks: $callbacks
        );

        // Verify that the arrays are converted to collections and preserved
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);
    }

    public function test_constructor_with_empty_collections(): void
    {
        // Test that empty collections are properly initialized
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: [],
            actions: [],
            onTransitionCallbacks: []
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    public function test_constructor_with_default_collections(): void
    {
        // Test that default collections are properly initialized when not provided
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    public function test_from_array_allows_null_to_state_for_wildcard(): void
    {
        // This should work - toState can be null for wildcard transitions
        $transition = TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'toState' => null,
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_from_array_throws_exception_when_to_state_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based initialization requires an associative array with a "toState" or "to_state" key.');

        TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
        ]);
    }

    public function test_from_array_preserves_collection_data(): void
    {
        // Test that fromArray also preserves collection data
        $data = [
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => [new TransitionGuard('guard1'), new TransitionGuard('guard2')],
            'actions' => [new TransitionAction('action1'), new TransitionAction('action2')],
            'onTransitionCallbacks' => [new TransitionCallback('callback1'), new TransitionCallback('callback2')],
        ];

        $transition = TransitionDefinition::fromArray($data);

        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);
    }

    public function test_constructor_with_string_states(): void
    {
        $transition = new TransitionDefinition(
            fromState: 'pending',
            toState: 'active'
        );

        $this->assertSame('pending', $transition->fromState);
        $this->assertSame('active', $transition->toState);
    }

    public function test_constructor_with_enum_states(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    public function test_constructor_with_mixed_state_types(): void
    {
        $transition = new TransitionDefinition(
            fromState: 'pending',
            toState: TestFeatureState::Active
        );

        $this->assertSame('pending', $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    public function test_constructor_validation_consistency(): void
    {
        // Test that validation is consistent between constructor and fromArray
        $testCases = [
            [
                'description' => 'Invalid fromState type',
                'constructor' => fn () => new TransitionDefinition(fromState: 123, toState: TestFeatureState::Active),
                'fromArray' => fn () => TransitionDefinition::fromArray(['fromState' => 123, 'toState' => TestFeatureState::Active]),
                'expectedException' => \TypeError::class,
            ],
            [
                'description' => 'Invalid toState type',
                'constructor' => fn () => new TransitionDefinition(fromState: TestFeatureState::Pending, toState: 456),
                'fromArray' => fn () => TransitionDefinition::fromArray(['fromState' => TestFeatureState::Pending, 'toState' => 456]),
                'expectedException' => \TypeError::class,
            ],
        ];

        foreach ($testCases as $testCase) {
            // Test constructor - should throw TypeError due to PHP's type system
            try {
                $testCase['constructor']();
                $this->fail("Expected exception for constructor: {$testCase['description']}");
            } catch (\TypeError $e) {
                $this->assertStringContainsString('must be of type', $e->getMessage());
            }

            // Test fromArray - should throw InvalidArgumentException due to our validation
            try {
                $testCase['fromArray']();
                $this->fail("Expected exception for fromArray: {$testCase['description']}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('must be', $e->getMessage());
            }
        }
    }

    public function test_constructor_handles_all_parameters(): void
    {
        $guards = collect([new TransitionGuard('guard1')]);
        $actions = collect([new TransitionAction('action1')]);
        $callbacks = collect([new TransitionCallback('callback1')]);

        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            event: 'activate',
            guards: $guards,
            actions: $actions,
            onTransitionCallbacks: $callbacks,
            description: 'Test transition',
            type: TransitionDefinition::TYPE_TRIGGERED,
            priority: TransitionDefinition::PRIORITY_HIGH,
            behavior: TransitionDefinition::BEHAVIOR_QUEUED,
            guardEvaluation: TransitionDefinition::GUARD_EVALUATION_ANY,
            metadata: ['key' => 'value'],
            isReversible: true,
            timeout: 60
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('activate', $transition->event);
        $this->assertSame('Test transition', $transition->description);
        $this->assertSame(TransitionDefinition::TYPE_TRIGGERED, $transition->type);
        $this->assertSame(TransitionDefinition::PRIORITY_HIGH, $transition->priority);
        $this->assertSame(TransitionDefinition::BEHAVIOR_QUEUED, $transition->behavior);
        $this->assertSame(TransitionDefinition::GUARD_EVALUATION_ANY, $transition->guardEvaluation);
        $this->assertSame(['key' => 'value'], $transition->metadata);
        $this->assertTrue($transition->isReversible);
        $this->assertSame(60, $transition->timeout);

        // Verify collections are preserved
        $this->assertCount(1, $transition->guards);
        $this->assertCount(1, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);
    }

    public function test_constructor_bug_fix_verification(): void
    {
        // This test specifically verifies that the bug is fixed
        // The old func_num_args() check would fail with named parameters
        $transition = new TransitionDefinition(
            toState: TestFeatureState::Active,
            fromState: TestFeatureState::Pending,
            event: 'activate'
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('activate', $transition->event);

        // Verify that collections are properly initialized and not reset
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);
    }
}
