<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm;

use Fsm\Constants;
use Fsm\Data\FsmRuntimeDefinition;
use Fsm\Data\StateDefinition;
use Fsm\Data\TransitionDefinition;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\FsmTestCase;

class FsmRuntimeDefinitionComprehensiveTest extends FsmTestCase
{
    public function test_constructor_with_null_initial_state(): void
    {
        $states = ['pending' => new StateDefinition('pending')];
        $transitions = [new TransitionDefinition('pending', 'completed')];

        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            $states,
            $transitions,
            null // null initial state
        );

        $this->assertEquals('TestModel', $definition->modelClass);
        $this->assertEquals('status', $definition->columnName);
        $this->assertEquals($states, $definition->states);
        $this->assertEquals($transitions, $definition->transitions);
        $this->assertNull($definition->initialState);
    }

    public function test_constructor_with_string_initial_state(): void
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

        $this->assertEquals('pending', $definition->initialState);
    }

    public function test_constructor_with_enum_initial_state(): void
    {
        $states = ['idle' => new StateDefinition('idle')];
        $transitions = [new TransitionDefinition('idle', 'pending')];

        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            $states,
            $transitions,
            TestFeatureState::Idle
        );

        $this->assertEquals(TestFeatureState::Idle, $definition->initialState);
    }

    public function test_constructor_with_context_dto_class(): void
    {
        $states = ['pending' => new StateDefinition('pending')];
        $transitions = [new TransitionDefinition('pending', 'completed')];

        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            $states,
            $transitions,
            'pending',
            'App\\Dtos\\TestContext'
        );

        $this->assertEquals('App\\Dtos\\TestContext', $definition->contextDtoClass);
    }

    public function test_constructor_with_description(): void
    {
        $states = ['pending' => new StateDefinition('pending')];
        $transitions = [new TransitionDefinition('pending', 'completed')];

        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            $states,
            $transitions,
            'pending',
            null,
            'Test FSM Description'
        );

        $this->assertEquals('Test FSM Description', $definition->description);
    }

    public function test_constructor_with_empty_states(): void
    {
        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            [],
            [],
            null
        );

        $this->assertEquals('TestModel', $definition->modelClass);
        $this->assertEquals('status', $definition->columnName);
        $this->assertEmpty($definition->states);
        $this->assertEmpty($definition->transitions);
        $this->assertNull($definition->initialState);
    }

    public function test_constructor_with_multiple_states(): void
    {
        $states = [
            'pending' => new StateDefinition('pending', [], [], 'Pending State'),
            'completed' => new StateDefinition('completed', [], [], 'Completed State'),
            'cancelled' => new StateDefinition('cancelled', [], [], 'Cancelled State'),
        ];

        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            $states,
            [],
            'pending'
        );

        $this->assertCount(3, $definition->states);
        $this->assertEquals('Pending State', $definition->states['pending']->description);
        $this->assertEquals('Completed State', $definition->states['completed']->description);
        $this->assertEquals('Cancelled State', $definition->states['cancelled']->description);
    }

    public function test_constructor_with_multiple_transitions(): void
    {
        $transitions = [
            new TransitionDefinition('pending', 'completed', 'complete'),
            new TransitionDefinition('pending', 'cancelled', 'cancel'),
            new TransitionDefinition(null, 'pending', 'start'), // wildcard from state
        ];

        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            ['pending' => new StateDefinition('pending')],
            $transitions,
            'pending'
        );

        $this->assertCount(3, $definition->transitions);
        $this->assertEquals('complete', $definition->transitions[0]->event);
        $this->assertEquals('cancel', $definition->transitions[1]->event);
        $this->assertEquals('start', $definition->transitions[2]->event);
    }

    public function test_get_state_definition_with_string_state(): void
    {
        $states = ['pending' => new StateDefinition('pending', [], [], 'Pending State')];
        $definition = new FsmRuntimeDefinition('TestModel', 'status', $states, [], 'pending');

        $stateDef = $definition->getStateDefinition('pending');

        $this->assertInstanceOf(StateDefinition::class, $stateDef);
        $this->assertEquals('pending', $stateDef->name);
        $this->assertEquals('Pending State', $stateDef->description);
    }

    public function test_get_state_definition_with_enum_state(): void
    {
        $states = ['idle' => new StateDefinition('idle', [], [], 'Idle State')];
        $definition = new FsmRuntimeDefinition('TestModel', 'status', $states, [], 'idle');

        $stateDef = $definition->getStateDefinition(TestFeatureState::Idle);

        $this->assertInstanceOf(StateDefinition::class, $stateDef);
        $this->assertEquals('idle', $stateDef->name);
        $this->assertEquals('Idle State', $stateDef->description);
    }

    public function test_get_state_definition_with_null_state(): void
    {
        $states = ['pending' => new StateDefinition('pending')];
        $definition = new FsmRuntimeDefinition('TestModel', 'status', $states, [], 'pending');

        $stateDef = $definition->getStateDefinition(null);

        $this->assertNull($stateDef);
    }

    public function test_get_state_definition_with_nonexistent_state(): void
    {
        $states = ['pending' => new StateDefinition('pending')];
        $definition = new FsmRuntimeDefinition('TestModel', 'status', $states, [], 'pending');

        $stateDef = $definition->getStateDefinition('nonexistent');

        $this->assertNull($stateDef);
    }

    public function test_get_state_definition_with_case_sensitive_state_names(): void
    {
        $states = ['pending' => new StateDefinition('pending')];
        $definition = new FsmRuntimeDefinition('TestModel', 'status', $states, [], 'pending');

        $stateDef = $definition->getStateDefinition('Pending'); // Different case

        $this->assertNull($stateDef);
    }

    public function test_get_transitions_for_with_exact_event_match(): void
    {
        $transitions = [
            new TransitionDefinition('pending', 'completed', 'complete'),
            new TransitionDefinition('pending', 'cancelled', 'cancel'),
            new TransitionDefinition('completed', 'pending', 'restart'),
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', [
            'pending' => new StateDefinition('pending'),
            'completed' => new StateDefinition('completed'),
            'cancelled' => new StateDefinition('cancelled'),
        ], $transitions, 'pending');

        $result = $definition->getTransitionsFor('pending', 'complete');

        $this->assertCount(1, $result);
        $this->assertEquals('complete', $result[0]->event);
        $this->assertEquals('completed', $result[0]->toState);
    }

    public function test_get_transitions_for_with_wildcard_event(): void
    {
        $transitions = [
            new TransitionDefinition('pending', 'completed', Constants::EVENT_WILDCARD),
            new TransitionDefinition('pending', 'cancelled', 'cancel'),
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', [
            'pending' => new StateDefinition('pending'),
            'completed' => new StateDefinition('completed'),
            'cancelled' => new StateDefinition('cancelled'),
        ], $transitions, 'pending');

        $result = $definition->getTransitionsFor('pending', Constants::EVENT_WILDCARD);

        $this->assertCount(1, $result);
        $this->assertEquals(Constants::EVENT_WILDCARD, $result[0]->event);
    }

    public function test_get_transitions_for_with_wildcard_from_state(): void
    {
        $transitions = [
            new TransitionDefinition(null, 'completed', 'start'), // wildcard from
            new TransitionDefinition('pending', 'cancelled', 'cancel'),
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', [
            'pending' => new StateDefinition('pending'),
            'completed' => new StateDefinition('completed'),
            'cancelled' => new StateDefinition('cancelled'),
        ], $transitions, 'pending');

        $result = $definition->getTransitionsFor('pending', 'start');

        $this->assertCount(1, $result);
        $this->assertEquals('start', $result[0]->event);
        $this->assertNull($result[0]->fromState);
    }

    public function test_get_transitions_for_with_null_from_state(): void
    {
        $transitions = [
            new TransitionDefinition('pending', 'completed', 'complete'),
            new TransitionDefinition(null, 'pending', 'start'),
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', [
            'pending' => new StateDefinition('pending'),
            'completed' => new StateDefinition('completed'),
        ], $transitions, 'pending');

        $result = $definition->getTransitionsFor(null, 'start');

        $this->assertCount(1, $result);
        $this->assertEquals('start', $result[0]->event);
    }

    public function test_get_transitions_for_with_no_matches(): void
    {
        $transitions = [
            new TransitionDefinition('pending', 'completed', 'complete'),
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', [
            'pending' => new StateDefinition('pending'),
            'completed' => new StateDefinition('completed'),
        ], $transitions, 'pending');

        $result = $definition->getTransitionsFor('pending', 'nonexistent_event');

        $this->assertEmpty($result);
    }

    public function test_get_transitions_for_with_multiple_matches(): void
    {
        $transitions = [
            new TransitionDefinition('pending', 'completed', Constants::EVENT_WILDCARD),
            new TransitionDefinition('pending', 'cancelled', Constants::EVENT_WILDCARD),
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', [
            'pending' => new StateDefinition('pending'),
            'completed' => new StateDefinition('completed'),
            'cancelled' => new StateDefinition('cancelled'),
        ], $transitions, 'pending');

        $result = $definition->getTransitionsFor('pending', Constants::EVENT_WILDCARD);

        $this->assertCount(2, $result);
        $this->assertEquals('completed', $result[0]->toState);
        $this->assertEquals('cancelled', $result[1]->toState);
    }

    public function test_get_transitions_for_with_enum_states(): void
    {
        $transitions = [
            new TransitionDefinition(TestFeatureState::Idle, TestFeatureState::Pending, 'start'),
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', [
            'idle' => new StateDefinition('idle'),
            'pending' => new StateDefinition('pending'),
        ], $transitions, TestFeatureState::Idle);

        $result = $definition->getTransitionsFor(TestFeatureState::Idle, 'start');

        $this->assertCount(1, $result);
        $this->assertEquals('start', $result[0]->event);
        $this->assertEquals(TestFeatureState::Pending, $result[0]->toState);
    }

    public function test_get_transitions_for_with_mixed_enum_and_string_states(): void
    {
        $transitions = [
            new TransitionDefinition(TestFeatureState::Idle, 'pending', 'start'),
            new TransitionDefinition('pending', TestFeatureState::Pending, 'convert'),
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', [
            'idle' => new StateDefinition('idle'),
            'pending' => new StateDefinition('pending'),
        ], $transitions, TestFeatureState::Idle);

        $result1 = $definition->getTransitionsFor(TestFeatureState::Idle, 'start');
        $result2 = $definition->getTransitionsFor('pending', 'convert');

        $this->assertCount(1, $result1);
        $this->assertCount(1, $result2);
        $this->assertEquals('pending', $result1[0]->toState);
        $this->assertEquals(TestFeatureState::Pending, $result2[0]->toState);
    }

    public function test_get_transitions_for_preserves_order(): void
    {
        $transitions = [
            new TransitionDefinition('pending', 'completed', 'complete'),
            new TransitionDefinition('pending', 'cancelled', 'cancel'),
            new TransitionDefinition('pending', 'paused', 'pause'),
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', [
            'pending' => new StateDefinition('pending'),
            'completed' => new StateDefinition('completed'),
            'cancelled' => new StateDefinition('cancelled'),
            'paused' => new StateDefinition('paused'),
        ], $transitions, 'pending');

        $result = $definition->getTransitionsFor('pending', Constants::EVENT_WILDCARD);

        $this->assertCount(0, $result);
    }

    public function test_export_returns_correct_structure(): void
    {
        $states = [
            'pending' => new StateDefinition('pending', [], [], 'Pending State'),
            'completed' => new StateDefinition('completed', [], [], 'Completed State'),
        ];

        $transitions = [
            new TransitionDefinition('pending', 'completed', 'complete'),
            new TransitionDefinition(null, 'pending', 'start'),
        ];

        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            $states,
            $transitions,
            'pending',
            'App\\Dtos\\TestContext',
            'Test FSM Description'
        );

        $exported = $definition->export();

        $this->assertEquals('TestModel', $exported['model']);
        $this->assertEquals('status', $exported['column']);
        $this->assertEquals('pending', $exported['initial_state']);
        $this->assertEquals('Test FSM Description', $exported['description']);
        $this->assertCount(2, $exported['states']);
        $this->assertCount(2, $exported['transitions']);
    }

    public function test_export_with_null_initial_state(): void
    {
        $states = ['pending' => new StateDefinition('pending')];
        $transitions = [new TransitionDefinition('pending', 'completed', 'complete')];

        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            $states,
            $transitions,
            null // null initial state
        );

        $exported = $definition->export();

        $this->assertNull($exported['initial_state']);
    }

    public function test_export_with_enum_initial_state(): void
    {
        $states = ['idle' => new StateDefinition('idle')];
        $transitions = [new TransitionDefinition('idle', 'pending', 'start')];

        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            $states,
            $transitions,
            TestFeatureState::Idle
        );

        $exported = $definition->export();

        $this->assertEquals('idle', $exported['initial_state']);
    }

    public function test_export_with_null_context_dto_class(): void
    {
        $states = ['pending' => new StateDefinition('pending')];
        $transitions = [new TransitionDefinition('pending', 'completed', 'complete')];

        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            $states,
            $transitions,
            'pending',
            null, // null context DTO class
            'Test Description'
        );

        $exported = $definition->export();

        $this->assertNull($exported['context_dto_class'] ?? null);
    }

    public function test_export_with_empty_states(): void
    {
        $definition = new FsmRuntimeDefinition(
            'TestModel',
            'status',
            [],
            [],
            null
        );

        $exported = $definition->export();

        $this->assertEmpty($exported['states']);
        $this->assertEmpty($exported['transitions']);
    }

    public function test_export_with_complex_transitions(): void
    {
        $transitions = [
            new TransitionDefinition('pending', 'completed', 'complete'),
            new TransitionDefinition(null, 'pending', 'start'),
            new TransitionDefinition('completed', 'pending', Constants::EVENT_WILDCARD),
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', [
            'pending' => new StateDefinition('pending'),
            'completed' => new StateDefinition('completed'),
        ], $transitions, 'pending');

        $exported = $definition->export();

        $this->assertCount(3, $exported['transitions']);

        // Check first transition
        $this->assertEquals('pending', $exported['transitions'][0]['from']);
        $this->assertEquals('completed', $exported['transitions'][0]['to']);
        $this->assertEquals('complete', $exported['transitions'][0]['event']);

        // Check wildcard from state
        $this->assertNull($exported['transitions'][1]['from']);
        $this->assertEquals('pending', $exported['transitions'][1]['to']);
        $this->assertEquals('start', $exported['transitions'][1]['event']);

        // Check wildcard event
        $this->assertEquals('completed', $exported['transitions'][2]['from']);
        $this->assertEquals('pending', $exported['transitions'][2]['to']);
        $this->assertEquals(Constants::EVENT_WILDCARD, $exported['transitions'][2]['event']);
    }

    public function test_export_preserves_state_order(): void
    {
        $states = [
            'completed' => new StateDefinition('completed', [], [], 'Completed State'),
            'pending' => new StateDefinition('pending', [], [], 'Pending State'),
            'cancelled' => new StateDefinition('cancelled', [], [], 'Cancelled State'),
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', $states, [], 'pending');

        $exported = $definition->export();

        $this->assertCount(3, $exported['states']);
        $this->assertEquals('completed', $exported['states'][0]['name']);
        $this->assertEquals('pending', $exported['states'][1]['name']);
        $this->assertEquals('cancelled', $exported['states'][2]['name']);
    }

    public function test_export_with_null_state_descriptions(): void
    {
        $states = [
            'pending' => new StateDefinition('pending', [], []), // no description
            'completed' => new StateDefinition('completed', [], [], null), // explicit null description
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', $states, [], 'pending');

        $exported = $definition->export();

        $this->assertCount(2, $exported['states']);
        $this->assertNull($exported['states'][0]['description']);
        $this->assertNull($exported['states'][1]['description']);
    }

    public function test_constructor_with_duplicate_state_names(): void
    {
        // This should overwrite the duplicate
        $states = [
            'pending' => new StateDefinition('pending', [], [], 'First Pending'),
            'pending' => new StateDefinition('pending', [], [], 'Second Pending'), // duplicate key
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', $states, [], 'pending');

        $this->assertCount(1, $definition->states);
        $this->assertEquals('Second Pending', $definition->states['pending']->description);
    }

    public function test_get_transitions_for_complex_scenario(): void
    {
        $transitions = [
            new TransitionDefinition('idle', 'pending', 'start'),
            new TransitionDefinition('pending', 'completed', Constants::EVENT_WILDCARD),
            new TransitionDefinition('pending', 'cancelled', Constants::EVENT_WILDCARD),
            new TransitionDefinition(null, 'idle', 'reset'),
            new TransitionDefinition('completed', 'pending', 'restart'),
        ];

        $definition = new FsmRuntimeDefinition('TestModel', 'status', [
            'idle' => new StateDefinition('idle'),
            'pending' => new StateDefinition('pending'),
            'completed' => new StateDefinition('completed'),
            'cancelled' => new StateDefinition('cancelled'),
        ], $transitions, 'idle');

        // Test specific event from specific state
        $result1 = $definition->getTransitionsFor('idle', 'start');
        $this->assertCount(1, $result1);
        $this->assertEquals('start', $result1[0]->event);

        // Test wildcard event from specific state
        $result2 = $definition->getTransitionsFor('pending', Constants::EVENT_WILDCARD);
        $this->assertCount(2, $result2);

        // Test specific event from wildcard state
        $result3 = $definition->getTransitionsFor('idle', 'reset');
        $this->assertCount(1, $result3);
        $this->assertEquals('reset', $result3[0]->event);
        $this->assertNull($result3[0]->fromState);
    }
}
