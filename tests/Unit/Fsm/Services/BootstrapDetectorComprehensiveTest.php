<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Services;

use Fsm\Constants;
use Fsm\Services\BootstrapDetector;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\TestCase;

class BootstrapDetectorComprehensiveTest extends TestCase
{
    private BootstrapDetector $detector;

    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = $this->createMock(Application::class);
        $this->detector = new BootstrapDetector($this->app);
    }

    public function test_constructor_initializes_with_application(): void
    {
        $this->assertInstanceOf(BootstrapDetector::class, $this->detector);
    }

    public function test_in_bootstrap_mode_returns_false_when_not_running_in_console(): void
    {
        $this->app->method('runningInConsole')->willReturn(false);
        $this->app->method('bound')->willReturnCallback(function ($service) {
            return $service === 'db' || $service === 'config';
        });

        // Mock successful database connection
        $dbMock = $this->createMock(\Illuminate\Database\DatabaseManager::class);
        $connectionMock = $this->createMock(\Illuminate\Database\Connection::class);
        $connectionMock->method('getPdo')->willReturn($this->createMock(\PDO::class));

        $dbMock->method('connection')->willReturn($connectionMock);
        $this->app->method('make')->willReturn($dbMock);

        $result = $this->detector->inBootstrapMode();

        $this->assertFalse($result);
    }

    public function test_in_bootstrap_mode_returns_true_for_package_discover_command(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);

        // Mock $_SERVER['argv'] to simulate package:discover command
        $_SERVER['argv'] = ['php', 'package:discover'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        // Clean up
        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_returns_true_for_config_cache_command(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);

        $_SERVER['argv'] = ['php', 'config:cache'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_returns_true_for_config_clear_command(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);

        $_SERVER['argv'] = ['php', 'config:clear'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_returns_true_for_cache_clear_command(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);

        $_SERVER['argv'] = ['php', 'cache:clear'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_returns_true_for_optimize_command(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);

        $_SERVER['argv'] = ['php', 'optimize'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_returns_true_for_optimize_clear_command(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);

        $_SERVER['argv'] = ['php', 'optimize:clear'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_returns_true_for_dump_autoload_command(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);

        $_SERVER['argv'] = ['php', 'dump-autoload'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_returns_false_for_other_commands(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);
        $this->app->method('bound')->willReturn(true);

        // Mock database manager with proper connection
        $dbMock = $this->createMock(\Illuminate\Database\DatabaseManager::class);
        $connectionMock = $this->createMock(\Illuminate\Database\Connection::class);
        $connectionMock->method('getPdo')->willReturn($this->createMock(\PDO::class));

        $dbMock->method('connection')->willReturn($connectionMock);
        $this->app->method('make')->willReturn($dbMock);

        $_SERVER['argv'] = ['php', 'migrate'];

        $result = $this->detector->inBootstrapMode();

        $this->assertFalse($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_returns_false_for_empty_argv(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);
        $this->app->method('bound')->willReturn(true);

        // Mock database manager with proper connection
        $dbMock = $this->createMock(\Illuminate\Database\DatabaseManager::class);
        $connectionMock = $this->createMock(\Illuminate\Database\Connection::class);
        $connectionMock->method('getPdo')->willReturn($this->createMock(\PDO::class));

        $dbMock->method('connection')->willReturn($connectionMock);
        $this->app->method('make')->willReturn($dbMock);

        $_SERVER['argv'] = ['php'];

        $result = $this->detector->inBootstrapMode();

        $this->assertFalse($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_returns_false_for_missing_argv(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);
        $this->app->method('bound')->willReturn(true);

        // Mock database manager with proper connection
        $dbMock = $this->createMock(\Illuminate\Database\DatabaseManager::class);
        $connectionMock = $this->createMock(\Illuminate\Database\Connection::class);
        $connectionMock->method('getPdo')->willReturn($this->createMock(\PDO::class));

        $dbMock->method('connection')->willReturn($connectionMock);
        $this->app->method('make')->willReturn($dbMock);

        unset($_SERVER['argv']);

        $result = $this->detector->inBootstrapMode();

        $this->assertFalse($result);
    }

    public function test_in_bootstrap_mode_handles_database_unavailable(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);
        $this->app->method('bound')->with('db')->willReturn(false);

        $_SERVER['argv'] = ['php', 'some:command'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_handles_database_connection_error(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);
        $this->app->method('bound')->with('db')->willReturn(true);

        $dbMock = $this->createMock(\Illuminate\Database\DatabaseManager::class);
        $connectionMock = $this->createMock(\Illuminate\Database\Connection::class);

        $this->app->method('make')->with('db')->willReturn($dbMock);
        $dbMock->method('connection')->willReturn($connectionMock);
        $connectionMock->method('getPdo')->willThrowException(new \Exception('Database connection failed'));

        $_SERVER['argv'] = ['php', 'some:command'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_returns_false_when_database_available(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);
        $this->app->method('bound')->willReturn(true);

        // Mock database manager with proper connection
        $dbMock = $this->createMock(\Illuminate\Database\DatabaseManager::class);
        $connectionMock = $this->createMock(\Illuminate\Database\Connection::class);
        $connectionMock->method('getPdo')->willReturn($this->createMock(\PDO::class));

        $dbMock->method('connection')->willReturn($connectionMock);
        $this->app->method('make')->willReturn($dbMock);

        $_SERVER['argv'] = ['php', 'some:command'];

        $result = $this->detector->inBootstrapMode();

        $this->assertFalse($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_handles_essential_services_unavailable(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);
        $this->app->method('bound')->with('config')->willReturn(false);

        $_SERVER['argv'] = ['php', 'some:command'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_handles_missing_functions(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);
        $this->app->method('bound')->with('config')->willReturn(false);

        $_SERVER['argv'] = ['php', 'some:command'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_handles_exceptions_in_service_check(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);
        $this->app->method('bound')->willThrowException(new \Exception('Service check failed'));

        $_SERVER['argv'] = ['php', 'some:command'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_multiple_conditions_combine_with_or(): void
    {
        // Test that any condition being true results in bootstrap mode

        // Test discovery command + available database should still return true
        $this->app->method('runningInConsole')->willReturn(true);
        $this->app->method('bound')->willReturn(true);

        // Mock database manager with proper connection
        $dbMock = $this->createMock(\Illuminate\Database\DatabaseManager::class);
        $connectionMock = $this->createMock(\Illuminate\Database\Connection::class);
        $connectionMock->method('getPdo')->willReturn($this->createMock(\PDO::class));

        $dbMock->method('connection')->willReturn($connectionMock);
        $this->app->method('make')->willReturn($dbMock);

        $_SERVER['argv'] = ['php', 'package:discover'];

        $result = $this->detector->inBootstrapMode();

        $this->assertTrue($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_exact_command_matching(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);
        $this->app->method('bound')->willReturn(true);

        // Mock database manager with proper connection
        $dbMock = $this->createMock(\Illuminate\Database\DatabaseManager::class);
        $connectionMock = $this->createMock(\Illuminate\Database\Connection::class);
        $connectionMock->method('getPdo')->willReturn($this->createMock(\PDO::class));

        $dbMock->method('connection')->willReturn($connectionMock);
        $this->app->method('make')->willReturn($dbMock);

        // Test that partial matches don't trigger bootstrap mode
        $_SERVER['argv'] = ['php', 'package:discover:something'];

        $result = $this->detector->inBootstrapMode();

        $this->assertFalse($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_handles_all_skip_commands(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);

        foreach (Constants::SKIP_DISCOVERY_COMMANDS as $command) {
            $_SERVER['argv'] = ['php', $command];

            $result = $this->detector->inBootstrapMode();

            $this->assertTrue($result, "Command '$command' should trigger bootstrap mode");
        }

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_case_sensitive_command_matching(): void
    {
        $this->app->method('runningInConsole')->willReturn(true);
        $this->app->method('bound')->willReturn(true);

        // Mock database manager with proper connection
        $dbMock = $this->createMock(\Illuminate\Database\DatabaseManager::class);
        $connectionMock = $this->createMock(\Illuminate\Database\Connection::class);
        $connectionMock->method('getPdo')->willReturn($this->createMock(\PDO::class));

        $dbMock->method('connection')->willReturn($connectionMock);
        $this->app->method('make')->willReturn($dbMock);

        // Test case variations don't match
        $_SERVER['argv'] = ['php', 'Package:Discover'];

        $result = $this->detector->inBootstrapMode();

        $this->assertFalse($result);

        unset($_SERVER['argv']);
    }

    public function test_in_bootstrap_mode_web_request(): void
    {
        // Test that web requests (not console) are never in bootstrap mode
        $this->app->method('runningInConsole')->willReturn(false);
        $this->app->method('bound')->willReturnCallback(function ($service) {
            return $service === 'db' || $service === 'config';
        });

        // Mock successful database connection
        $dbMock = $this->createMock(\Illuminate\Database\DatabaseManager::class);
        $connectionMock = $this->createMock(\Illuminate\Database\Connection::class);
        $connectionMock->method('getPdo')->willReturn($this->createMock(\PDO::class));

        $dbMock->method('connection')->willReturn($connectionMock);
        $this->app->method('make')->willReturn($dbMock);

        $_SERVER['argv'] = ['php', 'package:discover'];

        $result = $this->detector->inBootstrapMode();

        $this->assertFalse($result);

        unset($_SERVER['argv']);
    }
}
