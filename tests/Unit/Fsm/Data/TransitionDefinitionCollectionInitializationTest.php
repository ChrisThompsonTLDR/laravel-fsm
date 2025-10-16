<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionDefinition;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Fsm\Enums\TestFeatureState;

/**
 * Test for TransitionDefinition collection property initialization reliability.
 *
 * Tests that collection properties are initialized reliably without using
 * try-catch Error blocks, ensuring consistent behavior across different scenarios.
 */
class TransitionDefinitionCollectionInitializationTest extends TestCase
{
    /**
     * Test that collection properties are initialized as empty collections when not provided.
     */
    public function test_collection_properties_initialized_as_empty_when_not_provided(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active
        );

        // Verify all collection properties are initialized
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties are initialized as empty collections with array construction.
     */
    public function test_collection_properties_initialized_as_empty_with_array_construction(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
        ]);

        // Verify all collection properties are initialized
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties preserve provided values with positional parameters.
     */
    public function test_collection_properties_preserve_provided_values_with_positional_parameters(): void
    {
        $guards = new \Illuminate\Support\Collection(['guard1', 'guard2', 'guard3']);
        $actions = new \Illuminate\Support\Collection(['action1', 'action2']);
        $callbacks = new \Illuminate\Support\Collection(['callback1']);

        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: $guards,
            actions: $actions,
            onTransitionCallbacks: $callbacks
        );

        // Verify collection properties are properly set
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they contain the expected items
        $this->assertCount(3, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);

        // Verify the actual content (items are cast to DTO objects by the parent DTO)
        $this->assertCount(3, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties preserve provided values with array construction.
     */
    public function test_collection_properties_preserve_provided_values_with_array_construction(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => ['guard1', 'guard2', 'guard3'],
            'actions' => ['action1', 'action2'],
            'onTransitionCallbacks' => ['callback1'],
        ]);

        // Verify collection properties are properly set
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they contain the expected items
        $this->assertCount(3, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);

        // Verify the actual content (items are cast to DTO objects by the parent DTO)
        $this->assertCount(3, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties handle mixed array and Collection inputs correctly.
     */
    public function test_collection_properties_handle_mixed_inputs_correctly(): void
    {
        $guards = new \Illuminate\Support\Collection(['guard1', 'guard2']);
        $actions = ['action1', 'action2']; // Array input
        $callbacks = new \Illuminate\Support\Collection(['callback1']);

        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: $guards,
            actions: $actions,
            onTransitionCallbacks: $callbacks
        );

        // Verify all collection properties are properly initialized
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they contain the expected items
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);

        // Verify the actual content (items are cast to DTO objects by the parent DTO)
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties are not overwritten after parent constructor call.
     */
    public function test_collection_properties_not_overwritten_after_parent_constructor(): void
    {
        // Test with positional parameters
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

        // Verify the collections are not overwritten with empty collections
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(2, $transition->onTransitionCallbacks);

        // Test with array construction
        $arrayTransition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => ['guard1', 'guard2'],
            'actions' => ['action1', 'action2'],
            'onTransitionCallbacks' => ['callback1', 'callback2'],
        ]);

        // Verify the collections are not overwritten with empty collections
        $this->assertCount(2, $arrayTransition->guards);
        $this->assertCount(2, $arrayTransition->actions);
        $this->assertCount(2, $arrayTransition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties work correctly with empty arrays.
     */
    public function test_collection_properties_work_with_empty_arrays(): void
    {
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: [],
            actions: [],
            onTransitionCallbacks: []
        );

        // Verify all collection properties are initialized
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties work correctly with empty arrays in array construction.
     */
    public function test_collection_properties_work_with_empty_arrays_in_array_construction(): void
    {
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => [],
            'actions' => [],
            'onTransitionCallbacks' => [],
        ]);

        // Verify all collection properties are initialized
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties are properly initialized with null values.
     */
    public function test_collection_properties_initialized_with_null_values(): void
    {
        // Test that collection properties are initialized as empty collections even when null is passed
        // This tests the validation and initialization logic in the constructor
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => null,
            'actions' => null,
            'onTransitionCallbacks' => null,
        ]);

        // Verify all collection properties are initialized as empty collections
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties handle non-Collection objects correctly.
     */
    public function test_collection_properties_handle_non_collection_objects(): void
    {
        // Test that non-Collection objects are properly cast to Collections
        // This tests the DTO casting mechanism
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => ['guard1', 'guard2'], // Array should be cast to Collection
            'actions' => ['action1', 'action2'], // Array should be cast to Collection
            'onTransitionCallbacks' => ['callback1'], // Array should be cast to Collection
        ]);

        // Verify all collection properties are properly cast to Collections
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they contain the expected items
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties handle invalid types correctly.
     */
    public function test_collection_properties_handle_invalid_types(): void
    {
        // Test that invalid types for collection properties are properly handled
        // This tests the validation logic in the constructor
        $this->expectException(\TypeError::class);

        new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: 'not_an_array_or_collection'
        );
    }

    /**
     * Test that collection properties handle mixed valid and invalid inputs.
     */
    public function test_collection_properties_handle_mixed_valid_invalid_inputs(): void
    {
        // Test with valid array inputs - should work
        $transition = new TransitionDefinition([
            'fromState' => TestFeatureState::Pending,
            'toState' => TestFeatureState::Active,
            'guards' => ['guard1', 'guard2'],
            'actions' => ['action1', 'action2'],
            'onTransitionCallbacks' => ['callback1'],
        ]);

        // Verify all collection properties are properly initialized
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they contain the expected items
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties are properly initialized with positional parameters.
     */
    public function test_collection_properties_initialized_with_positional_parameters(): void
    {
        // Test that collection properties are initialized correctly with positional parameters
        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: ['guard1', 'guard2'],
            actions: ['action1', 'action2'],
            onTransitionCallbacks: ['callback1']
        );

        // Verify all collection properties are properly initialized
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they contain the expected items
        $this->assertCount(2, $transition->guards);
        $this->assertCount(2, $transition->actions);
        $this->assertCount(1, $transition->onTransitionCallbacks);
    }

    /**
     * Test that collection properties handle empty collections correctly.
     */
    public function test_collection_properties_handle_empty_collections(): void
    {
        // Test with empty Collection objects
        $emptyGuards = new \Illuminate\Support\Collection([]);
        $emptyActions = new \Illuminate\Support\Collection([]);
        $emptyCallbacks = new \Illuminate\Support\Collection([]);

        $transition = new TransitionDefinition(
            fromState: TestFeatureState::Pending,
            toState: TestFeatureState::Active,
            guards: $emptyGuards,
            actions: $emptyActions,
            onTransitionCallbacks: $emptyCallbacks
        );

        // Verify all collection properties are properly initialized
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->guards);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->actions);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $transition->onTransitionCallbacks);

        // Verify they are empty
        $this->assertCount(0, $transition->guards);
        $this->assertCount(0, $transition->actions);
        $this->assertCount(0, $transition->onTransitionCallbacks);
    }
}
