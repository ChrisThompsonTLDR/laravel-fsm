<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Events;

use Fsm\Events\StateTransitioned;
use Fsm\FsmBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class StateTransitionedEventTest extends FsmTestCase
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

    public function test_state_transitioned_event_is_dispatched_on_success(): void
    {
        Event::fake();

        $model = TestModel::factory()->create();
        $model->transitionFsm('status', TestFeatureState::Pending);

        Event::assertDispatched(StateTransitioned::class, function ($event) use ($model) {
            return $event->model->is($model) &&
                $event->columnName === 'status' &&
                $event->fromState === TestFeatureState::Idle->value &&
                $event->toState === TestFeatureState::Pending->value;
        });
    }
}
