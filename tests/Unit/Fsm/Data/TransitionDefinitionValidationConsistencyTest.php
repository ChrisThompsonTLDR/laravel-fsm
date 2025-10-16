<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionDefinition;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Fsm\Enums\TestFeatureState;

/**
 * Test for TransitionDefinition constructor validation consistency fixes.
 *
 * Tests that both array-based and positional parameter initialization
 * have consistent validation and reliable collection property initialization.
 */
class TransitionDefinitionValidationConsistencyTest extends TestCase
{
    /**
     * Test that positional parameters have the same validation as array-based initialization.
     */
    public function test_positional_parameters_have_consistent_validation(): void
    {
        $testCases = [
            [
                'name' => 'fromState with invalid type',
                'args' => [123, TestFeatureState::Active],
                'expectedMessage' => 'The "fromState" parameter must be a string, FsmStateEnum, or null, got: int',
            ],
            [
                'name' => 'toState with invalid type',
                'args' => [TestFeatureState::Pending, 456],
                'expectedMessage' => 'The "toState" parameter must be a string or FsmStateEnum, got: int',
            ],
            [
                'name' => 'event with invalid type',
                'args' => [TestFeatureState::Pending, TestFeatureState::Active, 123],
                'expectedMessage' => 'The "event" parameter must be a string or null, got: int',
            ],
            [
                'name' => 'description with invalid type',
                'args' => [TestFeatureState::Pending, TestFeatureState::Active, 'event', [], [], [], ['not_string']],
                'expectedMessage' => 'The "description" parameter must be a string or null, got: array',
            ],
            [
                'name' => 'type with invalid type',
                'args' => [TestFeatureState::Pending, TestFeatureState::Active, 'event', [], [], [], 'description', 123],
                'expectedMessage' => 'The "type" parameter must be a string, got: int',
            ],
            [
                'name' => 'priority with invalid type',
                'args' => [TestFeatureState::Pending, TestFeatureState::Active, 'event', [], [], [], 'description', 'manual', 'high'],
                'expectedMessage' => 'The "priority" parameter must be an integer, got: string',
            ],
            [
                'name' => 'behavior with invalid type',
                'args' => [TestFeatureState::Pending, TestFeatureState::Active, 'event', [], [], [], 'description', 'manual', 50, true],
                'expectedMessage' => 'The "behavior" parameter must be a string, got: bool',
            ],
            [
                'name' => 'guardEvaluation with invalid type',
                'args' => [TestFeatureState::Pending, TestFeatureState::Active, 'event', [], [], [], 'description', 'manual', 50, 'immediate', 123],
                'expectedMessage' => 'The "guardEvaluation" parameter must be a string, got: int',
            ],
            [
                'name' => 'metadata with invalid type',
                'args' => [TestFeatureState::Pending, TestFeatureState::Active, 'event', [], [], [], 'description', 'manual', 50, 'immediate', 'all', 'not_array'],
                'expectedMessage' => 'The "metadata" parameter must be an array, got: string',
            ],
            [
                'name' => 'isReversible with invalid type',
                'args' => [TestFeatureState::Pending, TestFeatureState::Active, 'event', [], [], [], 'description', 'manual', 50, 'immediate', 'all', [], 'yes'],
                'expectedMessage' => 'The "isReversible" parameter must be a boolean, got: string',
            ],
            [
                'name' => 'timeout with invalid type',
                'args' => [TestFeatureState::Pending, TestFeatureState::Active, 'event', [], [], [], 'description', 'manual', 50, 'immediate', 'all', [], true, 'sixty'],
                'expectedMessage' => 'The "timeout" parameter must be an integer, got: string',
            ],
        ];

        foreach ($testCases as $testCase) {
            try {
                new TransitionDefinition(...$testCase['args']);
                $this->fail("Expected exception was not thrown for test case: {$testCase['name']}");
            } catch (\TypeError $e) {
                // PHP's strict typing catches type mismatches before our validation
                $this->assertStringContainsString('must be of type', $e->getMessage(), "Failed for test case: {$testCase['name']}");
            } catch (\InvalidArgumentException $e) {
                $this->assertSame($testCase['expectedMessage'], $e->getMessage(), "Failed for test case: {$testCase['name']}");
            }
        }
    }

    /**
     * Test that array-based initialization validation is preserved.
     */
    public function test_array_based_validation_is_preserved(): void
    {
        $testCases = [
            [
                'name' => 'fromState with invalid type in array',
                'data' => ['fromState' => 123, 'toState' => TestFeatureState::Active],
                'expectedMessage' => 'The "fromState" value must be a string, FsmStateEnum, or null, got: int',
            ],
            [
                'name' => 'toState with invalid type in array',
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => 456],
                'expectedMessage' => 'The "toState" value must be a string, FsmStateEnum, or null, got: int',
            ],
            [
                'name' => 'event with invalid type in array',
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'event' => 123],
                'expectedMessage' => 'The "event" value must be a string or null, got: int',
            ],
            [
                'name' => 'description with invalid type in array',
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'description' => ['not_string']],
                'expectedMessage' => 'The "description" value must be a string or null, got: array',
            ],
            [
                'name' => 'type with invalid type in array',
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'type' => 123],
                'expectedMessage' => 'The "type" value must be a string, got: int',
            ],
            [
                'name' => 'priority with invalid type in array',
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'priority' => 'high'],
                'expectedMessage' => 'The "priority" value must be an integer, got: string',
            ],
            [
                'name' => 'behavior with invalid type in array',
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'behavior' => true],
                'expectedMessage' => 'The "behavior" value must be a string, got: bool',
            ],
            [
                'name' => 'guardEvaluation with invalid type in array',
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'guardEvaluation' => 123],
                'expectedMessage' => 'The "guardEvaluation" value must be a string, got: int',
            ],
            [
                'name' => 'metadata with invalid type in array',
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'metadata' => 'not_an_array'],
                'expectedMessage' => 'The "metadata" value must be an array, got: string',
            ],
            [
                'name' => 'isReversible with invalid type in array',
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'isReversible' => 'yes'],
                'expectedMessage' => 'The "isReversible" value must be a boolean, got: string',
            ],
            [
                'name' => 'timeout with invalid type in array',
                'data' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'timeout' => 30.5],
                'expectedMessage' => 'The "timeout" value must be an integer, got: float',
            ],
        ];

        foreach ($testCases as $testCase) {
            try {
                new TransitionDefinition($testCase['data']);
                $this->fail("Expected exception was not thrown for test case: {$testCase['name']}");
            } catch (\InvalidArgumentException $e) {
                $this->assertSame($testCase['expectedMessage'], $e->getMessage(), "Failed for test case: {$testCase['name']}");
            }
        }
    }

    /**
     * Test that collection properties are initialized reliably without try-catch Error blocks.
     */
    public function test_collection_properties_initialized_reliably_with_positional_parameters(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );

        // Verify collection properties are properly initialized
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty when not provided
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties are initialized reliably with array-based construction.
     */
    public function test_collection_properties_initialized_reliably_with_array_construction(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
        ]);

        // Verify collection properties are properly initialized
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty when not provided
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties are not overwritten when provided via positional parameters.
     */
    public function test_collection_properties_not_overwritten_with_positional_parameters(): void
    {
        $guards = new \Illuminate\Support\Collection(['guard1', 'guard2']);
        $actions = new \Illuminate\Support\Collection(['action1', 'action2']);
        $callbacks = new \Illuminate\Support\Collection(['callback1', 'callback2']);

        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: $guards,
            actions: $actions,
            onTransitionCallbacks: $callbacks
        );

        // Verify collection properties are properly set and not overwritten
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify the collections contain the expected items
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties are not overwritten when provided via array construction.
     */
    public function test_collection_properties_not_overwritten_with_array_construction(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => ['guard1', 'guard2'],
            'actions' => ['action1', 'action2'],
            'onTransitionCallbacks' => ['callback1', 'callback2'],
        ]);

        // Verify collection properties are properly set and not overwritten
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify the collections contain the expected items
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);
    }

    /**
     * Test that both initialization methods produce equivalent results for valid inputs.
     */
    public function test_both_initialization_methods_produce_equivalent_results(): void
    {
        $fromState = TestFeatureState::Pending;
        $toState = TestFeatureState::Active;
        $event = 'activate';
        $description = 'Test transition';
        $type = TransitionDefinition::TYPE_MANUAL;
        $priority = TransitionDefinition::PRIORITY_HIGH;
        $behavior = TransitionDefinition::BEHAVIOR_IMMEDIATE;
        $guardEvaluation = TransitionDefinition::GUARD_EVALUATION_ALL;
        $metadata = ['key' => 'value'];
        $isReversible = true;
        $timeout = 60;

        // Create using positional parameters
        $positionalTransition = new TransitionDefinition(
            fromState: $fromState,
            toState: $toState,
            event: $event,
            description: $description,
            type: $type,
            priority: $priority,
            behavior: $behavior,
            guardEvaluation: $guardEvaluation,
            metadata: $metadata,
            isReversible: $isReversible,
            timeout: $timeout
        );

        // Create using array-based construction
        $arrayTransition = new TransitionDefinition([
            'fromState' => $fromState,
            'toState' => $toState,
            'event' => $event,
            'description' => $description,
            'type' => $type,
            'priority' => $priority,
            'behavior' => $behavior,
            'guardEvaluation' => $guardEvaluation,
            'metadata' => $metadata,
            'isReversible' => $isReversible,
            'timeout' => $timeout,
        ]);

        // Verify both transitions have the same properties
        $this->assertSame($positionalTransition->fromState, $arrayTransition->fromState);
        $this->assertSame($positionalTransition->toState, $arrayTransition->toState);
        $this->assertSame($positionalTransition->event, $arrayTransition->event);
        $this->assertSame($positionalTransition->description, $arrayTransition->description);
        $this->assertSame($positionalTransition->type, $arrayTransition->type);
        $this->assertSame($positionalTransition->priority, $arrayTransition->priority);
        $this->assertSame($positionalTransition->behavior, $arrayTransition->behavior);
        $this->assertSame($positionalTransition->guardEvaluation, $arrayTransition->guardEvaluation);
        $this->assertSame($positionalTransition->metadata, $arrayTransition->metadata);
        $this->assertSame($positionalTransition->isReversible, $arrayTransition->isReversible);
        $this->assertSame($positionalTransition->timeout, $arrayTransition->timeout);

        // Verify collection properties are equivalent
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $positionalTransition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $arrayTransition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $positionalTransition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $arrayTransition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $positionalTransition->onTransitionCallbacks);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $arrayTransition->onTransitionCallbacks);
    }

    /**
     * Test that validation error messages are consistent between both initialization methods.
     */
    public function test_validation_error_messages_are_consistent(): void
    {
        $testCases = [
            [
                'name' => 'fromState with invalid type',
                'positionalArgs' => [123, TestFeatureState::Active],
                'arrayData' => ['fromState' => 123, 'toState' => TestFeatureState::Active],
                'expectedMessage' => 'The "fromState" parameter must be a string, FsmStateEnum, or null, got: int',
                'arrayExpectedMessage' => 'The "fromState" value must be a string, FsmStateEnum, or null, got: int',
            ],
            [
                'name' => 'toState with invalid type',
                'positionalArgs' => [TestFeatureState::Pending, 456],
                'arrayData' => ['fromState' => TestFeatureState::Pending, 'toState' => 456],
                'expectedMessage' => 'The "toState" parameter must be a string or FsmStateEnum, got: int',
                'arrayExpectedMessage' => 'The "toState" value must be a string, FsmStateEnum, or null, got: int',
            ],
            [
                'name' => 'event with invalid type',
                'positionalArgs' => [TestFeatureState::Pending, TestFeatureState::Active, 123],
                'arrayData' => ['fromState' => TestFeatureState::Pending, 'toState' => TestFeatureState::Active, 'event' => 123],
                'expectedMessage' => 'The "event" parameter must be a string or null, got: int',
                'arrayExpectedMessage' => 'The "event" value must be a string or null, got: int',
            ],
        ];

        foreach ($testCases as $testCase) {
            // Test positional parameters
            try {
                new TransitionDefinition(...$testCase['positionalArgs']);
                $this->fail("Expected exception was not thrown for positional test case: {$testCase['name']}");
            } catch (\TypeError $e) {
                // PHP's strict typing catches type mismatches before our validation
                $this->assertStringContainsString('must be of type', $e->getMessage(), "Failed for positional test case: {$testCase['name']}");
            } catch (\InvalidArgumentException $e) {
                $this->assertSame($testCase['expectedMessage'], $e->getMessage(), "Failed for positional test case: {$testCase['name']}");
            }

            // Test array-based construction
            try {
                new TransitionDefinition($testCase['arrayData']);
                $this->fail("Expected exception was not thrown for array test case: {$testCase['name']}");
            } catch (\InvalidArgumentException $e) {
                $this->assertSame($testCase['arrayExpectedMessage'], $e->getMessage(), "Failed for array test case: {$testCase['name']}");
            }
        }
    }
}
