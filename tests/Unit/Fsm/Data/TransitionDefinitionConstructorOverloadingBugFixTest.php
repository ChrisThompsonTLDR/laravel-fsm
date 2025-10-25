<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionDefinition;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Fsm\Enums\TestFeatureState;

/**
 * Test for TransitionDefinition constructor overloading bug fix.
 *
 * Tests that the constructor no longer overloads the first parameter for both
 * specific field values and array-based DTO construction, making the API clearer.
 */
class TransitionDefinitionConstructorOverloadingBugFixTest extends TestCase
{
    public function test_constructor_with_positional_parameters(): void
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

    public function test_constructor_accepts_array_as_first_parameter(): void
    {
        // This should work - array-based construction is supported
        $transition = new TransitionDefinition(['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    public function test_from_array_static_method_works_correctly(): void
    {
        $data = [
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'event' => 'activate',
            'description' => 'Activate the feature',
        ];

        $transition = TransitionDefinition::fromArray($data);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('activate', $transition->event);
        $this->assertSame('Activate the feature', $transition->description);
    }

    public function test_from_array_with_snake_case_keys(): void
    {
        $data = [
            'from_state' => TestFeatureState::Pending,
            'to_state' => TestFeatureState::Active,
            'event' => 'activate',
        ];

        $transition = TransitionDefinition::fromArray($data);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('activate', $transition->event);
    }

    public function test_from_array_allows_null_from_state(): void
    {
        $data = [
            'fromState' => null,
            'toState' => TestFeatureState::Active,
        ];

        $transition = TransitionDefinition::fromArray($data);

        $this->assertNull($transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
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
            'event' => 'activate',
        ]);
    }

    public function test_from_array_accepts_optional_parameters(): void
    {
        $data = [
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
        ];

        $transition = TransitionDefinition::fromArray($data);

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

    public function test_constructor_api_clarity_improvement(): void
    {
        // Test that the API is now clear - first parameter is always FsmStateEnum|string|null
        $transition1 = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );
        $this->assertSame(TestFeatureState::Pending, $transition1->fromState);
        $this->assertSame(TestFeatureState::Active, $transition1->toState);

        // Test that array-based construction is now explicit via fromArray
        $transition2 = TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'event' => 'activate',
        ]);
        $this->assertSame(TestFeatureState::Pending, $transition2->fromState);
        $this->assertSame(TestFeatureState::Active, $transition2->toState);
        $this->assertSame('activate', $transition2->event);
    }

    public function test_constructor_type_safety_improvement(): void
    {
        // Test that type safety is improved - no more ambiguous parameter types
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            event: 'activate',
            description: 'Type safe transition',
            type: TransitionDefinition::TYPE_MANUAL,
            priority: TransitionDefinition::PRIORITY_NORMAL,
            behavior: TransitionDefinition::BEHAVIOR_IMMEDIATE,
            guardEvaluation: TransitionDefinition::GUARD_EVALUATION_ALL,
            metadata: ['key' => 'value'],
            isReversible: false,
            timeout: 30
        );

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('activate', $transition->event);
        $this->assertSame('Type safe transition', $transition->description);
        $this->assertSame(TransitionDefinition::TYPE_MANUAL, $transition->type);
        $this->assertSame(TransitionDefinition::PRIORITY_NORMAL, $transition->priority);
        $this->assertSame(TransitionDefinition::BEHAVIOR_IMMEDIATE, $transition->behavior);
        $this->assertSame(TransitionDefinition::GUARD_EVALUATION_ALL, $transition->guardEvaluation);
        $this->assertSame(['key' => 'value'], $transition->metadata);
        $this->assertFalse($transition->isReversible);
        $this->assertSame(30, $transition->timeout);
    }

    public function test_from_array_handles_all_properties(): void
    {
        $data = [
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'event' => 'activate',
            'description' => 'Complex transition',
            'type' => TransitionDefinition::TYPE_TRIGGERED,
            'priority' => TransitionDefinition::PRIORITY_HIGH,
            'behavior' => TransitionDefinition::BEHAVIOR_QUEUED,
            'guardEvaluation' => TransitionDefinition::GUARD_EVALUATION_ANY,
            'metadata' => ['complex' => ['nested' => 'data']],
            'isReversible' => true,
            'timeout' => 60,
            'guards' => ['guard1', 'guard2'],
            'actions' => ['action1', 'action2'],
            'onTransitionCallbacks' => ['callback1', 'callback2'],
        ];

        $transition = TransitionDefinition::fromArray($data);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertSame('activate', $transition->event);
        $this->assertSame('Complex transition', $transition->description);
        $this->assertSame(TransitionDefinition::TYPE_TRIGGERED, $transition->type);
        $this->assertSame(TransitionDefinition::PRIORITY_HIGH, $transition->priority);
        $this->assertSame(TransitionDefinition::BEHAVIOR_QUEUED, $transition->behavior);
        $this->assertSame(TransitionDefinition::GUARD_EVALUATION_ANY, $transition->guardEvaluation);
        $this->assertSame(['complex' => ['nested' => 'data']], $transition->metadata);
        $this->assertTrue($transition->isReversible);
        $this->assertSame(60, $transition->timeout);
    }

    public function test_from_array_uses_defaults_for_missing_properties(): void
    {
        $data = [
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
        ];

        $transition = TransitionDefinition::fromArray($data);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
        $this->assertNull($transition->event); // Default null
        $this->assertNull($transition->description); // Default null
        $this->assertSame(TransitionDefinition::TYPE_MANUAL, $transition->type); // Default
        $this->assertSame(TransitionDefinition::PRIORITY_NORMAL, $transition->priority); // Default
        $this->assertSame(TransitionDefinition::BEHAVIOR_IMMEDIATE, $transition->behavior); // Default
        $this->assertSame(TransitionDefinition::GUARD_EVALUATION_ALL, $transition->guardEvaluation); // Default
        $this->assertSame([], $transition->metadata); // Default empty array
        $this->assertFalse($transition->isReversible); // Default false
        $this->assertSame(30, $transition->timeout); // Default 30
    }

    public function test_from_array_validation_error_messages(): void
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
                TransitionDefinition::fromArray($testCase['data']);
                $this->fail('Expected exception was not thrown for data: '.json_encode($testCase['data']));
            } catch (InvalidArgumentException $e) {
                $this->assertSame($testCase['expectedMessage'], $e->getMessage());
            }
        }
    }

    public function test_from_array_accepts_null_for_optional_string_properties(): void
    {
        $data = [
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'event' => null,
            'description' => null,
        ];

        $transition = TransitionDefinition::fromArray($data);

        $this->assertNull($transition->event);
        $this->assertNull($transition->description);
    }

    public function test_from_array_accepts_string_states(): void
    {
        $data = [
            'fromState' => 'pending',
            'toState' => 'active',
        ];

        $transition = TransitionDefinition::fromArray($data);

        $this->assertSame('pending', $transition->fromState);
        $this->assertSame('active', $transition->toState);
    }

    public function test_from_array_accepts_enum_states(): void
    {
        $data = [
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
        ];

        $transition = TransitionDefinition::fromArray($data);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    public function test_constructor_without_arguments_works(): void
    {
        // This should work - both fromState and toState have default values
        $transition = new TransitionDefinition;

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
    }

    public function test_constructor_with_only_from_state_works(): void
    {
        // This should work - toState has a default value of null
        $transition = new TransitionDefinition(fromState: TestFeatureState::Pending);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertNull($transition->toState);
    }
}
