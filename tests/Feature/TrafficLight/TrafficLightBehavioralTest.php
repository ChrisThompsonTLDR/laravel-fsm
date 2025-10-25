<?php

declare(strict_types=1);

namespace Tests\Feature\TrafficLight;

use Fsm\Events\StateTransitioned;
use Fsm\Events\TransitionAttempted;
use Fsm\Events\TransitionFailed;
use Fsm\Events\TransitionSucceeded;
use Fsm\Models\FsmLog;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Fsm\Behavioral\BehavioralTestCase;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Services\TestSpyService;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;

class TrafficLightBehavioralTest extends BehavioralTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->defineTrafficLightFsm(function ($builder) {
            $builder->from(TrafficLightState::Red)
                ->to(TrafficLightState::Yellow)
                ->event('cycle')
                ->guard(TestSpyService::class.'@successfulGuard', [], 'safety check')
                ->before(TestSpyService::class.'@OrderBeforeProcess')
                ->action(TestSpyService::class.'@anAction')
                ->after(TestSpyService::class.'@OrderAfterProcess');

            $builder->from(TrafficLightState::Yellow)
                ->to(TrafficLightState::Green)
                ->event('cycle')
                ->action(TestSpyService::class.'@anAction');

            $builder->from(TrafficLightState::Green)
                ->to(TrafficLightState::Yellow)
                ->event('cycle');

            $builder->from(TrafficLightState::Yellow)
                ->to(TrafficLightState::Red)
                ->event('cycle');
        });
    }

    public function test_valid_transitions_follow_cycle_order(): void
    {
        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);

        $this->assertTrue($this->engine->canTransition($light, 'state', TrafficLightState::Yellow));
        $this->engine->performTransition($light, 'state', TrafficLightState::Yellow);
        $this->assertSame(TrafficLightState::Yellow, $light->fresh()->state);

        $this->assertTrue($this->engine->canTransition($light, 'state', TrafficLightState::Green));
        $this->engine->performTransition($light, 'state', TrafficLightState::Green);
        $this->assertSame(TrafficLightState::Green, $light->fresh()->state);

        $this->assertTrue($this->engine->canTransition($light, 'state', TrafficLightState::Yellow));
        $this->engine->performTransition($light, 'state', TrafficLightState::Yellow);
        $this->assertSame(TrafficLightState::Yellow, $light->fresh()->state);

        $this->assertTrue($this->engine->canTransition($light, 'state', TrafficLightState::Red));
        $this->engine->performTransition($light, 'state', TrafficLightState::Red);
        $this->assertSame(TrafficLightState::Red, $light->fresh()->state);

        $this->assertFalse(
            $this->engine->canTransition($light, 'state', TrafficLightState::Green),
            'Cycle requires passing through yellow before returning to green'
        );
    }

    public function test_transition_persists_state_and_emits_events_and_callbacks(): void
    {
        Event::fake();

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('evening-cycle', 42);

        $result = $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);

        $this->assertSame($light->id, $result->id);
        $this->assertSame(TrafficLightState::Yellow, $result->state);
        $this->assertSame(TrafficLightState::Yellow, $light->fresh()->state);

        Event::assertDispatched(TransitionAttempted::class, function (TransitionAttempted $event) use ($light) {
            return $event->model->is($light) && $event->columnName === 'state';
        });
        Event::assertDispatched(TransitionSucceeded::class, function (TransitionSucceeded $event) use ($light) {
            return $event->model->is($light)
                && $event->fromState === TrafficLightState::Red->value
                && $event->toState === TrafficLightState::Yellow->value;
        });
        Event::assertDispatched(StateTransitioned::class, function (StateTransitioned $event) use ($light) {
            return $event->model->is($light)
                && $event->fromState === TrafficLightState::Red->value
                && $event->toState === TrafficLightState::Yellow->value;
        });
        Event::assertNotDispatched(TransitionFailed::class);

        $this->assertTrue(
            $this->spyService->wasCalledWith('OrderBeforeProcess', $light->id, TrafficLightState::Red, TrafficLightState::Yellow)
        );
        $this->assertTrue(
            $this->spyService->wasCalledWith('OrderAfterProcess', $light->id, TrafficLightState::Red, TrafficLightState::Yellow)
        );
        $this->assertTrue(
            $this->spyService->wasCalledWith('anAction', $light->id, TrafficLightState::Red, TrafficLightState::Yellow)
        );

        $this->assertDatabaseHas('fsm_logs', [
            'model_id' => (string) $light->id,
            'model_type' => $light->getMorphClass(),
            'from_state' => TrafficLightState::Red->value,
            'to_state' => TrafficLightState::Yellow->value,
            'fsm_column' => 'state',
        ]);

        $this->assertSame(
            1,
            FsmLog::query()->where('model_id', (string) $light->id)->count(),
            'Exactly one log entry should be recorded for the transition'
        );
    }
}
