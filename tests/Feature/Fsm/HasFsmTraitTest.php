<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm;

use Fsm\FsmBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Fsm\Data\TestContextDto;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class HasFsmTraitTest extends FsmTestCase
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

    public function test_model_can_transition_successfully_with_context(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextDto(info: 'test');

        $model->transitionFsm('status', TestFeatureState::Pending, $context);

        $this->assertEquals(TestFeatureState::Pending->value, $model->status);
    }
}
