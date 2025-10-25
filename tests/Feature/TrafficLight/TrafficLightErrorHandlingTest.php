<?php

declare(strict_types=1);

namespace Tests\Feature\TrafficLight;

use Fsm\Data\TransitionInput;
use Fsm\Events\TransitionFailed;
use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\Models\FsmLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Fsm\Behavioral\BehavioralTestCase;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Services\TestSpyService;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;

class TrafficLightErrorHandlingTest extends BehavioralTestCase
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
                            return true;
                        }

                        return $input->context->message !== 'maintenance-blocked';
                    },
                    [],
                    'maintenance interlock'
                )
                ->action(TestSpyService::class.'@anAction');

            $builder->from(TrafficLightState::Yellow)
                ->to(TrafficLightState::Green)
                ->event('cycle');

            $builder->from(TrafficLightState::Green)
                ->to(TrafficLightState::Yellow)
                ->event('cycle');

            $builder->from(TrafficLightState::Yellow)
                ->to(TrafficLightState::Red)
                ->event('cycle');
        });
    }

    public function test_invalid_transition_is_rejected_with_clear_message(): void
    {
        Event::fake();

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('attempt-skip-yellow');

        try {
            $this->engine->performTransition($light, 'state', TrafficLightState::Green, $context);
            $this->fail('Expected invalid transition to throw an exception.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertSame(TrafficLightState::Red, $light->fresh()->state);
            $this->assertStringContainsString('No defined transition from', $exception->getMessage());
            $from = $exception->getFromState();
            $to = $exception->getToState();
            $this->assertSame(
                TrafficLightState::Red->value,
                $from instanceof TrafficLightState ? $from->value : $from
            );
            $this->assertSame(
                TrafficLightState::Green->value,
                $to instanceof TrafficLightState ? $to->value : $to
            );

            Event::assertDispatched(TransitionFailed::class, function (TransitionFailed $event) use ($light) {
                return $event->model->is($light);
            });

            $this->assertSame(0, FsmLog::query()->count(), 'Invalid transitions should not create success logs');
        }
    }

    public function test_guard_failure_blocks_transition_and_does_not_change_state(): void
    {
        Event::fake();

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('maintenance-blocked');

        try {
            $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);
            $this->fail('Expected guard failure to throw an exception.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertSame(TrafficLightState::Red, $light->fresh()->state);
            $this->assertStringContainsString('maintenance interlock', $exception->reason);

            Event::assertDispatched(TransitionFailed::class, function (TransitionFailed $event) use ($light) {
                return $event->model->is($light);
            });

            $this->assertSame(0, FsmLog::query()->count(), 'Guard failures should not create success logs');
        }
    }

    public function test_null_state_defaults_to_initial_state(): void
    {
        $light = new TrafficLight(['name' => 'Test Junction']);
        $light->state = null;

        $state = $this->engine->getCurrentState($light, 'state');

        $this->assertSame(TrafficLightState::Red, $state);
    }

    public function test_concurrent_modification_is_detected_and_logged(): void
    {
        Config::set('fsm.logging.log_failures', true);

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('concurrency-check');

        TrafficLight::query()
            ->whereKey($light->getKey())
            ->update(['state' => TrafficLightState::Green->value]);

        try {
            $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);
            $this->fail('Expected concurrent modification to throw an exception.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertStringContainsString('Concurrent modification detected', $exception->reason);
            $this->assertSame(TrafficLightState::Green, $light->fresh()->state);

            $this->assertTrue(
                FsmLog::query()
                    ->where('model_id', (string) $light->id)
                    ->where('exception_details', 'like', '%Concurrent modification detected%')
                    ->exists(),
                'Failure log should include concurrent modification details'
            );
        }
    }
}
