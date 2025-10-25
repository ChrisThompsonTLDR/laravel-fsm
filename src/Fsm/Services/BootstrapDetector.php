<?php

declare(strict_types=1);

namespace Fsm\Services;

use Fsm\Constants;
use Illuminate\Contracts\Foundation\Application;

class BootstrapDetector
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Detect if we're in package discovery mode or bootstrap mode where database
     * access and full application services should be avoided.
     */
    public function inBootstrapMode(): bool
    {
        return $this->isRunningDiscoveryCommand() || $this->isDatabaseUnavailable() || $this->areEssentialServicesUnavailable();
    }

    /**
     * Check if we're running a package discovery or bootstrap command.
     */
    private function isRunningDiscoveryCommand(): bool
    {
        // Check if we're running in console mode
        if (! $this->app->runningInConsole()) {
            return false;
        }

        // Safely get the current command from command line arguments
        $argv = $_SERVER['argv'] ?? [];
        if (! is_array($argv) || count($argv) < 2) {
            return false;
        }

        $command = $argv[1];

        // Skip discovery for package discovery and basic bootstrap commands
        // Use exact matching to avoid false positives with custom commands
        foreach (Constants::SKIP_DISCOVERY_COMMANDS as $skipCommand) {
            if ($command === $skipCommand) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the database is unavailable or inaccessible.
     */
    private function isDatabaseUnavailable(): bool
    {
        try {
            // Check if database service is bound
            if (! $this->app->bound('db')) {
                return true;
            }

            // Try to access the database connection
            $db = $this->app->make('db');

            // Check if database connection is available
            if (method_exists($db, 'connection')) {
                $db->connection()->getPdo(); // Attempt to get the PDO instance
            }

            return false;
        } catch (\Throwable $e) {
            // Any database-related error suggests we're in bootstrap mode
            return true;
        }
    }

    /**
     * Check if essential Laravel services are unavailable.
     */
    private function areEssentialServicesUnavailable(): bool
    {
        try {
            // Check if config service is bound
            if (! $this->app->bound('config')) {
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            return true;
        }
    }
}
