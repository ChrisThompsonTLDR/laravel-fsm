<?php

namespace Tests;

use Fsm\FsmServiceProvider;
use Glhd\Bits\Support\BitsServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestbenchTestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            BitsServiceProvider::class,
            FsmServiceProvider::class,
            \Thunk\Verbs\VerbsServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        // Load FSM package migrations
        $this->loadMigrationsFrom(__DIR__.'/../src/database/migrations');

        // Load test migrations
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
