<?php

declare(strict_types=1);

namespace Fsm;

use Fsm\Commands\FsmCacheCommand;
use Fsm\Commands\FsmDiagramCommand;
use Fsm\Services\FsmEngineService;
use Illuminate\Support\ServiceProvider;

class FsmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/fsm.php', // Path relative to project root config
            'fsm'
        );

        $this->app->singleton(Services\BootstrapDetector::class);

        $this->app->singleton(FsmRegistry::class, function ($app) {
            return new FsmRegistry(
                $app->make(Services\BootstrapDetector::class),
                $app->make(\Illuminate\Contracts\Config\Repository::class)
            );
        });

        $this->app->singleton(FsmEngineService::class, function ($app) {
            return new FsmEngineService(
                $app->make(FsmRegistry::class),
                $app->make(Services\FsmLogger::class), // Assuming FsmLogger is also registered or auto-resolved
                $app->make(Services\FsmMetricsService::class),
                $app->make(\Illuminate\Database\DatabaseManager::class),
                $app->make(\Illuminate\Contracts\Config\Repository::class)
            );
        });

        $this->app->singleton(Services\FsmMetricsService::class, function ($app) {
            return new Services\FsmMetricsService(
                $app->make(\Illuminate\Contracts\Events\Dispatcher::class)
            );
        });

        // Bind FsmLogger with ConfigRepository dependency
        $this->app->singleton(Services\FsmLogger::class, function ($app) {
            return new Services\FsmLogger(
                $app->make(\Illuminate\Contracts\Config\Repository::class)
            );
        });

        // Bind PolicyGuard with Gate dependency
        $this->app->singleton(Guards\PolicyGuard::class, function ($app) {
            return new Guards\PolicyGuard(
                $app->make(\Illuminate\Contracts\Auth\Access\Gate::class)
            );
        });

        // Register FSM Extension Registry
        $this->app->singleton(FsmExtensionRegistry::class, function ($app) {
            return new FsmExtensionRegistry(
                $app->make(\Illuminate\Contracts\Config\Repository::class)
            );
        });

        // Register FSM Replay Service
        $this->app->singleton(Services\FsmReplayService::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/fsm.php' => config_path('fsm.php'),
        ], 'fsm-config');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'fsm-migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                FsmDiagramCommand::class,
                FsmCacheCommand::class,
            ]);
        }

        if ($this->app->environment() !== 'testing') {
            $this->app->booted(function () {
                $this->app->make(FsmRegistry::class)->discoverDefinitions();
            });
        }

        // Register event listeners for FSM event logging
        $this->registerEventListeners();
    }

    /**
     * Create a new migration name with the current timestamp.
     */
    protected function generateMigrationName(string $migrationName, ?string $path = null): string
    {
        $path = $path ?: database_path('migrations');
        $timestamp = date('Y_m_d_His');

        $existing = glob($path.DIRECTORY_SEPARATOR."*_{$migrationName}.php");

        if (! empty($existing)) {
            return basename($existing[0]);
        }

        return $path.DIRECTORY_SEPARATOR."{$timestamp}_{$migrationName}.php";
    }

    /**
     * Register event listeners for FSM events if enabled.
     */
    protected function registerEventListeners(): void
    {
        $config = $this->app->make(\Illuminate\Contracts\Config\Repository::class);

        if ($config->get('fsm.event_logging.auto_register_listeners', true)) {
            $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class)
                ->listen(
                    Events\StateTransitioned::class,
                    Listeners\PersistStateTransitionedEvent::class
                );
        }
    }
}
