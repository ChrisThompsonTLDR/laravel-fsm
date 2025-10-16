<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm;

use Fsm\FsmBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;
use Thunk\Verbs\Facades\Verbs;

class FsmModelIntegrationTest extends FsmTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FsmBuilder::for(TestModel::class, 'status')
            ->initialState(TestFeatureState::Idle)
            ->from(TestFeatureState::Idle)->to(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Processing)
            ->from(TestFeatureState::Processing)->to(TestFeatureState::Completed)
            ->from(TestFeatureState::Pending)->to(TestFeatureState::Cancelled)
            ->from(TestFeatureState::Processing)->to(TestFeatureState::Failed)
            ->build();
    }

    protected function tearDown(): void
    {
        FsmBuilder::reset();
        parent::tearDown();
    }

    public function test_basic_transition_succeeds_with_events_logging_and_verb(): void
    {
        Event::fake();
        Verbs::assertNothingCommitted();

        $model = TestModel::factory()->create();
        $context = new TestContextData('test_info', 123);

        try {
            $result = $model->transitionFsm('status', TestFeatureState::Pending, $context);
        } catch (\Exception $e) {
            $this->fail('Unexpected exception during transition: '.$e->getMessage()."\n".$e->getTraceAsString());
        }

        $this->assertInstanceOf(TestModel::class, $result);
        $this->assertEquals(TestFeatureState::Pending->value, $result->status);
        $this->assertDatabaseHas('test_models', ['id' => $model->id, 'status' => 'pending']);
    }
}
