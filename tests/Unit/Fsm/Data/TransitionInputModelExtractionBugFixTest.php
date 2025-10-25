<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionInput;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Fsm\Data\TestContextData;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\FsmTestCase;

/**
 * Test that TransitionInput correctly extracts the model from array-based construction.
 * This fixes the bug where the model was being ignored when passed in an array.
 */
class TransitionInputModelExtractionBugFixTest extends FsmTestCase
{
    #[Test]
    public function it_correctly_extracts_model_from_array_construction(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('test message', 123);

        $data = [
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => TestContextData::class,
                'payload' => $context->toArray(),
            ],
            'event' => 'test_event',
            'isDryRun' => false,
            'mode' => TransitionInput::MODE_NORMAL,
            'source' => TransitionInput::SOURCE_USER,
            'metadata' => ['key' => 'value'],
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertEquals('pending', $input->fromState);
        $this->assertEquals('completed', $input->toState);
        $this->assertInstanceOf(TestContextData::class, $input->context);
        $this->assertEquals('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertEquals(TransitionInput::MODE_NORMAL, $input->mode);
        $this->assertEquals(TransitionInput::SOURCE_USER, $input->source);
        $this->assertEquals(['key' => 'value'], $input->metadata);
    }

    #[Test]
    public function it_handles_snake_case_keys_correctly(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('snake case test', 456);

        $data = [
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => TestContextData::class,
                'payload' => $context->toArray(),
            ],
            'event' => 'snake_event',
            'is_dry_run' => true,
            'mode' => TransitionInput::MODE_DRY_RUN,
            'source' => TransitionInput::SOURCE_SYSTEM,
            'metadata' => ['snake' => 'case', 'test' => true],
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertEquals('pending', $input->fromState);
        $this->assertEquals('completed', $input->toState);
        $this->assertInstanceOf(TestContextData::class, $input->context);
        $this->assertEquals('snake_event', $input->event);
        $this->assertTrue($input->isDryRun);
        $this->assertEquals(TransitionInput::MODE_DRY_RUN, $input->mode);
        $this->assertEquals(TransitionInput::SOURCE_SYSTEM, $input->source);
        $this->assertEquals(['snake' => 'case', 'test' => true], $input->metadata);
    }

    #[Test]
    public function it_throws_exception_when_model_key_is_missing(): void
    {
        $data = [
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'test_event',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TransitionInput array-based construction requires a "model" key in the attributes array.');

        new TransitionInput($data);
    }

    #[Test]
    public function it_throws_exception_when_model_key_is_null(): void
    {
        $data = [
            'model' => null,
            'fromState' => 'pending',
            'toState' => 'completed',
            'event' => 'test_event',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TransitionInput array-based construction requires a "model" key in the attributes array.');

        new TransitionInput($data);
    }

    #[Test]
    public function it_handles_minimal_data_correctly(): void
    {
        $model = TestModel::factory()->create();

        $data = [
            'model' => $model,
            'toState' => 'completed',
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertNull($input->fromState);
        $this->assertEquals('completed', $input->toState);
        $this->assertNull($input->context);
        $this->assertNull($input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertEquals(TransitionInput::MODE_NORMAL, $input->mode);
        $this->assertEquals(TransitionInput::SOURCE_USER, $input->source);
        $this->assertEquals([], $input->metadata);
    }

    #[Test]
    public function it_handles_dry_run_mode_without_to_state(): void
    {
        $model = TestModel::factory()->create();

        $data = [
            'model' => $model,
            'mode' => TransitionInput::MODE_DRY_RUN,
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertNull($input->toState);
        $this->assertEquals(TransitionInput::MODE_DRY_RUN, $input->mode);
    }

    #[Test]
    public function it_handles_force_mode_without_to_state(): void
    {
        $model = TestModel::factory()->create();

        $data = [
            'model' => $model,
            'mode' => TransitionInput::MODE_FORCE,
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertNull($input->toState);
        $this->assertEquals(TransitionInput::MODE_FORCE, $input->mode);
    }

    #[Test]
    public function it_handles_silent_mode_without_to_state(): void
    {
        $model = TestModel::factory()->create();

        $data = [
            'model' => $model,
            'mode' => TransitionInput::MODE_SILENT,
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertNull($input->toState);
        $this->assertEquals(TransitionInput::MODE_SILENT, $input->mode);
    }

    #[Test]
    public function it_throws_exception_for_normal_mode_without_to_state(): void
    {
        $model = TestModel::factory()->create();

        $data = [
            'model' => $model,
            'mode' => TransitionInput::MODE_NORMAL,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TransitionInput requires a non-null "toState" or "to_state" value for normal mode transitions.');

        new TransitionInput($data);
    }

    #[Test]
    public function it_handles_context_hydration_correctly(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('context test', 789);

        $data = [
            'model' => $model,
            'toState' => 'completed',
            'context' => [
                'class' => TestContextData::class,
                'payload' => $context->toArray(),
            ],
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertInstanceOf(TestContextData::class, $input->context);
        $this->assertEquals('context test', $input->context->message);
        $this->assertEquals(789, $input->context->userId);
    }

    #[Test]
    public function it_handles_null_context_correctly(): void
    {
        $model = TestModel::factory()->create();

        $data = [
            'model' => $model,
            'toState' => 'completed',
            'context' => null,
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertNull($input->context);
    }

    #[Test]
    public function it_handles_timestamp_correctly(): void
    {
        $model = TestModel::factory()->create();
        $timestamp = now();

        $data = [
            'model' => $model,
            'toState' => 'completed',
            'timestamp' => $timestamp,
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertSame($timestamp, $input->timestamp);
    }

    #[Test]
    public function it_handles_enum_states_correctly(): void
    {
        $model = TestModel::factory()->create();

        $data = [
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertEquals('pending', $input->fromState);
        $this->assertEquals('completed', $input->toState);
    }

    #[Test]
    public function it_handles_mixed_snake_case_and_camel_case_keys(): void
    {
        $model = TestModel::factory()->create();
        $context = new TestContextData('mixed case test', 999);

        $data = [
            'model' => $model,
            'from_state' => 'pending',  // snake_case
            'toState' => 'completed',   // camelCase
            'context' => [
                'class' => TestContextData::class,
                'payload' => $context->toArray(),
            ],
            'event' => 'mixed_event',
            'is_dry_run' => true,       // snake_case
            'mode' => TransitionInput::MODE_DRY_RUN,
            'source' => TransitionInput::SOURCE_SYSTEM,
            'metadata' => ['mixed' => 'case', 'test' => true],
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertEquals('pending', $input->fromState);
        $this->assertEquals('completed', $input->toState);
        $this->assertInstanceOf(TestContextData::class, $input->context);
        $this->assertEquals('mixed_event', $input->event);
        $this->assertTrue($input->isDryRun);
        $this->assertEquals(TransitionInput::MODE_DRY_RUN, $input->mode);
        $this->assertEquals(TransitionInput::SOURCE_SYSTEM, $input->source);
        $this->assertEquals(['mixed' => 'case', 'test' => true], $input->metadata);
    }

    #[Test]
    public function it_handles_empty_metadata_correctly(): void
    {
        $model = TestModel::factory()->create();

        $data = [
            'model' => $model,
            'toState' => 'completed',
            'metadata' => [],
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertEquals([], $input->metadata);
    }

    #[Test]
    public function it_handles_complex_metadata_correctly(): void
    {
        $model = TestModel::factory()->create();

        $data = [
            'model' => $model,
            'toState' => 'completed',
            'metadata' => [
                'user_id' => 123,
                'action' => 'transition',
                'nested' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
                'boolean' => true,
                'null_value' => null,
            ],
        ];

        $input = new TransitionInput($data);

        $this->assertSame($model, $input->model);
        $this->assertEquals([
            'user_id' => 123,
            'action' => 'transition',
            'nested' => [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
            'boolean' => true,
            'null_value' => null,
        ], $input->metadata);
    }
}
