<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm;

use Fsm\Data\FsmRuntimeDefinition;
use Fsm\Data\StateDefinition;
use Fsm\Data\TransitionDefinition;
use Fsm\FsmBuilder;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class FsmRuntimeDefinitionTest extends FsmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)
            ->build();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_constructor_assigns_properties_correctly(): void
    {
        $states = ['pending' => new StateDefinition('pending')];
        $transitions = [new TransitionDefinition('pending', 'completed')];
        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            $states,
            $transitions,
            'pending'
        );

        $this->assertEquals('TestModel', $definition->modelClass);
        $this->assertEquals('status', $definition->columnName);
        $this->assertEquals($states, $definition->states);
        $this->assertEquals($transitions, $definition->transitions);
        $this->assertEquals('pending', $definition->initialState);
    }
}
