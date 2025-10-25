<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\Dto;
use Fsm\Data\TransitionInput;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * Test for TransitionInput context hydration bug fix.
 *
 * Tests the fix where prepareAttributes is now called BEFORE context hydration
 * to prevent double processing and ensure proper snake_case to camelCase normalization.
 * This ensures context hydration works correctly with both snake_case and camelCase keys.
 */
class TransitionInputContextHydrationBugFixTest extends TestCase
{
    /**
     * Test that context hydration works with snake_case keys in array-based constructor.
     * This test verifies the bug fix where prepareAttributes is called before context hydration.
     */
    public function test_array_constructor_hydrates_context_before_key_conversion(): void
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

        // Test with snake_case context keys - this should work after the bug fix
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
     * Test that context hydration works with camelCase keys in array-based constructor.
     * This ensures backward compatibility is maintained.
     */
    public function test_array_constructor_hydrates_context_with_camel_case_keys(): void
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

        // Test with camelCase context keys - this should continue to work
        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ],
            'event' => 'test_event',
            'isDryRun' => false,
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
     * Test that context hydration works with mixed snake_case and camelCase keys.
     * This tests the robustness of the fix.
     */
    public function test_array_constructor_hydrates_context_with_mixed_key_cases(): void
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
    public function test_array_constructor_hydrates_context_with_non_dto_argonaut_dto(): void
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
    public function test_array_constructor_hydrates_context_with_direct_dto_instance(): void
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
     * Test that context hydration works with null context.
     * This ensures the fix doesn't break null context handling.
     */
    public function test_array_constructor_handles_null_context(): void
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
     * Test that context hydration works with missing context key.
     * This ensures the fix doesn't break missing context handling.
     */
    public function test_array_constructor_handles_missing_context_key(): void
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
     * Test that context hydration works with invalid context data.
     * This ensures the fix doesn't break error handling.
     */
    public function test_array_constructor_handles_invalid_context_data(): void
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
     * Test that context hydration works with different transition modes.
     * This ensures the fix works across all modes.
     */
    public function test_array_constructor_hydrates_context_with_different_modes(): void
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
                'context' => [
                    'class' => $testDtoClass::class,
                    'payload' => ['message' => 'test'],
                ],
                'event' => 'test_event',
                'is_dry_run' => false,
                'mode' => $mode,
            ]);

            $this->assertSame($model, $input->model);
            $this->assertSame('pending', $input->fromState);
            $this->assertSame('completed', $input->toState);
            $this->assertSame('test_event', $input->event);
            $this->assertFalse($input->isDryRun);
            $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
            $this->assertSame('test', $input->context->message);
        }
    }

    /**
     * Test that context hydration works with different sources.
     * This ensures the fix works across all sources.
     */
    public function test_array_constructor_hydrates_context_with_different_sources(): void
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
                'context' => [
                    'class' => $testDtoClass::class,
                    'payload' => ['message' => 'test'],
                ],
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
            $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
            $this->assertSame('test', $input->context->message);
        }
    }
}
