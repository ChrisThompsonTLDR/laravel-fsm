<?php

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/TestCase.php';

// Ensure the test SQLite database file exists before any tests run
$testDbPath = __DIR__.'/../database/testbench.sqlite';
if (! file_exists($testDbPath)) {
    if (! is_dir(dirname($testDbPath))) {
        mkdir(dirname($testDbPath), 0777, true);
    }
    touch($testDbPath);
}

/**
 * Runs the FSM duration migration (2024_06_01_000000_add_duration_ms_to_fsm_logs_table).
 * Use in tests to ensure the migration is applied without duplicating include logic.
 */
function runFsmDurationMigration(): void
{
    $migrationPath = __DIR__.'/../src/database/migrations/2024_06_01_000000_add_duration_ms_to_fsm_logs_table.php';
    $migration = include $migrationPath;
    $migration->up();
}
