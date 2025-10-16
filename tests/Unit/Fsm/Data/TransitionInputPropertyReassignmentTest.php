<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\Dto;
use Fsm\Data\TransitionInput;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * Test DTO that extends Dto (has from() method)
 */
class TestContextWithDtoForReassignment extends Dto
{
    public string $message;

    public int $count;

    /**
     * @param  array<string, mixed>|string  $message
     */
    public function __construct(string|array $message, int $count = 0)
    {
        if (is_array($message) && func_num_args() === 1 && static::isAssociative($message)) {
            parent::__construct($message);

            return;
        }

        parent::__construct(['message' => $message, 'count' => $count]);
    }
}

/**
 * Test DTO that implements ArgonautDTOContract but has no from() method
 */
class TestContextNoFromMethodForReassignment implements ArgonautDTOContract
{
    public string $message;

    public int $count;

    public function __construct(string $message, int $count = 0)
    {
        $this->message = $message;
        $this->count = $count;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(int $depth = 3): array
    {
        return [
            'message' => $this->message,
            'count' => $this->count,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}

/**
 * Test for TransitionInput property re-assignment bug fix.
 *
 * Tests that properties are not re-assigned after parent constructor call,
 * preventing inconsistencies and overwriting values.
 */
class TransitionInputPropertyReassignmentTest extends TestCase
{
    public function test_context_property_not_reassigned_after_parent_constructor_with_positional_params(): void
    {
        $model = $this->createMock(Model::class);
        $context = new TestContextWithDtoForReassignment('test message', 42);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $context,
            event: 'test_event',
            isDryRun: false
        );

        // Verify context is properly set and not overwritten
        $this->assertSame($context, $input->context);
        $this->assertSame('test message', $input->context->message);
        $this->assertSame(42, $input->context->count);
    }

    public function test_context_property_not_reassigned_after_parent_constructor_with_array_params(): void
    {
        $model = $this->createMock(Model::class);
        $context = new TestContextWithDtoForReassignment('test message', 42);

        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => $context,
            'event' => 'test_event',
            'isDryRun' => false,
        ]);

        // Verify context is properly set and not overwritten
        $this->assertSame($context, $input->context);
        $this->assertSame('test message', $input->context->message);
        $this->assertSame(42, $input->context->count);
    }

    public function test_context_hydration_works_correctly_with_array_context(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => TestContextWithDtoForReassignment::class,
                'payload' => ['message' => 'hydrated message', 'count' => 100],
            ],
            'event' => 'test_event',
            'isDryRun' => false,
        ]);

        // Verify context was properly hydrated and not overwritten
        $this->assertInstanceOf(TestContextWithDtoForReassignment::class, $input->context);
        $this->assertSame('hydrated message', $input->context->message);
        $this->assertSame(100, $input->context->count);
    }

    public function test_context_hydration_works_correctly_with_positional_array_context(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => TestContextWithDtoForReassignment::class,
                'payload' => ['message' => 'hydrated message', 'count' => 100],
            ],
            event: 'test_event',
            isDryRun: false
        );

        // Verify context was properly hydrated and not overwritten
        $this->assertInstanceOf(TestContextWithDtoForReassignment::class, $input->context);
        $this->assertSame('hydrated message', $input->context->message);
        $this->assertSame(100, $input->context->count);
    }

    public function test_context_hydration_fallback_works_with_no_from_method(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to instantiate DTO class '.TestContextNoFromMethodForReassignment::class);

        new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => TestContextNoFromMethodForReassignment::class,
                'payload' => ['message' => 'fallback message', 'count' => 200],
            ],
            'event' => 'test_event',
            'isDryRun' => false,
        ]);
    }

    public function test_null_context_is_handled_correctly(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null,
            event: 'test_event',
            isDryRun: false
        );

        // Verify null context is properly handled
        $this->assertNull($input->context);
    }

    public function test_context_payload_serialization_works_correctly(): void
    {
        $model = $this->createMock(Model::class);
        $context = new TestContextWithDtoForReassignment('serialization test', 300);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $context,
            event: 'test_event',
            isDryRun: false
        );

        $payload = $input->contextPayload();

        // Verify context payload serialization works
        $this->assertIsArray($payload);
        $this->assertSame(TestContextWithDtoForReassignment::class, $payload['class']);
        $this->assertSame('serialization test', $payload['payload']['message']);
        $this->assertSame(300, $payload['payload']['count']);
    }

    public function test_context_payload_returns_null_for_null_context(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null,
            event: 'test_event',
            isDryRun: false
        );

        $payload = $input->contextPayload();

        // Verify null context returns null payload
        $this->assertNull($payload);
    }

    public function test_all_other_properties_are_set_correctly_without_reassignment(): void
    {
        $model = $this->createMock(Model::class);
        $timestamp = new \DateTimeImmutable('2024-01-01 12:00:00');

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null,
            event: 'test_event',
            isDryRun: true,
            mode: TransitionInput::MODE_FORCE,
            source: TransitionInput::SOURCE_API,
            metadata: ['key1' => 'value1', 'key2' => 'value2'],
            timestamp: $timestamp
        );

        // Verify all properties are set correctly
        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertTrue($input->isDryRun);
        $this->assertSame(TransitionInput::MODE_FORCE, $input->mode);
        $this->assertSame(TransitionInput::SOURCE_API, $input->source);
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $input->metadata);
        $this->assertSame($timestamp, $input->timestamp);
    }

    public function test_array_construction_with_all_properties_set_correctly(): void
    {
        $model = $this->createMock(Model::class);
        $timestamp = new \DateTimeImmutable('2024-01-01 12:00:00');

        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => true,
            'mode' => TransitionInput::MODE_SILENT,
            'source' => TransitionInput::SOURCE_SCHEDULER,
            'metadata' => ['key1' => 'value1', 'key2' => 'value2'],
            'timestamp' => $timestamp,
        ]);

        // Verify all properties are set correctly
        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertTrue($input->isDryRun);
        $this->assertSame(TransitionInput::MODE_SILENT, $input->mode);
        $this->assertSame(TransitionInput::SOURCE_SCHEDULER, $input->source);
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $input->metadata);
        $this->assertSame($timestamp, $input->timestamp);
    }

    public function test_context_hydration_with_invalid_class_throws_exception(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class NonExistentClass: class does not exist');

        new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => 'NonExistentClass',
                'payload' => ['message' => 'test'],
            ],
            'event' => 'test_event',
            'isDryRun' => false,
        ]);
    }

    public function test_context_hydration_with_invalid_payload_throws_exception(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class '.TestContextWithDtoForReassignment::class);

        new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => TestContextWithDtoForReassignment::class,
                'payload' => 'invalid_payload',
            ],
            'event' => 'test_event',
            'isDryRun' => false,
        ]);
    }
}
