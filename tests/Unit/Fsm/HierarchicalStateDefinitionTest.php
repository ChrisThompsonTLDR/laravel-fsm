<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm;

use Fsm\Data\HierarchicalStateDefinition;
use Fsm\TransitionBuilder;
use Tests\FsmTestCase;

class HierarchicalStateDefinitionTest extends FsmTestCase
{
    public function test_with_child_fsm_attaches_definition(): void
    {
        $child = (new TransitionBuilder('ChildModel', 'state'))
            ->state('start')
            ->initial('start')
            ->buildRuntimeDefinition();

        $builder = new TransitionBuilder('ParentModel', 'status');
        $builder->state('parent', function (TransitionBuilder $b) use ($child) {
            $b->withChildFsm($child, 'start');
        });

        $states = $builder->getStateDefinitions();
        $this->assertArrayHasKey('parent', $states);
        $state = $states['parent'];
        $this->assertInstanceOf(HierarchicalStateDefinition::class, $state);
        $this->assertSame($child, $state->childStateMachine);
        $this->assertSame('start', $state->parentState);
    }
}
