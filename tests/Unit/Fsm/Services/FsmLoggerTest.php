<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Fsm\Contracts\FsmStateEnum;
use Fsm\Data\Dto;
use Fsm\Services\FsmLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUser;
use Thunk\Verbs\Contracts\BrokersEvents;
use Thunk\Verbs\Facades\Verbs;

// Mock Enum for testing
enum MockStateForLog: string implements FsmStateEnum
{
    case LogFrom = 'log_from';
    case LogTo = 'log_to';

    public function displayName(): string
    {
        return $this->value;
    }

    public function icon(): string
    {
        return $this->value;
    }
}

// Mock Model for testing
class TestLogModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'test_log_models';
}

// Mock Context DTO
class TestLogContext extends Dto
{
    public string $data;

    /**
     * @param  array<string, mixed>|string  $data
     */
    public function __construct(string|array $data)
    {
        if (is_array($data) && func_num_args() === 1 && static::isAssociative($data)) {
            parent::__construct($data);

            return;
        }

        parent::__construct(['data' => $data]);
    }
}

class FsmLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected FsmLogger $logger;

    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set FSM logging config for tests
        $app['config']->set('fsm.logging.enabled', true);
        $app['config']->set('fsm.logging.log_failures', true);
        $app['config']->set('fsm.logging.exception_character_limit', 1000);
    }

    protected function getPackageProviders($app)
    {
        // If FsmServiceProvider registers FsmLogger, it might be needed here
        // For now, we instantiate it directly.
        return [
            // \Fsm\FsmServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->app->make(FsmLogger::class);

        // Run the fsm_logs migrations
        $migration = include __DIR__.'/../../../../src/database/migrations/2024_01_01_000000_create_fsm_logs_table.php';
        $migration->up();
        runFsmDurationMigration();

        // Create a simple table for TestLogModel
        $this->app['db']->connection()->getSchemaBuilder()->create('test_log_models', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
        });
    }

    protected function createTestModel(array $attributes = []): TestLogModel
    {
        return TestLogModel::create(array_merge(['name' => 'TestInstance'], $attributes));
    }

    public function test_log_success_creates_log_entry(): void
    {
        Date::setTestNow(now()); // Freeze time
        $model = $this->createTestModel();
        $context = new TestLogContext('success data');
        $fromState = MockStateForLog::LogFrom;
        $toState = MockStateForLog::LogTo;
        $event = 'test_success_event';

        $this->logger->logSuccess($model, 'status_column', $fromState, $toState, $event, $context, 123);

        $this->assertDatabaseHas('fsm_logs', [
            'model_id' => $model->id,
            'model_type' => $model->getMorphClass(),
            'fsm_column' => 'status_column',
            'from_state' => $fromState->value,
            'to_state' => $toState->value,
            'transition_event' => $event,
            'context_snapshot' => json_encode($context->toArray()),
            'exception_details' => null,
            'duration_ms' => 123,
            'happened_at' => now()->toDateTimeString(),
        ]);
        Date::setTestNow(); // Unfreeze time
    }

    public function test_log_failure_creates_log_entry_with_exception(): void
    {
        Date::setTestNow(now());
        $model = $this->createTestModel();
        $context = new TestLogContext('failure data');
        $fromState = 'string_from';
        $toState = MockStateForLog::LogTo;
        $event = 'test_failure_event';
        $exception = new \RuntimeException('Something went wrong here, it is a very long message to test truncation if needed, but let us make it reasonable.');

        $this->logger->logFailure($model, 'payment_status', $fromState, $toState, $event, $context, $exception, 50);

        // Check that the log entry was created with the correct basic fields
        $this->assertDatabaseHas('fsm_logs', [
            'model_id' => $model->id,
            'model_type' => $model->getMorphClass(),
            'fsm_column' => 'payment_status',
            'from_state' => $fromState,
            'to_state' => $toState->value,
            'transition_event' => $event,
            'context_snapshot' => json_encode($context->toArray()),
            'duration_ms' => 50,
        ]);

        // Check that the exception details field exists and starts with the expected exception message
        $logEntry = \Fsm\Models\FsmLog::where('model_id', $model->id)->first();
        $this->assertNotNull($logEntry);
        $this->assertNotNull($logEntry->exception_details);
        $this->assertStringStartsWith('RuntimeException: Something went wrong here', $logEntry->exception_details);

        Date::setTestNow();
    }

    public function test_logging_is_disabled_via_config(): void
    {
        Config::set('fsm.logging.enabled', false);
        $model = $this->createTestModel();
        $this->logger->logSuccess($model, 'cfg_col', 'cfg_from', 'cfg_to', 'cfg_event', null, 0);
        $this->assertDatabaseCount('fsm_logs', 0);
    }

    public function test_failure_logging_is_disabled_via_config(): void
    {
        Config::set('fsm.logging.log_failures', false);
        Config::set('fsm.logging.enabled', true); // Ensure main logging is on

        $model = $this->createTestModel();
        $exception = new \RuntimeException('A test failure');
        $this->logger->logFailure($model, 'fail_cfg_col', 'fail_from', 'fail_to', 'fail_event', null, $exception, 0);
        $this->assertDatabaseCount('fsm_logs', 0);

        // But success logging should still work
        $this->logger->logSuccess($model, 'succ_cfg_col', 'succ_from', 'succ_to', 'succ_event', null, 0);
        $this->assertDatabaseCount('fsm_logs', 1);
    }

    public function test_exception_message_is_truncated_if_exceeds_limit(): void
    {
        Date::setTestNow(now());
        Config::set('fsm.logging.exception_character_limit', 20);
        $model = $this->createTestModel();
        $exception = new \RuntimeException('This is a very long exception message that should be truncated.');
        $expectedTruncatedMessage = Str::limit((string) $exception, 20);

        $this->logger->logFailure($model, 'trunc_col', 't_from', 't_to', 't_event', null, $exception, 0);

        $this->assertDatabaseHas('fsm_logs', [
            'model_id' => $model->id,
            'exception_details' => $expectedTruncatedMessage,
        ]);
        Date::setTestNow();
    }

    public function test_subject_is_recorded_from_verbs_state(): void
    {
        Config::set('fsm.verbs.log_user_subject', true);
        Config::set('auth.providers.users.model', TestUser::class);

        $userId = (string) Str::uuid();

        $mockBroker = new class($userId) implements BrokersEvents
        {
            public function __construct(private string $id) {}

            public function fire(\Thunk\Verbs\Event $event): ?\Thunk\Verbs\Event
            {
                return $event;
            }

            public function commit(): bool
            {
                return true;
            }

            public function isAuthorized(\Thunk\Verbs\Event $event): bool
            {
                return true;
            }

            public function isValid(\Thunk\Verbs\Event $event): bool
            {
                return true;
            }

            public function replay(?callable $beforeEach = null, ?callable $afterEach = null) {}

            public function state()
            {
                return (object) ['user_id' => $this->id];
            }
        };
        $this->app->instance(BrokersEvents::class, $mockBroker);
        Verbs::swap($mockBroker);

        $model = $this->createTestModel();

        $this->logger->logSuccess($model, 'status_column', MockStateForLog::LogFrom, MockStateForLog::LogTo, null, null, 0);

        $this->assertDatabaseHas('fsm_logs', [
            'subject_id' => $userId,
            'subject_type' => TestUser::class,
        ]);
    }

    public function test_subject_is_recorded_from_verbs_state_with_protected_user_id(): void
    {
        Config::set('fsm.verbs.log_user_subject', true);
        Config::set('auth.providers.users.model', TestUser::class);

        $userId = (string) Str::uuid();

        // Verbs broker with protected user_id
        $mockBroker = new class($userId) implements BrokersEvents
        {
            protected string $user_id;

            public function __construct($id)
            {
                $this->user_id = $id;
            }

            public function fire(\Thunk\Verbs\Event $event): ?\Thunk\Verbs\Event
            {
                return $event;
            }

            public function commit(): bool
            {
                return true;
            }

            public function isAuthorized(\Thunk\Verbs\Event $event): bool
            {
                return true;
            }

            public function isValid(\Thunk\Verbs\Event $event): bool
            {
                return true;
            }

            public function replay(?callable $beforeEach = null, ?callable $afterEach = null) {}

            public function state()
            {
                return $this;
            }
        };
        $this->app->instance(BrokersEvents::class, $mockBroker);
        Verbs::swap($mockBroker);

        $model = $this->createTestModel();
        $this->logger->logSuccess($model, 'status_column', MockStateForLog::LogFrom, MockStateForLog::LogTo, null, null, 0);
        $this->assertDatabaseHas('fsm_logs', [
            'subject_id' => $userId,
            'subject_type' => TestUser::class,
        ]);
    }

    public function test_subject_is_recorded_from_verbs_state_with_private_user_id(): void
    {
        Config::set('fsm.verbs.log_user_subject', true);
        Config::set('auth.providers.users.model', TestUser::class);

        $userId = (string) Str::uuid();

        // Verbs broker with private user_id
        $mockBroker = new class($userId) implements BrokersEvents
        {
            private string $user_id;

            public function __construct($id)
            {
                $this->user_id = $id;
            }

            public function fire(\Thunk\Verbs\Event $event): ?\Thunk\Verbs\Event
            {
                return $event;
            }

            public function commit(): bool
            {
                return true;
            }

            public function isAuthorized(\Thunk\Verbs\Event $event): bool
            {
                return true;
            }

            public function isValid(\Thunk\Verbs\Event $event): bool
            {
                return true;
            }

            public function replay(?callable $beforeEach = null, ?callable $afterEach = null) {}

            public function state()
            {
                return $this;
            }
        };
        $this->app->instance(BrokersEvents::class, $mockBroker);
        Verbs::swap($mockBroker);

        $model = $this->createTestModel();
        $this->logger->logSuccess($model, 'status_column', MockStateForLog::LogFrom, MockStateForLog::LogTo, null, null, 0);
        $this->assertDatabaseHas('fsm_logs', [
            'subject_id' => $userId,
            'subject_type' => TestUser::class,
        ]);
    }

    public function test_subject_is_not_recorded_for_falsy_user_id_values(): void
    {
        Config::set('fsm.verbs.log_user_subject', true);
        Config::set('auth.providers.users.model', TestUser::class);
        $falsyValues = [0, '', false, null];
        foreach ($falsyValues as $falsy) {
            $mockBroker = new class($falsy) implements BrokersEvents
            {
                public function __construct(private $id) {}

                public function fire(\Thunk\Verbs\Event $event): ?\Thunk\Verbs\Event
                {
                    return $event;
                }

                public function commit(): bool
                {
                    return true;
                }

                public function isAuthorized(\Thunk\Verbs\Event $event): bool
                {
                    return true;
                }

                public function isValid(\Thunk\Verbs\Event $event): bool
                {
                    return true;
                }

                public function replay(?callable $beforeEach = null, ?callable $afterEach = null) {}

                public function state()
                {
                    return (object) ['user_id' => $this->id];
                }
            };
            $this->app->instance(BrokersEvents::class, $mockBroker);
            Verbs::swap($mockBroker);
            $model = $this->createTestModel();
            $this->logger->logSuccess($model, 'status_column', MockStateForLog::LogFrom, MockStateForLog::LogTo, null, null, 0);
            $log = \Fsm\Models\FsmLog::latest('happened_at')->first();
            $this->assertNull($log->subject_id, 'subject_id should not be set for user_id: '.var_export($falsy, true));
            $this->assertNull($log->subject_type, 'subject_type should not be set for user_id: '.var_export($falsy, true));
            // Clean up for next iteration
            \Fsm\Models\FsmLog::query()->delete();
        }
    }
}
