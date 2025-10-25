<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Behavioral;

use Fsm\FsmBuilder;
use Fsm\Services\FsmEngineService;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;
use Tests\FsmTestCase;

abstract class BehavioralTestCase extends FsmTestCase
{
    protected FsmEngineService $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureFsmLogInfrastructure();

        // Clear any definitions registered by the base test case so each suite
        // can define the exact behaviour it needs without interference.
        FsmBuilder::reset();

        $this->engine = $this->app->make(FsmEngineService::class);
    }

    /**
     * Ensure the FSM logging tables exist before running behavioural assertions.
     */
    protected function ensureFsmLogInfrastructure(): void
    {
        if (! Schema::hasTable('fsm_logs')) {
            $migration = include base_path('src/database/migrations/2024_01_01_000000_create_fsm_logs_table.php');
            $migration->up();
        }

        if (! Schema::hasColumn('fsm_logs', 'duration_ms')) {
            runFsmDurationMigration();
        }
    }

    /**
     * Register a traffic light FSM tailored for behavioural testing.
     *
     * @param  callable(\Fsm\TransitionBuilder): void  $configure
     */
    protected function defineTrafficLightFsm(callable $configure): void
    {
        $builder = FsmBuilder::for(TrafficLight::class, 'state')
            ->initialState(TrafficLightState::Red)
            ->state(TrafficLightState::Red)
            ->state(TrafficLightState::Yellow)
            ->state(TrafficLightState::Green)
            ->contextDto(TestContextData::class);

        $configure($builder);

        $builder->build();
    }
}
