<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Traits;

use Fsm\Contracts\FsmDefinition;
use Fsm\Contracts\FsmEventEnum;
use Fsm\Contracts\FsmStateEnum;
use Fsm\FsmBuilder;
use Fsm\FsmRegistry;
use Fsm\Traits\HasFsm;
use Illuminate\Database\Eloquent\Model;
use Tests\TestbenchTestCase;

enum HasFsmEventEnumTestState: string implements FsmStateEnum
{
    case Idle = 'idle';
    case Running = 'running';

    public function displayName(): string
    {
        return $this->value;
    }

    public function icon(): string
    {
        return '🔘';
    }
}

enum HasFsmEventEnumTestEvent: string implements FsmEventEnum
{
    case Start = 'start';
}

class HasFsmEventEnumTestModel extends Model
{
    use HasFsm;

    protected $table = 'has_fsm_event_enum_test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class HasFsmEventEnumTestFsm implements FsmDefinition
{
    public function define(): void
    {
        FsmBuilder::for(HasFsmEventEnumTestModel::class, 'state')
            ->initialState(HasFsmEventEnumTestState::Idle)
            ->state(HasFsmEventEnumTestState::Idle)
            ->state(HasFsmEventEnumTestState::Running)
            ->from(HasFsmEventEnumTestState::Idle)
            ->to(HasFsmEventEnumTestState::Running)
            ->event(HasFsmEventEnumTestEvent::Start)
            ->build();
    }
}

class HasFsmEventEnumTest extends TestbenchTestCase
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
            'has_fsm_event_enum_test_models',
            function ($table) {
                $table->id();
                $table->string('state')->default('idle');
            }
        );

        FsmBuilder::reset();
        (new HasFsmEventEnumTestFsm())->define();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_trigger_accepts_enum_and_string_identically(): void
    {
        $modelA = HasFsmEventEnumTestModel::create();
        $modelB = HasFsmEventEnumTestModel::create();

        $modelA->fsm('state')->trigger(HasFsmEventEnumTestEvent::Start);
        $modelB->fsm('state')->trigger('start');

        $this->assertSame($modelA->state, $modelB->state);
        $this->assertSame('running', $modelA->refresh()->state);
    }

    public function test_can_accepts_enum_and_string_identically(): void
    {
        $model = HasFsmEventEnumTestModel::create();

        $this->assertTrue($model->fsm('state')->can(HasFsmEventEnumTestEvent::Start));
        $this->assertTrue($model->fsm('state')->can('start'));
    }

    public function test_transition_builder_event_accepts_enum(): void
    {
        $model = HasFsmEventEnumTestModel::create();

        // The fact that setUp() registered the FSM with ->event(HasFsmEventEnumTestEvent::Start)
        // and then trigger('start') succeeds proves the builder accepted the enum.
        $model->fsm('state')->trigger('start');

        $this->assertSame('running', $model->refresh()->state);
    }
}
