<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Behavioral;

use Fsm\Data\TransitionInput;
use Fsm\Events\StateTransitioned;
use Fsm\Events\TransitionAttempted;
use Fsm\Events\TransitionFailed;
use Fsm\Events\TransitionSucceeded;
use Fsm\Models\FsmLog;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Services\TestSpyService;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;

class FsmEngineBehavioralTest extends BehavioralTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->defineTrafficLightFsm(function ($builder) {
            $builder->from(TrafficLightState::Red)
                ->to(TrafficLightState::Yellow)
                ->event('cycle')
                ->guard(
                    function (TransitionInput $input): bool {
                        if (! $input->context instanceof TestContextData) {
                            return false;
                        }

                        return $input->context->message !== 'maintenance-blocked';
                    },
                    [],
                    'traffic sensor'
                )
                ->before(TestSpyService::class.'@OrderBeforeProcess')
                ->action(TestSpyService::class.'@anAction')
                ->after(TestSpyService::class.'@OrderAfterProcess');

            $builder->from(TrafficLightState::Yellow)
                ->to(TrafficLightState::Green)
                ->event('cycle')
                ->action(TestSpyService::class.'@anAction');
        });
    }

    public function test_get_current_state_defaults_to_initial_state_when_state_unset(): void
    {
        $light = new TrafficLight(['name' => 'Main & 1st']);
        $light->state = null;

        $state = $this->engine->getCurrentState($light, 'state');

        $this->assertSame(TrafficLightState::Red, $state);
    }

    public function test_can_transition_and_dry_run_fail_when_guard_blocks_transition(): void
    {
        Event::fake();

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('maintenance-blocked');

        $this->assertFalse($this->engine->canTransition($light, 'state', TrafficLightState::Yellow, $context));

        $dryRunResult = $this->engine->dryRunTransition($light, 'state', TrafficLightState::Yellow, $context);

        $this->assertFalse($dryRunResult['can_transition']);
        $this->assertSame(TrafficLightState::Red->value, $dryRunResult['from_state']);
        $this->assertSame(TrafficLightState::Yellow->value, $dryRunResult['to_state']);
        $this->assertStringContainsString('traffic sensor', $dryRunResult['reason']);
        $this->assertSame(
            TrafficLightState::Red->value,
            $light->fresh()->state->value,
            'Guard failure should not change the persisted state'
        );

        Event::assertDispatched(TransitionAttempted::class);
        Event::assertNotDispatched(TransitionSucceeded::class);
        Event::assertNotDispatched(StateTransitioned::class);
        Event::assertNotDispatched(TransitionFailed::class);
    }

    public function test_perform_transition_updates_state_logs_and_executes_callbacks(): void
    {
        Event::fake();

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('evening-cycle', 42);

        $this->assertTrue($this->engine->canTransition($light, 'state', TrafficLightState::Yellow, $context));

        $result = $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);

        $this->assertSame($light->id, $result->id);
        $this->assertSame(TrafficLightState::Yellow, $result->state);
        $this->assertSame(TrafficLightState::Yellow, $light->fresh()->state);

        Event::assertDispatched(TransitionAttempted::class, function (TransitionAttempted $event) use ($light) {
            return $event->model->is($light) && $event->columnName === 'state';
        });
        Event::assertDispatched(TransitionSucceeded::class);
        Event::assertDispatched(StateTransitioned::class, function (StateTransitioned $event) use ($light) {
            return $event->model->is($light) && $event->fromState === TrafficLightState::Red->value;
        });
        Event::assertNotDispatched(TransitionFailed::class);

        $this->assertTrue(
            $this->spyService->wasCalledWith('OrderBeforeProcess', $light->id, TrafficLightState::Red, TrafficLightState::Yellow),
            'Expected before hook to execute for the transition'
        );
        $this->assertTrue(
            $this->spyService->wasCalledWith('OrderAfterProcess', $light->id, TrafficLightState::Red, TrafficLightState::Yellow),
            'Expected after hook to execute for the transition'
        );

        $this->assertDatabaseHas('fsm_logs', [
            'model_id' => (string) $light->id,
            'model_type' => $light->getMorphClass(),
            'from_state' => TrafficLightState::Red->value,
            'to_state' => TrafficLightState::Yellow->value,
            'fsm_column' => 'state',
        ]);

        $this->assertCount(1, FsmLog::all());
    }
}
