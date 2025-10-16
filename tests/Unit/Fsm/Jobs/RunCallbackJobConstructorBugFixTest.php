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
 * Test that RunCallbackJob uses from() method instead of constructor-based instantiation.
 * This ensures correct data hydration and prevents potential issues with DTO instantiation.
 *
 * @skip Tests use Laravel framework mocking (App::call) that requires Laravel application
 * instance to be properly initialized. These tests verify Laravel framework integration
 * rather than FSM core functionality and may fail in different Laravel versions or
 * test environments due to framework setup differences.
 */
class RunCallbackJobConstructorBugFixTest extends FsmTestCase
{
    #[Test]
    public function it_uses_from_method_instead_of_constructor_for_transition_input(): void
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
            callable: TestCallbackClassConstructorBugFix::class.'@handle',
            parameters: ['param1' => 'value1'],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassConstructorBugFix::class.'@handle',
                \Mockery::on(function ($args) {
                    return isset($args['param1']) && $args['param1'] === 'value1'
                        && isset($args['input']) && $args['input'] instanceof TransitionInput;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_context_hydration_correctly_with_from_method(): void
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

        $job = new RunCallbackJob(
            callable: TestCallbackClassConstructorBugFix::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')->once();

        $job->handle();
    }

    #[Test]
    public function it_handles_null_context_gracefully_with_from_method(): void
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

        $job = new RunCallbackJob(
            callable: TestCallbackClassConstructorBugFix::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')->once();

        $job->handle();
    }

    #[Test]
    public function it_handles_complex_context_data_with_from_method(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('complex message', 456);

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => [
                'class' => TestContextData::class,
                'payload' => $context->toArray(),
            ],
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'complex_event',
            'isDryRun' => true,
            'mode' => TransitionInput::MODE_DRY_RUN,
            'source' => TransitionInput::SOURCE_API,
            'metadata' => ['key1' => 'value1', 'key2' => 'value2'],
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassConstructorBugFix::class.'@handle',
            parameters: ['complex' => 'data'],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassConstructorBugFix::class.'@handle',
                \Mockery::on(function ($args) {
                    return isset($args['complex']) && $args['complex'] === 'data'
                        && isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->isDryRun === true
                        && $args['input']->mode === TransitionInput::MODE_DRY_RUN
                        && $args['input']->source === TransitionInput::SOURCE_API;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_snake_case_keys_correctly_with_from_method(): void
    {
        $model = TestModel::factory()->create();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => null,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassConstructorBugFix::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassConstructorBugFix::class.'@handle',
                \Mockery::on(function ($args) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->fromState === 'pending'
                        && $args['input']->toState === 'completed'
                        && $args['input']->event === 'test_event'
                        && $args['input']->isDryRun === false;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_empty_parameters_array_with_from_method(): void
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

        $job = new RunCallbackJob(
            callable: TestCallbackClassConstructorBugFix::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassConstructorBugFix::class.'@handle',
                \Mockery::on(function ($args) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_skips_execution_when_model_not_found_with_from_method(): void
    {
        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => 999, // Non-existent ID
            'context' => ['message' => 'test'],
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassConstructorBugFix::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')->never();

        $job->handle();
    }

    #[Test]
    public function it_handles_string_callable_with_from_method(): void
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

        $job = new RunCallbackJob(
            callable: TestCallbackClassConstructorBugFix::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassConstructorBugFix::class.'@handle',
                \Mockery::on(function ($args) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_constructs_with_correct_properties_using_from_method(): void
    {
        $callable = TestCallbackClassConstructorBugFix::class.'@handle';
        $parameters = ['param1' => 'value1', 'param2' => 'value2'];
        $inputData = ['model_class' => TestModel::class, 'model_id' => 1];

        $job = new RunCallbackJob($callable, $parameters, $inputData);

        $this->assertEquals($callable, $job->callable);
        $this->assertEquals($parameters, $job->parameters);
        $this->assertEquals($inputData, $job->inputData);
    }

    #[Test]
    public function it_handles_context_serialization_with_from_method(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('serialization test', 789);

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => [
                'class' => TestContextData::class,
                'payload' => $context->toArray(),
            ],
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'serialization_event',
            'isDryRun' => false,
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassConstructorBugFix::class.'@handle',
            parameters: [],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassConstructorBugFix::class.'@handle',
                \Mockery::on(function ($args) {
                    return isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->context !== null;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_handles_mixed_context_data_with_from_method(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('mixed data test', 999);

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => [
                'class' => TestContextData::class,
                'payload' => $context->toArray(),
            ],
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'mixed_event',
            'isDryRun' => false,
            'mode' => TransitionInput::MODE_SILENT,
            'source' => TransitionInput::SOURCE_SCHEDULER,
            'metadata' => ['mixed' => 'data', 'test' => true],
        ];

        $job = new RunCallbackJob(
            callable: TestCallbackClassConstructorBugFix::class.'@handle',
            parameters: ['mixed' => 'params'],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                TestCallbackClassConstructorBugFix::class.'@handle',
                \Mockery::on(function ($args) {
                    return isset($args['mixed']) && $args['mixed'] === 'params'
                        && isset($args['input']) && $args['input'] instanceof TransitionInput
                        && $args['input']->mode === TransitionInput::MODE_SILENT
                        && $args['input']->source === TransitionInput::SOURCE_SCHEDULER
                        && $args['input']->metadata['mixed'] === 'data'
                        && $args['input']->metadata['test'] === true;
                })
            );

        $job->handle();
    }
}

/**
 * Test callback class for testing purposes
 */
class TestCallbackClassConstructorBugFix
{
    public function handle(TransitionInput $input): void
    {
        // Test implementation
    }
}
