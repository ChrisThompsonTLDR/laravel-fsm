<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\Dto;
use Fsm\Data\TransitionInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * Test DTO that tracks hydration calls to detect double processing
 */
class HydrationTrackingContext extends Dto
{
    public string $message;

    public int $count;

    public int $hydrationCalls = 0;

    /**
     * @param  array<string, mixed>|string  $message
     */
    public function __construct(string|array $message, int $count = 0)
    {
        $this->hydrationCalls++;

        if (is_array($message) && func_num_args() === 1 && static::isAssociative($message)) {
            parent::__construct($message);

            return;
        }

        parent::__construct(['message' => $message, 'count' => $count]);
    }
}

/**
 * Test DTO that fails if hydrated multiple times
 */
class SingleHydrationContext extends Dto
{
    public string $message;

    public int $count;

    private bool $hydrated = false;

    /**
     * @param  array<string, mixed>|string  $message
     */
    public function __construct(string|array $message, int $count = 0)
    {
        if ($this->hydrated) {
            throw new \RuntimeException('Context was hydrated multiple times!');
        }
        $this->hydrated = true;

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
class NoFromMethodContext implements ArgonautDTOContract
{
    public string $message;

    public int $count;

    private bool $hydrated = false;

    public function __construct(string|array $message, int $count = 0)
    {
        if ($this->hydrated) {
            throw new \RuntimeException('Context was hydrated multiple times!');
        }
        $this->hydrated = true;

        if (is_array($message)) {
            $this->message = $message['message'] ?? '';
            $this->count = $message['count'] ?? 0;
        } else {
            $this->message = $message;
            $this->count = $count;
        }
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
 * Test for TransitionInput double hydration bug fix.
 *
 * This test specifically verifies that the context parameter is not hydrated twice
 * in the array-based constructor, which was causing the initial hydration result
 * to be discarded and potentially causing issues with the DTO's casting system.
 */
class TransitionInputDoubleHydrationBugTest extends TestCase
{
    public function test_array_construction_does_not_hydrate_context_twice_with_dto_extending_dto(): void
    {
        $model = $this->createMock(Model::class);

        // Test with DTO that extends Dto (has from() method)
        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => HydrationTrackingContext::class,
                'payload' => ['message' => 'test message', 'count' => 42],
            ],
            'event' => 'test_event',
            'isDryRun' => false,
        ]);

        // Verify context was hydrated exactly once
        $this->assertInstanceOf(HydrationTrackingContext::class, $input->context);
        $this->assertSame('test message', $input->context->message);
        $this->assertSame(42, $input->context->count);
        $this->assertSame(1, $input->context->hydrationCalls, 'Context should be hydrated exactly once');
    }

    public function test_array_construction_does_not_hydrate_context_twice_with_no_from_method(): void
    {
        $model = $this->createMock(Model::class);

        // Test with DTO that implements ArgonautDTOContract but has no from() method
        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => NoFromMethodContext::class,
                'payload' => ['message' => 'no from method', 'count' => 100],
            ],
            'event' => 'test_event',
            'isDryRun' => false,
        ]);

        // Verify context was hydrated exactly once (no exception thrown)
        $this->assertInstanceOf(NoFromMethodContext::class, $input->context);
        $this->assertSame('no from method', $input->context->message);
        $this->assertSame(100, $input->context->count);
    }

    public function test_array_construction_with_single_hydration_context_does_not_fail(): void
    {
        $model = $this->createMock(Model::class);

        // Test with DTO that throws exception if hydrated multiple times
        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => SingleHydrationContext::class,
                'payload' => ['message' => 'single hydration', 'count' => 200],
            ],
            'event' => 'test_event',
            'isDryRun' => false,
        ]);

        // Verify context was hydrated exactly once (no exception thrown)
        $this->assertInstanceOf(SingleHydrationContext::class, $input->context);
        $this->assertSame('single hydration', $input->context->message);
        $this->assertSame(200, $input->context->count);
    }

    public function test_array_construction_with_already_instantiated_context_preserves_instance(): void
    {
        $model = $this->createMock(Model::class);
        $originalContext = new HydrationTrackingContext('already instantiated', 300);
        $originalHydrationCalls = $originalContext->hydrationCalls;

        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => $originalContext,
            'event' => 'test_event',
            'isDryRun' => false,
        ]);

        // Verify the original context instance is preserved (not re-hydrated)
        $this->assertSame($originalContext, $input->context);
        $this->assertSame('already instantiated', $input->context->message);
        $this->assertSame(300, $input->context->count);
        $this->assertSame($originalHydrationCalls, $input->context->hydrationCalls, 'Original context should not be re-hydrated');
    }

    public function test_array_construction_with_null_context_handles_correctly(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => false,
        ]);

        // Verify null context is handled correctly
        $this->assertNull($input->context);
    }

    public function test_array_construction_with_snake_case_context_key(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => HydrationTrackingContext::class,
                'payload' => ['message' => 'snake case test', 'count' => 400],
            ],
            'event' => 'test_event',
            'is_dry_run' => false,
        ]);

        // Verify context was hydrated exactly once with snake_case keys
        $this->assertInstanceOf(HydrationTrackingContext::class, $input->context);
        $this->assertSame('snake case test', $input->context->message);
        $this->assertSame(400, $input->context->count);
        $this->assertSame(1, $input->context->hydrationCalls, 'Context should be hydrated exactly once');
    }

    public function test_array_construction_context_hydration_order_is_correct(): void
    {
        $model = $this->createMock(Model::class);

        // Create a context that logs when it's constructed
        $contextPayload = [
            'class' => HydrationTrackingContext::class,
            'payload' => ['message' => 'order test', 'count' => 500],
        ];

        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => $contextPayload,
            'event' => 'test_event',
            'isDryRun' => false,
        ]);

        // Verify the context was created correctly
        $this->assertInstanceOf(HydrationTrackingContext::class, $input->context);
        $this->assertSame('order test', $input->context->message);
        $this->assertSame(500, $input->context->count);
        $this->assertSame(1, $input->context->hydrationCalls, 'Context should be hydrated exactly once');
    }

    public function test_positional_construction_does_not_hydrate_context_twice(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => HydrationTrackingContext::class,
                'payload' => ['message' => 'positional test', 'count' => 600],
            ],
            event: 'test_event',
            isDryRun: false
        );

        // Verify context was hydrated exactly once in positional construction
        $this->assertInstanceOf(HydrationTrackingContext::class, $input->context);
        $this->assertSame('positional test', $input->context->message);
        $this->assertSame(600, $input->context->count);
        $this->assertSame(1, $input->context->hydrationCalls, 'Context should be hydrated exactly once');
    }

    public function test_context_hydration_failure_does_not_cause_double_processing(): void
    {
        Log::spy();
        $model = $this->createMock(Model::class);

        // Test with invalid class name
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

        // Verify error was logged
        Log::shouldHaveReceived('error')
            ->once()
            ->with('[FSM] Context hydration failed: class does not exist', \Mockery::any());
    }

    public function test_context_hydration_with_invalid_payload_does_not_cause_double_processing(): void
    {
        Log::spy();
        $model = $this->createMock(Model::class);

        // Test with invalid payload
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class '.HydrationTrackingContext::class);

        new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => HydrationTrackingContext::class,
                'payload' => 'not an array', // Invalid payload
            ],
            'event' => 'test_event',
            'isDryRun' => false,
        ]);

        // Verify error was logged
        Log::shouldHaveReceived('error')
            ->once()
            ->with(\Mockery::pattern('/Context hydration failed/'), \Mockery::any());
    }

    public function test_context_serialization_round_trip_works_correctly(): void
    {
        $model = $this->createMock(Model::class);
        $originalContext = new HydrationTrackingContext('round trip test', 700);

        // Create input with original context
        $input1 = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $originalContext,
            event: 'test_event',
            isDryRun: false
        );

        // Serialize context
        $payload = $input1->contextPayload();
        $this->assertIsArray($payload);
        $this->assertSame(HydrationTrackingContext::class, $payload['class']);

        // Deserialize context using array construction
        $input2 = new TransitionInput([
            'model' => $model,
            'fromState' => 'completed',
            'toState' => 'done',
            'context' => $payload,
            'event' => 'test_event',
            'isDryRun' => false,
        ]);

        // Verify deserialized context works correctly
        $this->assertInstanceOf(HydrationTrackingContext::class, $input2->context);
        $this->assertSame('round trip test', $input2->context->message);
        $this->assertSame(700, $input2->context->count);
        $this->assertSame(1, $input2->context->hydrationCalls, 'Deserialized context should be hydrated exactly once');
    }
}
