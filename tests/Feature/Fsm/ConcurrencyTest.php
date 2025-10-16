<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm;

use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\FsmBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class ConcurrencyTest extends FsmTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)
            ->build();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_it_detects_concurrent_modification_and_fails_transition(): void
    {
        $model = TestModel::factory()->create(['status' => TestFeatureState::Idle]);

        $model1 = TestModel::find($model->id);
        $model2 = TestModel::find($model->id);

        $model1->transitionFsm('status', TestFeatureState::Pending);

        $this->expectException(FsmTransitionFailedException::class);

        $model2->transitionFsm('status', TestFeatureState::Pending);
    }
}
