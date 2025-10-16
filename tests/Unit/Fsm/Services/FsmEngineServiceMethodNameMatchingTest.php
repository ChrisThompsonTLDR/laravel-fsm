<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Fsm\Contracts\FsmStateEnum;
use Fsm\Data\FsmRuntimeDefinition;
use Fsm\Data\StateDefinition;
use Fsm\Data\TransitionDefinition;
use Fsm\Data\TransitionGuard;
use Fsm\FsmRegistry;
use Fsm\Services\FsmEngineService;
use Fsm\Services\FsmLogger;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Orchestra\Testbench\TestCase;

class FsmEngineServiceMethodNameMatchingTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(FsmRuntimeDefinition $definition): FsmEngineService
    {
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

        $dispatcher = Mockery::mock(\Illuminate\Contracts\Events\Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->andReturn(null);
        $metrics = new \Fsm\Services\FsmMetricsService($dispatcher);

        return new FsmEngineService($registry, $logger, $metrics, $db, $config);
    }

    /**
     * Test that method name matching works correctly for object instances with camelCase methods.
     */
    public function test_object_instance_camel_case_method_matching(): void
    {
        $spy = new class
        {
            public bool $called = false;

            public ?\Fsm\Data\TransitionInput $input = null;

            public function testMethod(\Fsm\Data\TransitionInput $input): bool
            {
                $this->called = true;
                $this->input = $input;

                return true;
            }
        };

        $guard = new TransitionGuard(
            callable: [$spy, 'testMethod'],
            priority: 1,
            stopOnFailure: false
        );

        $transition = new TransitionDefinition(
            fromState: TestState::Pending,
            toState: TestState::Processing,
            event: null,
            guards: [$guard]
        );

        $definition = new FsmRuntimeDefinition(
            TestModel::class,
            'status',
            [new StateDefinition(TestState::Pending), new StateDefinition(TestState::Processing)],
            [$transition],
            TestState::Pending
        );

        $service = $this->makeService($definition);
        $model = new TestModel(['status' => TestState::Pending->value]);

        $result = $service->performTransition($model, 'status', TestState::Processing);

        $this->assertTrue($spy->called);
        $this->assertInstanceOf(\Fsm\Data\TransitionInput::class, $spy->input);
        $this->assertSame(TestState::Processing->value, $result->status);
    }

    /**
     * Test that method name matching works correctly for object instances with snake_case methods.
     */
    public function test_object_instance_snake_case_method_matching(): void
    {
        $spy = new class
        {
            public bool $called = false;

            public ?\Fsm\Data\TransitionInput $input = null;

            public function testMethod(\Fsm\Data\TransitionInput $input): bool
            {
                $this->called = true;
                $this->input = $input;

                return true;
            }
        };

        $guard = new TransitionGuard(
            callable: [$spy, 'testMethod'],
            priority: 1,
            stopOnFailure: false
        );

        $transition = new TransitionDefinition(
            fromState: TestState::Pending,
            toState: TestState::Processing,
            event: null,
            guards: [$guard]
        );

        $definition = new FsmRuntimeDefinition(
            TestModel::class,
            'status',
            [new StateDefinition(TestState::Pending), new StateDefinition(TestState::Processing)],
            [$transition],
            TestState::Pending
        );

        $service = $this->makeService($definition);
        $model = new TestModel(['status' => TestState::Pending->value]);

        $result = $service->performTransition($model, 'status', TestState::Processing);

        $this->assertTrue($spy->called);
        $this->assertInstanceOf(\Fsm\Data\TransitionInput::class, $spy->input);
        $this->assertSame(TestState::Processing->value, $result->status);
    }

    /**
     * Test that method name matching works correctly for class string callables.
     */
    public function test_class_string_method_matching(): void
    {
        $guard = new TransitionGuard(
            callable: [TestCallableClass::class, 'staticMethod'],
            priority: 1,
            stopOnFailure: false
        );

        $transition = new TransitionDefinition(
            fromState: TestState::Pending,
            toState: TestState::Processing,
            event: null,
            guards: [$guard]
        );

        $definition = new FsmRuntimeDefinition(
            TestModel::class,
            'status',
            [new StateDefinition(TestState::Pending), new StateDefinition(TestState::Processing)],
            [$transition],
            TestState::Pending
        );

        $service = $this->makeService($definition);
        $model = new TestModel(['status' => TestState::Pending->value]);

        $result = $service->performTransition($model, 'status', TestState::Processing);

        $this->assertSame(TestState::Processing->value, $result->status);
    }

    /**
     * Test that method name matching works correctly for string callables.
     */
    public function test_string_callable_method_matching(): void
    {
        $guard = new TransitionGuard(
            callable: TestCallableClass::class.'@staticMethod',
            priority: 1,
            stopOnFailure: false
        );

        $transition = new TransitionDefinition(
            fromState: TestState::Pending,
            toState: TestState::Processing,
            event: null,
            guards: [$guard]
        );

        $definition = new FsmRuntimeDefinition(
            TestModel::class,
            'status',
            [new StateDefinition(TestState::Pending), new StateDefinition(TestState::Processing)],
            [$transition],
            TestState::Pending
        );

        $service = $this->makeService($definition);
        $model = new TestModel(['status' => TestState::Pending->value]);

        $result = $service->performTransition($model, 'status', TestState::Processing);

        $this->assertSame(TestState::Processing->value, $result->status);
    }

    /**
     * Test that method name matching works correctly for closures.
     */
    public function test_closure_method_matching(): void
    {
        $called = false;
        $capturedInput = null;

        $guard = new TransitionGuard(
            callable: function (\Fsm\Data\TransitionInput $input) use (&$called, &$capturedInput): bool {
                $called = true;
                $capturedInput = $input;

                return true;
            },
            priority: 1,
            stopOnFailure: false
        );

        $transition = new TransitionDefinition(
            fromState: TestState::Pending,
            toState: TestState::Processing,
            event: null,
            guards: [$guard]
        );

        $definition = new FsmRuntimeDefinition(
            TestModel::class,
            'status',
            [new StateDefinition(TestState::Pending), new StateDefinition(TestState::Processing)],
            [$transition],
            TestState::Pending
        );

        $service = $this->makeService($definition);
        $model = new TestModel(['status' => TestState::Pending->value]);

        $result = $service->performTransition($model, 'status', TestState::Processing);

        $this->assertTrue($called);
        $this->assertInstanceOf(\Fsm\Data\TransitionInput::class, $capturedInput);
        $this->assertSame(TestState::Processing->value, $result->status);
    }

    /**
     * Test that method name mismatches throw appropriate exceptions.
     */
    public function test_method_name_mismatch_throws_exception(): void
    {
        $spy = new class
        {
            public function existingMethod(\Fsm\Data\TransitionInput $input): bool
            {
                return true;
            }
        };

        $guard = new TransitionGuard(
            callable: [$spy, 'nonExistentMethod'],
            priority: 1,
            stopOnFailure: false
        );

        $transition = new TransitionDefinition(
            fromState: TestState::Pending,
            toState: TestState::Processing,
            event: null,
            guards: [$guard]
        );

        $definition = new FsmRuntimeDefinition(
            TestModel::class,
            'status',
            [new StateDefinition(TestState::Pending), new StateDefinition(TestState::Processing)],
            [$transition],
            TestState::Pending
        );

        $service = $this->makeService($definition);
        $model = new TestModel(['status' => TestState::Pending->value]);

        $this->expectException(\Fsm\Exceptions\FsmTransitionFailedException::class);
        $this->expectExceptionMessage('Guard [class@anonymous');

        $service->performTransition($model, 'status', TestState::Processing);
    }

    /**
     * Test that method name matching works with complex parameter types.
     */
    public function test_complex_parameter_types_method_matching(): void
    {
        $spy = new class
        {
            public bool $called = false;

            public ?\Fsm\Data\TransitionInput $input = null;

            public array $parameters = [];

            public function complexMethod(\Fsm\Data\TransitionInput $input): bool
            {
                $this->called = true;
                $this->input = $input;

                return true;
            }
        };

        $guard = new TransitionGuard(
            callable: [$spy, 'complexMethod'],
            priority: 1,
            stopOnFailure: false
        );

        $transition = new TransitionDefinition(
            fromState: TestState::Pending,
            toState: TestState::Processing,
            event: null,
            guards: [$guard]
        );

        $definition = new FsmRuntimeDefinition(
            TestModel::class,
            'status',
            [new StateDefinition(TestState::Pending), new StateDefinition(TestState::Processing)],
            [$transition],
            TestState::Pending
        );

        $service = $this->makeService($definition);
        $model = new TestModel(['status' => TestState::Pending->value]);

        $result = $service->performTransition($model, 'status', TestState::Processing);

        $this->assertTrue($spy->called);
        $this->assertInstanceOf(\Fsm\Data\TransitionInput::class, $spy->input);
        $this->assertSame(TestState::Processing->value, $result->status);
    }

    /**
     * Test that method name matching works with named parameters.
     */
    public function test_named_parameters_method_matching(): void
    {
        $spy = new class
        {
            public bool $called = false;

            public ?\Fsm\Data\TransitionInput $input = null;

            public array $parameters = [];

            public function namedParamMethod(\Fsm\Data\TransitionInput $input): bool
            {
                $this->called = true;
                $this->input = $input;

                return true;
            }
        };

        $guard = new TransitionGuard(
            callable: [$spy, 'namedParamMethod'],
            priority: 1,
            stopOnFailure: false
        );

        $transition = new TransitionDefinition(
            fromState: TestState::Pending,
            toState: TestState::Processing,
            event: null,
            guards: [$guard]
        );

        $definition = new FsmRuntimeDefinition(
            TestModel::class,
            'status',
            [new StateDefinition(TestState::Pending), new StateDefinition(TestState::Processing)],
            [$transition],
            TestState::Pending
        );

        $service = $this->makeService($definition);
        $model = new TestModel(['status' => TestState::Pending->value]);

        $result = $service->performTransition($model, 'status', TestState::Processing);

        $this->assertTrue($spy->called);
        $this->assertInstanceOf(\Fsm\Data\TransitionInput::class, $spy->input);
        $this->assertSame(TestState::Processing->value, $result->status);
    }
}

enum TestState: string implements FsmStateEnum
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

class TestModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'test_models';

    public function save(array $options = []): bool
    {
        return true;
    }
}

class TestCallableClass
{
    public static function staticMethod(\Fsm\Data\TransitionInput $input): bool
    {
        return true;
    }
}
