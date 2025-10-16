<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm;

use Fsm\Data\StateDefinition;
use Fsm\Data\TransitionAction;
use Fsm\Data\TransitionCallback;
use Fsm\Data\TransitionDefinition;
use Fsm\Data\TransitionGuard;
use Fsm\TransitionBuilder;
use Orchestra\Testbench\TestCase;
use Tests\Unit\Fsm\Exceptions\MockState; // Using the MockState from the other test

// Dummy classes for callables
class MyGuardClass {}
class MyActionClass {}
class MyCallbackClass {}

class TransitionBuilderTest extends TestCase
{
    private TransitionBuilder $builder;

    private string $modelClass = 'App\\Models\\TestModel';

    private string $columnName = 'status_fsm';

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new TransitionBuilder($this->modelClass, $this->columnName);
    }

    protected function defineEnvironment($app)
    {
        // Setup minimal config for tests
        $app['config']->set('fsm.logging.enabled', true);
        $app['config']->set('fsm.logging.exception_character_limit', 1000);

    }

    public function test_constructor_sets_model_and_column(): void
    {
        $this->assertEquals($this->modelClass, $this->builder->getModelClass());
        $this->assertEquals($this->columnName, $this->builder->getColumnName());
    }

    public function test_initial_state_is_stored_and_retrieved(): void
    {
        $this->builder->initial(MockState::Pending);
        $this->assertEquals(MockState::Pending, $this->builder->getInitialState());
        // Initial state should also be added to state definitions
        $this->assertArrayHasKey(MockState::Pending->value, $this->builder->getStateDefinitions());
    }

    public function test_state_definition_is_stored(): void
    {
        $this->builder->state(MockState::Pending);
        $states = $this->builder->getStateDefinitions();
        $this->assertArrayHasKey(MockState::Pending->value, $states);
        $this->assertInstanceOf(StateDefinition::class, $states[MockState::Pending->value]);
        $this->assertEquals(MockState::Pending, $states[MockState::Pending->value]->name);
    }

    public function test_state_definition_with_callbacks(): void
    {
        $onEntryCallable = fn () => 'onEntry';
        $onExitCallable = [MyCallbackClass::class, 'handleExit'];

        $this->builder->state(MockState::Done, function (TransitionBuilder $builder) use ($onEntryCallable, $onExitCallable) {
            $builder->onEntry($onEntryCallable);
            $builder->onExit($onExitCallable, ['param' => 123]);
        });

        $states = $this->builder->getStateDefinitions();
        $this->assertArrayHasKey(MockState::Done->value, $states);
        $stateDef = $states[MockState::Done->value];

        $this->assertCount(1, $stateDef->onEntryCallbacks);
        $this->assertInstanceOf(TransitionCallback::class, $stateDef->onEntryCallbacks[0]);
        $this->assertEquals($onEntryCallable, $stateDef->onEntryCallbacks[0]->callable);

        $this->assertCount(1, $stateDef->onExitCallbacks);
        $this->assertInstanceOf(TransitionCallback::class, $stateDef->onExitCallbacks[0]);
        $this->assertEquals($onExitCallable, $stateDef->onExitCallbacks[0]->callable);
        $this->assertEquals(['param' => 123], $stateDef->onExitCallbacks[0]->parameters);
    }

    public function test_transition_definition_is_stored(): void
    {
        $this->builder
            ->transition('Process Order')
            ->from(MockState::Pending)
            ->to(MockState::Done)
            ->on('order.process')
            ->when(MyGuardClass::class, 'Admin Check', ['role' => 'admin'])
            ->before([MyCallbackClass::class, 'beforeHook'])
            ->after([MyCallbackClass::class, 'afterHook'])
            ->action(MyActionClass::class, ['notify' => true], false); // runAfterSave = false

        $transitions = $this->builder->getTransitionDefinitions();
        $this->assertCount(1, $transitions);

        /** @var TransitionDefinition $transitionDef */
        $transitionDef = $transitions[0];
        $this->assertEquals(MockState::Pending, $transitionDef->fromState);
        $this->assertEquals(MockState::Done, $transitionDef->toState);
        $this->assertEquals('order.process', $transitionDef->event);
        $this->assertEquals('Process Order', $transitionDef->description);

        $this->assertCount(1, $transitionDef->guards);
        $this->assertInstanceOf(TransitionGuard::class, $transitionDef->guards[0]);
        $this->assertEquals(MyGuardClass::class, $transitionDef->guards[0]->callable);
        $this->assertEquals('Admin Check', $transitionDef->guards[0]->description);
        $this->assertEquals(['role' => 'admin'], $transitionDef->guards[0]->parameters);

        $this->assertCount(2, $transitionDef->onTransitionCallbacks); // before and after
        $this->assertInstanceOf(TransitionCallback::class, $transitionDef->onTransitionCallbacks[0]);
        $this->assertEquals([MyCallbackClass::class, 'beforeHook'], $transitionDef->onTransitionCallbacks[0]->callable);
        $this->assertFalse($transitionDef->onTransitionCallbacks[0]->runAfterTransition);
        $this->assertInstanceOf(TransitionCallback::class, $transitionDef->onTransitionCallbacks[1]);
        $this->assertEquals([MyCallbackClass::class, 'afterHook'], $transitionDef->onTransitionCallbacks[1]->callable);
        $this->assertTrue($transitionDef->onTransitionCallbacks[1]->runAfterTransition);

        $this->assertCount(1, $transitionDef->actions);
        $this->assertInstanceOf(TransitionAction::class, $transitionDef->actions[0]);
        $this->assertEquals(MyActionClass::class, $transitionDef->actions[0]->callable);
        $this->assertEquals(['notify' => true], $transitionDef->actions[0]->parameters);
        $this->assertFalse($transitionDef->actions[0]->runAfterTransition);
    }

    public function test_multiple_from_states_create_multiple_transitions(): void
    {
        $this->builder
            ->transition()
            ->from([MockState::Pending, 'initial'])
            ->to(MockState::Done);

        $transitions = $this->builder->getTransitionDefinitions();
        $this->assertCount(2, $transitions);
        $this->assertEquals(MockState::Pending, $transitions[0]->fromState);
        $this->assertEquals(MockState::Done, $transitions[0]->toState);
        $this->assertEquals('initial', $transitions[1]->fromState);
        $this->assertEquals(MockState::Done, $transitions[1]->toState);
    }

    public function test_ensure_state_is_defined_automatically_when_used_in_transitions(): void
    {
        $this->builder
            ->transition()
            ->from('new_state_1')
            ->to('new_state_2');

        $this->builder->getTransitionDefinitions(); // Finalize

        $states = $this->builder->getStateDefinitions();
        $this->assertArrayHasKey('new_state_1', $states);
        $this->assertArrayHasKey('new_state_2', $states);
    }

    public function test_build_runtime_definition(): void
    {
        $this->builder
            ->initial(MockState::Pending)
            ->state(MockState::Pending)
            ->state(MockState::Done)
            ->transition('Test Transition')
            ->from(MockState::Pending)
            ->to(MockState::Done)
            ->on('go');

        $runtimeDef = $this->builder->buildRuntimeDefinition();

        $this->assertEquals($this->modelClass, $runtimeDef->modelClass);
        $this->assertEquals($this->columnName, $runtimeDef->columnName);
        $this->assertEquals(MockState::Pending, $runtimeDef->initialState);

        $this->assertCount(2, $runtimeDef->states);
        // Note: FsmRuntimeDefinition keys states by their string value directly in constructor.
        $this->assertArrayHasKey(MockState::Pending->value, $runtimeDef->states);
        $this->assertArrayHasKey(MockState::Done->value, $runtimeDef->states);

        $this->assertCount(1, $runtimeDef->transitions);
        $transition = $runtimeDef->transitions[0]; // Access by numeric index
        $this->assertInstanceOf(TransitionDefinition::class, $transition);
        $this->assertEquals(MockState::Pending, $transition->fromState);
        $this->assertEquals(MockState::Done, $transition->toState);
        $this->assertEquals('Test Transition', $transition->description);
        $this->assertEquals('go', $transition->event);
    }
}
