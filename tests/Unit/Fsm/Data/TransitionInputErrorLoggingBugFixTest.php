<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionInput;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * Test for TransitionInput hydrateContext error logging bug fix.
 *
 * Tests the fix where inconsistent error logging in the hydrateContext method
 * has been resolved by removing unnecessary function_exists('error_log') checks
 * and the unreliable class_exists(\Log::class) check.
 */
class TransitionInputErrorLoggingBugFixTest extends TestCase
{
    /**
     * Test that hydrateContext throws RuntimeException when class is not a string
     * without attempting to log via Laravel Log facade.
     */
    public function test_hydrate_context_throws_exception_when_class_is_not_string(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed: class is not a string');

        new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => 123, // Invalid: not a string
                'payload' => ['message' => 'test'],
            ],
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);
    }

    /**
     * Test that hydrateContext throws RuntimeException when class does not exist
     * without attempting to log via error_log.
     */
    public function test_hydrate_context_throws_exception_when_class_does_not_exist(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class NonExistentClass: class does not exist');

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
     * Test that hydrateContext throws RuntimeException when class does not implement ArgonautDTOContract
     * without attempting to log via error_log.
     */
    public function test_hydrate_context_throws_exception_when_class_does_not_implement_argonaut_dto(): void
    {
        $model = $this->createMock(Model::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class stdClass: class does not implement ArgonautDTOContract');

        new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => \stdClass::class,
                'payload' => ['message' => 'test'],
            ],
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);
    }

    /**
     * Test that hydrateContext throws RuntimeException when payload is not an array
     * without attempting to log via error_log.
     */
    public function test_hydrate_context_throws_exception_when_payload_is_not_array(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that implements ArgonautDTOContract
        $testDtoClass = new class implements ArgonautDTOContract
        {
            public static function from(array $data): self
            {
                return new self;
            }

            public function toArray(): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return '{}';
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class '.$testDtoClass::class);

        new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => $testDtoClass::class,
                'payload' => 'invalid_payload', // Invalid: not an array
            ],
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);
    }

    /**
     * Test that hydrateContext throws RuntimeException when DTO instantiation fails
     * without attempting to log via error_log.
     */
    public function test_hydrate_context_throws_exception_when_dto_instantiation_fails(): void
    {
        $model = $this->createMock(Model::class);

        // Use a class that exists but will fail instantiation
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class stdClass: class does not implement ArgonautDTOContract');

        new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => [
                'class' => \stdClass::class, // stdClass doesn't implement ArgonautDTOContract
                'payload' => ['message' => 'test'],
            ],
            'event' => 'test_event',
            'is_dry_run' => false,
            'mode' => TransitionInput::MODE_NORMAL,
        ]);
    }

    /**
     * Test that hydrateContext throws RuntimeException when from() method fails
     * without attempting to log via error_log.
     */
    public function test_hydrate_context_throws_exception_when_from_method_fails(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that will fail in from() method
        $testDtoClass = new class implements ArgonautDTOContract
        {
            public static function from(array $data): self
            {
                throw new \Exception('From method failed');
            }

            public function toArray(): array
            {
                return [];
            }

            public function toJson($options = 0): string
            {
                return '{}';
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context hydration failed for class '.$testDtoClass::class.': From method failed');

        new TransitionInput([
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
    }

    /**
     * Test that hydrateContext works correctly with valid DTO
     * to ensure the fix doesn't break normal functionality.
     */
    public function test_hydrate_context_works_with_valid_dto(): void
    {
        $model = $this->createMock(Model::class);

        // Create a valid test DTO
        $testDtoClass = new class implements ArgonautDTOContract
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
     * Test that hydrateContext works correctly with DTO that extends Dto base class
     * to ensure the fix works with different DTO types.
     */
    public function test_hydrate_context_works_with_dto_base_class(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO that extends Dto
        $testDtoClass = new class(['message' => 'test']) extends \Fsm\Data\Dto
        {
            public string $message;

            public function __construct(array $data = [])
            {
                $this->message = $data['message'] ?? '';
                parent::__construct($data);
            }
        };

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
     * Test that hydrateContext works correctly with direct DTO instance
     * to ensure the fix doesn't break existing functionality.
     */
    public function test_hydrate_context_works_with_direct_dto_instance(): void
    {
        $model = $this->createMock(Model::class);

        // Create a test DTO instance directly
        $testDto = new class implements ArgonautDTOContract
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
        $this->assertSame('', $input->context->message); // Empty because no data was passed
    }

    /**
     * Test that hydrateContext handles null context correctly
     * to ensure the fix doesn't break null context handling.
     */
    public function test_hydrate_context_handles_null_context(): void
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
        $this->assertNull($input->context);
    }

    /**
     * Test that hydrateContext handles non-array context correctly
     * to ensure the fix doesn't break non-array context handling.
     */
    public function test_hydrate_context_handles_non_array_context(): void
    {
        $model = $this->createMock(Model::class);

        // Create a valid test DTO instance directly
        $testDto = new class implements ArgonautDTOContract
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

        $input = new TransitionInput([
            'model' => $model,
            'from_state' => 'pending',
            'to_state' => 'completed',
            'context' => $testDto, // Direct DTO instance (not an array)
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
        $this->assertSame('', $input->context->message); // Empty because no data was passed
    }

    /**
     * Test that hydrateContext works with positional constructor parameters
     * to ensure the fix works with all construction methods.
     */
    public function test_hydrate_context_works_with_positional_constructor(): void
    {
        $model = $this->createMock(Model::class);

        // Create a valid test DTO
        $testDtoClass = new class implements ArgonautDTOContract
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

        $context = [
            'class' => $testDtoClass::class,
            'payload' => ['message' => 'test'],
        ];

        $input = new TransitionInput(
            $model,
            'pending',
            'completed',
            $context,
            'test_event',
            false,
            TransitionInput::MODE_NORMAL,
            TransitionInput::SOURCE_USER,
            [],
            null
        );

        $this->assertSame($model, $input->model);
        $this->assertSame('pending', $input->fromState);
        $this->assertSame('completed', $input->toState);
        $this->assertSame('test_event', $input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertInstanceOf(ArgonautDTOContract::class, $input->context);
        $this->assertSame('test', $input->context->message);
    }
}
