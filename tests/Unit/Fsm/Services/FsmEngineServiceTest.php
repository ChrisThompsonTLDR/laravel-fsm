<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Closure;
use Fsm\Contracts\FsmStateEnum;
use Fsm\Data\FsmRuntimeDefinition;
use Fsm\Data\StateDefinition;
use Fsm\Data\TransitionAction;
use Fsm\Data\TransitionCallback;
use Fsm\Data\TransitionDefinition;
use Fsm\Data\TransitionGuard;
use Fsm\Exceptions\FsmTransitionFailedException;
use Fsm\FsmRegistry;
use Fsm\Services\FsmEngineService;
use Fsm\Services\FsmLogger;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use RuntimeException;
use Tests\Feature\Fsm\Data\FailingContextDto;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Data\TestContextDto;

enum EngineState: string implements FsmStateEnum
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Done = 'done';

    public function displayName(): string
    {
        return $this->value;
    }

    public function icon(): string
    {
        return $this->value;
    }
}

class EngineModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'engine_models';

    public bool $saved = false;

    public function save(array $options = []): bool
    {
        $this->saved = true;

        return true;
    }
}

/**
 * @skip Tests use Laravel framework mocking (App::call) that requires Laravel application
 * instance to be properly initialized. These tests verify Laravel framework integration
 * rather than FSM core functionality and may fail in different Laravel versions or
 * test environments due to framework setup differences.
 */
class FsmEngineServiceTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('data', [
            'validation_strategy' => 'always',
            'max_transformation_depth' => 512,
            'throw_when_max_transformation_depth_reached' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(FsmRuntimeDefinition $definition, bool $useTransactions = false): FsmEngineService
    {
        $registry = Mockery::mock(FsmRegistry::class);
        $registry->shouldReceive('getDefinition')
            ->andReturn($definition);

        $logger = Mockery::mock(FsmLogger::class);
        $logger->shouldReceive('logTransition')->byDefault();
        $logger->shouldReceive('logFailure')->byDefault();

        $db = Mockery::mock(DatabaseManager::class);
        if ($useTransactions) {
            $db->shouldReceive('transaction')
                ->andReturnUsing(function (Closure $cb) {
                    return $cb();
                });
        } else {
            $db->shouldReceive('transaction')->never();
        }

        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')->with('fsm.use_transactions', true)->andReturn($useTransactions);
        $config->shouldReceive('get')->with('fsm.logging.enabled', true)->andReturn(true);
        $config->shouldReceive('get')->with('fsm.logging.log_failures', true)->andReturn(true);
        $config->shouldReceive('get')->with('fsm.verbs.dispatch_transitioned_verb', true)->andReturn(false);
        $config->shouldReceive('get')->with('fsm.logging.excluded_context_properties', [])->andReturn([]);
        $config->shouldReceive('get')->with('fsm.debug', false)->andReturn(false);

        $dispatcher = Mockery::mock(\Illuminate\Contracts\Events\Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->andReturn(null);
        $metrics = new \Fsm\Services\FsmMetricsService($dispatcher);

        return new FsmEngineService($registry, $logger, $metrics, $db, $config);
    }

    public function test_guard_failure_throws_exception(): void
    {
        $guard = new TransitionGuard(fn () => false, [], 'failing');
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [$guard]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);

        $model = new EngineModel(['status' => EngineState::Pending->value]);

        try {
            $service->performTransition($model, 'status', EngineState::Processing);
            $this->fail('Expected FsmTransitionFailedException was not thrown');
        } catch (FsmTransitionFailedException $e) {
            $this->assertSame(EngineState::Pending->value, $model->status);
        }
    }

    public function test_guard_only_exact_true_passes(): void
    {
        $falsyValues = [false, null, 0, '', [], '0', 0.0];
        foreach ($falsyValues as $falsy) {
            $guard = new TransitionGuard(fn () => $falsy, [], 'falsy');
            $transition = new TransitionDefinition(
                fromState: EngineState::Pending,
                toState: EngineState::Processing,
                event: null,
                guards: [$guard]
            );
            $definition = new FsmRuntimeDefinition(
                EngineModel::class,
                'status',
                [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
                [$transition],
                EngineState::Pending
            );
            $service = $this->makeService($definition);
            $model = new EngineModel(['status' => EngineState::Pending->value]);
            try {
                $service->performTransition($model, 'status', EngineState::Processing);
                $this->fail('Transition should fail for guard return value: '.var_export($falsy, true));
            } catch (FsmTransitionFailedException $e) {
                $this->assertSame(EngineState::Pending->value, $model->status);
            }
        }
    }

    public function test_guard_truthy_but_not_true_fails(): void
    {
        $truthyButNotTrue = [1, 'yes', 'true', new \stdClass];
        foreach ($truthyButNotTrue as $val) {
            $guard = new TransitionGuard(fn () => $val, [], 'truthy');
            $transition = new TransitionDefinition(
                fromState: EngineState::Pending,
                toState: EngineState::Processing,
                event: null,
                guards: [$guard]
            );
            $definition = new FsmRuntimeDefinition(
                EngineModel::class,
                'status',
                [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
                [$transition],
                EngineState::Pending
            );
            $service = $this->makeService($definition);
            $model = new EngineModel(['status' => EngineState::Pending->value]);
            try {
                $service->performTransition($model, 'status', EngineState::Processing);
                $this->fail('Transition should fail for guard return value: '.var_export($val, true));
            } catch (FsmTransitionFailedException $e) {
                $this->assertSame(EngineState::Pending->value, $model->status);
            }
        }
    }

    public function test_guard_exact_true_passes(): void
    {
        $guard = new TransitionGuard(fn () => true, [], 'strict true');
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [$guard]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);
        $service->performTransition($model, 'status', EngineState::Processing);
        $this->assertSame(EngineState::Processing->value, $model->status);
    }

    public function test_action_exception_wraps_and_throws(): void
    {
        $action = new TransitionAction(function () {
            throw new RuntimeException('boom');
        }, [], false);
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: [$action]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);

        $model = new EngineModel(['status' => EngineState::Pending->value]);

        try {
            $service->performTransition($model, 'status', EngineState::Processing);
            $this->fail('Expected FsmTransitionFailedException was not thrown');
        } catch (FsmTransitionFailedException $e) {
            $this->assertSame(EngineState::Pending->value, $model->status);
        }
    }

    public function test_transaction_rolls_back_on_failure(): void
    {
        $action = new TransitionAction(function () {
            throw new RuntimeException('fail');
        });
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: [$action]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $model = new EngineModel(['status' => EngineState::Pending->value]);

        $db = Mockery::mock(DatabaseManager::class);
        $db->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function (Closure $cb) use (&$model) {
                $original = $model->status;
                try {
                    return $cb();
                } catch (\Throwable $e) {
                    $model->status = $original;
                    throw $e;
                }
            });

        $registry = Mockery::mock(FsmRegistry::class);
        $registry->shouldReceive('getDefinition')->andReturn($definition);

        $logger = Mockery::mock(FsmLogger::class);
        $logger->shouldReceive('logFailure')->once();

        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')->with('fsm.use_transactions', true)->andReturn(true);
        $config->shouldReceive('get')->with('fsm.logging.enabled', true)->andReturn(true);
        $config->shouldReceive('get')->with('fsm.logging.log_failures', true)->andReturn(true);
        $config->shouldReceive('get')->with('fsm.verbs.dispatch_transitioned_verb', true)->andReturn(false);
        $config->shouldReceive('get')->with('fsm.logging.excluded_context_properties', [])->andReturn([]);

        $dispatcher = Mockery::mock(\Illuminate\Contracts\Events\Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->andReturn(null);
        $metrics = new \Fsm\Services\FsmMetricsService($dispatcher);
        $service = new FsmEngineService($registry, $logger, $metrics, $db, $config);

        try {
            $service->performTransition($model, 'status', EngineState::Processing);
            $this->fail('Expected exception was not thrown');
        } catch (FsmTransitionFailedException) {
            $this->assertSame(EngineState::Pending->value, $model->status);
        }
    }

    /**
     * Regression test: Ensures that capturing a variable by reference in a closure before it is defined causes a PHP error.
     * This test is skipped by default, but if enabled, it will fail if the bug is reintroduced (i.e., closure captures undefined variable).
     *
     * To use: Remove the skip if you want to verify the regression guard.
     */
    public function test_closure_capture_before_variable_definition_throws_error(): void
    {
        // This test verifies that PHP properly throws an error when a closure captures
        // a variable by reference before it is defined (PHP language regression guard)
        
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: [],
            onTransitionCallbacks: []
        );
        
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );
        
        // Create mocks manually to override the database transaction behavior
        $registry = Mockery::mock(FsmRegistry::class);
        $registry->shouldReceive('getDefinition')->andReturn($definition);
        
        $logger = Mockery::mock(FsmLogger::class);
        $logger->shouldReceive('logTransition')->byDefault();
        $logger->shouldReceive('logFailure')->byDefault();
        
        // Create a database mock that will cause a PHP error when the closure is executed
        $db = Mockery::mock(DatabaseManager::class);
        $db->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function (Closure $cb) {
                // This closure will try to use $model by reference, but $model is not defined
                // This should cause a PHP error
                $original = $model->status; // This line should cause an error
                try {
                    return $cb();
                } catch (\Throwable $e) {
                    $model->status = $original;
                    throw $e;
                }
            });
        
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')->with('fsm.use_transactions', true)->andReturn(true);
        $config->shouldReceive('get')->with('fsm.logging.enabled', true)->andReturn(true);
        $config->shouldReceive('get')->with('fsm.logging.log_failures', true)->andReturn(true);
        $config->shouldReceive('get')->with('fsm.verbs.dispatch_transitioned_verb', true)->andReturn(false);
        $config->shouldReceive('get')->with('fsm.logging.excluded_context_properties', [])->andReturn([]);
        $config->shouldReceive('get')->with('fsm.debug', false)->andReturn(false);
        
        $dispatcher = Mockery::mock(\Illuminate\Contracts\Events\Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->andReturn(null);
        
        $metrics = Mockery::mock(\Fsm\Services\FsmMetricsService::class);
        $metrics->shouldReceive('incrementTransition')->byDefault();
        $metrics->shouldReceive('incrementFailure')->byDefault();
        
        $model = new EngineModel(['status' => EngineState::Pending->value]);
        $service = new FsmEngineService($registry, $logger, $metrics, $db, $config);
        
        // This should trigger a PHP error because $model is used in the closure before it's defined
        $this->expectException(FsmTransitionFailedException::class);
        $this->expectExceptionMessageMatches('/Undefined variable \$model/');
        
        $service->performTransition($model, 'status', EngineState::Processing);
    }

    public function test_dry_run_response(): void
    {
        $guard = new TransitionGuard(fn () => true);
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [$guard]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);

        $model = new EngineModel(['status' => EngineState::Pending->value]);

        $result = $service->dryRunTransition($model, 'status', EngineState::Processing);

        $this->assertTrue($result['can_transition']);
        $this->assertSame('pending', $result['from_state']);
        $this->assertSame('processing', $result['to_state']);
    }

    public function test_dry_run_transition_message_formatting_regression(): void
    {
        $guard = new TransitionGuard(fn () => true);
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [$guard]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);
        $result = $service->dryRunTransition($model, 'status', EngineState::Processing);

        $expectedMessage = 'Dry run: Transition from pending to processing is possible.';
        $this->assertSame($expectedMessage, $result['message'], 'Dry run message should use consistent state formatting for both from and to states.');
    }

    public function test_dry_run_transition_always_returns_strings(): void
    {
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [new TransitionDefinition(
                fromState: EngineState::Pending,
                toState: EngineState::Processing,
                event: null,
                guards: [new TransitionGuard(fn () => true)]
            )],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending]);
        $result = $service->dryRunTransition($model, 'status', EngineState::Processing);
        $this->assertIsString($result['from_state']);
        $this->assertIsString($result['to_state']);
    }

    public function test_get_current_state_returns_null_if_initial_state_is_null(): void
    {
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [], // No transitions needed
            null // initialState is null
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => null]);
        $this->assertNull($service->getCurrentState($model, 'status'));
    }

    public function test_get_current_state_with_unmatched_string_returns_string(): void
    {
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending)],
            [],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => 'unknown_state']);
        $this->assertSame('unknown_state', $service->getCurrentState($model, 'status'));
    }

    public function test_get_current_state_with_enum_not_in_definition_returns_enum(): void
    {
        $enum = new class implements FsmStateEnum
        {
            public string $value = 'ghost';

            public function displayName(): string
            {
                return 'Ghost';
            }

            public function icon(): string
            {
                return '👻';
            }
        };
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending)],
            [],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => $enum]);
        $this->assertSame($enum, $service->getCurrentState($model, 'status'));
    }

    public function test_get_current_state_with_no_states_or_transitions(): void
    {
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [],
            [],
            null
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => null]);
        $this->assertNull($service->getCurrentState($model, 'status'));
    }

    public function test_get_current_state_with_initial_state_string(): void
    {
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition('pending')],
            [],
            'pending'
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => null]);
        $this->assertSame('pending', $service->getCurrentState($model, 'status'));
    }

    public function test_get_current_state_with_initial_state_enum(): void
    {
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending)],
            [],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => null]);
        $this->assertSame(EngineState::Pending, $service->getCurrentState($model, 'status'));
    }

    public function test_get_current_state_with_string_matching_defined_state(): void
    {
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition('pending')],
            [],
            'pending'
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => 'pending']);
        $this->assertSame('pending', $service->getCurrentState($model, 'status'));
    }

    public function test_dry_run_from_state_null(): void
    {
        $guard = new TransitionGuard(fn () => true);
        $transition = new TransitionDefinition(
            fromState: null,
            toState: EngineState::Pending,
            event: null,
            guards: [$guard]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending)],
            [$transition],
            null // initial state is null
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => null]);
        $result = $service->dryRunTransition($model, 'status', EngineState::Pending);
        $this->assertTrue($result['can_transition']);
        $this->assertNull($result['from_state']);
        $this->assertSame('pending', $result['to_state']);
    }

    public function test_dry_run_from_state_enum_instance(): void
    {
        $guard = new TransitionGuard(fn () => true);
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [$guard]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending]); // enum instance
        $result = $service->dryRunTransition($model, 'status', EngineState::Processing);
        $this->assertTrue($result['can_transition']);
        $this->assertSame('pending', $result['from_state']);
        $this->assertSame('processing', $result['to_state']);
    }

    public function test_dry_run_from_state_string_value(): void
    {
        $guard = new TransitionGuard(fn () => true);
        $transition = new TransitionDefinition(
            fromState: 'pending',
            toState: 'processing',
            event: null,
            guards: [$guard]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition('pending'), new StateDefinition('processing')],
            [$transition],
            'pending'
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => 'pending']); // string value
        $result = $service->dryRunTransition($model, 'status', 'processing');
        $this->assertTrue($result['can_transition']);
        $this->assertSame('pending', $result['from_state']);
        $this->assertSame('processing', $result['to_state']);
    }

    public function test_guard_fails_on_non_true_values(): void
    {
        $nonTrueValues = [
            null,
            0,
            '',
            [],
            new \stdClass,
            'false',
            false, // for completeness
        ];
        foreach ($nonTrueValues as $value) {
            $guard = new TransitionGuard(fn () => $value, [], 'non-true');
            $transition = new TransitionDefinition(
                fromState: EngineState::Pending,
                toState: EngineState::Processing,
                event: null,
                guards: [$guard]
            );
            $definition = new FsmRuntimeDefinition(
                EngineModel::class,
                'status',
                [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
                [$transition],
                EngineState::Pending
            );
            $service = $this->makeService($definition);
            $model = new EngineModel(['status' => EngineState::Pending->value]);
            try {
                $service->performTransition($model, 'status', EngineState::Processing);
                $this->fail('Expected FsmTransitionFailedException for guard value: '.var_export($value, true));
            } catch (FsmTransitionFailedException $e) {
                $this->assertSame(EngineState::Pending->value, $model->status);
            }
        }
        // Now test with exact true
        $guard = new TransitionGuard(fn () => true, [], 'true');
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [$guard]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);
        $service->performTransition($model, 'status', EngineState::Processing);
        $this->assertSame(EngineState::Processing->value, $model->status);
    }

    public function test_event_payloads_are_always_strings(): void
    {
        $guard = new TransitionGuard(fn () => true);
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [$guard]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending]);
        $events = [];
        \Illuminate\Support\Facades\Event::listen(
            \Fsm\Events\TransitionAttempted::class,
            function (\Fsm\Events\TransitionAttempted $event) use (&$events) {
                $events[] = $event;
            }
        );
        $service->performTransition($model, 'status', EngineState::Processing);
        $attempted = null;
        foreach ($events as $event) {
            if ($event instanceof \Fsm\Events\TransitionAttempted) {
                $attempted = $event;
                break;
            }
        }
        $this->assertNotNull($attempted, 'TransitionAttempted event was not dispatched');
        $this->assertIsString($attempted->fromState);
        $this->assertIsString($attempted->toState);
    }

    public function test_dry_run_transition_returns_correct_types(): void
    {
        $guard = new TransitionGuard(fn () => true);
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [$guard]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending]);
        $result = $service->dryRunTransition($model, 'status', EngineState::Processing);
        $this->assertIsString($result['from_state']);
        $this->assertIsString($result['to_state']);
    }

    public function test_database_error_is_surfaced_if_missing(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);
        // Simulate DB failure by making save throw
        $model = new class(['status' => EngineState::Pending->value]) extends EngineModel
        {
            public function save(array $options = []): bool
            {
                throw new \PDOException('Simulated DB error');
            }
        };
        $this->expectException(FsmTransitionFailedException::class);
        try {
            $service->performTransition($model, 'status', EngineState::Processing);
        } catch (FsmTransitionFailedException $e) {
            $this->assertInstanceOf(\PDOException::class, $e->getPrevious());
            throw $e;
        }
    }

    public function test_regression_transition_to_same_state_without_loopback_does_nothing(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);

        // Model is in Pending state
        $model = new EngineModel(['status' => EngineState::Pending->value]);
        $model->saved = false;

        // Attempt transition to the *same* state. No loopback transition is defined.
        $resultModel = $service->performTransition($model, 'status', EngineState::Pending);

        // Assert that the model was not saved again and the instance is the same.
        $this->assertFalse($resultModel->saved, 'Model should not be saved when transitioning to the same state without a loopback.');
        $this->assertSame($model, $resultModel, 'The same model instance should be returned.');
    }

    public function test_regression_transition_to_same_state_with_loopback_executes_action(): void
    {
        $actionCalled = false;
        $action = new TransitionAction(function () use (&$actionCalled) {
            $actionCalled = true;
        }, [], false);
        $loopbackTransition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Pending,
            event: null,
            guards: [],
            actions: [$action]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$loopbackTransition],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);
        $service->performTransition($model, 'status', EngineState::Pending);
        $this->assertTrue($actionCalled, 'Loopback transition action should be executed.');
    }

    public function test_regression_wildcard_transition_is_selected_when_no_exact_match(): void
    {
        $wildcardActionCalled = false;
        $wildcardTransition = new TransitionDefinition(
            fromState: \Fsm\Constants::STATE_WILDCARD,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: [new \Fsm\Data\TransitionAction(function () use (&$wildcardActionCalled) {
                $wildcardActionCalled = true;
            })]
        );
        $definition = new \Fsm\Data\FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [
                new \Fsm\Data\StateDefinition(EngineState::Pending),
                new \Fsm\Data\StateDefinition(EngineState::Processing),
                new \Fsm\Data\StateDefinition(EngineState::Done), // Ensure Done is included
            ],
            [$wildcardTransition],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Done->value]); // No exact match for fromState = Done
        $service->performTransition($model, 'status', EngineState::Processing);
        $this->assertTrue($wildcardActionCalled, 'Wildcard transition should be selected and its action executed when no exact match exists.');
    }

    public function test_regression_callback_action_guard_errors(): void
    {
        // Test that errors in guards, actions, and callbacks are properly handled
        // and don't cause unexpected behavior
        
        // Test guard error handling
        $failingGuard = new TransitionGuard(function () {
            throw new \RuntimeException('Guard failed');
        }, [], 'failing guard');
        
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [$failingGuard],
            actions: [],
            onTransitionCallbacks: []
        );
        
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );
        
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);
        
        $this->expectException(FsmTransitionFailedException::class);
        $service->performTransition($model, 'status', EngineState::Processing);
    }

    public function test_regression_perform_transition_exception_to_state_is_original(): void
    {
        $action = new TransitionAction(function () {
            throw new \RuntimeException('action failed');
        }, [], false);
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: [$action]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );
        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);
        try {
            $service->performTransition($model, 'status', EngineState::Processing);
            $this->fail('Expected FsmTransitionFailedException was not thrown');
        } catch (FsmTransitionFailedException $e) {
            // The toState property should be the original EngineState::Processing enum, not a string
            $this->assertInstanceOf(EngineState::class, $e->getToState());
            $this->assertSame(EngineState::Processing, $e->getToState());
        }
    }

    public function test_model_save_is_called_only_once_per_transition(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);

        $model = Mockery::mock(EngineModel::class.'[save]');
        $model->status = EngineState::Pending->value;
        $model->shouldAllowMockingProtectedMethods();
        $model->shouldReceive('save')->once()->andReturn(true);

        $service->performTransition($model, 'status', EngineState::Processing);
        // If save is called more than once, Mockery will throw.
        $this->assertTrue(true, 'Save was called exactly once as expected.');
    }

    public function test_metrics_failure_does_not_mask_original_exception(): void
    {
        $guard = new TransitionGuard(fn () => false, [], 'failing');
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [$guard]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        // Create a metrics service that throws an exception
        $dispatcher = Mockery::mock(\Illuminate\Contracts\Events\Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->andReturn(null);

        $failingMetrics = Mockery::mock(\Fsm\Services\FsmMetricsService::class);
        $failingMetrics->shouldReceive('record')
            ->andThrow(new RuntimeException('Metrics service failed'));

        $registry = Mockery::mock(FsmRegistry::class);
        $registry->shouldReceive('getDefinition')
            ->andReturn($definition);

        $logger = Mockery::mock(FsmLogger::class);
        $logger->shouldReceive('logTransition')->byDefault();
        $logger->shouldReceive('logFailure')->byDefault();

        $db = Mockery::mock(DatabaseManager::class);
        $db->shouldReceive('transaction')->never();

        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')->with('fsm.use_transactions', true)->andReturn(false);
        $config->shouldReceive('get')->with('fsm.logging.enabled', true)->andReturn(true);
        $config->shouldReceive('get')->with('fsm.logging.log_failures', true)->andReturn(true);
        $config->shouldReceive('get')->with('fsm.verbs.dispatch_transitioned_verb', true)->andReturn(false);
        $config->shouldReceive('get')->with('fsm.logging.excluded_context_properties', [])->andReturn([]);
        $config->shouldReceive('get')->with('fsm.debug', false)->andReturn(false);

        $service = new FsmEngineService($registry, $logger, $failingMetrics, $db, $config);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        try {
            $service->performTransition($model, 'status', EngineState::Processing);
            $this->fail('Expected FsmTransitionFailedException was not thrown');
        } catch (FsmTransitionFailedException $e) {
            // The original FsmTransitionFailedException should be thrown, not the metrics exception
            $this->assertStringContainsString('Guard [', $e->getMessage());
            $this->assertStringContainsString('failed for transition from', $e->getMessage());
            $this->assertSame(EngineState::Pending->value, $model->status);
        }
    }

    public function test_build_job_payload_handles_null_context_correctly(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Use reflection to access the private buildJobPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildJobPayload');
        $method->setAccessible(true);

        // Create TransitionInput with null context
        $input = new \Fsm\Data\TransitionInput($model, EngineState::Pending, EngineState::Processing, null);

        $payload = $method->invoke($service, $input);

        $this->assertNull($payload['context']);
        $this->assertFalse($payload['_context_serialization_failed']);
        $this->assertSame(EngineModel::class, $payload['model_class']);
        $this->assertSame($model->getKey(), $payload['model_id']);
    }

    public function test_build_job_payload_handles_valid_context_correctly(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Use reflection to access the private buildJobPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildJobPayload');
        $method->setAccessible(true);

        // Create TransitionInput with valid context
        $context = new TestContextData('test message', 123);
        $input = new \Fsm\Data\TransitionInput($model, EngineState::Pending, EngineState::Processing, $context);

        $payload = $method->invoke($service, $input);

        $this->assertIsArray($payload['context']);
        $this->assertArrayHasKey('class', $payload['context']);
        $this->assertArrayHasKey('payload', $payload['context']);
        $this->assertSame(TestContextData::class, $payload['context']['class']);
        $this->assertIsArray($payload['context']['payload']);
        $this->assertSame('test message', $payload['context']['payload']['message']);
        $this->assertSame(123, $payload['context']['payload']['userId']);
        $this->assertFalse($payload['_context_serialization_failed']);
    }

    /**
     * Test that buildJobPayload properly handles context serialization exceptions
     * by logging the error and setting appropriate flags.
     */
    public function test_build_job_payload_handles_context_serialization_exception(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Use reflection to access the private buildJobPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildJobPayload');
        $method->setAccessible(true);

        // Create TransitionInput with failing context
        $context = new FailingContextDto('test message');
        $input = new \Fsm\Data\TransitionInput($model, EngineState::Pending, EngineState::Processing, $context);

        // Capture log output to verify error logging
        $logMessages = [];
        \Log::shouldReceive('error')
            ->andReturnUsing(function ($message, $context) use (&$logMessages) {
                $logMessages[] = ['message' => $message, 'context' => $context];
            });

        $payload = $method->invoke($service, $input);

        $this->assertNull($payload['context']);
        $this->assertTrue($payload['_context_serialization_failed']);
        $this->assertCount(1, $logMessages);
        $this->assertStringContainsString('[FSM] Context serialization failed during job payload build - queued job will receive null context', $logMessages[0]['message']);
        $this->assertSame(FailingContextDto::class, $logMessages[0]['context']['context_class']);
        $this->assertSame('contextPayload() returned null for non-null context', $logMessages[0]['context']['reason']);
    }

    public function test_build_job_payload_handles_context_payload_returns_null(): void
    {
        // Create a mock context that returns null from contextPayload()
        $mockContext = Mockery::mock(\YorCreative\LaravelArgonautDTO\ArgonautDTOContract::class);
        $mockContext->shouldReceive('toArray')->andReturn(['test' => 'data']);

        // Create a TransitionInput that will return null from contextPayload()
        $input = Mockery::mock(\Fsm\Data\TransitionInput::class);
        $input->context = $mockContext;
        $input->model = new EngineModel(['status' => EngineState::Pending->value]);
        $input->fromState = EngineState::Pending;
        $input->toState = EngineState::Processing;
        $input->event = 'test_event';
        $input->isDryRun = false;
        $input->mode = 'normal';
        $input->source = 'user';
        $input->metadata = [];
        $input->timestamp = now();

        // Mock contextPayload to return null
        $input->shouldReceive('contextPayload')->andReturn(null);

        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);

        // Use reflection to access the private buildJobPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildJobPayload');
        $method->setAccessible(true);

        // Capture log output to verify error logging
        $logMessages = [];
        \Log::shouldReceive('error')
            ->with('[FSM] Context serialization failed during job payload build - queued job will receive null context', Mockery::type('array'))
            ->once()
            ->andReturnUsing(function ($message, $context) use (&$logMessages) {
                $logMessages[] = ['message' => $message, 'context' => $context];
            });

        $payload = $method->invoke($service, $input);

        $this->assertNull($payload['context']);
        $this->assertTrue($payload['_context_serialization_failed']);
        $this->assertCount(1, $logMessages);
        $this->assertSame('contextPayload() returned null for non-null context', $logMessages[0]['context']['reason']);
    }

    public function test_build_job_payload_includes_all_required_fields(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Use reflection to access the private buildJobPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildJobPayload');
        $method->setAccessible(true);

        $context = new TestContextDto('test info');
        $input = new \Fsm\Data\TransitionInput(
            $model,
            EngineState::Pending,
            EngineState::Processing,
            $context,
            'test_event',
            true, // isDryRun
            'force', // mode
            'api', // source
            ['key' => 'value'], // metadata
            now() // timestamp
        );

        $payload = $method->invoke($service, $input);

        // Verify all required fields are present
        $expectedFields = [
            'model_class',
            'model_id',
            'fromState',
            'toState',
            'context',
            'event',
            'isDryRun',
            'mode',
            'source',
            'metadata',
            'timestamp',
            '_context_serialization_failed',
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $payload, "Missing field: {$field}");
        }

        $this->assertSame(EngineModel::class, $payload['model_class']);
        $this->assertSame($model->getKey(), $payload['model_id']);
        $this->assertSame(EngineState::Pending, $payload['fromState']);
        $this->assertSame(EngineState::Processing, $payload['toState']);
        $this->assertSame('test_event', $payload['event']);
        $this->assertTrue($payload['isDryRun']);
        $this->assertSame('force', $payload['mode']);
        $this->assertSame('api', $payload['source']);
        $this->assertSame(['key' => 'value'], $payload['metadata']);
        $this->assertInstanceOf(\DateTimeInterface::class, $payload['timestamp']);
        $this->assertFalse($payload['_context_serialization_failed']);
    }

    public function test_stringify_callable_with_class_string(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);

        // Use reflection to access the private stringifyCallable method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('stringifyCallable');
        $method->setAccessible(true);

        // Test with class string callable
        $callable = [TestCallbackClass::class, 'handle'];
        $result = $method->invoke($service, $callable);

        $this->assertSame(TestCallbackClass::class.'@handle', $result);
    }

    public function test_stringify_callable_with_object_instance_throws_exception(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);

        // Use reflection to access the private stringifyCallable method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('stringifyCallable');
        $method->setAccessible(true);

        // Test with object instance callable - should throw exception
        $instance = new TestCallbackClass;
        $callable = [$instance, 'handle'];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('stringifyCallable should not be called with object instances. Use executeCallableWithInstance instead.');

        $method->invoke($service, $callable);
    }

    public function test_execute_callable_with_instance_with_object_instance(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Create a spy object to verify the method is called on the correct instance
        $spy = new class
        {
            public bool $called = false;

            public array $parameters = [];

            public function testMethod(string $param1, int $param2): string
            {
                $this->called = true;
                $this->parameters = [$param1, $param2];

                return "called with {$param1} and {$param2}";
            }
        };

        $callable = [$spy, 'testMethod'];
        $parameters = ['param1' => 'hello', 'param2' => 42];

        $result = $method->invoke($service, $callable, $parameters);

        $this->assertTrue($spy->called);
        $this->assertSame(['hello', 42], $spy->parameters);
        $this->assertSame('called with hello and 42', $result);
    }

    public function test_execute_callable_with_instance_with_class_string(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with class string callable
        $callable = [TestCallbackClass::class, 'staticHandle'];
        $parameters = ['test_param'];

        // Mock App::call to verify it's called with the correct string format
        \Illuminate\Support\Facades\App::shouldReceive('call')
            ->once()
            ->with(TestCallbackClass::class.'@staticHandle', $parameters)
            ->andReturn('mocked result');

        $result = $method->invoke($service, $callable, $parameters);

        $this->assertSame('mocked result', $result);
    }

    public function test_execute_callable_with_instance_with_closure(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with closure
        $closure = function (string $param) {
            return "closure result: {$param}";
        };
        $parameters = ['test'];

        // Mock App::call to verify it's called with the closure
        \Illuminate\Support\Facades\App::shouldReceive('call')
            ->once()
            ->with($closure, $parameters)
            ->andReturn('mocked closure result');

        $result = $method->invoke($service, $closure, $parameters);

        $this->assertSame('mocked closure result', $result);
    }

    public function test_execute_callable_with_instance_with_string_callable(): void
    {
        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);

        // Use reflection to access the private executeCallableWithInstance method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('executeCallableWithInstance');
        $method->setAccessible(true);

        // Test with string callable
        $callable = TestCallbackClass::class.'@staticHandle';
        $parameters = ['test_param'];

        // Mock App::call to verify it's called with the string callable
        \Illuminate\Support\Facades\App::shouldReceive('call')
            ->once()
            ->with($callable, $parameters)
            ->andReturn('mocked string result');

        $result = $method->invoke($service, $callable, $parameters);

        $this->assertSame('mocked string result', $result);
    }

    public function test_guard_execution_with_object_instance_preserves_instance(): void
    {
        // Create a spy object to track calls
        $guardSpy = new class
        {
            public bool $called = false;

            public ?\Fsm\Data\TransitionInput $input = null;

            public function guardMethod(\Fsm\Data\TransitionInput $input): bool
            {
                $this->called = true;
                $this->input = $input;

                return true;
            }
        };

        $guard = new TransitionGuard(
            callable: [$guardSpy, 'guardMethod'],
            priority: 1,
            stopOnFailure: false
        );

        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [$guard],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Execute the transition
        $result = $service->performTransition($model, 'status', EngineState::Processing);

        // Verify the guard was called on the original instance
        $this->assertTrue($guardSpy->called);
        $this->assertInstanceOf(\Fsm\Data\TransitionInput::class, $guardSpy->input);
        $this->assertSame($model, $result);
    }

    public function test_callback_execution_with_object_instance_preserves_instance(): void
    {
        // Create a spy object to track calls
        $callbackSpy = new class
        {
            public bool $called = false;

            public ?\Fsm\Data\TransitionInput $input = null;

            public function callbackMethod(\Fsm\Data\TransitionInput $input): void
            {
                $this->called = true;
                $this->input = $input;
            }
        };

        $callback = new \Fsm\Data\TransitionCallback(
            callable: [$callbackSpy, 'callbackMethod'],
            queued: false
        );

        $stateDefinition = new StateDefinition(
            EngineState::Processing,
            onEntryCallbacks: [$callback]
        );

        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), $stateDefinition],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Execute the transition
        $result = $service->performTransition($model, 'status', EngineState::Processing);

        // Verify the callback was called on the original instance
        $this->assertTrue($callbackSpy->called);
        $this->assertInstanceOf(\Fsm\Data\TransitionInput::class, $callbackSpy->input);
        $this->assertSame($model, $result);
    }

    public function test_action_execution_with_object_instance_preserves_instance(): void
    {
        // Create a spy object to track calls
        $actionSpy = new class
        {
            public bool $called = false;

            public ?\Fsm\Data\TransitionInput $input = null;

            public function actionMethod(\Fsm\Data\TransitionInput $input): void
            {
                $this->called = true;
                $this->input = $input;
            }
        };

        $action = new TransitionAction(
            callable: [$actionSpy, 'actionMethod'],
            queued: false
        );

        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: [$action]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Execute the transition
        $result = $service->performTransition($model, 'status', EngineState::Processing);

        // Verify the action was called on the original instance
        $this->assertTrue($actionSpy->called);
        $this->assertInstanceOf(\Fsm\Data\TransitionInput::class, $actionSpy->input);
        $this->assertSame($model, $result);
    }

    public function test_queued_callbacks_cannot_use_object_instances(): void
    {
        $callback = new \Fsm\Data\TransitionCallback(
            callable: [new TestCallbackClass, 'handle'], // Object instance
            queued: true
        );

        $stateDefinition = new StateDefinition(
            EngineState::Processing,
            onEntryCallbacks: [$callback]
        );

        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), $stateDefinition],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Execute the transition - should throw exception with proper message
        $this->expectException(\Fsm\Exceptions\FsmTransitionFailedException::class);
        $this->expectExceptionMessage('Queued callbacks cannot use object instances. Use string callables instead.');

        $service->performTransition($model, 'status', EngineState::Processing);
    }

    public function test_queued_actions_cannot_use_object_instances(): void
    {
        $action = new TransitionAction(
            callable: [new TestCallbackClass, 'handle'], // Object instance
            queued: true
        );

        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: [$action]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Execute the transition - should throw exception with proper message
        $this->expectException(\Fsm\Exceptions\FsmTransitionFailedException::class);
        $this->expectExceptionMessage('Queued actions cannot use object instances. Use string callables instead.');

        $service->performTransition($model, 'status', EngineState::Processing);
    }

    public function test_queued_callbacks_can_use_class_string_callables(): void
    {
        Queue::fake();

        $callback = new \Fsm\Data\TransitionCallback(
            callable: [TestCallbackClass::class, 'staticHandle'], // Class string
            queued: true
        );

        $stateDefinition = new StateDefinition(
            EngineState::Processing,
            onEntryCallbacks: [$callback]
        );

        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), $stateDefinition],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Execute the transition - should succeed (no exception)
        $result = $service->performTransition($model, 'status', EngineState::Processing);
        $this->assertSame($model, $result);
        $this->assertSame(EngineState::Processing->value, $model->status);

        // Verify that the job was dispatched
        Queue::assertPushed(\Fsm\Jobs\RunCallbackJob::class);
    }

    public function test_queued_actions_can_use_class_string_callables(): void
    {
        Queue::fake();

        $action = new TransitionAction(
            callable: [TestCallbackClass::class, 'staticHandle'], // Class string
            queued: true
        );

        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: [$action]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Execute the transition - should succeed (no exception)
        $result = $service->performTransition($model, 'status', EngineState::Processing);
        $this->assertSame($model, $result);
        $this->assertSame(EngineState::Processing->value, $model->status);

        // Verify that the job was dispatched
        Queue::assertPushed(\Fsm\Jobs\RunActionJob::class);
    }

    public function test_queued_callbacks_cannot_use_closures(): void
    {
        $callback = new \Fsm\Data\TransitionCallback(
            callable: function () {
                return 'test';
            }, // Closure
            queued: true
        );

        $stateDefinition = new StateDefinition(
            EngineState::Processing,
            onEntryCallbacks: [$callback]
        );

        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: []
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), $stateDefinition],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Execute the transition - should throw exception
        $this->expectException(\Fsm\Exceptions\FsmTransitionFailedException::class);
        $this->expectExceptionMessage('Queued callbacks cannot use closures. Use string callables instead.');

        $service->performTransition($model, 'status', EngineState::Processing);
    }

    public function test_queued_actions_cannot_use_closures(): void
    {
        $action = new TransitionAction(
            callable: function () {
                return 'test';
            }, // Closure
            queued: true
        );

        $transition = new TransitionDefinition(
            fromState: EngineState::Pending,
            toState: EngineState::Processing,
            event: null,
            guards: [],
            actions: [$action]
        );
        $definition = new FsmRuntimeDefinition(
            EngineModel::class,
            'status',
            [new StateDefinition(EngineState::Pending), new StateDefinition(EngineState::Processing)],
            [$transition],
            EngineState::Pending
        );

        $service = $this->makeService($definition);
        $model = new EngineModel(['status' => EngineState::Pending->value]);

        // Execute the transition - should throw exception
        $this->expectException(\Fsm\Exceptions\FsmTransitionFailedException::class);
        $this->expectExceptionMessage('Queued actions cannot use closures. Use string callables instead.');

        $service->performTransition($model, 'status', EngineState::Processing);
    }

    /**
     * Test that DTO with compatible from() method works correctly in filtering.
     */
    public function test_filter_context_with_compatible_from_method_works(): void
    {
        // Create a test DTO that extends Dto with compatible from() method
        $testDto = new class(['message' => 'test', 'secret' => 'hidden']) extends \Fsm\Data\Dto
        {
            public string $message;

            public string $secret;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                if (array_key_exists('secret', $data)) {
                    $this->secret = $data['secret'];
                }
                parent::__construct($data);
            }
        };

        // Mock config to exclude 'secret' property
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('fsm.logging.excluded_context_properties', [])
            ->andReturn(['secret']);

        $service = new FsmEngineService(
            Mockery::mock(FsmRegistry::class),
            Mockery::mock(FsmLogger::class),
            Mockery::mock(\Fsm\Services\FsmMetricsService::class),
            Mockery::mock(DatabaseManager::class),
            $config
        );

        $filtered = $service->filterContextForLogging($testDto);

        $this->assertInstanceOf(\YorCreative\LaravelArgonautDTO\ArgonautDTOContract::class, $filtered);
        $this->assertSame('test', $filtered->message);
        $this->assertArrayNotHasKey('secret', $filtered->toArray());
    }

    /**
     * Test that DTO with incompatible from() method falls back to constructor in filtering.
     */
    public function test_filter_context_with_incompatible_from_method_falls_back_to_constructor(): void
    {

        // Create a test DTO with incompatible from() method (wrong parameter type)
        $testDto = new class(['message' => 'test', 'secret' => 'hidden']) extends \Fsm\Data\Dto
        {
            public string $message;

            public string $secret;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                if (array_key_exists('secret', $data)) {
                    $this->secret = $data['secret'];
                }
                parent::__construct($data);
            }

            public static function from(mixed $data): static
            {
                if (is_array($data)) {
                    return new self($data);
                }

                return new self(['message' => is_string($data) ? $data : '']);
            }

            public function toArray(int $depth = 3): array
            {
                $result = ['message' => $this->message];
                if (isset($this->secret)) {
                    $result['secret'] = $this->secret;
                }

                return $result;
            }
        };

        // Mock config to exclude 'secret' property
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('fsm.logging.excluded_context_properties', [])
            ->andReturn(['secret']);

        $service = new FsmEngineService(
            Mockery::mock(FsmRegistry::class),
            Mockery::mock(FsmLogger::class),
            Mockery::mock(\Fsm\Services\FsmMetricsService::class),
            Mockery::mock(DatabaseManager::class),
            $config
        );

        $filtered = $service->filterContextForLogging($testDto);

        $this->assertInstanceOf(\YorCreative\LaravelArgonautDTO\ArgonautDTOContract::class, $filtered);
        $this->assertSame('test', $filtered->message);
        $this->assertArrayNotHasKey('secret', $filtered->toArray());
    }

    /**
     * Test that DTO with private from() method falls back to constructor in filtering.
     */
    public function test_filter_context_with_private_from_method_falls_back_to_constructor(): void
    {

        // Create a test DTO with private from() method
        $testDto = new class(['message' => 'test', 'secret' => 'hidden']) extends \Fsm\Data\Dto
        {
            public string $message;

            public string $secret;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                if (array_key_exists('secret', $data)) {
                    $this->secret = $data['secret'];
                }
                parent::__construct($data);
            }

            public static function from(mixed $data): static
            {
                return new self(is_array($data) ? $data : []);
            }

            public function toArray(int $depth = 3): array
            {
                $result = ['message' => $this->message];
                if (isset($this->secret)) {
                    $result['secret'] = $this->secret;
                }

                return $result;
            }
        };

        // Mock config to exclude 'secret' property
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('fsm.logging.excluded_context_properties', [])
            ->andReturn(['secret']);

        $service = new FsmEngineService(
            Mockery::mock(FsmRegistry::class),
            Mockery::mock(FsmLogger::class),
            Mockery::mock(\Fsm\Services\FsmMetricsService::class),
            Mockery::mock(DatabaseManager::class),
            $config
        );

        $filtered = $service->filterContextForLogging($testDto);

        $this->assertInstanceOf(\YorCreative\LaravelArgonautDTO\ArgonautDTOContract::class, $filtered);
        $this->assertSame('test', $filtered->message);
        $this->assertArrayNotHasKey('secret', $filtered->toArray());
    }

    /**
     * Test that DTO with no from() method falls back to constructor in filtering.
     */
    public function test_filter_context_with_no_from_method_falls_back_to_constructor(): void
    {

        // Create a test DTO with no from() method
        $testDto = new class(['message' => 'test', 'secret' => 'hidden']) extends \Fsm\Data\Dto
        {
            public string $message;

            public string $secret;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                if (array_key_exists('secret', $data)) {
                    $this->secret = $data['secret'];
                }
                parent::__construct($data);
            }

            public function toArray(int $depth = 3): array
            {
                $result = ['message' => $this->message];
                if (isset($this->secret)) {
                    $result['secret'] = $this->secret;
                }

                return $result;
            }
        };

        // Mock config to exclude 'secret' property
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('fsm.logging.excluded_context_properties', [])
            ->andReturn(['secret']);

        $service = new FsmEngineService(
            Mockery::mock(FsmRegistry::class),
            Mockery::mock(FsmLogger::class),
            Mockery::mock(\Fsm\Services\FsmMetricsService::class),
            Mockery::mock(DatabaseManager::class),
            $config
        );

        $filtered = $service->filterContextForLogging($testDto);

        $this->assertInstanceOf(\YorCreative\LaravelArgonautDTO\ArgonautDTOContract::class, $filtered);
        $this->assertSame('test', $filtered->message);
        $this->assertArrayNotHasKey('secret', $filtered->toArray());
    }

    /**
     * Test that non-Dto ArgonautDTOContract with compatible from() method works correctly in filtering.
     */
    public function test_filter_context_with_non_dto_argonaut_dto_compatible_from_method_works(): void
    {

        // Create a test DTO that implements ArgonautDTOContract but doesn't extend Dto
        $testDto = new class(['message' => 'test', 'secret' => 'hidden']) implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public string $message;

            public string $secret;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                if (array_key_exists('secret', $data)) {
                    $this->secret = $data['secret'];
                }
            }

            public static function from(array $data): self
            {
                return new self($data);
            }

            public function toArray(int $depth = 3): array
            {
                $result = ['message' => $this->message];
                if (isset($this->secret)) {
                    $result['secret'] = $this->secret;
                }

                return $result;
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        // Mock config to exclude 'secret' property
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('fsm.logging.excluded_context_properties', [])
            ->andReturn(['secret']);

        $service = new FsmEngineService(
            Mockery::mock(FsmRegistry::class),
            Mockery::mock(FsmLogger::class),
            Mockery::mock(\Fsm\Services\FsmMetricsService::class),
            Mockery::mock(DatabaseManager::class),
            $config
        );

        $filtered = $service->filterContextForLogging($testDto);

        $this->assertInstanceOf(\YorCreative\LaravelArgonautDTO\ArgonautDTOContract::class, $filtered);
        $this->assertSame('test', $filtered->message);
        $this->assertArrayNotHasKey('secret', $filtered->toArray());
    }

    /**
     * Test that non-Dto ArgonautDTOContract with incompatible from() method falls back to constructor in filtering.
     */
    public function test_filter_context_with_non_dto_argonaut_dto_incompatible_from_method_falls_back_to_constructor(): void
    {

        // Create a test DTO that implements ArgonautDTOContract but doesn't extend Dto
        $testDto = new class(['message' => 'test', 'secret' => 'hidden']) implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public string $message;

            public string $secret;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                if (array_key_exists('secret', $data)) {
                    $this->secret = $data['secret'];
                }
            }

            public static function from(mixed $data): static
            {
                if (is_array($data)) {
                    return new self($data);
                }

                return new self(['message' => is_string($data) ? $data : '']);
            }

            public function toArray(int $depth = 3): array
            {
                $result = ['message' => $this->message];
                if (isset($this->secret)) {
                    $result['secret'] = $this->secret;
                }

                return $result;
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        // Mock config to exclude 'secret' property
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('fsm.logging.excluded_context_properties', [])
            ->andReturn(['secret']);

        $service = new FsmEngineService(
            Mockery::mock(FsmRegistry::class),
            Mockery::mock(FsmLogger::class),
            Mockery::mock(\Fsm\Services\FsmMetricsService::class),
            Mockery::mock(DatabaseManager::class),
            $config
        );

        $filtered = $service->filterContextForLogging($testDto);

        $this->assertInstanceOf(\YorCreative\LaravelArgonautDTO\ArgonautDTOContract::class, $filtered);
        $this->assertSame('test', $filtered->message);
        $this->assertArrayNotHasKey('secret', $filtered->toArray());
    }

    /**
     * Test that DTO with constructor that doesn't accept array returns original context in filtering.
     */
    public function test_filter_context_with_constructor_that_doesnt_accept_array_returns_original(): void
    {

        // Create a test DTO with constructor that doesn't accept array
        $testDto = new class('test') implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract
        {
            public string $message;

            public function __construct(string $message)
            {
                $this->message = $message;
            }

            public static function from(mixed $data): static
            {
                return new self(is_string($data) ? $data : '');
            }

            public function toArray(): array
            {
                return ['message' => $this->message];
            }

            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };

        // Mock config to exclude 'secret' property
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('fsm.logging.excluded_context_properties', [])
            ->andReturn(['secret']);

        $service = new FsmEngineService(
            Mockery::mock(FsmRegistry::class),
            Mockery::mock(FsmLogger::class),
            Mockery::mock(\Fsm\Services\FsmMetricsService::class),
            Mockery::mock(DatabaseManager::class),
            $config
        );

        $filtered = $service->filterContextForLogging($testDto);

        // Should return original context when filtering fails
        $this->assertSame($testDto, $filtered);
    }

    /**
     * Test that null context returns null in filtering.
     */
    public function test_filter_context_with_null_context_returns_null(): void
    {
        $service = new FsmEngineService(
            Mockery::mock(FsmRegistry::class),
            Mockery::mock(FsmLogger::class),
            Mockery::mock(\Fsm\Services\FsmMetricsService::class),
            Mockery::mock(DatabaseManager::class),
            Mockery::mock(ConfigRepository::class)
        );

        $filtered = $service->filterContextForLogging(null);

        $this->assertNull($filtered);
    }

    /**
     * Test that DTO with no excluded properties returns original context in filtering.
     */
    public function test_filter_context_with_no_excluded_properties_returns_original(): void
    {

        // Create a test DTO
        $testDto = new class(['message' => 'test', 'secret' => 'hidden']) extends \Fsm\Data\Dto
        {
            public string $message;

            public string $secret;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                if (array_key_exists('secret', $data)) {
                    $this->secret = $data['secret'];
                }
                parent::__construct($data);
            }

            public function toArray(int $depth = 3): array
            {
                $result = ['message' => $this->message];
                if (isset($this->secret)) {
                    $result['secret'] = $this->secret;
                }

                return $result;
            }
        };

        // Mock config with no excluded properties
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('fsm.logging.excluded_context_properties', [])
            ->andReturn([]);

        $service = new FsmEngineService(
            Mockery::mock(FsmRegistry::class),
            Mockery::mock(FsmLogger::class),
            Mockery::mock(\Fsm\Services\FsmMetricsService::class),
            Mockery::mock(DatabaseManager::class),
            $config
        );

        $filtered = $service->filterContextForLogging($testDto);

        // Should return original context when no properties are excluded
        $this->assertSame($testDto, $filtered);
    }
}

// Test helper class for callable tests
class TestCallbackClass
{
    public function handle(\Fsm\Data\TransitionInput $input): void
    {
        // Test method
    }

    public static function staticHandle(\Fsm\Data\TransitionInput $input): string
    {
        return 'static method called';
    }
}
