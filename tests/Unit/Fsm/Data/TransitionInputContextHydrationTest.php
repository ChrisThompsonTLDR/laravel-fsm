<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\Dto;
use Fsm\Data\TransitionInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use YorCreative\LaravelArgonautDTO\ArgonautDTO;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

// Test DTO that extends Dto (has from() method)
class TestContextWithDto extends Dto
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

// Test DTO that extends ArgonautDTO directly (has from() via parent)
class TestContextWithArgonautDTO extends ArgonautDTO implements ArgonautDTOContract
{
    public string $message;

    public int $count;

    /**
     * @param  array<string, mixed>|string  $message
     */
    public function __construct(string|array $message, int $count = 0)
    {
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
}

// Test DTO that implements ArgonautDTOContract but has no from() method
class TestContextNoFromMethod implements ArgonautDTOContract
{
    public string $message;

    public int $count;

    /**
     * @param  array<string, mixed>|string  $message
     */
    public function __construct(string|array $message, int $count = 0)
    {
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

// Test Model
class TestContextModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;
}

class TransitionInputContextHydrationTest extends TestCase
{
    public function test_context_hydration_with_dto_extending_dto_class(): void
    {
        Log::spy();

        $model = new TestContextModel(['id' => 1]);
        $contextPayload = [
            'class' => TestContextWithDto::class,
            'payload' => ['message' => 'test', 'count' => 5],
        ];

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $contextPayload
        );

        $this->assertInstanceOf(TestContextWithDto::class, $input->context);
        $this->assertSame('test', $input->context->message);
        $this->assertSame(5, $input->context->count);

        // No warnings should be logged
        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('error');
    }

    public function test_context_hydration_with_dto_extending_argonaut_dto(): void
    {
        Log::spy();

        $model = new TestContextModel(['id' => 1]);
        $contextPayload = [
            'class' => TestContextWithArgonautDTO::class,
            'payload' => ['message' => 'argonaut test', 'count' => 10],
        ];

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $contextPayload
        );

        $this->assertInstanceOf(TestContextWithArgonautDTO::class, $input->context);
        $this->assertSame('argonaut test', $input->context->message);
        $this->assertSame(10, $input->context->count);

        // No warnings should be logged
        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('error');
    }

    public function test_context_hydration_with_no_from_method_uses_direct_instantiation(): void
    {
        Log::spy();

        $model = new TestContextModel(['id' => 1]);
        $contextPayload = [
            'class' => TestContextNoFromMethod::class,
            'payload' => ['message' => 'direct instantiation', 'count' => 15],
        ];

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $contextPayload
        );

        // Should successfully instantiate via direct constructor call
        $this->assertInstanceOf(TestContextNoFromMethod::class, $input->context);
        $this->assertSame('direct instantiation', $input->context->message);
        $this->assertSame(15, $input->context->count);

        // No warnings should be logged since direct instantiation succeeded
        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('error');
    }

    public function test_context_hydration_with_already_instantiated_context(): void
    {
        Log::spy();

        $model = new TestContextModel(['id' => 1]);
        $contextInstance = new TestContextWithDto('already instantiated', 20);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $contextInstance
        );

        $this->assertSame($contextInstance, $input->context);
        $this->assertSame('already instantiated', $input->context->message);
        $this->assertSame(20, $input->context->count);

        // No logs should be generated
        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('error');
    }

    public function test_context_hydration_with_null_context(): void
    {
        Log::spy();

        $model = new TestContextModel(['id' => 1]);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null
        );

        $this->assertNull($input->context);

        // No logs should be generated
        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('error');
    }

    public function test_context_hydration_with_invalid_class_name(): void
    {
        Log::spy();

        $model = new TestContextModel(['id' => 1]);
        $contextPayload = [
            'class' => 'NonExistentClass',
            'payload' => ['message' => 'test'],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class NonExistentClass: class does not exist');

        new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $contextPayload
        );

        // Should log an error about missing class
        Log::shouldHaveReceived('error')
            ->once()
            ->with('[FSM] Context hydration failed: class does not exist', \Mockery::any());
    }

    public function test_context_hydration_with_class_not_implementing_contract(): void
    {
        Log::spy();

        $model = new TestContextModel(['id' => 1]);
        $contextPayload = [
            'class' => \stdClass::class,
            'payload' => ['message' => 'test'],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class stdClass: class does not implement ArgonautDTOContract');

        new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $contextPayload
        );

        // Should log an error about not implementing ArgonautDTOContract
        Log::shouldHaveReceived('error')
            ->once()
            ->with('[FSM] Context hydration failed: class does not implement ArgonautDTOContract', \Mockery::any());
    }

    public function test_context_hydration_with_invalid_payload(): void
    {
        Log::spy();

        $model = new TestContextModel(['id' => 1]);
        $contextPayload = [
            'class' => TestContextWithDto::class,
            'payload' => 'not an array', // Invalid: should be an array
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class '.TestContextWithDto::class);

        new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $contextPayload
        );

        // Should log an error about invalid payload
        Log::shouldHaveReceived('error')
            ->once()
            ->with(\Mockery::pattern('/Context hydration failed/'), \Mockery::any());
    }

    public function test_context_payload_serialization(): void
    {
        $model = new TestContextModel(['id' => 1]);
        $contextInstance = new TestContextWithDto('serialization test', 25);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $contextInstance
        );

        $payload = $input->contextPayload();

        $this->assertIsArray($payload);
        $this->assertSame(TestContextWithDto::class, $payload['class']);
        $this->assertIsArray($payload['payload']);
        $this->assertSame('serialization test', $payload['payload']['message']);
        $this->assertSame(25, $payload['payload']['count']);
    }

    public function test_context_payload_serialization_with_null_context(): void
    {
        $model = new TestContextModel(['id' => 1]);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null
        );

        $this->assertNull($input->contextPayload());
    }

    public function test_context_hydration_round_trip(): void
    {
        // Test that we can serialize and deserialize context
        $model = new TestContextModel(['id' => 1]);
        $originalContext = new TestContextNoFromMethod('round trip', 30);

        $input1 = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $originalContext
        );

        // Serialize
        $payload = $input1->contextPayload();

        // Deserialize
        $input2 = new TransitionInput(
            model: $model,
            fromState: 'completed',
            toState: 'done',
            context: $payload
        );

        $this->assertInstanceOf(TestContextNoFromMethod::class, $input2->context);
        $this->assertSame('round trip', $input2->context->message);
        $this->assertSame(30, $input2->context->count);
    }
}
