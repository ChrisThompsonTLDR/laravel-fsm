<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Jobs;

use Fsm\Data\TransitionInput;
use Fsm\Jobs\RunCallbackJob;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

/**
 * Test that RunCallbackJob correctly incorporates the model in TransitionInput.
 * This fixes the bug where the model was being ignored during array-based construction.
 *
 * @skip Tests use Laravel framework mocking (App::call) that requires Laravel application
 * instance to be properly initialized. These tests verify Laravel framework integration
 * rather than FSM core functionality and may fail in different Laravel versions or
 * test environments due to framework setup differences.
 */
class RunCallbackJobModelExtractionBugFixTest extends FsmTestCase
{
    #[Test]
    public function it_correctly_incorporates_model_in_transition_input(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('test message', 123);

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => [
                'class' => TestContextData::class,
                'payload' => $context->toArray(),
            ],
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'test_event',
            'isDryRun' => false,
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassModelExtraction::class.'@handle',
            parameters: ['param1' => 'value1'],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassModelExtraction::class.'@handle',
                \Mockery::on(function ($args) use ($model) {
                    return isset($args['param1']) && $args['param1'] === 'value1'
                        && isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->model->is($model); // Verify the model is correctly incorporated
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_snake_case_keys_correctly(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('snake case test', 456);

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => [
                'class' => TestContextData::class,
                'payload' => $context->toArray(),
            ],
            'from_state' => 'pending',
            'to_state' => 'completed',
            'event' => 'snake_event',
            'is_dry_run' => true,
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassModelExtraction::class.'@handle',
            parameters: ['snake' => 'case'],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassModelExtraction::class.'@handle',
                \Mockery::on(function ($args) use ($model) {
                    return isset($args['snake']) && $args['snake'] === 'case'
                        && isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->model->is($model)
                        && $args['input']->fromState === 'pending'
                        && $args['input']->toState === 'completed'
                        && $args['input']->isDryRun === true;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_context_hydration_correctly(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('context test', 789);

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => [
                'class' => TestContextData::class,
                'payload' => $context->toArray(),
            ],
            'toState' => 'completed',
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassModelExtraction::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassModelExtraction::class.'@handle',
                \Mockery::on(function ($args) use ($model, $context) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->model->is($model)
                        && $args['input']->context instanceof TestContextData
                        && $args['input']->context->message === $context->message
                        && $args['input']->context->userId === $context->userId;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_null_context_correctly(): void
    {
        $model = TestModel::factory()->create();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => null,
            'toState' => 'completed',
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassModelExtraction::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassModelExtraction::class.'@handle',
                \Mockery::on(function ($args) use ($model) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->model->is($model)
                        && $args['input']->context === null;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_metadata_correctly(): void
    {
        $model = TestModel::factory()->create();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'toState' => 'completed',
            'metadata' => [
                'user_id' => 123,
                'action' => 'callback',
                'nested' => ['key' => 'value'],
            ],
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassModelExtraction::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassModelExtraction::class.'@handle',
                \Mockery::on(function ($args) use ($model) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->model->is($model)
                        && $args['input']->metadata['user_id'] === 123
                        && $args['input']->metadata['action'] === 'callback'
                        && $args['input']->metadata['nested']['key'] === 'value';
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_dry_run_mode_correctly(): void
    {
        $model = TestModel::factory()->create();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'mode' => TransitionInput::MODE_DRY_RUN,
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassModelExtraction::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassModelExtraction::class.'@handle',
                \Mockery::on(function ($args) use ($model) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->model->is($model)
                        && $args['input']->mode === TransitionInput::MODE_DRY_RUN
                        && $args['input']->isDryRun === true;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_force_mode_correctly(): void
    {
        $model = TestModel::factory()->create();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'toState' => 'completed',
            'mode' => TransitionInput::MODE_FORCE,
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassModelExtraction::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassModelExtraction::class.'@handle',
                \Mockery::on(function ($args) use ($model) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->model->is($model)
                        && $args['input']->mode === TransitionInput::MODE_FORCE
                        && $args['input']->isForced() === true;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_silent_mode_correctly(): void
    {
        $model = TestModel::factory()->create();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'toState' => 'completed',
            'mode' => TransitionInput::MODE_SILENT,
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassModelExtraction::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassModelExtraction::class.'@handle',
                \Mockery::on(function ($args) use ($model) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->model->is($model)
                        && $args['input']->mode === TransitionInput::MODE_SILENT
                        && $args['input']->isSilent() === true;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_different_sources_correctly(): void
    {
        $model = TestModel::factory()->create();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'toState' => 'completed',
            'source' => TransitionInput::SOURCE_SYSTEM,
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassModelExtraction::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassModelExtraction::class.'@handle',
                \Mockery::on(function ($args) use ($model) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->model->is($model)
                        && $args['input']->source === TransitionInput::SOURCE_SYSTEM;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_timestamp_correctly(): void
    {
        $model = TestModel::factory()->create();
        $timestamp = now();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'toState' => 'completed',
            'timestamp' => $timestamp,
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassModelExtraction::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassModelExtraction::class.'@handle',
                \Mockery::on(function ($args) use ($model, $timestamp) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->model->is($model)
                        && $args['input']->timestamp === $timestamp;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_skips_execution_when_model_not_found(): void
    {
        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => 999999, // Non-existent ID
            'toState' => 'completed',
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassModelExtraction::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')->never();

        $job->handle();
    }
}

/**
 * Test callback class for testing purposes
 */
class TestCallbackClassModelExtraction
{
    public function handle(TransitionInput $input): void
    {
        // Test implementation
    }
}
