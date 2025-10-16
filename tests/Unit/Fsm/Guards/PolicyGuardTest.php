<?php

declare(strict_types=1);

use Fsm\Data\TransitionInput;
use Fsm\Guards\PolicyGuard;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PolicyGuardTest extends TestCase
{
    private PolicyGuard $policyGuard;

    private Gate $gate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gate = Mockery::mock(Gate::class);
        $this->policyGuard = new PolicyGuard($this->gate);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_check_returns_false_when_no_user_authenticated(): void
    {
        // Arrange
        $input = $this->createTransitionInput();

        // Mock Auth facade to return null user
        Auth::shouldReceive('user')->andReturn(null);

        // Act
        $result = $this->policyGuard->check($input, 'update');

        // Assert
        expect($result)->toBeFalse();
    }

    public function test_check_returns_gate_result_for_authenticated_user(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $model = Mockery::mock(\Illuminate\Database\Eloquent\Model::class);
        $input = $this->createTransitionInput($model);

        Auth::shouldReceive('user')->andReturn($user);

        $userGate = Mockery::mock(Gate::class);
        $this->gate->shouldReceive('forUser')->with($user)->andReturn($userGate);
        $userGate->shouldReceive('check')->with('update', [$model])->andReturn(true);

        // Act
        $result = $this->policyGuard->check($input, 'update');

        // Assert
        expect($result)->toBeTrue();
    }

    public function test_check_uses_provided_user_instead_of_auth(): void
    {
        // Arrange
        $authUser = Mockery::mock(Authenticatable::class);
        $providedUser = Mockery::mock(Authenticatable::class);
        $model = Mockery::mock(\Illuminate\Database\Eloquent\Model::class);
        $input = $this->createTransitionInput($model);

        Auth::shouldReceive('user')->andReturn($authUser);

        $userGate = Mockery::mock(Gate::class);
        $this->gate->shouldReceive('forUser')->with($providedUser)->andReturn($userGate);
        $userGate->shouldReceive('check')->with('update', [$model])->andReturn(true);

        // Act
        $result = $this->policyGuard->check($input, 'update', $providedUser);

        // Assert
        expect($result)->toBeTrue();
    }

    public function test_check_merges_additional_parameters(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $model = Mockery::mock(\Illuminate\Database\Eloquent\Model::class);
        $input = $this->createTransitionInput($model);
        $additionalParams = ['extra' => 'value'];

        Auth::shouldReceive('user')->andReturn($user);

        $userGate = Mockery::mock(Gate::class);
        $this->gate->shouldReceive('forUser')->with($user)->andReturn($userGate);
        $userGate->shouldReceive('check')->with('update', [$model, 'extra' => 'value'])->andReturn(true);

        // Act
        $result = $this->policyGuard->check($input, 'update', null, $additionalParams);

        // Assert
        expect($result)->toBeTrue();
    }

    public function test_can_transition_uses_event_as_ability(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $model = Mockery::mock(\Illuminate\Database\Eloquent\Model::class);
        $input = $this->createTransitionInput($model, event: 'confirm');

        Auth::shouldReceive('user')->andReturn($user);

        $userGate = Mockery::mock(Gate::class);
        $this->gate->shouldReceive('forUser')->with($user)->andReturn($userGate);
        $userGate->shouldReceive('check')->with('confirm', [$model])->andReturn(true);

        // Act
        $result = $this->policyGuard->canTransition($input);

        // Assert
        expect($result)->toBeTrue();
    }

    public function test_can_transition_defaults_to_transition_when_no_event(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $model = Mockery::mock(\Illuminate\Database\Eloquent\Model::class);
        $input = $this->createTransitionInput($model, event: null);

        Auth::shouldReceive('user')->andReturn($user);

        $userGate = Mockery::mock(Gate::class);
        $this->gate->shouldReceive('forUser')->with($user)->andReturn($userGate);
        $userGate->shouldReceive('check')->with('transition', [$model])->andReturn(true);

        // Act
        $result = $this->policyGuard->canTransition($input);

        // Assert
        expect($result)->toBeTrue();
    }

    public function test_can_transition_from_uses_formatted_ability(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $model = Mockery::mock(\Illuminate\Database\Eloquent\Model::class);
        $fromState = Mockery::mock(\Fsm\Contracts\FsmStateEnum::class);
        $fromState->value = 'pending';
        $input = $this->createTransitionInput($model, fromState: $fromState);

        Auth::shouldReceive('user')->andReturn($user);

        $userGate = Mockery::mock(Gate::class);
        $this->gate->shouldReceive('forUser')->with($user)->andReturn($userGate);
        $userGate->shouldReceive('check')->with('transitionFrompending', [$model])->andReturn(true);

        // Act
        $result = $this->policyGuard->canTransitionFrom($input);

        // Assert
        expect($result)->toBeTrue();
    }

    public function test_can_transition_to_uses_formatted_ability(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $model = Mockery::mock(\Illuminate\Database\Eloquent\Model::class);
        $toState = Mockery::mock(\Fsm\Contracts\FsmStateEnum::class);
        $toState->value = 'completed';
        $input = $this->createTransitionInput($model, toState: $toState);

        Auth::shouldReceive('user')->andReturn($user);

        $userGate = Mockery::mock(Gate::class);
        $this->gate->shouldReceive('forUser')->with($user)->andReturn($userGate);
        $userGate->shouldReceive('check')->with('transitionTocompleted', [$model])->andReturn(true);

        // Act
        $result = $this->policyGuard->canTransitionTo($input);

        // Assert
        expect($result)->toBeTrue();
    }

    private function createTransitionInput(
        ?\Illuminate\Database\Eloquent\Model $model = null,
        mixed $fromState = null,
        mixed $toState = null,
        ?string $event = null
    ): TransitionInput {
        return new TransitionInput(
            model: $model ?? Mockery::mock(\Illuminate\Database\Eloquent\Model::class),
            fromState: $fromState ?? 'pending',
            toState: $toState ?? 'completed',
            context: null,
            event: $event,
            isDryRun: false
        );
    }
}
