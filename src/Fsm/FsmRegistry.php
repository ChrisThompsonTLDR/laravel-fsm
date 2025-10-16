<?php

declare(strict_types=1);

namespace Fsm;

use Fsm\Contracts\FsmDefinition;
use Fsm\Data\FsmRuntimeDefinition;
use Fsm\Services\BootstrapDetector;
use Fsm\Support\DefinitionDiscoverer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\App;

class FsmRegistry
{
    /**
     * Stores compiled FSM runtime definitions, keyed by model class and then by column name.
     *
     * @var array<string, array<string, FsmRuntimeDefinition>>
     */
    private array $compiledDefinitions = [];

    /**
     * Stores whether FSMs have been discovered and compiled.
     */
    private bool $compiled = false;

    /**
     * Tracks whether we've attempted to load from cache.
     */
    private bool $cacheLoaded = false;

    /**
     * Log a debug message when FSM debug mode is enabled.
     *
     * @param  array<string, mixed>  $context
     */
    private function debug(string $message, array $context = []): void
    {
        if (function_exists('logger') && $this->config->get('fsm.debug', false)) {
            logger()->debug($message, $context);
        }
    }

    public function __construct(
        private readonly BootstrapDetector $bootstrapDetector,
        private readonly ConfigRepository $config
    ) {}

    /**
     * Retrieve a compiled FSM definition for a model and column.
     */
    public function getDefinition(string $modelClass, string $columnName): ?FsmRuntimeDefinition
    {
        if (! $this->compiled) {
            $this->loadFromCache();
            if (! $this->compiled) {
                $this->compileFsmDefinitions();
            }
        }

        return $this->compiledDefinitions[$modelClass][$columnName] ?? null;
    }

    /**
     * Retrieve all compiled FSM definitions for a given model.
     *
     * @return array<string, FsmRuntimeDefinition>
     */
    public function getDefinitionsForModel(string $modelClass): array
    {
        if (! $this->compiled) {
            $this->loadFromCache();
            if (! $this->compiled) {
                $this->compileFsmDefinitions();
            }
        }

        return $this->compiledDefinitions[$modelClass] ?? [];
    }

    /**
     * Discover all FsmDefinition implementations, build them and store as runtime definitions.
     */
    private function compileFsmDefinitions(): void
    {
        $this->debug('FsmRegistry: Entered compileFsmDefinitions()');
        if ($this->compiled) {
            $this->debug('FsmRegistry: Already compiled, returning early');

            return;
        }

        // Additional safety check: prevent discovery during package discovery or bootstrap
        if ($this->bootstrapDetector->inBootstrapMode()) {
            $this->compiled = true;
            $this->debug('FsmRegistry: In bootstrap mode, returning early');

            return;
        }

        $builder = App::make(FsmBuilder::class);

        FsmBuilder::reset();

        // Safely get configuration, distinguishing between missing config and explicitly empty array
        $paths = [];
        try {
            $discoveryConfig = $this->config->get('fsm.discovery_paths');

            // Handle callback configuration for safe package discovery
            if (is_callable($discoveryConfig)) {
                $callableResult = $discoveryConfig();
                // Validate callable return value is an array
                if (is_array($callableResult)) {
                    $paths = $callableResult;
                } else {
                    // Log invalid callable return value for debugging
                    if (function_exists('logger')) {
                        logger()->warning('FsmRegistry: Callable discovery_paths returned non-array value', [
                            'returned_type' => gettype($callableResult),
                            'returned_value' => $callableResult,
                        ]);
                    }
                    $paths = $this->getDefaultDiscoveryPaths();
                }
            } elseif (is_array($discoveryConfig)) {
                $paths = $discoveryConfig;
            } elseif ($discoveryConfig === null) {
                // If config is null (not set), use default path if available
                // Use try/catch for safety as getDefaultDiscoveryPaths might fail in unbootstrapped state
                try {
                    $paths = $this->getDefaultDiscoveryPaths();
                } catch (\Throwable $defaultPathException) {
                    if (function_exists('logger')) {
                        logger()->warning('FsmRegistry: Failed to get default discovery paths', [
                            'exception' => $defaultPathException->getMessage(),
                        ]);
                    }
                    $paths = [];
                }
            } else {
                $paths = [];
            }
        } catch (\Throwable $e) {
            // Log the configuration access issue to provide insight during debugging
            if (function_exists('logger')) {
                logger()->warning('FsmRegistry: Configuration access failed during package discovery', [
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }

            // If config access fails (e.g., during package discovery), use default if available
            try {
                $paths = $this->getDefaultDiscoveryPaths();
            } catch (\Throwable $defaultPathException) {
                if (function_exists('logger')) {
                    logger()->warning('FsmRegistry: Failed to get default discovery paths in exception handler', [
                        'exception' => $defaultPathException->getMessage(),
                    ]);
                }
                $paths = [];
            }
        }

        // Filter paths to only include those that exist
        $this->debug('FsmRegistry: Discovery paths before filter', ['paths' => $paths]);
        $existingPaths = array_filter($paths, function ($path) {
            return is_dir($path);
        });
        $this->debug('FsmRegistry: Discovery paths after filter', ['paths' => $existingPaths]);

        // If no paths exist, skip discovery
        if (empty($existingPaths)) {
            $this->compiled = true;
            $this->debug('FsmRegistry: No discovery paths, returning early');

            return;
        }

        $definitionClasses = DefinitionDiscoverer::discover(array_values($existingPaths));

        foreach ($definitionClasses as $definitionClass) {
            /** @var FsmDefinition $definition */
            $definition = new $definitionClass;
            $definition->define();
        }

        // Apply runtime extensions if enabled
        if ($this->config->get('fsm.modular.runtime_extensions.enabled', true)) {
            $this->applyRuntimeExtensions();
        }

        // Get all compiled definitions from the builder
        $builderDefinitions = FsmBuilder::getDefinitions();

        /**
         * @var class-string $modelClass
         * @var array<string, TransitionBuilder> $columns
         */
        foreach ($builderDefinitions as $modelClass => $columns) {
            // Ensure the class exists before proceeding
            if (! class_exists($modelClass)) {
                continue;
            }

            /** @var array<string, FsmRuntimeDefinition> $modelDefinitions */
            $modelDefinitions = [];

            foreach ($columns as $columnName => $transitionBuilder) {
                $runtimeDefinition = $transitionBuilder->buildRuntimeDefinition();
                $modelDefinitions[$columnName] = $runtimeDefinition;
            }

            $this->compiledDefinitions[$modelClass] = $modelDefinitions;
        }

        $this->compiled = true;

        $this->debug('FsmRegistry: Calling writeCache()');
        $this->writeCache();
        $this->debug('FsmRegistry: Finished compileFsmDefinitions()');
    }

    /**
     * Safely retrieve default discovery paths with consolidated validation.
     *
     * @return array<string>
     */
    private function getDefaultDiscoveryPaths(): array
    {
        // Check if required functions exist
        if (! function_exists('app_path') || ! function_exists('app')) {
            return [];
        }

        // Safely check if app container is available
        try {
            app();
        } catch (\Throwable $appException) {
            // App container not available or failed to access
            return [];
        }

        try {
            return [app_path('Fsm')];
        } catch (\Throwable $appPathException) {
            // Even app_path() failed, so we're likely in package discovery
            return [];
        }
    }

    /**
     * Attempt to load compiled definitions from cache.
     */
    private function loadFromCache(): void
    {
        if ($this->cacheLoaded || ! $this->config->get('fsm.cache.enabled', false)) {
            return;
        }

        $this->cacheLoaded = true;

        $path = $this->config->get('fsm.cache.path', storage_path('framework/cache/fsm.php'));
        if (! is_file($path)) {
            return;
        }

        try {
            $contents = file_get_contents($path);
            if ($contents !== false) {
                $data = unserialize($contents);
                if (is_array($data)) {
                    $this->compiledDefinitions = $data;
                    $this->compiled = true;
                }
            }
        } catch (\Throwable $e) {
            // Ignore cache load failures
        }
    }

    /**
     * Write compiled definitions to cache if enabled.
     */
    private function writeCache(): void
    {
        $this->debug('FsmRegistry: Entered writeCache()');
        $enabled = $this->config->get('fsm.cache.enabled', false);
        $this->debug('FsmRegistry: fsm.cache.enabled', ['enabled' => $enabled]);
        if (! $enabled) {
            $this->debug('FsmRegistry: Caching not enabled, returning early');

            return;
        }

        $path = $this->config->get('fsm.cache.path', storage_path('framework/cache/fsm.php'));
        $dir = dirname($path);
        $this->debug('FsmRegistry: Cache path', ['path' => $path]);
        if (! is_dir($dir)) {
            if (! @mkdir($dir, 0777, true) && ! is_dir($dir)) {
                if (function_exists('logger')) {
                    logger()->warning('FsmRegistry: Failed to create cache directory', ['dir' => $dir]);
                }

                return;
            }
        }
        $this->debug('FsmRegistry: Directory exists, proceeding to write cache');
        try {
            $serialized = serialize($this->compiledDefinitions);
            $this->debug('FsmRegistry: About to write cache file');
            file_put_contents($path, $serialized, LOCK_EX);
            $this->debug('FsmRegistry: Cache written to', ['path' => $path]);
        } catch (\Throwable $e) {
            // Ignore cache write failures, but log for debugging
            if (function_exists('logger')) {
                logger()->warning('FsmRegistry: Failed to write cache', [
                    'exception' => $e->getMessage(),
                    'path' => $path,
                ]);
            }
        }
    }

    /**
     * Remove the cached definitions file.
     */
    public function clearCache(): void
    {
        $path = $this->config->get('fsm.cache.path', storage_path('framework/cache/fsm.php'));
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Force discovery and compilation of FSM definitions.
     */
    public function discoverDefinitions(): void
    {
        $this->compileFsmDefinitions();
    }

    /**
     * Apply runtime extensions to all discovered FSM definitions.
     */
    private function applyRuntimeExtensions(): void
    {
        try {
            $extensionRegistry = App::make(FsmExtensionRegistry::class);
            $builderDefinitions = FsmBuilder::getDefinitions();

            foreach ($builderDefinitions as $modelClass => $columns) {
                foreach ($columns as $columnName => $transitionBuilder) {
                    FsmBuilder::applyExtensions($modelClass, $columnName, $extensionRegistry);
                }
            }
        } catch (\Throwable $e) {
            // Log extension application failures but don't stop compilation
            if (function_exists('logger')) {
                logger()->warning('FsmRegistry: Failed to apply runtime extensions', [
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Manually register or override a compiled FSM definition.
     */
    public function registerDefinition(string $modelClass, string $columnName, FsmRuntimeDefinition $definition): void
    {
        $this->compiledDefinitions[$modelClass][$columnName] = $definition;
        $this->compiled = true;
    }
}
