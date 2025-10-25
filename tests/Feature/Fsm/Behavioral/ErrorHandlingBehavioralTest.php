<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Behavioral;

use Fsm\Data\TransitionInput;
use Fsm\Events\TransitionFailed;
use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\Models\FsmLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Services\TestSpyService;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;
use Tests\Feature\TrafficLight\Models\TrafficLight;

class ErrorHandlingBehavioralTest extends BehavioralTestCase
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

                        return $input->context->message !== 'blocked';
                    },
                    [],
                    'safety interlock'
                )
                ->action(TestSpyService::class.'@anAction', [], true);

            $builder->from(TrafficLightState::Yellow)
                ->to(TrafficLightState::Green)
                ->event('cycle');
        });
    }

    public function test_invalid_transition_throws_and_retains_state(): void
    {
        Event::fake();

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('cycle');

        $this->expectException(FsmTransitionFailedException::class);
        $this->expectExceptionMessage("No defined transition from 'red' to 'green'");

        try {
            $this->engine->performTransition($light, 'state', TrafficLightState::Green, $context);
        } catch (FsmTransitionFailedException $exception) {
            $this->assertSame(TrafficLightState::Red, $light->fresh()->state);
            Event::assertDispatched(TransitionFailed::class);

            throw $exception;
        }
    }

    public function test_guard_failure_emits_transition_failed_event(): void
    {
        Event::fake();

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('blocked');

        try {
            $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);
            $this->fail('Expected guard failure to throw.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertStringContainsString('safety interlock', $exception->reason);
            $this->assertSame(TrafficLightState::Red, $light->fresh()->state);
            Event::assertDispatched(TransitionFailed::class, function (TransitionFailed $event) use ($light) {
                return $event->model->is($light);
            });
            $this->assertSame(0, FsmLog::query()->count());
        }
    }

    public function test_callback_failure_wraps_exception_and_records_failure_log(): void
    {
        Config::set('fsm.logging.log_failures', true);

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('cycle', 5, true);

        try {
            $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);
            $this->fail('Expected callback exception to bubble via FSM wrapper.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertInstanceOf(\RuntimeException::class, $exception->originalException);
            $this->assertSame('Simulated action failure', $exception->originalException->getMessage());
            $this->assertSame(TrafficLightState::Red, $light->fresh()->state);
            $this->assertTrue(
                FsmLog::query()
                    ->where('model_id', (string) $light->id)
                    ->where('exception_details', 'like', '%Simulated action failure%')
                    ->exists()
            );
        }
    }

    public function test_concurrent_modification_is_detected_and_reports_failure(): void
    {
        Config::set('fsm.logging.log_failures', true);

        $light = TrafficLight::factory()->create(['state' => TrafficLightState::Red->value]);
        $context = new TestContextData('cycle');

        // Simulate another process updating the row before the FSM engine performs its update.
        TrafficLight::query()
            ->whereKey($light->id)
            ->update(['state' => TrafficLightState::Green->value]);

        try {
            $this->engine->performTransition($light, 'state', TrafficLightState::Yellow, $context);
            $this->fail('Expected concurrent modification to throw.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertStringContainsString('Concurrent modification detected', $exception->reason);
            $this->assertSame(TrafficLightState::Green, $light->fresh()->state);
            $this->assertTrue(
                FsmLog::query()
                    ->where('model_id', (string) $light->id)
                    ->where('exception_details', 'like', '%Concurrent modification detected%')
                    ->exists()
            );
        }
    }
}
