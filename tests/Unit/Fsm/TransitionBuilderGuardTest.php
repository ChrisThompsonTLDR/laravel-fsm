<?php

declare(strict_types=1);

use Fsm\Data\TransitionGuard;
use Fsm\TransitionBuilder;
use Tests\TestCase;

class TransitionBuilderGuardTest extends TestCase
{
    private TransitionBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new TransitionBuilder('TestModel', 'status');
    }

    public function test_policy_method_adds_policy_guard(): void
    {
        // Arrange & Act
        $runtimeDef = $this->builder
            ->from('pending')->to('completed')
            ->policy('update', ['param' => 'value'], 'Can update order')
            ->buildRuntimeDefinition();

        // Assert
        $transitions = $runtimeDef->transitions;
        expect($transitions)->toHaveCount(1);

        $guards = $transitions[0]->guards;
        expect($guards)->toHaveCount(1);

        $guard = $guards[0];
        expect($guard)->toBeInstanceOf(TransitionGuard::class);
        expect($guard->description)->toBe('Can update order');
        expect($guard->parameters)->toHaveKey('ability');
        expect($guard->parameters['ability'])->toBe('update');
        expect($guard->parameters['param'])->toBe('value');
    }

    public function test_policy_can_transition_adds_generic_policy_guard(): void
    {
        // Arrange & Act
        $runtimeDef = $this->builder
            ->from('pending')->to('completed')
            ->policyCanTransition(['context' => 'test'])
            ->buildRuntimeDefinition();

        // Assert
        $transitions = $runtimeDef->transitions;
        expect($transitions)->toHaveCount(1);

        $guards = $transitions[0]->guards;
        expect($guards)->toHaveCount(1);

        $guard = $guards[0];
        expect($guard)->toBeInstanceOf(TransitionGuard::class);
        expect($guard->description)->toBe('Policy check: can transition');
        expect($guard->parameters)->toHaveKey('context');
        expect($guard->parameters['context'])->toBe('test');
    }

    public function test_critical_guard_sets_high_priority_and_stop_on_failure(): void
    {
        // Arrange & Act
        $runtimeDef = $this->builder
            ->from('pending')->to('completed')
            ->criticalGuard(fn () => true, ['test' => 'param'], 'Critical security check')
            ->buildRuntimeDefinition();

        // Assert
        $transitions = $runtimeDef->transitions;
        expect($transitions)->toHaveCount(1);

        $guards = $transitions[0]->guards;
        expect($guards)->toHaveCount(1);

        $guard = $guards[0];
        expect($guard)->toBeInstanceOf(TransitionGuard::class);
        expect($guard->priority)->toBe(TransitionGuard::PRIORITY_CRITICAL);
        expect($guard->stopOnFailure)->toBeTrue();
        expect($guard->description)->toBe('Critical security check');
        expect($guard->parameters)->toHaveKey('test');
        expect($guard->parameters['test'])->toBe('param');
    }

    public function test_multiple_guard_types_can_be_combined(): void
    {
        // Arrange & Act
        $runtimeDef = $this->builder
            ->from('pending')->to('completed')
            ->guard(fn () => true, [], 'Basic guard')
            ->policy('update')
            ->criticalGuard(fn () => true, [], 'Critical guard')
            ->policyCanTransition()
            ->buildRuntimeDefinition();

        // Assert
        $transitions = $runtimeDef->transitions;
        expect($transitions)->toHaveCount(1);

        $guards = $transitions[0]->guards;
        expect($guards)->toHaveCount(4);

        // Check that we have the expected guard types
        $descriptions = [];
        foreach ($guards as $guard) {
            $descriptions[] = $guard->description;
        }
        expect($descriptions)->toContain('Basic guard');
        expect($descriptions)->toContain('Policy check: update');
        expect($descriptions)->toContain('Critical guard');
        expect($descriptions)->toContain('Policy check: can transition');

        // Check that critical guard has correct properties
        $criticalGuard = null;
        foreach ($guards as $guard) {
            if ($guard->description === 'Critical guard') {
                $criticalGuard = $guard;
                break;
            }
        }
        expect($criticalGuard)->not()->toBeNull();
        expect($criticalGuard->priority)->toBe(TransitionGuard::PRIORITY_CRITICAL);
        expect($criticalGuard->stopOnFailure)->toBeTrue();
    }

    public function test_policy_methods_throw_exception_without_from_and_to(): void
    {
        // Act & Assert
        expect(fn () => $this->builder->policy('update'))
            ->toThrow(\LogicException::class, 'policy() must be called after from() and to() in a transition definition.');

        expect(fn () => $this->builder->policyCanTransition())
            ->toThrow(\LogicException::class, 'policyCanTransition() must be called after from() and to() in a transition definition.');

        expect(fn () => $this->builder->criticalGuard(fn () => true))
            ->toThrow(\LogicException::class, 'criticalGuard() must be called after from() and to() in a transition definition.');
    }

    public function test_policy_with_custom_description(): void
    {
        // Arrange & Act
        $runtimeDef = $this->builder
            ->from('pending')->to('completed')
            ->policy('cancel', [], 'Custom cancellation policy check')
            ->buildRuntimeDefinition();

        // Assert
        $transitions = $runtimeDef->transitions;
        $guards = $transitions[0]->guards;
        $guard = $guards[0];

        expect($guard->description)->toBe('Custom cancellation policy check');
        expect($guard->parameters['ability'])->toBe('cancel');
    }
}
