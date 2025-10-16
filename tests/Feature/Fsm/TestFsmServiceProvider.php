<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm;

use Fsm\FsmServiceProvider;
use Fsm\Services\FsmEngineService;

class TestFsmServiceProvider extends FsmServiceProvider
{
    public function register(): void
    {
        // Call parent register but override the FsmEngineService binding
        parent::register();

        // Override the singleton binding to use a regular binding for testing
        $this->app->bind(FsmEngineService::class, function ($app) {
            return new FsmEngineService(
                $app->make(\Fsm\FsmRegistry::class),
                $app->make(\Fsm\Services\FsmLogger::class),
                $app->make(\Fsm\Services\FsmMetricsService::class),
                $app->make(\Illuminate\Database\DatabaseManager::class),
                $app->make(\Illuminate\Contracts\Config\Repository::class)
            );
        });
    }
}
