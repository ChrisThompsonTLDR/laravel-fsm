<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm;

use Fsm\FsmRegistry;
use Fsm\FsmServiceProvider;
use Fsm\Services\FsmEngineService;
use Fsm\Services\FsmLogger;
use Fsm\Services\FsmMetricsService;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;

class TestFsmServiceProvider extends FsmServiceProvider
{
    public function register(): void
    {
        // Call parent register but override the FsmEngineService binding
        parent::register();

        // Override the singleton binding to use a regular binding for testing
        $this->app->bind(FsmEngineService::class, function ($app) {
            return new FsmEngineService(
                $app->make(FsmRegistry::class),
                $app->make(FsmLogger::class),
                $app->make(FsmMetricsService::class),
                $app->make(DatabaseManager::class),
                $app->make(Repository::class)
            );
        });
    }
}
