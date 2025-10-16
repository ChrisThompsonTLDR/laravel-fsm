<?php

declare(strict_types=1);

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use Fsm\FsmBuilder;
use Fsm\Models\FsmLog;
use Fsm\Services\FsmHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;
use Tests\Models\TestUser;

class FsmHistoryServiceTest extends FsmTestCase
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

    public function test_it_returns_empty_collection_for_model_with_no_history(): void
    {
        $model = TestModel::factory()->create();
        $service = new FsmHistoryService;

        $timeline = $service->getStateTimeline($model, 'status');

        $this->assertEmpty($timeline);
    }

    public function test_state_timeline_includes_subject_and_timestamp_information(): void
    {
        $model = TestModel::factory()->create();
        $happenedAt = CarbonImmutable::parse('2024-01-01 12:00:00', 'UTC');

        $subjectId = Str::uuid()->toString();

        FsmLog::create([
            'model_id' => (string) $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'fsm_column' => 'status',
            'from_state' => '__initial__',
            'to_state' => TestFeatureState::Pending->value,
            'transition_event' => 'start_pending',
            'context_snapshot' => ['foo' => 'bar'],
            'duration_ms' => 123,
            'happened_at' => $happenedAt,
            'subject_id' => $subjectId,
            'subject_type' => TestUser::class,
        ]);

        $service = new FsmHistoryService;
        $timeline = $service->getStateTimeline($model, 'status');

        $this->assertCount(1, $timeline);

        /** @var \Fsm\Data\StateTimelineEntryData $entry */
        $entry = $timeline->first();

        $this->assertSame(TestFeatureState::Pending->value, $entry->toState);
        $this->assertSame('start_pending', $entry->transitionEvent);
        $this->assertSame('status', $entry->fsmColumn);
        $this->assertSame(TestUser::class, $entry->subjectType);
        $this->assertSame($subjectId, $entry->subjectId);
        $this->assertTrue($entry->happenedAt?->equalTo($happenedAt) ?? false);
        $this->assertSame(123, $entry->durationMs);
    }

    public function test_state_time_analysis_calculates_durations_using_chronological_events(): void
    {
        $model = TestModel::factory()->create();

        $first = CarbonImmutable::parse('2024-01-01 08:00:00', 'UTC');
        $second = $first->addMinutes(5);
        $third = $second->addMinutes(10);

        FsmLog::insert([
            [
                'id' => Str::uuid()->toString(),
                'model_id' => (string) $model->getKey(),
                'model_type' => $model->getMorphClass(),
                'fsm_column' => 'status',
                'from_state' => '__initial__',
                'to_state' => TestFeatureState::Pending->value,
                'transition_event' => 'start_pending',
                'happened_at' => $first,
            ],
            [
                'id' => Str::uuid()->toString(),
                'model_id' => (string) $model->getKey(),
                'model_type' => $model->getMorphClass(),
                'fsm_column' => 'status',
                'from_state' => TestFeatureState::Pending->value,
                'to_state' => TestFeatureState::Processing->value,
                'transition_event' => 'start_processing',
                'happened_at' => $second,
            ],
            [
                'id' => Str::uuid()->toString(),
                'model_id' => (string) $model->getKey(),
                'model_type' => $model->getMorphClass(),
                'fsm_column' => 'status',
                'from_state' => TestFeatureState::Processing->value,
                'to_state' => TestFeatureState::Completed->value,
                'transition_event' => 'complete',
                'happened_at' => $third,
            ],
        ]);

        $analysis = (new FsmHistoryService)->getStateTimeAnalysis($model, 'status');

        $this->assertCount(3, $analysis);

        $pending = $analysis->firstWhere('state', TestFeatureState::Pending->value);
        $processing = $analysis->firstWhere('state', TestFeatureState::Processing->value);
        $completed = $analysis->firstWhere('state', TestFeatureState::Completed->value);

        $this->assertNotNull($pending);
        $this->assertSame(300000, $pending->totalDurationMs);
        $this->assertSame(300000.0, $pending->averageDurationMs);
        $this->assertSame(1, $pending->occurrenceCount);

        $this->assertNotNull($processing);
        $this->assertSame(600000, $processing->totalDurationMs);
        $this->assertSame(600000.0, $processing->averageDurationMs);
        $this->assertSame(1, $processing->occurrenceCount);

        $this->assertNotNull($completed);
        $this->assertSame(1, $completed->occurrenceCount);
        $this->assertSame(0, $completed->totalDurationMs);
        $this->assertSame(0.0, $completed->averageDurationMs);
    }
}
