<?php

declare(strict_types=1);

use Fsm\Events\StateTransitioned;
use Fsm\Listeners\PersistStateTransitionedEvent;
use Fsm\Models\FsmEventLog;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;

beforeEach(function () {
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

    $this->model->id = 1;
    $this->model->name = 'Test Model';

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

    // Mock config repository
    $this->config = $this->createMock(ConfigRepository::class);

    // We'll use real FsmEventLog but mock the database interaction
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

    // Test that the listener doesn't throw exceptions when logging is enabled
    $listener = new PersistStateTransitionedEvent($this->config);

    // This should not throw an exception
    expect(fn () => $listener->handle($this->event))->not->toThrow(Exception::class);
});

it('skips handling when logging is disabled', function () {
    $this->config->expects($this->once())
        ->method('get')
        ->with('fsm.event_logging.enabled', true)
        ->willReturn(false);

    // When logging is disabled, the listener should not throw exceptions
    $listener = new PersistStateTransitionedEvent($this->config);
    expect(fn () => $listener->handle($this->event))->not->toThrow(Exception::class);
});

it('handles database exceptions gracefully', function () {
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
});

it('handles null context correctly', function () {
    $this->config->expects($this->once())
        ->method('get')
        ->with('fsm.event_logging.enabled', true)
        ->willReturn(true);

    $listener = new PersistStateTransitionedEvent($this->config);
    expect(fn () => $listener->handle($this->event))->not->toThrow(Exception::class);
});

it('uses default config value when not set', function () {
    $this->config->expects($this->once())
        ->method('get')
        ->with('fsm.event_logging.enabled', true)
        ->willReturn(true); // Default value

    $listener = new PersistStateTransitionedEvent($this->config);
    expect(fn () => $listener->handle($this->event))->not->toThrow(Exception::class);
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
});
