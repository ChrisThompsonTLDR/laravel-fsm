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
        $service = $this->app->make(FsmHistoryService::class);

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

        $service = $this->app->make(FsmHistoryService::class);
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

        $analysis = $this->app->make(FsmHistoryService::class)->getStateTimeAnalysis($model, 'status');

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

    public function test_custom_fsm_log_model_via_config(): void
    {
        // Create a custom FsmLog model class
        $customLogClass = new class extends FsmLog
        {
            protected $table = 'custom_history_logs';
        };

        // Create the custom table
        $this->app['db']->connection()->getSchemaBuilder()->create('custom_history_logs', function ($table) {
            $table->uuid('id')->primary();
            $table->string('subject_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->string('model_type');
            $table->string('model_id', 255);
            $table->string('fsm_column');
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->string('transition_event')->nullable();
            $table->json('context_snapshot')->nullable();
            $table->text('exception_details')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestampTz('happened_at')->useCurrent();
            $table->index(['model_type', 'model_id']);
        });

        // Set the config to use the custom model
        config(['fsm.models.fsm_log' => get_class($customLogClass)]);

        // Create test data in the custom table
        $model = TestModel::factory()->create();
        $happenedAt1 = CarbonImmutable::parse('2024-01-01 12:00:00', 'UTC');
        $happenedAt2 = CarbonImmutable::parse('2024-01-01 12:01:00', 'UTC');

        // First transition to Pending
        $customLogClass::create([
            'model_id' => (string) $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'fsm_column' => 'status',
            'from_state' => '__initial__',
            'to_state' => TestFeatureState::Pending->value,
            'transition_event' => 'custom_start',
            'context_snapshot' => ['custom' => 'data'],
            'duration_ms' => 999,
            'happened_at' => $happenedAt1,
        ]);

        // Second transition to Processing
        $customLogClass::create([
            'model_id' => (string) $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'fsm_column' => 'status',
            'from_state' => TestFeatureState::Pending->value,
            'to_state' => TestFeatureState::Processing->value,
            'transition_event' => 'start_processing',
            'context_snapshot' => ['custom' => 'data2'],
            'duration_ms' => 888,
            'happened_at' => $happenedAt2,
        ]);

        // Create a new history service instance with the updated config
        $service = new FsmHistoryService;

        // Test getStateTimeline with custom model
        $timeline = $service->getStateTimeline($model, 'status');

        $this->assertCount(2, $timeline);
        $firstEntry = $timeline->first();
        $this->assertSame(TestFeatureState::Pending->value, $firstEntry->toState);
        $this->assertSame('custom_start', $firstEntry->transitionEvent);
        $this->assertSame(999, $firstEntry->durationMs);

        // Test getStateTimeAnalysis with custom model
        $analysis = $service->getStateTimeAnalysis($model, 'status');
        // Should have analysis for Pending (time between first and second transition) and Processing (final state)
        $this->assertCount(2, $analysis);

        $pendingAnalysis = $analysis->firstWhere('state', TestFeatureState::Pending->value);
        $this->assertNotNull($pendingAnalysis);
        $this->assertSame(TestFeatureState::Pending->value, $pendingAnalysis->state);
        // Duration should be time between happenedAt1 and happenedAt2 (60 seconds = 60000ms)
        $this->assertSame(60000, $pendingAnalysis->totalDurationMs);
    }
}
