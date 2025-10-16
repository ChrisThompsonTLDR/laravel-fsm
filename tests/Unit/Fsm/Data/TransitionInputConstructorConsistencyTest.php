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
class TestContextForConsistency extends Dto
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
class TestContextNoFromMethodForConsistency implements ArgonautDTOContract
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
 * Test for TransitionInput constructor consistency.
 *
 * Tests that the constructor handles nullable toState property correctly
 * and that context assignment works properly for both array and positional construction.
 */
class TransitionInputConstructorConsistencyTest extends TestCase
{
    public function test_to_state_property_type_declaration_matches_runtime_behavior(): void
    {
        $model = $this->createMock(Model::class);

        // Test that toState can be null for non-normal modes
        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: null,
            context: null,
            event: 'test_event',
            isDryRun: false,
            mode: TransitionInput::MODE_DRY_RUN
        );

        $this->assertNull($input->toState);

        // Test that toState must be non-null for normal mode
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TransitionInput requires a non-null "toState" or "to_state" value for normal mode transitions.');

        new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: null,
            context: null,
            event: 'test_event',
            isDryRun: false,
            mode: TransitionInput::MODE_NORMAL
        );
    }

    public function test_array_construction_context_assignment_before_parent_constructor(): void
    {
        $model = $this->createMock(Model::class);
        $context = new TestContextForConsistency('test message', 42);

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

    public function test_array_construction_context_hydration_before_parent_constructor(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => TestContextForConsistency::class,
                'payload' => ['message' => 'hydrated message', 'count' => 100],
            ],
            'event' => 'test_event',
            'isDryRun' => false,
        ]);

        // Verify context was properly hydrated and not overwritten
        $this->assertInstanceOf(TestContextForConsistency::class, $input->context);
        $this->assertSame('hydrated message', $input->context->message);
        $this->assertSame(100, $input->context->count);
    }

    public function test_positional_construction_context_assignment_before_parent_constructor(): void
    {
        $model = $this->createMock(Model::class);
        $context = new TestContextForConsistency('test message', 42);

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

    public function test_positional_construction_context_hydration_before_parent_constructor(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => TestContextForConsistency::class,
                'payload' => ['message' => 'hydrated message', 'count' => 100],
            ],
            event: 'test_event',
            isDryRun: false
        );

        // Verify context was properly hydrated and not overwritten
        $this->assertInstanceOf(TestContextForConsistency::class, $input->context);
        $this->assertSame('hydrated message', $input->context->message);
        $this->assertSame(100, $input->context->count);
    }

    public function test_array_construction_to_state_validation_consistency(): void
    {
        $model = $this->createMock(Model::class);

        // Test that toState can be null for non-normal modes in array construction
        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => null,
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => false,
            'mode' => TransitionInput::MODE_DRY_RUN,
        ]);

        $this->assertNull($input->toState);

        // Test that toState must be non-null for normal mode in array construction
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TransitionInput requires a non-null "toState" or "to_state" value for normal mode transitions.');

        new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => null,
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);
    }

    public function test_array_construction_to_state_validation_with_snake_case(): void
    {
        $model = $this->createMock(Model::class);

        // Test that to_state can be null for non-normal modes in array construction
        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => null,
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => false,
            'mode' => TransitionInput::MODE_FORCE,
        ]);

        $this->assertNull($input->toState);

        // Test that to_state must be non-null for normal mode in array construction
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TransitionInput requires a non-null "toState" or "to_state" value for normal mode transitions.');

        new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => null,
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);
    }

    public function test_array_construction_to_state_validation_when_mode_missing(): void
    {
        $model = $this->createMock(Model::class);

        // Test that missing mode defaults to normal and requires non-null toState
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TransitionInput requires a non-null "toState" or "to_state" value for normal mode transitions.');

        new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => null,
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => false,
            // mode is missing, defaults to MODE_NORMAL
        ]);
    }

    public function test_context_hydration_fallback_works_correctly(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to instantiate DTO class '.TestContextNoFromMethodForConsistency::class);

        new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => TestContextNoFromMethodForConsistency::class,
                'payload' => ['message' => 'fallback message', 'count' => 200],
            ],
            'event' => 'test_event',
            'isDryRun' => false,
        ]);
    }

    public function test_context_hydration_with_invalid_class_throws_exception(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class NonExistentClass');

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
        $this->expectExceptionMessage('Context hydration failed for class '.TestContextForConsistency::class);

        new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => TestContextForConsistency::class,
                'payload' => 'invalid_payload',
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
        $context = new TestContextForConsistency('serialization test', 300);

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
        $this->assertSame(TestContextForConsistency::class, $payload['class']);
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

    public function test_all_properties_are_set_correctly_without_reassignment(): void
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

    public function test_array_construction_with_snake_case_keys(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => null,
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
    }

    public function test_mode_constants_are_correctly_defined(): void
    {
        $this->assertSame('normal', TransitionInput::MODE_NORMAL);
        $this->assertSame('dry_run', TransitionInput::MODE_DRY_RUN);
        $this->assertSame('force', TransitionInput::MODE_FORCE);
        $this->assertSame('silent', TransitionInput::MODE_SILENT);
    }

    public function test_source_constants_are_correctly_defined(): void
    {
        $this->assertSame('user', TransitionInput::SOURCE_USER);
        $this->assertSame('system', TransitionInput::SOURCE_SYSTEM);
        $this->assertSame('api', TransitionInput::SOURCE_API);
        $this->assertSame('scheduler', TransitionInput::SOURCE_SCHEDULER);
        $this->assertSame('migration', TransitionInput::SOURCE_MIGRATION);
    }

    public function test_helper_methods_work_correctly(): void
    {
        $model = $this->createMock(Model::class);

        // Test isDryRun method
        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null,
            event: 'test_event',
            isDryRun: true
        );
        $this->assertTrue($input->isDryRun());

        // Test isForced method
        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null,
            event: 'test_event',
            isDryRun: false,
            mode: TransitionInput::MODE_FORCE
        );
        $this->assertTrue($input->isForced());

        // Test isSilent method
        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null,
            event: 'test_event',
            isDryRun: false,
            mode: TransitionInput::MODE_SILENT
        );
        $this->assertTrue($input->isSilent());

        // Test getSource method
        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null,
            event: 'test_event',
            isDryRun: false,
            source: TransitionInput::SOURCE_API
        );
        $this->assertSame(TransitionInput::SOURCE_API, $input->getSource());

        // Test getMetadata method
        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null,
            event: 'test_event',
            isDryRun: false,
            metadata: ['key1' => 'value1', 'key2' => 'value2']
        );
        $this->assertSame('value1', $input->getMetadata('key1'));
        $this->assertSame('default', $input->getMetadata('nonexistent', 'default'));
        $this->assertTrue($input->hasMetadata('key1'));
        $this->assertFalse($input->hasMetadata('nonexistent'));

        // Test getTimestamp method
        $timestamp = new \DateTimeImmutable('2024-01-01 12:00:00');
        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null,
            event: 'test_event',
            isDryRun: false,
            timestamp: $timestamp
        );
        $this->assertSame($timestamp, $input->getTimestamp());

        // Test getTimestamp method with null timestamp (should return now())
        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null,
            event: 'test_event',
            isDryRun: false
        );
        $this->assertInstanceOf(\DateTimeInterface::class, $input->getTimestamp());
    }
}
