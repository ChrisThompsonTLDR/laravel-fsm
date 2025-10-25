<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Behavioral;

use Fsm\Data\TransitionInput;
use Fsm\Events\TransitionSucceeded;
use Fsm\Models\FsmEventLog;
use Fsm\Models\FsmLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Services\TestSpyService;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;
use Thunk\Verbs\Facades\Verbs;

class IntegrationBehavioralTest extends BehavioralTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->defineTrafficLightFsm(function ($builder) {
            $builder->from(TrafficLightState::Red)
                ->to(TrafficLightState::Yellow)
                ->event('cycle')
                ->before(TestSpyService::class.'@OrderBeforeProcess');

            $builder->from(TrafficLightState::Yellow)
                ->to(TrafficLightState::Green)
                ->event('cycle')
                ->action(
                    function (TransitionInput $input): void {
                        /** @var \Tests\Feature\TrafficLight\Models\TrafficLight $model */
                        $model = $input->model;
                        $model->update(['name' => $model->name.' (synced)']);
                    },
                    [],
                    true
                );

            $builder->from(TrafficLightState::Green)
                ->to(TrafficLightState::Red)
                ->event('cycle')
                ->after(TestSpyService::class.'@OrderAfterProcess');
        });
    }

    public function test_full_cycle_persists_logs_and_event_history(): void
    {
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('evening-cycle', 101);

        $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);
        $this->engine->performTransition($light, 'state', TrafficLightState::Green, $context);
        $this->engine->performTransition($light, 'state', TrafficLightState::Red, $context);

        $light->refresh();
        $this->assertSame(TrafficLightState::Red, $light->state);

        $this->assertSame(3, FsmLog::query()->count());
        $this->assertSame(3, FsmEventLog::query()->count());

        $this->assertTrue(
            FsmEventLog::query()
                ->where('model_id', (string) $light->id)
                ->where('transition_name', 'cycle')
                ->exists()
        );
    }

    public function test_context_is_recorded_in_logs_and_events(): void
    {
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('context-capture', 202);

        $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);

        $this->assertTrue(
            FsmLog::query()
                ->where('model_id', (string) $light->id)
                ->whereJsonContains('context_snapshot->message', 'context-capture')
                ->exists()
        );

        $this->assertTrue(
            FsmEventLog::query()
                ->where('model_id', (string) $light->id)
                ->whereJsonContains('context->message', 'context-capture')
                ->exists()
        );
    }

    public function test_verbs_remain_disabled_when_dispatching_is_off(): void
    {
        Config::set('fsm.verbs.dispatch_transitioned_verb', false);
        Verbs::assertNothingCommitted();

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('verb-check', 303);

        $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);

        Verbs::assertNothingCommitted();
    }

    public function test_dry_run_does_not_create_persistent_artifacts(): void
    {
        Event::fake();

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('dry-run', 404);

        $result = $this->engine->dryRunTransition($light, 'state', TrafficLightState::Yellow, $context);

        $this->assertTrue($result['can_transition']);
        $this->assertSame(0, FsmLog::query()->count());
        $this->assertSame(0, FsmEventLog::query()->count());
        Event::assertNotDispatched(TransitionSucceeded::class);
    }
}
