<?php

declare(strict_types=1);

use Fsm\Events\StateTransitioned;
use Fsm\Listeners\PersistStateTransitionedEvent;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create tables for testing (matches pattern from FsmReplayServiceTest)
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->dropIfExists('fsm_event_logs');
    $schema->create('fsm_event_logs', function ($table) {
        $table->uuid('id')->primary();
        $table->string('model_id');
        $table->string('model_type');
        $table->string('column_name');
        $table->string('from_state')->nullable();
        $table->string('to_state');
        $table->string('transition_name')->nullable();
        $table->timestamp('occurred_at')->nullable();
        $table->json('context')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamp('created_at')->nullable();
    });

    $this->model = new class extends Model
    {
        protected $fillable = ['name', 'status'];

        public $timestamps = false;

        public $table = 'test_models';

        public function getMorphClass()
        {
            return 'TestModel';
        }
    };

    // Set properties using setAttribute to avoid PHPStan errors
    $this->model->setAttribute('id', 1);
    $this->model->setAttribute('name', 'Test Model');

    $this->columnName = 'status';
    $this->fromState = 'pending';
    $this->toState = 'processing';
    $this->transitionName = 'process';
    $this->timestamp = now();
    $this->context = null;
    $this->metadata = ['test' => 'data'];

    $this->event = new StateTransitioned(
        model: $this->model,
        columnName: $this->columnName,
        fromState: $this->fromState,
        toState: $this->toState,
        transitionName: $this->transitionName,
        timestamp: $this->timestamp,
        context: $this->context,
        metadata: $this->metadata
    );

    // Mock config repository (matches pattern from FsmLoggerEdgeCasesTest)
    $this->config = $this->createMock(ConfigRepository::class);
});

it('constructs with config repository', function () {
    $this->config->method('get')->willReturn(true);

    $listener = new PersistStateTransitionedEvent($this->config);

    expect($listener)->toBeInstanceOf(PersistStateTransitionedEvent::class);
});

it('handles event when logging is enabled', function () {
    $this->config->expects($this->once())
        ->method('get')
        ->with('fsm.event_logging.enabled', true)
        ->willReturn(true);

    $listener = new PersistStateTransitionedEvent($this->config);

    // Should not throw an exception
    expect(fn () => $listener->handle($this->event))->not->toThrow(Exception::class);

    // Verify record was created (matches pattern from FsmReplayServiceTest)
    $this->assertDatabaseHas('fsm_event_logs', [
        'model_id' => (string) $this->model->getKey(),
        'model_type' => $this->model->getMorphClass(),
        'column_name' => $this->columnName,
        'from_state' => $this->fromState,
        'to_state' => $this->toState,
        'transition_name' => $this->transitionName,
    ]);
});

it('skips handling when logging is disabled', function () {
    $this->config->expects($this->once())
        ->method('get')
        ->with('fsm.event_logging.enabled', true)
        ->willReturn(false);

    // When logging is disabled, the listener should not throw exceptions
    $listener = new PersistStateTransitionedEvent($this->config);
    expect(fn () => $listener->handle($this->event))->not->toThrow(Exception::class);

    // Verify no record was created
    $this->assertDatabaseMissing('fsm_event_logs', [
        'model_id' => (string) $this->model->getKey(),
    ]);
});

it('handles database exceptions gracefully', function () {
    // Drop table to force database exception
    $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('fsm_event_logs');

    $this->config->expects($this->once())
        ->method('get')
        ->with('fsm.event_logging.enabled', true)
        ->willReturn(true);

    // Test that database exceptions are caught and don't bubble up
    $listener = new PersistStateTransitionedEvent($this->config);
    expect(fn () => $listener->handle($this->event))->not->toThrow(Exception::class);
});

it('handles model with context data', function () {
    $context = $this->createMock(\YorCreative\LaravelArgonautDTO\ArgonautDTOContract::class);
    $context->expects($this->once())
        ->method('toArray')
        ->willReturn(['user_id' => 123, 'reason' => 'test']);

    $eventWithContext = new StateTransitioned(
        model: $this->model,
        columnName: $this->columnName,
        fromState: $this->fromState,
        toState: $this->toState,
        transitionName: $this->transitionName,
        timestamp: $this->timestamp,
        context: $context,
        metadata: $this->metadata
    );

    $this->config->expects($this->once())
        ->method('get')
        ->with('fsm.event_logging.enabled', true)
        ->willReturn(true);

    $listener = new PersistStateTransitionedEvent($this->config);
    expect(fn () => $listener->handle($eventWithContext))->not->toThrow(Exception::class);

    // Verify record was created with context
    $this->assertDatabaseHas('fsm_event_logs', [
        'model_id' => (string) $this->model->getKey(),
        'model_type' => $this->model->getMorphClass(),
        'column_name' => $this->columnName,
    ]);
});

it('handles null context correctly', function () {
    $this->config->expects($this->once())
        ->method('get')
        ->with('fsm.event_logging.enabled', true)
        ->willReturn(true);

    $listener = new PersistStateTransitionedEvent($this->config);
    expect(fn () => $listener->handle($this->event))->not->toThrow(Exception::class);

    // Verify record was created
    $this->assertDatabaseHas('fsm_event_logs', [
        'model_id' => (string) $this->model->getKey(),
        'model_type' => $this->model->getMorphClass(),
    ]);
});

it('uses default config value when not set', function () {
    $this->config->expects($this->once())
        ->method('get')
        ->with('fsm.event_logging.enabled', true)
        ->willReturn(true); // Default value

    $listener = new PersistStateTransitionedEvent($this->config);
    expect(fn () => $listener->handle($this->event))->not->toThrow(Exception::class);

    // Verify record was created
    $this->assertDatabaseHas('fsm_event_logs', [
        'model_id' => (string) $this->model->getKey(),
    ]);
});

it('handles enum states correctly', function () {
    // Create proper enum classes for testing
    if (! enum_exists('TestPendingState')) {
        eval('enum TestPendingState: string implements \Fsm\Contracts\FsmStateEnum {
            case PENDING = "pending";

            public function displayName(): string {
                return "Pending";
            }

            public function icon(): string {
                return "pending-icon";
            }
        }');
    }

    if (! enum_exists('TestProcessingState')) {
        eval('enum TestProcessingState: string implements \Fsm\Contracts\FsmStateEnum {
            case PROCESSING = "processing";

            public function displayName(): string {
                return "Processing";
            }

            public function icon(): string {
                return "processing-icon";
            }
        }');
    }

    $eventWithEnums = new StateTransitioned(
        model: $this->model,
        columnName: $this->columnName,
        fromState: TestPendingState::PENDING->value,
        toState: TestProcessingState::PROCESSING->value,
        transitionName: $this->transitionName,
        timestamp: $this->timestamp,
        context: $this->context,
        metadata: $this->metadata
    );

    $this->config->expects($this->once())
        ->method('get')
        ->with('fsm.event_logging.enabled', true)
        ->willReturn(true);

    $listener = new PersistStateTransitionedEvent($this->config);
    expect(fn () => $listener->handle($eventWithEnums))->not->toThrow(Exception::class);

    // Verify record was created
    $this->assertDatabaseHas('fsm_event_logs', [
        'model_id' => (string) $this->model->getKey(),
        'from_state' => TestPendingState::PENDING->value,
        'to_state' => TestProcessingState::PROCESSING->value,
    ]);
});

it('handles empty metadata correctly', function () {
    $eventWithEmptyMetadata = new StateTransitioned(
        model: $this->model,
        columnName: $this->columnName,
        fromState: $this->fromState,
        toState: $this->toState,
        transitionName: $this->transitionName,
        timestamp: $this->timestamp,
        context: $this->context,
        metadata: []
    );

    $this->config->expects($this->once())
        ->method('get')
        ->with('fsm.event_logging.enabled', true)
        ->willReturn(true);

    $listener = new PersistStateTransitionedEvent($this->config);
    expect(fn () => $listener->handle($eventWithEmptyMetadata))->not->toThrow(Exception::class);

    // Verify record was created
    $this->assertDatabaseHas('fsm_event_logs', [
        'model_id' => (string) $this->model->getKey(),
    ]);
});
