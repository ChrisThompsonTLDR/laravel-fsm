<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Definitions;

use Fsm\Contracts\FsmDefinition;
use Fsm\FsmBuilder;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\Feature\Fsm\Services\TestSpyService;

class TestModelFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->state(TestFeatureState::Idle)
            ->state(TestFeatureState::Pending, function ($builder) {
                $builder->onExit(TestSpyService::class.'@onExitCallback');
            })
            ->state(TestFeatureState::Processing, function ($builder) {
                $builder->onEntry(TestSpyService::class.'@OrderStatusProcessingEntry');
            })
            ->state(TestFeatureState::Active)
            ->state(TestFeatureState::Running)
            ->state(TestFeatureState::Completed)
            ->state(TestFeatureState::Cancelled)
            ->state(TestFeatureState::Failed)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Active)
            ->event('activate')
            ->guard(TestSpyService::class.'@successfulGuard')
            ->action(TestSpyService::class.'@anAction')
            ->from(TestFeatureState::Active)->to(TestFeatureState::Idle)
            ->event('deactivate')
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)
            ->event('start_pending')
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Processing)
            ->event('start_processing')
            ->guard(TestSpyService::class.'@successfulGuard')
            ->action(TestSpyService::class.'@anAction')
            ->onTransitionCallback(TestSpyService::class.'@OrderAfterProcess')
            ->from(TestFeatureState::Processing)->to(TestFeatureState::Completed)
            ->event('complete')
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Cancelled)
            ->event('cancel')
            ->guard(TestSpyService::class.'@failingGuard')
            ->build();

        FsmBuilder::for(TestModel::class, 'lifecycle')
            ->initialState(TestFeatureState::Pending)
            ->state(TestFeatureState::Pending, function ($builder) {
                $builder->onEntry(TestSpyService::class.'@onEntryCallback');
            })
            ->state(TestFeatureState::Processing)
            ->state(TestFeatureState::Completed)
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Processing)
            ->event('start_processing')
            ->from(TestFeatureState::Processing)->to(TestFeatureState::Completed)
            ->event('complete')
            ->build();
    }
}
