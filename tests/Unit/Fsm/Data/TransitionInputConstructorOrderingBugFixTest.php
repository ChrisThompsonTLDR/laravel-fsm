<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\Dto;
use Fsm\Data\TransitionInput;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * Test for TransitionInput constructor ordering bug fix.
 *
 * Tests the fix where prepareAttributes is now called BEFORE context hydration
 * to prevent double processing and ensure proper snake_case to camelCase normalization.
 */
class TransitionInputConstructorOrderingBugFixTest extends TestCase
{
    /**
     * Test that to_state is properly normalized to toState after the fix.
     * This was the main issue - snake_case keys weren't being normalized correctly
     * because context hydration happened before prepareAttributes.
     */
    public function test_to_state_is_properly_normalized_to_to_state(): void
    {
        $model = $this->createMock(Model::class);

        // Test with snake_case to_state key - this should work after the fix
        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed', // snake_case key
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState); // Should be accessible via camelCase
        $this->assertSame('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
    }

    /**
     * Test that context hydration still works correctly after reordering.
     * This ensures the fix doesn't break existing functionality.
     */
    public function test_context_hydration_still_works_after_reordering(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that extends Dto
        $testDtoClass = new class(['message' => 'test']) extends Dto
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                parent::__construct($data);
            }
        };

        // Test with snake_case keys and context hydration
        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that the fix prevents double processing of context.
     * Before the fix, context was hydrated, then prepareAttributes was called,
     * which could cause issues if prepareAttributes modified the context.
     */
    public function test_fix_prevents_double_processing_of_context(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that tracks how many times it's instantiated
        $instantiationCount = 0;
        $testDtoClass = new class(['message' => 'test']) extends Dto
        {
            public string $message;

            public static int $instantiationCount = 0;

            public function __construct(array $data = [])
            {
                self::$instantiationCount++;
                $this->message = $data['message'] ?? '';
                parent::__construct($data);
            }
        };

        // Reset counter
        $testDtoClass::$instantiationCount = 0;

        // Test with snake_case keys and context hydration
        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);

        // Context should only be instantiated once (not double processed)
        $this->assertSame(1, $testDtoClass::$instantiationCount);
        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that all snake_case keys are properly normalized to camelCase.
     * This ensures the fix works for all properties, not just toState.
     */
    public function test_all_snake_case_keys_are_normalized_to_camel_case(): void
    {
        $model = $this->createMock(Model::class);
        $timestamp = now();

        // Test with all snake_case keys
        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'event' => 'test_event',
            'is_dry_run' => true,
            'mode' => TransitionInput::MODE_DRY_RUN,
            'source' => TransitionInput::SOURCE_API,
            'metadata' => ['key' => 'value'],
            'timestamp' => $timestamp,
        ]);

        // All properties should be accessible via camelCase
        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertTrue($input->isDryRun);
        $this->assertSame(TransitionInput::MODE_DRY_RUN, $input->mode);
        $this->assertSame(TransitionInput::SOURCE_API, $input->source);
        $this->assertSame(['key' => 'value'], $input->metadata);
        $this->assertSame($timestamp, $input->timestamp);
    }

    /**
     * Test that context hydration works with mixed snake_case and camelCase keys.
     * This tests the robustness of the fix.
     */
    public function test_context_hydration_works_with_mixed_key_cases(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that extends Dto
        $testDtoClass = new class(['message' => 'test']) extends Dto
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                parent::__construct($data);
            }
        };

        // Test with mixed key cases - snake_case for main attributes, camelCase for context
        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that context hydration works with non-Dto ArgonautDTOContract.
     * This ensures the fix works with different DTO types.
     */
    public function test_context_hydration_works_with_non_dto_argonaut_dto(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract but doesn't extend Dto
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
            }

            public static function from(array $data): self
            {
                return new self($data);
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

        // Test with snake_case keys and non-Dto ArgonautDTOContract
        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that context hydration works with direct DTO instance.
     * This ensures the fix doesn't break existing functionality.
     */
    public function test_context_hydration_works_with_direct_dto_instance(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO instance directly
        $testDto = new class(['message' => 'test']) extends Dto
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                parent::__construct($data);
            }
        };

        // Test with snake_case keys and direct DTO instance
        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => $testDto,
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test that the fix works with different transition modes.
     * This ensures the fix works across all modes.
     */
    public function test_fix_works_with_different_transition_modes(): void
    {
        $model = $this->createMock(Model::class);

        $modes = [
            TransitionInput::MODE_NORMAL,
            TransitionInput::MODE_DRY_RUN,
            TransitionInput::MODE_FORCE,
            TransitionInput::MODE_SILENT,
        ];

        foreach ($modes as $mode) {
            $input = new TransitionInput([
                'model' => $model,
                'from_state' => 'pending',
                'to_state' => 'completed',
                'event' => 'test_event',
                'is_dry_run' => false,
                'mode' => $mode,
            ]);

            $this->assertSame($model, $input->model);
            $this->assertSame('pending', $input->fromState);
            $this->assertSame('completed', $input->toState);
            $this->assertSame('test_event', $input->event);
            $this->assertFalse($input->isDryRun);
            $this->assertSame($mode, $input->mode);
        }
    }

    /**
     * Test that the fix works with different sources.
     * This ensures the fix works across all sources.
     */
    public function test_fix_works_with_different_sources(): void
    {
        $model = $this->createMock(Model::class);

        $sources = [
            TransitionInput::SOURCE_USER,
            TransitionInput::SOURCE_SYSTEM,
            TransitionInput::SOURCE_API,
            TransitionInput::SOURCE_SCHEDULER,
            TransitionInput::SOURCE_MIGRATION,
        ];

        foreach ($sources as $source) {
            $input = new TransitionInput([
                'model' => $model,
                'from_state' => 'pending',
                'to_state' => 'completed',
                'event' => 'test_event',
                'is_dry_run' => false,
                'mode' => TransitionInput::MODE_NORMAL,
                'source' => $source,
            ]);

            $this->assertSame($model, $input->model);
            $this->assertSame('pending', $input->fromState);
            $this->assertSame('completed', $input->toState);
            $this->assertSame('test_event', $input->event);
            $this->assertFalse($input->isDryRun);
            $this->assertSame($source, $input->source);
        }
    }

    /**
     * Test that the fix works with null context.
     * This ensures the fix doesn't break null context handling.
     */
    public function test_fix_works_with_null_context(): void
    {
        $model = $this->createMock(Model::class);

        // Test with snake_case keys and null context
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
        $this->assertNull($input->context);
    }

    /**
     * Test that the fix works with missing context key.
     * This ensures the fix doesn't break missing context handling.
     */
    public function test_fix_works_with_missing_context_key(): void
    {
        $model = $this->createMock(Model::class);

        // Test with snake_case keys and missing context key
        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertNull($input->context);
    }

    /**
     * Test that the fix works with invalid context data.
     * This ensures the fix doesn't break error handling.
     */
    public function test_fix_works_with_invalid_context_data(): void
    {
        $model = $this->createMock(Model::class);

        // Test with snake_case keys and invalid context data
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class NonExistentClass');

        new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => 'NonExistentClass',
                'payload' => ['message' => 'test'],
            ],
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);
    }

    /**
     * Test that the fix works with complex metadata.
     * This ensures the fix works with complex data structures.
     */
    public function test_fix_works_with_complex_metadata(): void
    {
        $model = $this->createMock(Model::class);

        $complexMetadata = [
            'user_id' => 123,
            'session_id' => 'abc123',
            'nested' => [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
            'array' => [1, 2, 3],
        ];

        // Test with snake_case keys and complex metadata
        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
            'metadata' => $complexMetadata,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertSame($complexMetadata, $input->metadata);
    }

    /**
     * Test that the fix works with positional constructor (not array-based).
     * This ensures the fix doesn't break the positional constructor.
     */
    public function test_fix_does_not_affect_positional_constructor(): void
    {
        $model = $this->createMock(Model::class);

        // Test with positional constructor - should work exactly as before
        $input = new TransitionInput(
            $model,
            'pending',
            'completed',
            null,
            'test_event',
            false,
            TransitionInput::MODE_NORMAL,
            TransitionInput::SOURCE_USER,
            ['key' => 'value'],
            now()
        );

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertSame(TransitionInput::MODE_NORMAL, $input->mode);
        $this->assertSame(TransitionInput::SOURCE_USER, $input->source);
        $this->assertSame(['key' => 'value'], $input->metadata);
        $this->assertNull($input->context);
    }
}
