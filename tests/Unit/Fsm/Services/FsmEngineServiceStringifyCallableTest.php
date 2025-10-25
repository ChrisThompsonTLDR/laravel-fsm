<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Fsm\FsmRegistry;
use Fsm\Services\FsmEngineService;
use Fsm\Services\FsmLogger;
use Fsm\Services\FsmMetricsService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use LogicException;
use Mockery;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class FsmEngineServiceStringifyCallableTest extends TestCase
{
    private FsmEngineService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FsmEngineService(
            Mockery::mock(FsmRegistry::class),
            Mockery::mock(FsmLogger::class),
            Mockery::mock(FsmMetricsService::class),
            Mockery::mock(DatabaseManager::class),
            Mockery::mock(ConfigRepository::class)
        );
    }

    #[Test]
    public function it_converts_class_string_callable_to_string_format(): void
    {
        $callable = ['TestClass', 'testMethod'];
        $result = $this->callPrivateMethod('stringifyCallable', $callable);

        $this->assertEquals('TestClass@testMethod', $result);
    }

    #[Test]
    public function it_converts_namespaced_class_string_callable_to_string_format(): void
    {
        $callable = ['App\\Services\\TestService', 'handle'];
        $result = $this->callPrivateMethod('stringifyCallable', $callable);

        $this->assertEquals('App\\Services\\TestService@handle', $result);
    }

    #[Test]
    public function it_throws_exception_for_object_instance_callable(): void
    {
        $object = new \stdClass;
        $callable = [$object, 'method'];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('stringifyCallable should not be called with object instances. Use executeCallableWithInstance instead.');

        $this->callPrivateMethod('stringifyCallable', $callable);
    }

    #[Test]
    #[DataProvider('validClassStringCallablesProvider')]
    public function it_handles_valid_class_string_callables(array $callable, string $expected): void
    {
        $result = $this->callPrivateMethod('stringifyCallable', $callable);

        $this->assertEquals($expected, $result);
    }

    #[Test]
    #[DataProvider('invalidCallablesProvider')]
    public function it_throws_exception_for_invalid_callables(array $callable): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('stringifyCallable should not be called with object instances. Use executeCallableWithInstance instead.');

        $this->callPrivateMethod('stringifyCallable', $callable);
    }

    public static function validClassStringCallablesProvider(): array
    {
        return [
            'simple class and method' => [
                ['UserService', 'create'],
                'UserService@create',
            ],
            'namespaced class' => [
                ['App\\Models\\User', 'find'],
                'App\\Models\\User@find',
            ],
            'class with underscore' => [
                ['Test_Class', 'method_name'],
                'Test_Class@method_name',
            ],
            'class with numbers' => [
                ['Class123', 'method456'],
                'Class123@method456',
            ],
            'empty method name' => [
                ['TestClass', ''],
                'TestClass@',
            ],
        ];
    }

    public static function invalidCallablesProvider(): array
    {
        return [
            'object instance' => [
                [new \stdClass, 'method'],
            ],
            'closure' => [
                [function () {}, 'method'],
            ],
            'invokable object' => [
                [new class
                {
                    public function __invoke() {}
                }, 'method'],
            ],
            'mixed array with object' => [
                [new \DateTime, 'format'],
            ],
        ];
    }

    /**
     * Call a private method on the service using reflection.
     */
    private function callPrivateMethod(string $methodName, ...$args): mixed
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->service, $args);
    }
}
