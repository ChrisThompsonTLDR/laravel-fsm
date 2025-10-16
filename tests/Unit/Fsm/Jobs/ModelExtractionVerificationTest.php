<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Jobs;

use Fsm\Data\TransitionInput;
use Fsm\Jobs\RunActionJob;
use Fsm\Jobs\RunCallbackJob;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

/**
 * Comprehensive verification tests for model extraction functionality in FSM jobs.
 * These tests verify that models are correctly extracted, passed to job handlers,
 * and that various edge cases are handled properly.
 *
 * @skip Tests use Laravel framework mocking (App::call) that requires Laravel application
 * instance to be properly initialized. These tests verify Laravel framework integration
 * rather than FSM core functionality and may fail in different Laravel versions or
 * test environments due to framework setup differences.
 */
class ModelExtractionVerificationTest extends FsmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
    }

    #[Test]
    public function it_verifies_model_extraction_with_complex_context(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('complex context message', 789);

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
            'isDryRun' => false,
            'metadata' => ['source' => 'test', 'version' => '1.0'],
        ];

        $job = new RunActionJob(
            callable: VerificationTestActionClass::class.'@handle',
            parameters: ['complexParam' => 'complexValue', 'nested' => ['key' => 'value']],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                VerificationTestActionClass::class.'@handle',
                \Mockery::on(function ($args) use ($model, $context) {
                    $this->assertArrayHasKey('input', $args);
                    $this->assertInstanceOf(TransitionInput::class, $args['input']);

                    // Verify model extraction
                    $this->assertEquals($model->id, $args['input']->model->id);

                    // Verify context is properly hydrated
                    $this->assertInstanceOf(TestContextData::class, $args['input']->context);
                    $this->assertSame($context->message, $args['input']->context->message);
                    $this->assertSame($context->userId, $args['input']->context->userId);

                    // Verify other properties
                    $this->assertSame('pending', $args['input']->fromState);
                    $this->assertSame('completed', $args['input']->toState);
                    $this->assertSame('complex_event', $args['input']->event);
                    $this->assertFalse($args['input']->isDryRun);

                    // Verify parameters
                    $this->assertSame('complexValue', $args['complexParam']);
                    $this->assertSame(['key' => 'value'], $args['nested']);

                    return true;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_verifies_model_extraction_with_null_context(): void
    {
        $model = TestModel::factory()->create();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => null,
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'null_context_event',
            'isDryRun' => false,
        ];

        $job = new RunCallbackJob(
            callable: VerificationTestCallbackClass::class.'@handle',
            parameters: ['nullContextParam' => 'nullValue'],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                VerificationTestCallbackClass::class.'@handle',
                \Mockery::on(function ($args) use ($model) {
                    $this->assertArrayHasKey('input', $args);
                    $this->assertInstanceOf(TransitionInput::class, $args['input']);

                    // Verify model extraction
                    $this->assertEquals($model->id, $args['input']->model->id);

                    // Verify context is null
                    $this->assertNull($args['input']->context);

                    // Verify other properties
                    $this->assertSame('pending', $args['input']->fromState);
                    $this->assertSame('completed', $args['input']->toState);
                    $this->assertSame('null_context_event', $args['input']->event);

                    // Verify parameters
                    $this->assertSame('nullValue', $args['nullContextParam']);

                    return true;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_verifies_model_extraction_with_dry_run_mode(): void
    {
        $model = TestModel::factory()->create();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => null,
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'dry_run_event',
            'isDryRun' => true,
        ];

        $job = new RunActionJob(
            callable: VerificationTestActionClass::class.'@handle',
            parameters: ['dryRunParam' => 'dryRunValue'],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                VerificationTestActionClass::class.'@handle',
                \Mockery::on(function ($args) use ($model) {
                    $this->assertArrayHasKey('input', $args);
                    $this->assertInstanceOf(TransitionInput::class, $args['input']);

                    // Verify model extraction
                    $this->assertEquals($model->id, $args['input']->model->id);

                    // Verify dry run mode
                    $this->assertTrue($args['input']->isDryRun);

                    return true;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_verifies_logging_when_model_not_found_in_action_job(): void
    {
        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => 99999, // Non-existent ID
            'context' => null,
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'missing_model_event',
            'isDryRun' => false,
        ];

        $job = new RunActionJob(
            callable: VerificationTestActionClass::class.'@handle',
            parameters: ['missingModelParam' => 'missingModelValue'],
            inputData: $inputData
        );

        // Should not call App::call when model is not found
        App::shouldReceive('call')->never();

        $job->handle();

        // Verify warning was logged
        Log::shouldHaveReceived('warning')
            ->once()
            ->with(
                '[FSM] Queued action skipped: model not found',
                \Mockery::on(function ($context) {
                    return $context['model_class'] === TestModel::class
                        && $context['model_id'] === 99999
                        && $context['callable'] === VerificationTestActionClass::class.'@handle';
                })
            );
    }

    #[Test]
    public function it_verifies_logging_when_model_not_found_in_callback_job(): void
    {
        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => 99999, // Non-existent ID
            'context' => null,
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'missing_model_callback_event',
            'isDryRun' => false,
        ];

        $job = new RunCallbackJob(
            callable: VerificationTestCallbackClass::class.'@handle',
            parameters: ['missingModelCallbackParam' => 'missingModelCallbackValue'],
            inputData: $inputData
        );

        // Should not call App::call when model is not found
        App::shouldReceive('call')->never();

        $job->handle();

        // Verify warning was logged
        Log::shouldHaveReceived('warning')
            ->once()
            ->with(
                '[FSM] Queued callback skipped: model not found',
                \Mockery::on(function ($context) {
                    return $context['model_class'] === TestModel::class
                        && $context['model_id'] === 99999
                        && $context['callable'] === VerificationTestCallbackClass::class.'@handle';
                })
            );
    }

    #[Test]
    public function it_verifies_context_loss_logging_in_action_job(): void
    {
        $model = TestModel::factory()->create();
        $originalContext = ['class' => 'NonExistentClass', 'payload' => ['test' => 'data']];

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => $originalContext,
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'context_loss_event',
            'isDryRun' => false,
        ];

        $job = new RunActionJob(
            callable: VerificationTestActionClass::class.'@handle',
            parameters: ['contextLossParam' => 'contextLossValue'],
            inputData: $inputData
        );

        // Expect the job to handle the context hydration failure gracefully
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class NonExistentClass: class does not exist');

        $job->handle();
    }

    #[Test]
    public function it_verifies_context_loss_logging_in_callback_job(): void
    {
        $model = TestModel::factory()->create();
        $originalContext = ['class' => 'NonExistentClass', 'payload' => ['test' => 'data']];

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => $originalContext,
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'context_loss_callback_event',
            'isDryRun' => false,
        ];

        $job = new RunCallbackJob(
            callable: VerificationTestCallbackClass::class.'@handle',
            parameters: ['contextLossCallbackParam' => 'contextLossCallbackValue'],
            inputData: $inputData
        );

        // Expect the job to handle the context hydration failure gracefully
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class NonExistentClass: class does not exist');

        $job->handle();
    }

    #[Test]
    public function it_verifies_model_extraction_with_string_callable(): void
    {
        $model = TestModel::factory()->create();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => null,
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'string_callable_event',
            'isDryRun' => false,
        ];

        $job = new RunActionJob(
            callable: 'VerificationTestActionClass::handle', // String callable format
            parameters: ['stringCallableParam' => 'stringCallableValue'],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                'VerificationTestActionClass@handle',
                \Mockery::on(function ($args) use ($model) {
                    $this->assertArrayHasKey('input', $args);
                    $this->assertInstanceOf(TransitionInput::class, $args['input']);
                    $this->assertEquals($model->id, $args['input']->model->id);

                    return true;
                })
            );

        $job->handle();
    }

    #[Test]
    public function it_verifies_model_extraction_with_callback_string_callable(): void
    {
        $model = TestModel::factory()->create();

        $inputData = [
            'model_class' => TestModel::class,
            'model_id' => $model->id,
            'context' => null,
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'callback_string_callable_event',
            'isDryRun' => false,
        ];

        $job = new RunCallbackJob(
            callable: 'VerificationTestCallbackClass::handle', // String callable format
            parameters: ['callbackStringParam' => 'callbackStringValue'],
            inputData: $inputData
        );

        App::shouldReceive('call')
            ->once()
            ->with(
                'VerificationTestCallbackClass::handle',
                \Mockery::on(function ($args) use ($model) {
                    $this->assertArrayHasKey('input', $args);
                    $this->assertInstanceOf(TransitionInput::class, $args['input']);
                    $this->assertEquals($model->id, $args['input']->model->id);

                    return true;
                })
            );

        $job->handle();
    }
}

/**
 * Verification test action class for comprehensive model extraction testing.
 */
class VerificationTestActionClass
{
    public function handle(TransitionInput $input, ?string $complexParam = null, ?array $nested = null): void
    {
        // Test implementation - verify the model is accessible and properly extracted
        if ($input->model === null) {
            throw new \RuntimeException('Model should not be null in verification action');
        }

        // Additional verification can be added here
    }
}

/**
 * Verification test callback class for comprehensive model extraction testing.
 */
class VerificationTestCallbackClass
{
    public function handle(TransitionInput $input, ?string $nullContextParam = null): void
    {
        // Test implementation - verify the model is accessible and properly extracted
        if ($input->model === null) {
            throw new \RuntimeException('Model should not be null in verification callback');
        }

        // Additional verification can be added here
    }
}
