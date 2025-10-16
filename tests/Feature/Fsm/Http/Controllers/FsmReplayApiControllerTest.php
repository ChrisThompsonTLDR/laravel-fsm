<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Http\Controllers;

use Fsm\FsmBuilder;
use Fsm\FsmServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;

class FsmReplayApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            \Glhd\Bits\Support\BitsServiceProvider::class,
            FsmServiceProvider::class,
            \Thunk\Verbs\VerbsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Configure database
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        // Configure migrations for Testbench
        $app['config']->set('database.migrations.paths', [
            __DIR__.'/../../../../database/migrations',
        ]);

        $app['router']->post('fsm/replay/history', [\Fsm\Http\Controllers\FsmReplayApiController::class, 'getHistory'])->name('fsm.replay.history');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Load FSM package migrations
        $this->loadMigrationsFrom(__DIR__.'/../../../../../src/database/migrations');

        // Load test migrations
        $this->loadMigrationsFrom(__DIR__.'/../../../../database/migrations');

        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)
            ->build();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_get_history_returns_successful_response_with_valid_input(): void
    {
        $model = TestModel::factory()->create();

        $response = $this->postJson(route('fsm.replay.history'), [
            'modelClass' => TestModel::class,
            'modelId' => (string) $model->id,
            'columnName' => 'status',
        ]);

        $response->assertOk();
    }
}
