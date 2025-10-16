<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Events;

use Fsm\Events\StateTransitioned;
use Fsm\Events\TransitionSucceeded;
use Fsm\FsmBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class EventCoexistenceTest extends FsmTestCase
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

    public function test_both_events_are_dispatched_on_successful_transition(): void
    {
        Event::fake();

        $model = TestModel::factory()->create();
        $model->transitionFsm('status', TestFeatureState::Pending);

        Event::assertDispatched(TransitionSucceeded::class);
        Event::assertDispatched(StateTransitioned::class);
    }
}
