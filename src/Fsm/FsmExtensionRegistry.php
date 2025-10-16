<?php

declare(strict_types=1);

namespace Fsm;

use Fsm\Contracts\FsmExtension;
use Fsm\Contracts\ModularStateDefinition;
use Fsm\Contracts\ModularTransitionDefinition;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Registry and manager for FSM extensions and modular definitions.
 */
class FsmExtensionRegistry
{
    /**
     * Registered FSM extensions.
     *
     * @var array<string, FsmExtension>
     */
    private array $extensions = [];

    /**
     * Modular state definitions keyed by model:column.
     *
     * @var array<string, array<string, ModularStateDefinition>>
     */
    private array $stateDefinitions = [];

    /**
     * Modular transition definitions keyed by model:column.
     *
     * @var array<string, array<string, ModularTransitionDefinition>>
     */
    private array $transitionDefinitions = [];

    public function __construct(
        private readonly ConfigRepository $config
    ) {
        $this->loadFromConfig();
    }

    /**
     * Register an FSM extension.
     */
    public function registerExtension(FsmExtension $extension): void
    {
        $this->extensions[$extension->getName()] = $extension;
    }

    /**
     * Register a modular state definition.
     */
    public function registerStateDefinition(string $modelClass, string $columnName, ModularStateDefinition $definition): void
    {
        $key = $this->makeKey($modelClass, $columnName);
        $stateKey = is_string($definition->getStateName())
            ? $definition->getStateName()
            : $definition->getStateName()->value;

        $this->stateDefinitions[$key][$stateKey] = $definition;
    }

    /**
     * Register a modular transition definition.
     */
    public function registerTransitionDefinition(string $modelClass, string $columnName, ModularTransitionDefinition $definition): void
    {
        $key = $this->makeKey($modelClass, $columnName);
        $transitionKey = $this->makeTransitionKey($definition);

        $this->transitionDefinitions[$key][$transitionKey] = $definition;
    }

    /**
     * Get all applicable extensions for a given FSM.
     *
     * @return array<FsmExtension>
     */
    public function getExtensionsFor(string $modelClass, string $columnName): array
    {
        $extensions = array_filter(
            $this->extensions,
            fn (FsmExtension $extension) => $extension->appliesTo($modelClass, $columnName)
        );

        // Sort by priority (highest first)
        uasort($extensions, fn (FsmExtension $a, FsmExtension $b) => $b->getPriority() <=> $a->getPriority());

        // Reindex the array numerically so tests can access extensions[0], extensions[1], etc.
        return array_values($extensions);
    }

    /**
     * Get modular state definitions for a given FSM.
     *
     * @return array<string, ModularStateDefinition>
     */
    public function getStateDefinitionsFor(string $modelClass, string $columnName): array
    {
        $key = $this->makeKey($modelClass, $columnName);
        $definitions = $this->stateDefinitions[$key] ?? [];

        // Sort by priority (highest first)
        uasort($definitions, fn (ModularStateDefinition $a, ModularStateDefinition $b) => $b->getPriority() <=> $a->getPriority());

        return $definitions;
    }

    /**
     * Get modular transition definitions for a given FSM.
     *
     * @return array<string, ModularTransitionDefinition>
     */
    public function getTransitionDefinitionsFor(string $modelClass, string $columnName): array
    {
        $key = $this->makeKey($modelClass, $columnName);
        $definitions = $this->transitionDefinitions[$key] ?? [];

        // Sort by priority (highest first)
        uasort($definitions, fn (ModularTransitionDefinition $a, ModularTransitionDefinition $b) => $b->getPriority() <=> $a->getPriority());

        return $definitions;
    }

    /**
     * Load modular definitions from configuration.
     */
    private function loadFromConfig(): void
    {
        $modularConfig = $this->config->get('fsm.modular', []);

        // Load extensions from config
        if (isset($modularConfig['extensions'])) {
            foreach ($modularConfig['extensions'] as $extensionClass) {
                if (class_exists($extensionClass) && is_subclass_of($extensionClass, FsmExtension::class)) {
                    $extension = app($extensionClass);
                    $this->registerExtension($extension);
                }
            }
        }

        // Load state overrides from config
        if (isset($modularConfig['state_overrides'])) {
            foreach ($modularConfig['state_overrides'] as $modelClass => $columns) {
                foreach ($columns as $columnName => $states) {
                    foreach ($states as $stateName => $stateConfig) {
                        $definition = new ConfigStateDefinition($stateName, $stateConfig);
                        $this->registerStateDefinition($modelClass, $columnName, $definition);
                    }
                }
            }
        }

        // Load transition overrides from config
        if (isset($modularConfig['transition_overrides'])) {
            foreach ($modularConfig['transition_overrides'] as $modelClass => $columns) {
                foreach ($columns as $columnName => $transitions) {
                    foreach ($transitions as $transitionConfig) {
                        $definition = new ConfigTransitionDefinition($transitionConfig);
                        $this->registerTransitionDefinition($modelClass, $columnName, $definition);
                    }
                }
            }
        }
    }

    private function makeKey(string $modelClass, string $columnName): string
    {
        return "{$modelClass}:{$columnName}";
    }

    private function makeTransitionKey(ModularTransitionDefinition $definition): string
    {
        $from = $definition->getFromState();
        $fromKey = match (true) {
            $from === null => 'null',
            is_string($from) => $from,
            default => $from->value ?? 'null',
        };

        $to = $definition->getToState();
        $toKey = match (true) {
            $to === null => 'null',
            is_string($to) => $to,
            default => $to->value ?? 'null',
        };

        return "{$fromKey}->{$toKey}:{$definition->getEvent()}";
    }
}

/**
 * Configuration-based state definition implementation.
 */
class ConfigStateDefinition implements ModularStateDefinition
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly string $stateName,
        private readonly array $config
    ) {}

    public function getStateName(): string
    {
        return $this->stateName;
    }

    public function getDefinition(): array
    {
        return $this->config['definition'] ?? [];
    }

    public function shouldOverride(): bool
    {
        return $this->config['override'] ?? false;
    }

    public function getPriority(): int
    {
        return $this->config['priority'] ?? 50;
    }
}

/**
 * Configuration-based transition definition implementation.
 */
class ConfigTransitionDefinition implements ModularTransitionDefinition
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config
    ) {}

    public function getFromState(): ?string
    {
        return $this->config['from'] ?? null;
    }

    public function getToState(): string
    {
        if (! isset($this->config['to'])) {
            throw new \InvalidArgumentException('Transition definition is missing required "to" state configuration');
        }

        return $this->config['to'];
    }

    public function getEvent(): string
    {
        return $this->config['event'];
    }

    public function getDefinition(): array
    {
        return $this->config['definition'] ?? [];
    }

    public function shouldOverride(): bool
    {
        return $this->config['override'] ?? false;
    }

    public function getPriority(): int
    {
        return $this->config['priority'] ?? 50;
    }
}
