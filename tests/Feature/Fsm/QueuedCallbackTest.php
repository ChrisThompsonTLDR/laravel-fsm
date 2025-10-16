<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm;

use Fsm\FsmBuilder;
use Fsm\Jobs\RunCallbackJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class QueuedCallbackTest extends FsmTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->state(TestFeatureState::Pending, function ($builder) {
                $builder->onEntry([\Tests\Feature\Fsm\Callbacks\TestCallback::class, 'handle'], [], false, true);
            })
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)
            ->build();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_queued_callback_is_dispatched(): void
    {
        Queue::fake();

        $model = TestModel::factory()->create();
        $model->transitionFsm('status', TestFeatureState::Pending, new TestContextData('test'));

        Queue::assertPushed(RunCallbackJob::class);
    }
}
