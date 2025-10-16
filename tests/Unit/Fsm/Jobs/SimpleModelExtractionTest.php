<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Jobs;

use Fsm\Data\TransitionInput;
use Fsm\Jobs\RunActionJob;
use Fsm\Jobs\RunCallbackJob;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

/**
 * Simple tests for model extraction functionality in FSM jobs.
 * These tests verify that models are correctly extracted and passed to job handlers.
 *
 * @skip Tests use Laravel framework mocking (App::call) that requires Laravel application
 * instance to be properly initialized. These tests verify Laravel framework integration
 * rather than FSM core functionality and may fail in different Laravel versions or
 * test environments due to framework setup differences.
 */
class SimpleModelExtractionTest extends FsmTestCase
{
    #[Test]
    public function it_extracts_model_in_run_action_job(): void
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

        $job = new RunActionJob(
            callable: SimpleTestActionClass::class.'@handle',
            parameters: ['param1' => 'value1'],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                SimpleTestActionClass::class.'@handle',
                \Mockery::on(function ($args) use ($model) {
                    $this->assertArrayHasKey('input', $args);
                    $this->assertInstanceOf(TransitionInput::class, $args['input']);
                    $this->assertEquals($model->id, $args['input']->model->id);
                    $this->assertSame('value1', $args['param1']);

                    return true;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_extracts_model_in_run_callback_job(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('callback message', 456);

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
            callable: SimpleTestCallbackClass::class.'@handle',
            parameters: ['param2' => 'value2'],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                SimpleTestCallbackClass::class.'@handle',
                \Mockery::on(function ($args) use ($model) {
                    $this->assertArrayHasKey('input', $args);
                    $this->assertInstanceOf(TransitionInput::class, $args['input']);
                    $this->assertEquals($model->id, $args['input']->model->id);
                    $this->assertSame('value2', $args['param2']);

                    return true;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_missing_model_gracefully_in_action_job(): void
    {
        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => 99999, // Non-existent ID
            'context' => [
                'class' => TestContextData::class,
                'payload' => ['message' => 'test', 'value' => 123],
            ],
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'test_event',
            'isDryRun' => false,
        ];

        $job = new RunActionJob(
            callable: SimpleTestActionClass::class.'@handle',
            parameters: ['param1' => 'value1'],
            inputData: $inputData
        );

        // Should not call App::call when model is not found
        App::shouldReceive('call')->never();

        $job->handle();
    }

    #[Test]
    public function it_handles_missing_model_gracefully_in_callback_job(): void
    {
        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => 99999, // Non-existent ID
            'context' => [
                'class' => TestContextData::class,
                'payload' => ['message' => 'test', 'value' => 123],
            ],
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'test_event',
            'isDryRun' => false,
        ];

        $job = new RunCallbackJob(
            callable: SimpleTestCallbackClass::class.'@handle',
            parameters: ['param2' => 'value2'],
            inputData: $inputData
        );

        // Should not call App::call when model is not found
        App::shouldReceive('call')->never();

        $job->handle();
    }
}

/**
 * Simple test action class for model extraction testing.
 */
class SimpleTestActionClass
{
    public function handle(TransitionInput $input, string $param1): void
    {
        // Test implementation - just verify the model is accessible
        if ($input->model === null) {
            throw new \RuntimeException('Model should not be null in action');
        }
    }
}

/**
 * Simple test callback class for model extraction testing.
 */
class SimpleTestCallbackClass
{
    public function handle(TransitionInput $input, string $param2): void
    {
        // Test implementation - just verify the model is accessible
        if ($input->model === null) {
            throw new \RuntimeException('Model should not be null in callback');
        }
    }
}
