<?php

declare(strict_types=1);

require_once __DIR__.'/../../src/Fsm/Contracts/FsmStateEnum.php';
require_once __DIR__.'/../../src/Fsm/TransitionBuilder.php';
require_once __DIR__.'/../../src/Fsm/Data/FsmRuntimeDefinition.php';
require_once __DIR__.'/../../src/Fsm/Data/StateDefinition.php';
require_once __DIR__.'/../../src/Fsm/Data/TransitionDefinition.php';
require_once __DIR__.'/../../src/Fsm/Data/TransitionGuard.php';
require_once __DIR__.'/../../src/Fsm/Data/TransitionCallback.php';
require_once __DIR__.'/../../src/Fsm/Data/TransitionAction.php';

use Fsm\Constants;
use Fsm\Contracts\FsmStateEnum;
use Fsm\Data\FsmRuntimeDefinition;
use Fsm\TransitionBuilder;

enum CombatState: string implements FsmStateEnum
{
    case Idle = 'idle';
    case Attacking = 'attacking';
    case Dead = 'dead';

    public function displayName(): string
    {
        return ucfirst($this->value);
    }

    public function icon(): string
    {
        return 'icon-'.$this->value;
    }
}

function canTransition(FsmRuntimeDefinition $runtime, FsmStateEnum|string $reqFrom, FsmStateEnum|string $reqTo): bool
{
    $reqFromValue = ($reqFrom instanceof FsmStateEnum) ? $reqFrom->value : (string) $reqFrom;
    $reqToValue = ($reqTo instanceof FsmStateEnum) ? $reqTo->value : (string) $reqTo;

    // $runtime->transitions is a flat array: TransitionDefinition[]
    foreach ($runtime->transitions as $td) { // $td IS a TransitionDefinition object
        $actualTdFromState = $td->fromState; // Property of TransitionDefinition
        $actualTdToState = $td->toState;   // Property of TransitionDefinition

        // Normalize the fromState from the current transition definition
        $actualTdFromValue = $actualTdFromState === null
                            ? null
                            : (($actualTdFromState instanceof FsmStateEnum)
                                ? $actualTdFromState->value
                                : (string) $actualTdFromState); // Handles enums, strings, and importantly, '*' directly

        // Normalize the toState from the current transition definition
        $actualTdToValue = $actualTdToState === null
                            ? null
                            : (($actualTdToState instanceof FsmStateEnum)
                                ? $actualTdToState->value
                                : (string) $actualTdToState);

        // Check if the current transition definition's fromState matches the required fromState (or is a wildcard)
        $fromStateMatches = ($actualTdFromValue === $reqFromValue) || ($actualTdFromValue === Constants::STATE_WILDCARD);

        // Check if the current transition definition's toState matches the required toState
        $toStateMatches = ($actualTdToValue === $reqToValue);

        if ($fromStateMatches && $toStateMatches) {
            return true; // Found a valid transition path
        }
    }

    return false; // No suitable transition found after checking all definitions
}

test('TransitionBuilder registers transitions and default state', function () {
    $builder = new TransitionBuilder(stdClass::class, 'state');

    $builder
        ->initial(CombatState::Idle)
        ->state(CombatState::Idle, fn (TransitionBuilder $b) => $b->onEntry(fn () => null))
        ->state(CombatState::Attacking, fn (TransitionBuilder $b) => $b->onEntry(fn () => null))
        ->state(CombatState::Dead, fn (TransitionBuilder $b) => $b->onEntry(fn () => null))
        ->transition()
        ->from(CombatState::Idle)
        ->to(CombatState::Attacking)
        ->add()
        ->transition()
        ->from(Constants::STATE_WILDCARD)
        ->to(CombatState::Dead)
        ->add();

    $runtime = $builder->buildRuntimeDefinition();

    expect(canTransition($runtime, CombatState::Idle, CombatState::Attacking))->toBeTrue();
    expect($builder->getInitialState())->toBe(CombatState::Idle);
    expect(canTransition($runtime, CombatState::Attacking, CombatState::Dead))->toBeTrue();
});
