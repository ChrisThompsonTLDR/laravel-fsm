<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm;

use Fsm\FsmBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class ActionCallbackFunctionalityTest extends FsmTestCase
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

    public function test_basic_action_registration_works(): void
    {
        $model = TestModel::factory()->create();
        $model->transitionFsm('status', TestFeatureState::Pending);
        $this->assertEquals(TestFeatureState::Pending->value, $model->status);
    }
}
