<?php

declare(strict_types=1);

namespace Tests;

use Fsm\FsmBuilder;
use Fsm\FsmRegistry;
use Fsm\Services\FsmEngineService;
use Fsm\Services\FsmLogger;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\Feature\Fsm\Definitions\OrderStatusFsm;
use Tests\Feature\Fsm\Definitions\PaymentStatusFsm;
use Tests\Feature\Fsm\Definitions\TestFeatureFsmDefinition;
use Tests\Feature\Fsm\Services\TestSpyService;

abstract class FsmTestCase extends TestbenchTestCase
{
    use RefreshDatabase;

    protected TestSpyService $spyService;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $app['config']->set('fsm.logging.enabled', true);
        $app['config']->set('fsm.logging.log_failures', false);
        $app['config']->set('fsm.verbs.dispatch_transitioned_verb', false);
        $app['config']->set('fsm.use_transactions', true);

        $app['config']->set('data', [
            'validation_strategy' => 'always',
            'max_transformation_depth' => 512,
            'throw_when_max_transformation_depth_reached' => false,
        ]);

        $app['config']->set('verbs.migrations', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Fake events by default so most tests can use the convenient assertion
        // helpers, but allow certain events to pass through to real listeners so
        // tests that rely on synchronous event handling (e.g. EventCoexistenceTest)
        // still function correctly.
        Event::fakeExcept([
            \Fsm\Events\TransitionSucceeded::class,
            \Fsm\Events\StateTransitioned::class,
        ]);

        \Thunk\Verbs\Facades\Verbs::fake();

        $this->spyService = $this->app->make(TestSpyService::class);
        $this->spyService->reset();
        TestSpyService::$staticCalled = [];
        $this->app->instance(TestSpyService::class, $this->spyService);

        FsmBuilder::reset();
        (new OrderStatusFsm)->define();
        (new PaymentStatusFsm)->define();
        (new TestFeatureFsmDefinition)->define();

        // FSM definitions now register themselves via ->build() calls
        // No need for manual registration loop

        $this->app->forgetInstance(FsmEngineService::class);
        $this->app->singleton(FsmEngineService::class, function ($app) {
            return new FsmEngineService(
                $app->make(FsmRegistry::class),
                $app->make(FsmLogger::class),
                $app->make(\Fsm\Services\FsmMetricsService::class),
                $app->make(DatabaseManager::class),
                $app->make(ConfigRepository::class),
            );
        });

    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        $this->spyService->reset();
        TestSpyService::$staticCalled = [];

        // Recreate FsmRegistry to clear compiled state
        $this->app->forgetInstance(FsmRegistry::class);

        parent::tearDown();
    }
}
