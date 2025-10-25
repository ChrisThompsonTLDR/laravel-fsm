<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Behavioral;

use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\Models\FsmLog;
use Illuminate\Support\Facades\Config;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Services\TestSpyService;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;

class ConfigurationBehavioralTest extends BehavioralTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->defineTrafficLightFsm(function ($builder) {
            $builder->from(TrafficLightState::Red)
                ->to(TrafficLightState::Yellow)
                ->event('cycle')
                ->action(TestSpyService::class.'@anAction', [], true);

            $builder->from(TrafficLightState::Yellow)
                ->to(TrafficLightState::Green)
                ->event('cycle');
        });
    }

    public function test_transactions_roll_back_state_when_post_transition_action_fails(): void
    {
        Config::set('fsm.use_transactions', true);
        Config::set('fsm.logging.log_failures', true);

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('cycle', 1, true);

        try {
            $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);
            $this->fail('Expected transition failure when post-transition action throws.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertSame(TrafficLightState::Red, $light->fresh()->state);
            $this->assertStringContainsString('Simulated action failure', $exception->reason);

            $this->assertTrue(
                FsmLog::query()
                    ->where('model_id', (string) $light->id)
                    ->where('from_state', TrafficLightState::Red->value)
                    ->where('to_state', TrafficLightState::Yellow->value)
                    ->exists()
            );
        }
    }

    public function test_disabling_transactions_leaves_state_updated_when_action_fails_after_save(): void
    {
        Config::set('fsm.use_transactions', false);
        Config::set('fsm.logging.log_failures', false);

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('cycle', 2, true);

        try {
            $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);
            $this->fail('Expected transition failure when post-transition action throws.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertSame(TrafficLightState::Yellow, $light->fresh()->state);
            $this->assertStringContainsString('Simulated action failure', $exception->reason);

            $this->assertFalse(
                FsmLog::query()
                    ->where('model_id', (string) $light->id)
                    ->exists()
            );
        }
    }

    public function test_disabling_logging_prevents_successful_transition_logs(): void
    {
        Config::set('fsm.logging.enabled', false);
        Config::set('fsm.logging.log_failures', false);

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('cycle', 3);

        $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);

        $this->assertSame(TrafficLightState::Yellow, $light->fresh()->state);
        $this->assertSame(0, FsmLog::query()->count());
    }

    public function test_enabling_failure_logging_records_failed_transition_entry(): void
    {
        Config::set('fsm.logging.enabled', true);
        Config::set('fsm.logging.log_failures', true);
        Config::set('fsm.use_transactions', true);

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('cycle', 4, true);

        try {
            $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);
            $this->fail('Expected transition failure when post-transition action throws.');
        } catch (FsmTransitionFailedException) {
            $this->assertTrue(
                FsmLog::query()
                    ->where('model_id', (string) $light->id)
                    ->where('from_state', TrafficLightState::Red->value)
                    ->where('to_state', TrafficLightState::Yellow->value)
                    ->where('exception_details', 'like', '%Simulated action failure%')
                    ->exists()
            );
        }
    }
}
