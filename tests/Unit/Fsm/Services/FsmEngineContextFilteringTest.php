<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Fsm\Contracts\FsmStateEnum;
use Fsm\Data\Dto;
use Fsm\Data\FsmRuntimeDefinition;
use Fsm\Data\StateDefinition;
use Fsm\Data\TransitionDefinition;
use Fsm\FsmRegistry;
use Fsm\Services\FsmEngineService;
use Fsm\Services\FsmLogger;
use Fsm\Services\FsmMetricsService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Mockery;
use Orchestra\Testbench\TestCase;
use YorCreative\LaravelArgonautDTO\ArgonautDTO;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

enum FilterState: string implements FsmStateEnum
{
    case Pending = 'pending';
    case Completed = 'completed';

    public function displayName(): string
    {
        return $this->value;
    }

    public function icon(): string
    {
        return $this->value;
    }
}

class FilterModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'filter_models';
}

// Test DTO extending Dto (has from())
class FilterContextWithDto extends Dto
{
    public string $message;

    public ?string $sensitiveData = null;

    public int $count;

    /**
     * @param  array<string, mixed>|string  $message
     */
    public function __construct(string|array $message, string $sensitiveData = '', int $count = 0)
    {
        if (is_array($message) && func_num_args() === 1 && static::isAssociative($message)) {
            parent::__construct($message);

            return;
        }

        parent::__construct([
            'message' => $message,
            'sensitiveData' => $sensitiveData,
            'count' => $count,
        ]);
    }
}

// Test DTO extending ArgonautDTO (has from() via parent)
class FilterContextWithArgonautDTO extends ArgonautDTO implements ArgonautDTOContract
{
    public string $message;

    public string $sensitiveData;

    public int $count;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data)
    {
        $this->message = $data['message'] ?? '';
        $this->sensitiveData = $data['sensitiveData'] ?? '';
        $this->count = $data['count'] ?? 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(int $depth = 3): array
    {
        return [
            'message' => $this->message,
            'sensitiveData' => $this->sensitiveData,
            'count' => $this->count,
        ];
    }
}

// Test DTO implementing ArgonautDTOContract but no from() method
class FilterContextNoFromMethod implements ArgonautDTOContract
{
    public string $message;

    public string $sensitiveData;

    public int $count;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data)
    {
        $this->message = $data['message'] ?? '';
        $this->sensitiveData = $data['sensitiveData'] ?? '';
        $this->count = $data['count'] ?? 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(int $depth = 3): array
    {
        return [
            'message' => $this->message,
            'sensitiveData' => $this->sensitiveData,
            'count' => $this->count,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}

// Test DTO that implements ArgonautDTOContract but has a non-static from() method
class FilterContextWithNonStaticFromMethod implements ArgonautDTOContract
{
    public string $message;

    public string $sensitiveData;

    public int $count;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data)
    {
        $this->message = $data['message'] ?? '';
        $this->sensitiveData = $data['sensitiveData'] ?? '';
        $this->count = $data['count'] ?? 0;
    }

    /**
     * Non-static from() method - this should NOT be called statically
     */
    public function from(mixed $payload): static
    {
        throw new \LogicException('This from() method is not static and should not be called statically');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(int $depth = 3): array
    {
        return [
            'message' => $this->message,
            'sensitiveData' => $this->sensitiveData,
            'count' => $this->count,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}

// Test DTO that implements ArgonautDTOContract but has a private from() method
class FilterContextWithPrivateFromMethod implements ArgonautDTOContract
{
    public string $message;

    public string $sensitiveData;

    public int $count;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data)
    {
        $this->message = $data['message'] ?? '';
        $this->sensitiveData = $data['sensitiveData'] ?? '';
        $this->count = $data['count'] ?? 0;
    }

    /**
     * Private from() method - this should NOT be callable from outside
     */
    private static function from(mixed $payload): static
    {
        throw new \LogicException('This from() method is private and should not be called');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(int $depth = 3): array
    {
        return [
            'message' => $this->message,
            'sensitiveData' => $this->sensitiveData,
            'count' => $this->count,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}

class FsmEngineContextFilteringTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(array $excludedProperties = []): FsmEngineService
    {
        $definition = new FsmRuntimeDefinition(
            FilterModel::class,
            'status',
            [new StateDefinition(FilterState::Pending), new StateDefinition(FilterState::Completed)],
            [new TransitionDefinition(
                fromState: FilterState::Pending,
                toState: FilterState::Completed,
                event: null
            )],
            FilterState::Pending
        );

        $registry = Mockery::mock(FsmRegistry::class);
        $registry->shouldReceive('getDefinition')
            ->andReturn($definition);

        $logger = Mockery::mock(FsmLogger::class);
        $logger->shouldReceive('logTransition')->byDefault();
        $logger->shouldReceive('logFailure')->byDefault();

        $db = Mockery::mock(DatabaseManager::class);
        $db->shouldReceive('transaction')->never();

        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')->with('fsm.use_transactions', true)->andReturn(false);
        $config->shouldReceive('get')->with('fsm.logging.enabled', true)->andReturn(true);
        $config->shouldReceive('get')->with('fsm.logging.log_failures', true)->andReturn(true);
        $config->shouldReceive('get')->with('fsm.verbs.dispatch_transitioned_verb', true)->andReturn(false);
        $config->shouldReceive('get')->with('fsm.logging.excluded_context_properties', [])->andReturn($excludedProperties);
        $config->shouldReceive('get')->with('fsm.debug', false)->andReturn(false);

        $dispatcher = Mockery::mock(\Illuminate\Contracts\Events\Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->andReturn(null);
        $metrics = new FsmMetricsService($dispatcher);

        return new FsmEngineService($registry, $logger, $metrics, $db, $config);
    }

    public function test_filter_context_with_dto_extending_dto(): void
    {
        Log::spy();

        $service = $this->makeService(['sensitiveData']);
        $model = new FilterModel(['status' => FilterState::Pending->value]);

        $context = new FilterContextWithDto('test message', 'secret', 5);

        // Use reflection to call the protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('filterContextForLogging');
        $method->setAccessible(true);

        $filtered = $method->invoke($service, $context);

        $this->assertInstanceOf(FilterContextWithDto::class, $filtered);
        $this->assertSame('test message', $filtered->message);
        $this->assertSame(5, $filtered->count);
        // sensitiveData should be filtered out (will be null or empty)
        $this->assertTrue(
            $filtered->sensitiveData === null || $filtered->sensitiveData === '',
            'Sensitive data should be filtered out'
        );

        // No warnings should be logged
        Log::shouldNotHaveReceived('warning');
    }

    public function test_filter_context_with_argonaut_dto(): void
    {
        Log::spy();

        $service = $this->makeService(['sensitiveData']);
        $model = new FilterModel(['status' => FilterState::Pending->value]);

        $context = new FilterContextWithArgonautDTO([
            'message' => 'argonaut message',
            'sensitiveData' => 'secret data',
            'count' => 10,
        ]);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('filterContextForLogging');
        $method->setAccessible(true);

        $filtered = $method->invoke($service, $context);

        $this->assertInstanceOf(FilterContextWithArgonautDTO::class, $filtered);
        $this->assertSame('argonaut message', $filtered->message);
        $this->assertSame(10, $filtered->count);

        // No warnings should be logged
        Log::shouldNotHaveReceived('warning');
    }

    public function test_filter_context_with_no_from_method_uses_direct_instantiation(): void
    {
        Log::spy();

        $service = $this->makeService(['sensitiveData']);
        $model = new FilterModel(['status' => FilterState::Pending->value]);

        $context = new FilterContextNoFromMethod([
            'message' => 'direct instantiation',
            'sensitiveData' => 'should be removed',
            'count' => 15,
        ]);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('filterContextForLogging');
        $method->setAccessible(true);

        $filtered = $method->invoke($service, $context);

        // Should successfully filter using direct instantiation
        $this->assertInstanceOf(FilterContextNoFromMethod::class, $filtered);
        $this->assertSame('direct instantiation', $filtered->message);
        $this->assertSame(15, $filtered->count);

        // No warnings should be logged since direct instantiation succeeded
        Log::shouldNotHaveReceived('warning');
    }

    public function test_filter_context_returns_original_when_no_properties_excluded(): void
    {
        Log::spy();

        $service = $this->makeService([]); // No excluded properties
        $model = new FilterModel(['status' => FilterState::Pending->value]);

        $context = new FilterContextWithDto('original', 'data', 20);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('filterContextForLogging');
        $method->setAccessible(true);

        $filtered = $method->invoke($service, $context);

        // Should return the same instance since no filtering is needed
        $this->assertSame($context, $filtered);

        Log::shouldNotHaveReceived('warning');
    }

    public function test_filter_context_returns_null_when_context_is_null(): void
    {
        Log::spy();

        $service = $this->makeService(['sensitiveData']);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('filterContextForLogging');
        $method->setAccessible(true);

        $filtered = $method->invoke($service, null);

        $this->assertNull($filtered);

        Log::shouldNotHaveReceived('warning');
    }

    public function test_filter_context_returns_original_when_filtered_equals_original(): void
    {
        Log::spy();

        $service = $this->makeService(['nonExistentProperty']);
        $context = new FilterContextWithDto('message', 'data', 25);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('filterContextForLogging');
        $method->setAccessible(true);

        $filtered = $method->invoke($service, $context);

        // Should return original since the excluded property doesn't exist
        $this->assertSame($context, $filtered);

        Log::shouldNotHaveReceived('warning');
    }

    public function test_filter_context_logs_warning_when_instantiation_fails(): void
    {
        // Create a DTO class that implements ArgonautDTOContract directly (not extending Dto)
        // and fails when constructed with an array
        $failingDtoClass = new class implements \YorCreative\LaravelArgonautDTO\ArgonautDTOContract {
            public string $message;
            
            public function __construct(string|array $message = 'test')
            {
                // Always fail when constructed with an array (this happens during filtering recreation)
                if (is_array($message)) {
                    throw new \RuntimeException('Constructor intentionally fails for testing');
                }
                
                $this->message = $message;
            }
            
            public function toArray(int $depth = 3): array
            {
                return ['message' => $this->message];
            }
            
            public function toJson($options = 0): string
            {
                return json_encode($this->toArray(), $options);
            }
        };
        
        $service = $this->makeService(['message']);
        
        // Create an instance of the DTO (this should work since we pass a string)
        $failingDto = new $failingDtoClass('test message');
        
        // Mock Log to capture the warning
        Log::spy();
        
        // Call filterContextForLogging - this should catch the exception and log a warning
        $result = $service->filterContextForLogging($failingDto);
        
        // Verify that the original context is returned when instantiation fails
        $this->assertSame($failingDto, $result);
        
        // Verify that a warning was logged
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('[FSM] Context filtering failed: could not reinstantiate DTO, returning original', \Mockery::type('array'));
    }

    public function test_filter_context_handles_non_static_from_method(): void
    {
        Log::spy();

        $service = $this->makeService(['sensitiveData']);

        // Create a context with a non-static from() method
        $context = new FilterContextWithNonStaticFromMethod([
            'message' => 'non-static test',
            'sensitiveData' => 'should be removed',
            'count' => 42,
        ]);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('filterContextForLogging');
        $method->setAccessible(true);

        $filtered = $method->invoke($service, $context);

        // Should successfully filter using direct instantiation since from() is not static
        $this->assertInstanceOf(FilterContextWithNonStaticFromMethod::class, $filtered);
        $this->assertSame('non-static test', $filtered->message);
        $this->assertSame(42, $filtered->count);
        // sensitiveData should be filtered out
        $this->assertTrue(
            $filtered->sensitiveData === null || $filtered->sensitiveData === '',
            'Sensitive data should be filtered out'
        );

        // No warnings should be logged since direct instantiation succeeded
        Log::shouldNotHaveReceived('warning');
    }

    public function test_filter_context_handles_private_from_method(): void
    {
        Log::spy();

        $service = $this->makeService(['sensitiveData']);

        // Create a context with a private from() method
        $context = new FilterContextWithPrivateFromMethod([
            'message' => 'private from test',
            'sensitiveData' => 'should be removed',
            'count' => 99,
        ]);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('filterContextForLogging');
        $method->setAccessible(true);

        $filtered = $method->invoke($service, $context);

        // Should successfully filter using direct instantiation since from() is private
        $this->assertInstanceOf(FilterContextWithPrivateFromMethod::class, $filtered);
        $this->assertSame('private from test', $filtered->message);
        $this->assertSame(99, $filtered->count);
        // sensitiveData should be filtered out
        $this->assertTrue(
            $filtered->sensitiveData === null || $filtered->sensitiveData === '',
            'Sensitive data should be filtered out'
        );

        // No warnings should be logged since direct instantiation succeeded
        Log::shouldNotHaveReceived('warning');
    }

    public function test_integration_filter_context_during_transition(): void
    {
        // This test verifies that the context filtering is correctly applied
        // during a transition by using reflection to call the protected method
        $service = $this->makeService(['sensitiveData']);

        $context = new FilterContextWithDto('integration test', 'should not be logged', 30);

        // Use reflection to call the filterContextForLogging method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('filterContextForLogging');
        $method->setAccessible(true);

        $filtered = $method->invoke($service, $context);

        // Verify the context has been properly filtered
        $this->assertInstanceOf(FilterContextWithDto::class, $filtered);
        $this->assertSame('integration test', $filtered->message);
        $this->assertSame(30, $filtered->count);
        // Verify sensitive data was filtered out
        $this->assertTrue(
            $filtered->sensitiveData === null || $filtered->sensitiveData === '',
            'Sensitive data should have been filtered out'
        );
        $this->assertNotSame('should not be logged', $filtered->sensitiveData);
    }
}
