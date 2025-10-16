<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm;

use Fsm\Data\StateDefinition;
use Fsm\FsmBuilder;
use Fsm\TransitionBuilder;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\TestCase;

/**
 * Test class specifically for the overrideState bug fix.
 */
class FsmBuilderOverrideStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        FsmBuilder::reset();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_override_state_receives_correct_object_type(): void
    {
        // Create initial FSM definition
        FsmBuilder::for(TestModel::class, 'status')
            ->state('pending', function ($builder) {
                $this->assertInstanceOf(TransitionBuilder::class, $builder);

                return $builder->description('Original description');
            });

        // Override the state with various configuration options
        FsmBuilder::overrideState(TestModel::class, 'status', 'pending', [
            'description' => 'Overridden description',
            'type' => StateDefinition::TYPE_INITIAL,
            'category' => StateDefinition::CATEGORY_PENDING,
            'behavior' => StateDefinition::BEHAVIOR_PERSISTENT,
            'metadata' => [
                'display_name' => 'Pending Order',
                'color' => '#fbbf24',
                'timeout_hours' => 24,
            ],
            'isTerminal' => false,
            'priority' => 75,
        ]);

        $definition = FsmBuilder::getDefinition(TestModel::class, 'status');
        $runtimeDefinition = $definition->buildRuntimeDefinition();

        $pendingState = $runtimeDefinition->states['pending'];

        // Verify all configuration was applied correctly
        $this->assertEquals('Overridden description', $pendingState->description);
        $this->assertEquals(StateDefinition::TYPE_INITIAL, $pendingState->type);
        $this->assertEquals(StateDefinition::CATEGORY_PENDING, $pendingState->category);
        $this->assertEquals(StateDefinition::BEHAVIOR_PERSISTENT, $pendingState->behavior);
        $this->assertEquals([
            'display_name' => 'Pending Order',
            'color' => '#fbbf24',
            'timeout_hours' => 24,
        ], $pendingState->metadata);
        $this->assertFalse($pendingState->isTerminal);
        $this->assertEquals(75, $pendingState->priority);
    }

    public function test_override_state_handles_partial_configuration(): void
    {
        // Create initial FSM definition
        FsmBuilder::for(TestModel::class, 'status')
            ->state('active', function ($builder) {
                return $builder
                    ->description('Original description')
                    ->type(StateDefinition::TYPE_INTERMEDIATE)
                    ->metadata(['original' => true]);
            });

        // Override only some properties
        FsmBuilder::overrideState(TestModel::class, 'status', 'active', [
            'description' => 'Updated description',
            'category' => 'workflow',
        ]);

        $definition = FsmBuilder::getDefinition(TestModel::class, 'status');
        $runtimeDefinition = $definition->buildRuntimeDefinition();

        $activeState = $runtimeDefinition->states['active'];

        // Verify only specified properties were changed
        $this->assertEquals('Updated description', $activeState->description);
        $this->assertEquals('workflow', $activeState->category);
        // Original properties should be preserved
        $this->assertEquals(StateDefinition::TYPE_INTERMEDIATE, $activeState->type);
        $this->assertEquals(['original' => true], $activeState->metadata);
    }

    public function test_override_state_throws_exception_for_invalid_method(): void
    {
        FsmBuilder::for(TestModel::class, 'status')
            ->state('test', function ($builder) {
                return $builder->description('Test state');
            });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid state configuration method: nonExistentMethod');

        FsmBuilder::overrideState(TestModel::class, 'status', 'test', [
            'nonExistentMethod' => 'some value',
        ]);
    }

    public function test_override_state_handles_null_values(): void
    {
        FsmBuilder::for(TestModel::class, 'status')
            ->state('nullable', function ($builder) {
                return $builder
                    ->description('Original description')
                    ->category('original_category');
            });

        // Override with some null values (should be skipped)
        FsmBuilder::overrideState(TestModel::class, 'status', 'nullable', [
            'description' => 'New description',
            'category' => null, // Should be skipped
            'metadata' => ['key' => 'value'],
        ]);

        $definition = FsmBuilder::getDefinition(TestModel::class, 'status');
        $runtimeDefinition = $definition->buildRuntimeDefinition();

        $nullableState = $runtimeDefinition->states['nullable'];

        // Verify non-null values were applied
        $this->assertEquals('New description', $nullableState->description);
        $this->assertEquals(['key' => 'value'], $nullableState->metadata);
        // Null value should be skipped, keeping original
        $this->assertEquals('original_category', $nullableState->category);
    }

    public function test_override_state_with_array_parameters(): void
    {
        FsmBuilder::for(TestModel::class, 'status')
            ->state('complex', function ($builder) {
                return $builder->description('Original');
            });

        // Test with complex metadata array
        $complexMetadata = [
            'nested' => [
                'level1' => [
                    'level2' => 'deep_value',
                ],
            ],
            'array_values' => [1, 2, 3, 'string'],
            'boolean' => true,
            'integer' => 42,
        ];

        FsmBuilder::overrideState(TestModel::class, 'status', 'complex', [
            'metadata' => $complexMetadata,
            'priority' => 100,
        ]);

        $definition = FsmBuilder::getDefinition(TestModel::class, 'status');
        $runtimeDefinition = $definition->buildRuntimeDefinition();

        $complexState = $runtimeDefinition->states['complex'];

        $this->assertEquals($complexMetadata, $complexState->metadata);
        $this->assertEquals(100, $complexState->priority);
    }

    public function test_override_state_preserves_callbacks(): void
    {
        FsmBuilder::for(TestModel::class, 'status')
            ->state('with_callbacks', function ($builder) {
                return $builder
                    ->description('Original')
                    ->onEntry(function () {
                        // Test callback
                    })
                    ->onExit(function () {
                        // Test callback
                    });
            });

        FsmBuilder::overrideState(TestModel::class, 'status', 'with_callbacks', [
            'description' => 'Updated with callbacks',
            'type' => StateDefinition::TYPE_FINAL,
        ]);

        $definition = FsmBuilder::getDefinition(TestModel::class, 'status');
        $runtimeDefinition = $definition->buildRuntimeDefinition();

        $callbackState = $runtimeDefinition->states['with_callbacks'];

        // Verify configuration was updated
        $this->assertEquals('Updated with callbacks', $callbackState->description);
        $this->assertEquals(StateDefinition::TYPE_FINAL, $callbackState->type);
        // Verify callbacks were preserved
        $this->assertCount(1, $callbackState->onEntryCallbacks);
        $this->assertCount(1, $callbackState->onExitCallbacks);
    }
}
