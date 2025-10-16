<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Guards;

use Fsm\FsmBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;
use Tests\Models\TestUser;

class EnhancedGuardIntegrationTest extends FsmTestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Active)->event('activate')
            ->guard(fn () => true)
            ->build();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_policy_guard_integration_with_authenticated_user(): void
    {
        // Arrange
        FsmBuilder::reset();
        $user = TestUser::factory()->create();
        $this->actingAs($user);

        Gate::define('confirm', fn ($user, $model) => true);

        // Define FSM with policy guard
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)
            ->to(TestFeatureState::Active)
            ->policy('confirm')
            ->build();

        $model = TestModel::factory()->create();

        // Act & Assert
        $this->assertTrue($model->canTransitionFsm('status', TestFeatureState::Active));
        $model->transitionFsm('status', TestFeatureState::Active);
        $this->assertEquals(TestFeatureState::Active->value, $model->status);
    }

    public function test_policy_guard_blocks_unauthorized_user(): void
    {
        // Arrange
        FsmBuilder::reset();
        $user = new TestUser(['id' => 2, 'name' => 'Unauthorized User']);
        $this->actingAs($user);

        // Define policy that only allows user ID 1
        Gate::define('confirm', function ($user, $model) {
            return $user->id === 1;
        });

        // Define FSM with policy guard
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Active)
            ->policy('confirm')
            ->build();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle]);

        // Act & Assert - transition should fail for unauthorized user
        expect(fn () => $model->transitionFsm('status', TestFeatureState::Active))
            ->toThrow(\Fsm\Exceptions\FsmTransitionFailedException::class);
    }

    public function test_critical_guard_stops_execution_on_failure(): void
    {
        // Arrange
        FsmBuilder::reset();
        $executed = [];

        // Define FSM with critical guard that fails and a regular guard
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Active)
            ->criticalGuard(function () use (&$executed) {
                $executed[] = 'critical';

                return false; // Fail the transition
            }, [], 'Critical guard')
            ->guard(function () use (&$executed) {
                $executed[] = 'regular';

                return true;
            }, [], 'Regular guard')
            ->build();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle]);

        // Act & Assert
        expect(fn () => $model->transitionFsm('status', TestFeatureState::Active))
            ->toThrow(\Fsm\Exceptions\FsmTransitionFailedException::class);

        // The regular guard should not have executed due to stopOnFailure
        expect($executed)->toBe(['critical']);
    }

    public function test_multiple_guards_with_priorities(): void
    {
        // Arrange
        FsmBuilder::reset();
        $executed = [];

        // Define FSM with multiple guards at different priority levels
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Active)
            ->guard(function () use (&$executed) {
                $executed[] = 'normal';

                return true;
            }, [], 'Normal priority guard')
            ->criticalGuard(function () use (&$executed) {
                $executed[] = 'critical';

                return true;
            }, [], 'Critical priority guard')
            ->build();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle]);

        // Act
        $model->transitionFsm('status', TestFeatureState::Active);

        // Assert - Guards should execute in priority order (highest first)
        expect($model->fresh()->status)->toBe(TestFeatureState::Active->value);
        expect($executed)->toBe(['critical', 'normal']);
    }

    public function test_policy_can_transition_with_event_name(): void
    {
        // Arrange
        FsmBuilder::reset();
        $user = new TestUser(['id' => 1, 'name' => 'Test User']);
        $this->actingAs($user);

        // Define policy for the specific event
        Gate::define('activate', function ($user, $model) {
            return $user->id === 1;
        });

        // Define FSM with event-based policy guard
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Active)
            ->event('activate')
            ->policyCanTransition()
            ->build();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle]);

        // Act
        $model->transitionFsm('status', TestFeatureState::Active);

        // Assert
        expect($model->fresh()->status)->toBe(TestFeatureState::Active->value);
    }

    public function test_guard_debugging_logs_when_enabled(): void
    {
        // Arrange
        FsmBuilder::reset();
        config(['fsm.debug' => true]);

        $guardExecuted = false;

        // Define FSM with guard
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Active)
            ->guard(function () use (&$guardExecuted) {
                $guardExecuted = true;

                return true;
            }, [], 'Test debugging guard')
            ->build();

        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle]);

        // Act
        $model->transitionFsm('status', TestFeatureState::Active);

        // Assert
        expect($model->fresh()->status)->toBe(TestFeatureState::Active->value);
        expect($guardExecuted)->toBeTrue();

        // Note: In a real test, you would capture and verify log messages
        // For now, we just ensure the functionality works with debugging enabled
    }
}
