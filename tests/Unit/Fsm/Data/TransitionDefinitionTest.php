<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionDefinition;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Fsm\Enums\TestFeatureState;

/**
 * Test for TransitionDefinition nullable parameters and validation.
 *
 * Tests the changes where parameters became nullable with defaults and validation logic was updated.
 */
class TransitionDefinitionTest extends TestCase
{
    public function test_constructor_with_all_required_parameters(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            event: 'activate',
            description: 'Activate the feature'
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('activate', $transition->event);
        $this->assertSame('Activate the feature', $transition->description);
    }

    public function test_constructor_allows_null_from_state_for_wildcard(): void
    {
        $transition = new TransitionDefinition(
            fromState: null,
            toState: TestFeatureState::Active
        );

        $this->assertNull($transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
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

    public function test_constructor_with_array_and_all_required_keys(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'event' => 'activate',
            'description' => 'Activate the feature',
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('activate', $transition->event);
        $this->assertSame('Activate the feature', $transition->description);
    }

    public function test_constructor_with_array_using_snake_case_keys(): void
    {
        $transition = new TransitionDefinition([
            'from_state' => TestFeatureState::Pending,
            'to_state' => TestFeatureState::Active,
            'event' => 'activate',
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('activate', $transition->event);
    }

    public function test_constructor_with_array_allows_null_from_state(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => null,
            'toState' => TestFeatureState::Active,
        ]);

        $this->assertNull($transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    public function test_constructor_with_array_allows_null_to_state_for_wildcard(): void
    {
        // This should work - toState can be null for wildcard transitions
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => null,
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_constructor_with_array_throws_exception_when_to_state_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based initialization requires an associative array with a "toState" or "to_state" key.');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'event' => 'activate',
        ]);
    }

    public function test_constructor_with_array_accepts_optional_parameters(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'event' => 'activate',
            'description' => 'Activate the feature',
            'type' => TransitionDefinition::TYPE_TRIGGERED,
            'priority' => TransitionDefinition::PRIORITY_HIGH,
            'behavior' => TransitionDefinition::BEHAVIOR_QUEUED,
            'guardEvaluation' => TransitionDefinition::GUARD_EVALUATION_ANY,
            'metadata' => ['key' => 'value'],
            'isReversible' => true,
            'timeout' => 60,
        ]);

        $this->assertSame('activate', $transition->event);
        $this->assertSame('Activate the feature', $transition->description);
        $this->assertSame(TransitionDefinition::TYPE_TRIGGERED, $transition->type);
        $this->assertSame(TransitionDefinition::PRIORITY_HIGH, $transition->priority);
        $this->assertSame(TransitionDefinition::BEHAVIOR_QUEUED, $transition->behavior);
        $this->assertSame(TransitionDefinition::GUARD_EVALUATION_ANY, $transition->guardEvaluation);
        $this->assertSame(['key' => 'value'], $transition->metadata);
        $this->assertTrue($transition->isReversible);
        $this->assertSame(60, $transition->timeout);
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

    public function test_guards_actions_callbacks_are_collections(): void
    {
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

    public function test_constructor_rejects_empty_array_for_from_state(): void
    {
        $this->expectException(\TypeError::class);

        new TransitionDefinition(
            fromState: [],
            toState: TestFeatureState::Active
        );
    }

    public function test_constructor_rejects_non_associative_array_for_from_state(): void
    {
        $this->expectException(\TypeError::class);

        new TransitionDefinition(
            fromState: [TestFeatureState::Pending, TestFeatureState::Active],
            toState: TestFeatureState::Active
        );
    }

    public function test_constructor_rejects_associative_array_without_to_state_key(): void
    {
        $this->expectException(\TypeError::class);

        new TransitionDefinition(
            fromState: ['event' => 'test'],
            toState: TestFeatureState::Active
        );
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

    public function test_constructor_with_array_explicit_null_to_state_works(): void
    {
        // This should work - toState can be null for wildcard transitions
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => null,
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_constructor_with_array_snake_case_explicit_null_to_state_works(): void
    {
        // This should work - toState can be null for wildcard transitions
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'to_state' => null,
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_constructor_with_array_missing_to_state_key_throws_consistent_error(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based initialization requires an associative array with a "toState" or "to_state" key.');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'event' => 'activate',
        ]);
    }

    public function test_constructor_with_array_non_associative_throws_consistent_error(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based initialization requires an associative array with a "toState" or "to_state" key.');

        new TransitionDefinition([
            TestFeatureState::Pending,
            TestFeatureState::Active,
        ]);
    }

    public function test_constructor_with_array_empty_array_throws_consistent_error(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based initialization requires an associative array with a "toState" or "to_state" key.');

        new TransitionDefinition([]);
    }

    public function test_constructor_with_array_collection_properties_not_overwritten(): void
    {
        // Test that collection properties are properly initialized by the DTO casting
        // and not overwritten by manual initialization
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => ['guard1', 'guard2'], // These should be cast to Collection
            'actions' => ['action1', 'action2'], // These should be cast to Collection
            'onTransitionCallbacks' => ['callback1', 'callback2'], // These should be cast to Collection
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify the collections contain the expected items
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);
    }

    public function test_constructor_with_array_default_collection_properties(): void
    {
        // Test that collection properties default to empty collections when not provided
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    public function test_constructor_with_array_validation_error_messages_are_consistent(): void
    {
        // Test that missing key scenarios give consistent error messages
        $testCases = [
            [
                'data' => ['fromState' => TestFeatureState::Pending],
                'expectedMessage' => 'Array-based initialization requires an associative array with a "toState" or "to_state" key.',
            ],
            [
                'data' => ['event' => 'test'],
                'expectedMessage' => 'Array-based initialization requires an associative array with a "toState" or "to_state" key.',
            ],
        ];

        foreach ($testCases as $testCase) {
            try {
                new TransitionDefinition($testCase['data']);
                $this->fail('Expected exception was not thrown for data: '.json_encode($testCase['data']));
            } catch (\InvalidArgumentException $e) {
                $this->assertSame($testCase['expectedMessage'], $e->getMessage());
            }
        }
    }

    public function test_constructor_with_array_throws_exception_when_from_state_is_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "fromState" value must be a string, FsmStateEnum, or null, got: int');

        new TransitionDefinition([
            'fromState' => 123,
            'toState' => TestFeatureState::Active,
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_to_state_is_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "toState" value must be a string, FsmStateEnum, or null, got: int');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => 456,
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_event_is_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "event" value must be a string or null, got: int');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'event' => 123,
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_description_is_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "description" value must be a string or null, got: array');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'description' => ['not_a_string'],
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_type_is_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "type" value must be a string, got: int');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'type' => 123,
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_behavior_is_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "behavior" value must be a string, got: bool');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'behavior' => true,
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_guard_evaluation_is_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "guardEvaluation" value must be a string, got: int');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guardEvaluation' => 123,
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_priority_is_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "priority" value must be an integer, got: string');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'priority' => 'high',
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_timeout_is_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "timeout" value must be an integer, got: float');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'timeout' => 30.5,
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_is_reversible_is_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "isReversible" value must be a boolean, got: string');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'isReversible' => 'yes',
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_metadata_is_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "metadata" value must be an array, got: string');

        new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'metadata' => 'not_an_array',
        ]);
    }

    public function test_constructor_with_array_accepts_null_for_optional_string_properties(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'event' => null,
            'description' => null,
        ]);

        $this->assertNull($transition->event);
        $this->assertNull($transition->description);
    }

    public function test_constructor_with_array_accepts_string_states(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => 'pending',
            'toState' => 'active',
        ]);

        $this->assertSame('pending', $transition->fromState);
        $this->assertSame('active', $transition->toState);
    }

    public function test_constructor_with_array_accepts_enum_states(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    /**
     * Test that FsmStateEnum instances are properly recognized in type validation.
     * This test specifically verifies the fix for the instanceof bug where
     * \Fsm\Contracts\FsmStateEnum was used instead of the imported FsmStateEnum.
     */
    public function test_constructor_with_array_accepts_fsm_state_enum_instances(): void
    {
        // Test with fromState as FsmStateEnum
        $transition1 = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => 'active',
        ]);
        $this->assertSame(TestFeatureState::Pending, $transition1->fromState);
        $this->assertSame('active', $transition1->toState);

        // Test with toState as FsmStateEnum
        $transition2 = new TransitionDefinition([
            'fromState' => 'pending',
            'toState' => TestFeatureState::Active,
        ]);
        $this->assertSame('pending', $transition2->fromState);
        $this->assertSame(TestFeatureState::Active, $transition2->toState);

        // Test with both states as FsmStateEnum
        $transition3 = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
        ]);
        $this->assertSame(TestFeatureState::Pending, $transition3->fromState);
        $this->assertSame(TestFeatureState::Active, $transition3->toState);

        // Test with null fromState and FsmStateEnum toState
        $transition4 = new TransitionDefinition([
            'fromState' => null,
            'toState' => TestFeatureState::Active,
        ]);
        $this->assertNull($transition4->fromState);
        $this->assertSame(TestFeatureState::Active, $transition4->toState);
    }

    /**
     * Test that FsmStateEnum instances are properly recognized in positional constructor.
     * This test specifically verifies the fix for the instanceof bug.
     */
    public function test_constructor_with_positional_parameters_accepts_fsm_state_enum_instances(): void
    {
        // Test with fromState as FsmStateEnum
        $transition1 = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: 'active'
        );
        $this->assertSame(TestFeatureState::Pending, $transition1->fromState);
        $this->assertSame('active', $transition1->toState);

        // Test with toState as FsmStateEnum
        $transition2 = new TransitionDefinition(
            fromState: 'pending',
            toState: TestFeatureState::Active
        );
        $this->assertSame('pending', $transition2->fromState);
        $this->assertSame(TestFeatureState::Active, $transition2->toState);

        // Test with both states as FsmStateEnum
        $transition3 = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );
        $this->assertSame(TestFeatureState::Pending, $transition3->fromState);
        $this->assertSame(TestFeatureState::Active, $transition3->toState);

        // Test with null fromState and FsmStateEnum toState
        $transition4 = new TransitionDefinition(
            fromState: null,
            toState: TestFeatureState::Active
        );
        $this->assertNull($transition4->fromState);
        $this->assertSame(TestFeatureState::Active, $transition4->toState);
    }

    public function test_constructor_with_array_accepts_null_from_state(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => null,
            'toState' => TestFeatureState::Active,
        ]);

        $this->assertNull($transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    public function test_constructor_with_array_type_validation_error_messages_are_descriptive(): void
    {
        $testCases = [
            [
                'data' => ['fromState' => 123, 'toState' => TestFeatureState::Active],
                'expectedMessage' => 'The "fromState" value must be a string, FsmStateEnum, or null, got: int',
            ],
            [
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => 456],
                'expectedMessage' => 'The "toState" value must be a string, FsmStateEnum, or null, got: int',
            ],
            [
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'event' => 123],
                'expectedMessage' => 'The "event" value must be a string or null, got: int',
            ],
            [
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'description' => ['not_string']],
                'expectedMessage' => 'The "description" value must be a string or null, got: array',
            ],
            [
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'type' => 123],
                'expectedMessage' => 'The "type" value must be a string, got: int',
            ],
            [
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'behavior' => true],
                'expectedMessage' => 'The "behavior" value must be a string, got: bool',
            ],
            [
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'guardEvaluation' => 123],
                'expectedMessage' => 'The "guardEvaluation" value must be a string, got: int',
            ],
            [
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'priority' => 'high'],
                'expectedMessage' => 'The "priority" value must be an integer, got: string',
            ],
            [
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'timeout' => 30.5],
                'expectedMessage' => 'The "timeout" value must be an integer, got: float',
            ],
            [
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'isReversible' => 'yes'],
                'expectedMessage' => 'The "isReversible" value must be a boolean, got: string',
            ],
            [
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'metadata' => 'not_an_array'],
                'expectedMessage' => 'The "metadata" value must be an array, got: string',
            ],
        ];

        foreach ($testCases as $testCase) {
            try {
                new TransitionDefinition($testCase['data']);
                $this->fail('Expected exception was not thrown for data: '.json_encode($testCase['data']));
            } catch (\InvalidArgumentException $e) {
                $this->assertSame($testCase['expectedMessage'], $e->getMessage());
            }
        }
    }
}
