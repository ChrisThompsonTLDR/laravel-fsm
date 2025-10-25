<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Mutations;

use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\FsmBuilder;
use Fsm\Services\FsmEngineService;
use Fsm\Services\FsmLogger;
use Mockery;
use Tests\Feature\Fsm\Enums\TestFeatureState;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

class ConfigurationMutationTests extends FsmTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_default_column_name_configuration_is_honoured(): void
    {
        config()->set('fsm.default_column_name', 'secondary_status');

        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);

        $model->transitionFsm(null, TestFeatureState::Processing);

        $this->assertSame(
            TestFeatureState::Processing->value,
            $model->fresh()->secondary_status,
            'Config-driven default column should be used when column name is omitted.'
        );
    }

    public function test_transition_logging_can_be_disabled(): void
    {
        config()->set('fsm.logging.enabled', false);

        $logger = Mockery::mock(FsmLogger::class);
        $logger->shouldReceive('logTransition')->never();
        $logger->shouldReceive('logFailure')->never();

        $this->app->instance(FsmLogger::class, $logger);
        $this->app->forgetInstance(FsmEngineService::class);

        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);

        $model->transitionFsm('secondary_status', TestFeatureState::Processing);

        $this->assertSame(
            TestFeatureState::Processing->value,
            $model->fresh()->secondary_status,
            'Transition should still succeed while logging is disabled.'
        );
    }

    public function test_failure_logging_can_be_toggled_independently(): void
    {
        config()->set('fsm.logging.enabled', true);
        config()->set('fsm.logging.log_failures', false);

        $logger = Mockery::mock(FsmLogger::class);
        $logger->shouldReceive('logTransition')->never();
        $logger->shouldReceive('logFailure')->never();

        $this->app->instance(FsmLogger::class, $logger);
        $this->app->forgetInstance(FsmEngineService::class);

        FsmBuilder::for(TestModel::class, 'secondary_status')
            ->initialState(TestFeatureState::Pending)
            ->from(TestFeatureState::Pending)
            ->to(TestFeatureState::Processing)
            ->guard(function (): bool {
                return false;
            }, description: 'failing guard for logging test')
            ->build();

        $model = TestModel::factory()->create(['secondary_status' => TestFeatureState::Pending->value]);

        try {
            $model->transitionFsm('secondary_status', TestFeatureState::Processing);
            $this->fail('Transition should fail due to guard rejection.');
        } catch (FsmTransitionFailedException $exception) {
            $this->assertStringContainsString('failing guard for logging test', $exception->getMessage());
        }
    }
}
