<?php

declare(strict_types=1);

use Fsm\Contracts\FsmExtension;
use Fsm\Contracts\ModularStateDefinition;
use Fsm\Contracts\ModularTransitionDefinition;
use Fsm\FsmBuilder;
use Fsm\FsmExtensionRegistry;
use Fsm\TransitionBuilder;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\TestCase;

class ModularFsmDefinitionsTest extends TestCase
{
    public function test_extension_registry_can_register_extensions(): void
    {
        $registry = new FsmExtensionRegistry(config());
        $extension = new TestFsmExtension;

        $registry->registerExtension($extension);

        $extensions = $registry->getExtensionsFor(TestModel::class, 'status');
        $this->assertCount(1, $extensions);
        $this->assertSame($extension, $extensions[0]);
    }

    public function test_extension_registry_can_register_state_definitions(): void
    {
        $registry = new FsmExtensionRegistry(config());
        $stateDefinition = new TestStateDefinition('pending', ['description' => 'Test state']);

        $registry->registerStateDefinition(TestModel::class, 'status', $stateDefinition);

        $states = $registry->getStateDefinitionsFor(TestModel::class, 'status');
        $this->assertCount(1, $states);
        $this->assertSame($stateDefinition, $states['pending']);
    }

    public function test_extension_registry_can_register_transition_definitions(): void
    {
        $registry = new FsmExtensionRegistry(config());
        $transitionDefinition = new TestTransitionDefinition('pending', 'confirmed', 'confirm', []);

        $registry->registerTransitionDefinition(TestModel::class, 'status', $transitionDefinition);

        $transitions = $registry->getTransitionDefinitionsFor(TestModel::class, 'status');
        $this->assertCount(1, $transitions);
    }

    public function test_fsm_builder_can_extend_existing_definitions(): void
    {
        FsmBuilder::reset();

        // Create initial FSM definition
        FsmBuilder::for(TestModel::class, 'status')
            ->state('pending', fn ($state) => $state->description('Original pending state'));

        // Extend the FSM
        FsmBuilder::extend(TestModel::class, 'status', function ($builder) {
            $builder->state('new_state', fn ($state) => $state->description('Extended state'));
        });

        $definition = FsmBuilder::getDefinition(TestModel::class, 'status');
        $this->assertNotNull($definition);

        // Verify both original and extended states exist
        $runtimeDefinition = $definition->buildRuntimeDefinition();
        $this->assertArrayHasKey('pending', $runtimeDefinition->states);
        $this->assertArrayHasKey('new_state', $runtimeDefinition->states);
    }

    public function test_fsm_builder_can_override_state_definitions(): void
    {
        FsmBuilder::reset();

        // Create initial FSM definition
        FsmBuilder::for(TestModel::class, 'status')
            ->state('pending', fn ($state) => $state->description('Original description'));

        // Override the state
        FsmBuilder::overrideState(TestModel::class, 'status', 'pending', [
            'description' => 'Overridden description',
        ]);

        $definition = FsmBuilder::getDefinition(TestModel::class, 'status');
        $runtimeDefinition = $definition->buildRuntimeDefinition();

        $pendingState = $runtimeDefinition->states['pending'];
        $this->assertEquals('Overridden description', $pendingState->description);
    }

    public function test_fsm_builder_can_override_transition_definitions(): void
    {
        FsmBuilder::reset();

        // Create initial FSM with transition
        FsmBuilder::for(TestModel::class, 'status')
            ->transition('pending', 'confirmed')
            ->event('confirm')
            ->description('Original transition');

        // Override the transition
        FsmBuilder::overrideTransition(
            TestModel::class,
            'status',
            'pending',
            'confirmed',
            'confirm',
            ['description' => 'Overridden transition']
        );

        $definition = FsmBuilder::getDefinition(TestModel::class, 'status');
        $runtimeDefinition = $definition->buildRuntimeDefinition();

        $transitions = $runtimeDefinition->getTransitionsFor('pending', 'confirm');
        $this->assertCount(1, $transitions);
        $this->assertEquals('Overridden transition', $transitions[0]->description);
    }

    public function test_fsm_builder_applies_extensions_correctly(): void
    {
        FsmBuilder::reset();

        // Create registry with extension
        $registry = new FsmExtensionRegistry(config());
        $extension = new TestFsmExtension;
        $registry->registerExtension($extension);

        // Create initial FSM
        FsmBuilder::for(TestModel::class, 'status')
            ->state('pending', fn ($state) => $state->description('Pending state'));

        // Apply extensions
        FsmBuilder::applyExtensions(TestModel::class, 'status', $registry);

        $definition = FsmBuilder::getDefinition(TestModel::class, 'status');
        $runtimeDefinition = $definition->buildRuntimeDefinition();

        // Verify extension was applied
        $this->assertArrayHasKey('extended_state', $runtimeDefinition->states);
        $this->assertEquals('Extended by test extension', $runtimeDefinition->states['extended_state']->description);
    }

    public function test_config_based_state_overrides_work(): void
    {
        $config = [
            'fsm' => [
                'modular' => [
                    'state_overrides' => [
                        TestModel::class => [
                            'status' => [
                                'pending' => [
                                    'override' => true,
                                    'priority' => 100,
                                    'definition' => [
                                        'description' => 'Config overridden state',
                                        'metadata' => ['config_override' => true],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $configRepo = new \Illuminate\Config\Repository($config);
        $registry = new FsmExtensionRegistry($configRepo);

        $stateDefinitions = $registry->getStateDefinitionsFor(TestModel::class, 'status');
        $this->assertCount(1, $stateDefinitions);

        $pendingState = $stateDefinitions['pending'];
        $this->assertTrue($pendingState->shouldOverride());
        $this->assertEquals(100, $pendingState->getPriority());

        $definition = $pendingState->getDefinition();
        $this->assertEquals('Config overridden state', $definition['description']);
        $this->assertTrue($definition['metadata']['config_override']);
    }

    public function test_config_based_transition_overrides_work(): void
    {
        $config = [
            'fsm' => [
                'modular' => [
                    'transition_overrides' => [
                        TestModel::class => [
                            'status' => [
                                [
                                    'from' => 'pending',
                                    'to' => 'confirmed',
                                    'event' => 'confirm',
                                    'override' => true,
                                    'priority' => 100,
                                    'definition' => [
                                        'description' => 'Config overridden transition',
                                        'guards' => ['custom_guard'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $configRepo = new \Illuminate\Config\Repository($config);
        $registry = new FsmExtensionRegistry($configRepo);

        $transitionDefinitions = $registry->getTransitionDefinitionsFor(TestModel::class, 'status');
        $this->assertCount(1, $transitionDefinitions);

        $transition = array_values($transitionDefinitions)[0];
        $this->assertEquals('pending', $transition->getFromState());
        $this->assertEquals('confirmed', $transition->getToState());
        $this->assertEquals('confirm', $transition->getEvent());
        $this->assertTrue($transition->shouldOverride());

        $definition = $transition->getDefinition();
        $this->assertEquals('Config overridden transition', $definition['description']);
        $this->assertEquals(['custom_guard'], $definition['guards']);
    }

    public function test_extensions_are_sorted_by_priority(): void
    {
        $registry = new FsmExtensionRegistry(config());

        $lowPriorityExtension = new class implements FsmExtension
        {
            public function extend(string $modelClass, string $columnName, TransitionBuilder $builder): void {}

            public function appliesTo(string $modelClass, string $columnName): bool
            {
                return true;
            }

            public function getPriority(): int
            {
                return 10;
            }

            public function getName(): string
            {
                return 'low';
            }
        };

        $highPriorityExtension = new class implements FsmExtension
        {
            public function extend(string $modelClass, string $columnName, TransitionBuilder $builder): void {}

            public function appliesTo(string $modelClass, string $columnName): bool
            {
                return true;
            }

            public function getPriority(): int
            {
                return 100;
            }

            public function getName(): string
            {
                return 'high';
            }
        };

        $registry->registerExtension($lowPriorityExtension);
        $registry->registerExtension($highPriorityExtension);

        $extensions = $registry->getExtensionsFor(TestModel::class, 'status');

        $this->assertCount(2, $extensions);
        $this->assertEquals('high', $extensions[0]->getName());
        $this->assertEquals('low', $extensions[1]->getName());
    }
}

// Test classes
class TestFsmExtension implements FsmExtension
{
    public function extend(string $modelClass, string $columnName, TransitionBuilder $builder): void
    {
        $builder->state('extended_state', fn ($state) => $state->description('Extended by test extension'));
    }

    public function appliesTo(string $modelClass, string $columnName): bool
    {
        return $modelClass === TestModel::class && $columnName === 'status';
    }

    public function getPriority(): int
    {
        return 50;
    }

    public function getName(): string
    {
        return 'test_extension';
    }
}

class TestStateDefinition implements ModularStateDefinition
{
    public function __construct(
        private readonly string $stateName,
        private readonly array $definition,
        private readonly bool $override = false,
        private readonly int $priority = 50
    ) {}

    public function getStateName(): string
    {
        return $this->stateName;
    }

    public function getDefinition(): array
    {
        return $this->definition;
    }

    public function shouldOverride(): bool
    {
        return $this->override;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}

class TestTransitionDefinition implements ModularTransitionDefinition
{
    public function __construct(
        private readonly ?string $fromState,
        private readonly string $toState,
        private readonly string $event,
        private readonly array $definition,
        private readonly bool $override = false,
        private readonly int $priority = 50
    ) {}

    public function getFromState(): ?string
    {
        return $this->fromState;
    }

    public function getToState(): string
    {
        return $this->toState;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getDefinition(): array
    {
        return $this->definition;
    }

    public function shouldOverride(): bool
    {
        return $this->override;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
