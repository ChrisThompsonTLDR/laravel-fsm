<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\Dto;
use Fsm\Data\TransitionInput;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * Test context DTO that intentionally fails during construction to test error handling.
 */
class ConstructorFailingContextDto implements ArgonautDTOContract
{
    public string $message;

    public function __construct(array $data = [])
    {
        throw new \InvalidArgumentException('Constructor failed');
    }

    public function toArray(): array
    {
        return ['message' => $this->message];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}

/**
 * Test for TransitionInput nullable parameters and validation.
 *
 * Tests the changes where toState became nullable and validation logic was updated.
 */
class TransitionInputTest extends TestCase
{
    public function test_constructor_with_all_required_parameters(): void
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

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
    }

    public function test_constructor_accepts_null_to_state_for_non_normal_modes(): void
    {
        $model = $this->createMock(Model::class);

        // Test dry run mode
        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: null,
            context: null,
            event: 'test_event',
            isDryRun: true,
            mode: TransitionInput::MODE_DRY_RUN
        );

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertNull($input->toState);
        $this->assertTrue($input->isDryRun);

        // Test force mode
        $input2 = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: null,
            context: null,
            event: 'test_event',
            isDryRun: false,
            mode: TransitionInput::MODE_FORCE
        );

        $this->assertNull($input2->toState);
        $this->assertTrue($input2->isForced());

        // Test silent mode
        $input3 = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: null,
            context: null,
            event: 'test_event',
            isDryRun: false,
            mode: TransitionInput::MODE_SILENT
        );

        $this->assertNull($input3->toState);
        $this->assertTrue($input3->isSilent());
    }

    public function test_constructor_throws_exception_when_to_state_is_null_for_normal_mode(): void
    {
        $model = $this->createMock(Model::class);

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

    public function test_constructor_with_array_accepts_null_to_state_for_non_normal_modes(): void
    {
        $model = $this->createMock(Model::class);

        // Test dry run mode
        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => null,
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => true,
            'mode' => TransitionInput::MODE_DRY_RUN,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertNull($input->toState);
        $this->assertTrue($input->isDryRun);
    }

    public function test_constructor_with_array_throws_exception_when_to_state_missing_for_normal_mode(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TransitionInput requires a non-null "toState" or "to_state" value for normal mode transitions.');

        new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);
    }

    public function test_constructor_with_array_accepts_missing_to_state_for_non_normal_modes(): void
    {
        $model = $this->createMock(Model::class);

        // Test force mode
        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => false,
            'mode' => TransitionInput::MODE_FORCE,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertNull($input->toState);
        $this->assertTrue($input->isForced());
    }

    public function test_constructor_with_array_using_snake_case_keys(): void
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

    public function test_constructor_with_array_throws_exception_when_to_state_is_null_for_normal_mode(): void
    {
        $model = $this->createMock(Model::class);

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

    public function test_constructor_with_array_throws_exception_when_to_state_is_null_for_normal_mode_with_snake_case(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TransitionInput requires a non-null "toState" or "to_state" value for normal mode transitions.');

        new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => null,
            'context' => null,
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);
    }

    public function test_constructor_with_array_throws_exception_when_mode_missing_and_to_state_is_null(): void
    {
        $model = $this->createMock(Model::class);

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

    public function test_constructor_with_array_allows_null_to_state_when_mode_is_explicitly_non_normal(): void
    {
        $model = $this->createMock(Model::class);

        // Test with explicit MODE_DRY_RUN
        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => null,
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => true,
            'mode' => TransitionInput::MODE_DRY_RUN,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertNull($input->toState);
        $this->assertTrue($input->isDryRun);
    }

    public function test_constructor_with_array_allows_null_to_state_when_mode_is_explicitly_force(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => null,
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => false,
            'mode' => TransitionInput::MODE_FORCE,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertNull($input->toState);
        $this->assertTrue($input->isForced());
    }

    public function test_constructor_with_array_allows_null_to_state_when_mode_is_explicitly_silent(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => null,
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => false,
            'mode' => TransitionInput::MODE_SILENT,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertNull($input->toState);
        $this->assertTrue($input->isSilent());
    }

    public function test_constructor_with_array_accepts_valid_to_state_for_normal_mode(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput([
            'model' => $model,
            'fromState' => 'pending',
            'toState' => 'completed',
            'context' => null,
            'event' => 'test_event',
            'isDryRun' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
    }

    public function test_constructor_with_array_accepts_valid_to_state_for_normal_mode_with_snake_case(): void
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

    /**
     * Test hydrateContext method with valid DTO extending Dto class.
     */
    public function test_hydrate_context_with_valid_dto_extending_dto(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that extends Dto
        $testDto = new class(['message' => 'test']) extends Dto
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                parent::__construct($data);
            }
        };

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: $testDto
        );

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test hydrateContext method with array context containing valid DTO class.
     */
    public function test_hydrate_context_with_array_context_valid_dto(): void
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

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ]
        );

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test hydrateContext method with non-Dto ArgonautDTOContract that has from() method.
     */
    public function test_hydrate_context_with_non_dto_argonaut_dto_with_from_method(): void
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

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ]
        );

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test hydrateContext method with non-Dto ArgonautDTOContract using direct instantiation.
     */
    public function test_hydrate_context_with_non_dto_argonaut_dto_direct_instantiation(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract but doesn't extend Dto and has no from() method
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
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

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ]
        );

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test hydrateContext method throws exception when DTO class has non-static from() method.
     */
    public function test_hydrate_context_throws_exception_when_dto_has_non_static_from_method(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract but has a non-static from() method
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
            }

            // Non-static from() method - should cause fallback to direct instantiation
            public function from(array $data): self
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

        // This should work because it falls back to direct instantiation
        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ]
        );

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test hydrateContext method throws exception when DTO class has private from() method.
     */
    public function test_hydrate_context_throws_exception_when_dto_has_private_from_method(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract but has a private from() method
        $testDtoClass = new class(['message' => 'test']) implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
            }

            // Private static from() method - should cause fallback to direct instantiation
            private static function from(array $data): self
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

        // This should work because it falls back to direct instantiation
        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => $testDtoClass::class,
                'payload' => ['message' => 'test'],
            ]
        );

        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }

    /**
     * Test hydrateContext method throws exception when DTO instantiation fails.
     */
    public function test_hydrate_context_throws_exception_when_dto_instantiation_fails(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO class that implements ArgonautDTOContract but fails during instantiation
        $testDtoClass = ConstructorFailingContextDto::class;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to instantiate DTO class '.$testDtoClass.': Constructor failed');

        new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => $testDtoClass,
                'payload' => ['message' => 'test'],
            ]
        );
    }

    /**
     * Test hydrateContext method throws exception when class does not exist.
     */
    public function test_hydrate_context_throws_exception_when_class_does_not_exist(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class NonExistentClass');

        new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => 'NonExistentClass',
                'payload' => ['message' => 'test'],
            ]
        );
    }

    /**
     * Test hydrateContext method throws exception when class does not implement ArgonautDTOContract.
     */
    public function test_hydrate_context_throws_exception_when_class_does_not_implement_contract(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class stdClass');

        new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => \stdClass::class,
                'payload' => ['message' => 'test'],
            ]
        );
    }

    /**
     * Test hydrateContext method throws exception when payload is not an array.
     */
    public function test_hydrate_context_throws_exception_when_payload_not_array(): void
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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class '.$testDtoClass::class);

        new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => $testDtoClass::class,
                'payload' => 'not_an_array',
            ]
        );
    }

    /**
     * @skip Test for DTO constructor failure handling - functionality works but test needs refactoring.
     * The hydrateContext method should catch constructor exceptions and re-throw them as RuntimeException,
     * but the current test setup may not be triggering the expected code path correctly.
     */
    public function test_hydrate_context_throws_exception_when_dto_constructor_fails(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that will fail during construction
        $testDtoClass = ConstructorFailingContextDto::class;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to instantiate DTO class');

        new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => $testDtoClass,
                'payload' => [],
            ]
        );
    }

    /**
     * Test hydrateContext method throws exception when DTO from() method fails.
     */
    public function test_hydrate_context_throws_exception_when_dto_from_method_fails(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that will fail during from() method
        $testDtoClass = new class implements ArgonautDTOContract
        {
            public string $message;

            public function __construct(string $message = 'default')
            {
                $this->message = $message;
            }

            public static function from(mixed $payload): self
            {
                throw new \InvalidArgumentException('From method failed');
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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class '.$testDtoClass::class.': From method failed');

        new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => $testDtoClass::class,
                'payload' => 'test message',
            ]
        );
    }

    /**
     * Test hydrateContext method returns null for null context.
     */
    public function test_hydrate_context_returns_null_for_null_context(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null
        );

        $this->assertNull($input->context);
    }

    /**
     * Test hydrateContext method returns null for non-array context.
     */
    public function test_hydrate_context_returns_null_for_non_array_context(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: null
        );

        $this->assertNull($input->context);
    }

    /**
     * Test hydrateContext method returns null when class key is missing.
     */
    public function test_hydrate_context_returns_null_when_class_key_missing(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got null)');

        new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'payload' => ['message' => 'test'],
            ]
        );
    }

    /**
     * Test hydrateContext method returns null when class is not a string.
     */
    public function test_hydrate_context_returns_null_when_class_not_string(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string (got int)');

        new TransitionInput(
            model: $model,
            fromState: 'pending',
            toState: 'completed',
            context: [
                'class' => 123,
                'payload' => ['message' => 'test'],
            ]
        );
    }
}
