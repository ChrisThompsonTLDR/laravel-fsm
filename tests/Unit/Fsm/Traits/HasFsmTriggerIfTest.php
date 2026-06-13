<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Traits;

use Fsm\Contracts\FsmDefinition;
use Fsm\Contracts\FsmEventEnum;
use Fsm\Contracts\FsmStateEnum;
use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\FsmBuilder;
use Fsm\Traits\HasFsm;
use Illuminate\Database\Eloquent\Model;
use Tests\TestbenchTestCase;

enum TriggerIfTestState: string implements FsmStateEnum
{
    case Idle = 'idle';
    case Running = 'running';
    case Done = 'done';
    case Guarded = 'guarded';
    case Exploded = 'exploded';

    public function displayName(): string
    {
        return $this->value;
    }

    public function icon(): string
    {
        return '🔘';
    }
}

enum TriggerIfTestEvent: string implements FsmEventEnum
{
    case Start = 'start';
    case Finish = 'finish';
    case TryGuarded = 'try_guarded';
    case TryExplode = 'try_explode';
}

class TriggerIfTestModel extends Model
{
    use HasFsm;

    protected $table = 'trigger_if_test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class TriggerIfTestFsm implements FsmDefinition
{
    public function define(): void
    {
        FsmBuilder::for(TriggerIfTestModel::class, 'state')
            ->initialState(TriggerIfTestState::Idle)
            ->state(TriggerIfTestState::Idle)
            ->state(TriggerIfTestState::Running)
            ->state(TriggerIfTestState::Done, static function ($builder): void {
                $builder->isTerminal(true);
            })
            ->state(TriggerIfTestState::Guarded)
            ->state(TriggerIfTestState::Exploded)
            ->from(TriggerIfTestState::Idle)
            ->to(TriggerIfTestState::Running)
            ->event(TriggerIfTestEvent::Start)
            ->from(TriggerIfTestState::Running)
            ->to(TriggerIfTestState::Done)
            ->event(TriggerIfTestEvent::Finish)
            // Always-rejecting guard: the transition is never available.
            ->from(TriggerIfTestState::Idle)
            ->to(TriggerIfTestState::Guarded)
            ->event(TriggerIfTestEvent::TryGuarded)
            ->guard(static fn (): bool => false)
            // Available transition whose action throws while being performed.
            ->from(TriggerIfTestState::Idle)
            ->to(TriggerIfTestState::Exploded)
            ->event(TriggerIfTestEvent::TryExplode)
            ->action(static function (): void {
                throw new \RuntimeException('action boom');
            })
            ->build();
    }
}

class HasFsmTriggerIfTest extends TestbenchTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $app['config']->set('fsm.logging.enabled', false);
        $app['config']->set('fsm.verbs.dispatch_transitioned_verb', false);
        $app['config']->set('fsm.use_transactions', false);

        $app['config']->set('verbs.migrations', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make('db')->connection()->getSchemaBuilder()->create(
            'trigger_if_test_models',
            function ($table) {
                $table->id();
                $table->string('state')->default('idle');
            }
        );

        FsmBuilder::reset();
        (new TriggerIfTestFsm)->define();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_trigger_if_performs_an_available_transition_and_returns_true(): void
    {
        $model = TriggerIfTestModel::create();

        $result = $model->fsm('state')->triggerIf(TriggerIfTestEvent::Start);

        $this->assertTrue($result);
        $this->assertSame('running', $model->refresh()->state);
    }

    public function test_trigger_if_is_a_noop_returning_false_when_the_transition_is_unavailable(): void
    {
        $model = TriggerIfTestModel::create();
        $model->fsm('state')->trigger(TriggerIfTestEvent::Start);

        // Start is not valid from the Running state: must not throw, must not change state.
        $result = $model->fsm('state')->triggerIf(TriggerIfTestEvent::Start);

        $this->assertFalse($result);
        $this->assertSame('running', $model->refresh()->state);
    }

    public function test_trigger_if_is_idempotent_across_repeated_calls(): void
    {
        $model = TriggerIfTestModel::create();

        $first = $model->fsm('state')->triggerIf(TriggerIfTestEvent::Start);
        $second = $model->fsm('state')->triggerIf(TriggerIfTestEvent::Start);

        $this->assertTrue($first);
        $this->assertFalse($second);
        // Did not double-advance past Running.
        $this->assertSame('running', $model->refresh()->state);
    }

    public function test_trigger_if_accepts_enum_and_string_identically(): void
    {
        $modelA = TriggerIfTestModel::create();
        $modelB = TriggerIfTestModel::create();

        $this->assertTrue($modelA->fsm('state')->triggerIf(TriggerIfTestEvent::Start));
        $this->assertTrue($modelB->fsm('state')->triggerIf('start'));

        $this->assertSame($modelA->refresh()->state, $modelB->refresh()->state);
    }

    public function test_trigger_if_advances_through_a_multi_step_chain(): void
    {
        $model = TriggerIfTestModel::create();

        $this->assertTrue($model->fsm('state')->triggerIf(TriggerIfTestEvent::Start));
        $this->assertTrue($model->fsm('state')->triggerIf(TriggerIfTestEvent::Finish));
        $this->assertFalse($model->fsm('state')->triggerIf(TriggerIfTestEvent::Finish));

        $this->assertSame('done', $model->refresh()->state);
    }

    public function test_trigger_if_noops_returning_false_when_a_guard_rejects_the_transition(): void
    {
        $model = TriggerIfTestModel::create();

        // The guard on Idle->Guarded always returns false, so the transition is
        // unavailable: triggerIf must not throw, must not change state.
        $result = $model->fsm('state')->triggerIf(TriggerIfTestEvent::TryGuarded);

        $this->assertFalse($result);
        $this->assertSame('idle', $model->refresh()->state);
    }

    public function test_trigger_if_lets_a_real_error_in_a_transition_action_propagate(): void
    {
        $model = TriggerIfTestModel::create();

        // The Idle->Exploded transition is available (no guard), so triggerIf
        // proceeds to perform it — and an exception thrown by its action must
        // surface, not be swallowed into a false return.
        $this->expectException(FsmTransitionFailedException::class);

        $model->fsm('state')->triggerIf(TriggerIfTestEvent::TryExplode);
    }
}
