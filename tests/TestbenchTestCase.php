<?php

namespace Tests;

use Fsm\FsmServiceProvider;
use Glhd\Bits\Support\BitsServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Thunk\Verbs\VerbsServiceProvider;

abstract class TestbenchTestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            BitsServiceProvider::class,
            FsmServiceProvider::class,
            VerbsServiceProvider::class,
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
