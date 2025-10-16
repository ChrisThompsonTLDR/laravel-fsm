<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Definitions;

use Fsm\Contracts\FsmDefinition;
use Fsm\FsmBuilder;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\Feature\Fsm\Services\TestSpyService;

class PaymentStatusFsm implements FsmDefinition
{
    public function define(): void
    {
        $builder = FsmBuilder::for(TestModel::class, 'payment_status')
            ->initialState(TestFeatureState::Pending)
            ->state(TestFeatureState::Pending)
            ->state(TestFeatureState::Completed, function ($builder) {
                $builder->onEntry(TestSpyService::class.'@PaymentCompletedEntry');
            })
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Completed)
            ->event('pay_invoice')
            ->action(TestSpyService::class.'@anAction')
            ->build();
    }
}
