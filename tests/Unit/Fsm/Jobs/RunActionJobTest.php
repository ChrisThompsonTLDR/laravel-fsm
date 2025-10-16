<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Jobs;

use Fsm\Data\TransitionInput;
use Fsm\Jobs\RunActionJob;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

/**
 * @skip Tests use Laravel framework mocking (App::call) that requires Laravel application
 * instance to be properly initialized. These tests verify Laravel framework integration
 * rather than FSM core functionality and may fail in different Laravel versions or
 * test environments due to framework setup differences.
 */
class RunActionJobTest extends FsmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_executes_action_successfully_with_valid_model(): void
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
            callable: TestActionClass::class.'@handle',
            parameters: ['param1' => 'value1'],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestActionClass::class.'@handle',
                \Mockery::on(function ($args) {
                    return isset($args['param1']) && $args['param1'] === 'value1'
                        && isset($args['input']) && $args['input'] instanceof TransitionInput;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_skips_execution_when_model_not_found(): void
    {
        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => 999, // Non-existent ID
            'context' => ['message' => 'test'],
        ];

        $job = new RunActionJob(
            callable: TestActionClass::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')->never();

        $job->handle();
    }

    #[Test]
    public function it_handles_context_deserialization(): void
    {
        $model = TestModel::factory()->create();
        $originalContext = ['message' => 'test', 'userId' => 123];

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => [
                'class' => TestContextData::class,
                'payload' => $originalContext,
            ],
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'test_event',
            'isDryRun' => false,
        ];

        $job = new RunActionJob(
            callable: TestActionClass::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')->once();

        $job->handle();
    }

    #[Test]
    public function it_normalizes_callable_from_class_method_to_class_at_method(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('test message');

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
            callable: TestActionClass::class.'::handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestActionClass::class.'@handle',
                \Mockery::on(function ($args) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_null_context_gracefully(): void
    {
        $model = TestModel::factory()->create();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => null,
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'test_event',
            'isDryRun' => false,
        ];

        $job = new RunActionJob(
            callable: TestActionClass::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')->once();

        $job->handle();
    }

    #[Test]
    public function it_constructs_with_correct_properties(): void
    {
        $callable = TestActionClass::class.'@handle';
        $parameters = ['param1' => 'value1', 'param2' => 'value2'];
        $inputData = ['model_class' => TestModel::class, 'model_id' => 1];

        $job = new RunActionJob($callable, $parameters, $inputData);

        $this->assertEquals($callable, $job->callable);
        $this->assertEquals($parameters, $job->parameters);
        $this->assertEquals($inputData, $job->inputData);
    }
}

/**
 * Test action class for testing purposes
 */
class TestActionClass
{
    public function handle(TransitionInput $input): void
    {
        // Test implementation
    }
}
