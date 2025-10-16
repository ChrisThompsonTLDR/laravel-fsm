<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Definitions;

use Fsm\Contracts\FsmDefinition;
use Fsm\FsmBuilder;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\Feature\Fsm\Services\TestSpyService;

class TestFeatureFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        $builder = FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->state(TestFeatureState::Idle)
            ->state(TestFeatureState::Pending, function ($builder) {
                $builder->onExit(TestSpyService::class.'@onExitCallback');
            })
            ->state(TestFeatureState::Processing, function ($builder) {
                $builder->onEntry(TestSpyService::class.'@OrderStatusProcessingEntry');
            })
            ->state(TestFeatureState::Completed)
            ->state(TestFeatureState::Cancelled)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Processing)
            ->event('process_order')
            ->guard(TestSpyService::class.'@successfulGuard')
            ->action(TestSpyService::class.'@anAction')
            ->before(TestSpyService::class.'@OrderBeforeProcess')
            ->after(TestSpyService::class.'@OrderAfterProcess')
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Cancelled)
            ->guard(TestSpyService::class.'@failingGuard');

        FsmBuilder::for(TestModel::class, 'lifecycle')
            ->initialState(TestFeatureState::Pending)
            ->state(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Processing)
            ->event('start_processing')
            ->from(TestFeatureState::Processing)->to(TestFeatureState::Completed)
            ->event('complete');
    }
}
