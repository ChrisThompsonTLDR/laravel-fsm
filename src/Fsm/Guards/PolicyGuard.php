<?php

declare(strict_types=1);

namespace Fsm\Guards;

use Fsm\Data\TransitionInput;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Laravel Policy-based guard for FSM transitions.
 *
 * Integrates with Laravel's authorization system to enforce
 * policy-based access control on state transitions.
 */
class PolicyGuard
{
    public function __construct(
        private readonly Gate $gate,
    ) {}

    /**
     * Check if the authenticated user can perform the transition based on a policy ability.
     *
     * @param  TransitionInput  $input  The transition input data
     * @param  string  $ability  The policy ability to check (e.g., 'update', 'cancel')
     * @param  Authenticatable|null  $user  The user to check, or null for current user
     * @param  array<string, mixed>  $parameters  Additional parameters to pass to the policy
     */
    public function check(
        TransitionInput $input,
        string $ability,
        ?Authenticatable $user = null,
        array $parameters = []
    ): bool {
        // Use the provided user or the currently authenticated user
        $authUser = $user ?? auth()->user();

        if (! $authUser) {
            return false;
        }

        // Merge model and additional parameters
        $policyParams = array_merge([$input->model], $parameters);

        return $this->gate->forUser($authUser)->check($ability, $policyParams);
    }

    /**
     * Check if the authenticated user can perform a specific transition event.
     *
     * @param  TransitionInput  $input  The transition input data
     * @param  Authenticatable|null  $user  The user to check, or null for current user
     * @param  array<string, mixed>  $parameters  Additional parameters to pass to the policy
     */
    public function canTransition(
        TransitionInput $input,
        ?Authenticatable $user = null,
        array $parameters = []
    ): bool {
        $event = $input->event ?? 'transition';

        return $this->check($input, $event, $user, $parameters);
    }

    /**
     * Check if the authenticated user can transition from the current state.
     *
     * @param  TransitionInput  $input  The transition input data
     * @param  Authenticatable|null  $user  The user to check, or null for current user
     * @param  array<string, mixed>  $parameters  Additional parameters to pass to the policy
     */
    public function canTransitionFrom(
        TransitionInput $input,
        ?Authenticatable $user = null,
        array $parameters = []
    ): bool {
        $fromStateValue = $input->fromState instanceof \Fsm\Contracts\FsmStateEnum
            ? $input->fromState->value
            : (string) $input->fromState;

        $ability = "transitionFrom{$fromStateValue}";

        return $this->check($input, $ability, $user, $parameters);
    }

    /**
     * Check if the authenticated user can transition to the target state.
     *
     * @param  TransitionInput  $input  The transition input data
     * @param  Authenticatable|null  $user  The user to check, or null for current user
     * @param  array<string, mixed>  $parameters  Additional parameters to pass to the policy
     */
    public function canTransitionTo(
        TransitionInput $input,
        ?Authenticatable $user = null,
        array $parameters = []
    ): bool {
        $toStateValue = $input->toState instanceof \Fsm\Contracts\FsmStateEnum
            ? $input->toState->value
            : (string) $input->toState;

        $ability = "transitionTo{$toStateValue}";

        return $this->check($input, $ability, $user, $parameters);
    }
}
