<?php

declare(strict_types=1);

namespace Fsm\Commands;

use Fsm\Http\Controllers\FsmReplayApiController;
use Fsm\Models\FsmEventLog;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Command to test the FSM Replay API functionality.
 *
 * This command helps validate that the Replay API is working correctly
 * by creating test data and exercising all API endpoints.
 */
class TestReplayApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fsm:test-replay-api 
                            {--model-class= : The model class to test with}
                            {--model-id= : The model ID to test with} 
                            {--column-name=status : The column name to test with}
                            {--create-test-data : Create test transition data}';

    /**
     * The console command description.
     */
    protected $description = 'Test the FSM Replay API functionality';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Testing FSM Replay API...');

        $modelClassOption = $this->option('model-class');
        $modelIdOption = $this->option('model-id');
        $columnNameOption = $this->option('column-name');

        if (! is_string($modelClassOption) || empty($modelClassOption)) {
            $this->error('Please provide a model class with --model-class option');

            return 1;
        }

        $modelClass = $modelClassOption;
        $modelId = is_string($modelIdOption) ? $modelIdOption : '1';
        $columnName = is_string($columnNameOption) ? $columnNameOption : 'status';

        if (! class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist");

            return 1;
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            $this->error("Class {$modelClass} is not an Eloquent model");

            return 1;
        }

        // Create test data if requested
        if ($this->option('create-test-data')) {
            $this->createTestData($modelClass, $modelId, $columnName);
        }

        // Test all API endpoints
        $controller = app(FsmReplayApiController::class);

        $this->testHistoryEndpoint($controller, $modelClass, $modelId, $columnName);
        $this->testReplayEndpoint($controller, $modelClass, $modelId, $columnName);
        $this->testValidateEndpoint($controller, $modelClass, $modelId, $columnName);
        $this->testStatisticsEndpoint($controller, $modelClass, $modelId, $columnName);

        $this->info('✅ All API tests completed successfully!');

        return 0;
    }

    private function createTestData(string $modelClass, string $modelId, string $columnName): void
    {
        $this->info('📝 Creating test transition data...');

        $transitions = [
            ['from' => null, 'to' => 'pending', 'event' => 'create', 'time' => '08:00:00'],
            ['from' => 'pending', 'to' => 'processing', 'event' => 'process', 'time' => '10:00:00'],
            ['from' => 'processing', 'to' => 'completed', 'event' => 'complete', 'time' => '12:00:00'],
        ];

        foreach ($transitions as $index => $transition) {
            FsmEventLog::updateOrCreate([
                'model_type' => $modelClass,
                'model_id' => $modelId,
                'column_name' => $columnName,
                'from_state' => $transition['from'],
                'to_state' => $transition['to'],
            ], [
                'transition_name' => $transition['event'],
                'occurred_at' => now()->format('Y-m-d').' '.$transition['time'],
                'context' => ['test_data' => true, 'sequence' => $index + 1],
                'metadata' => ['source' => 'test_command'],
            ]);
        }

        $this->info('✅ Test data created successfully');
    }

    private function testHistoryEndpoint(
        FsmReplayApiController $controller,
        string $modelClass,
        string $modelId,
        string $columnName
    ): void {
        $this->info('🧪 Testing history endpoint...');

        $request = new Request([
            'model_class' => $modelClass,
            'model_id' => $modelId,
            'column_name' => $columnName,
        ]);

        $response = $controller->getHistory($request);
        $content = $response->getContent();
        if ($content === false) {
            $this->error('Failed to get response content for history endpoint');

            return;
        }
        $data = json_decode($content, true);

        if (! $data['success']) {
            $this->error('History endpoint failed: '.$data['error']);

            return;
        }

        $this->line("  ✅ Retrieved {$data['data']['count']} transitions");

        if ($data['data']['count'] > 0) {
            $firstTransition = $data['data']['transitions'][0];
            $this->line("  📍 First transition: {$firstTransition['from_state']} → {$firstTransition['to_state']}");
        }
    }

    private function testReplayEndpoint(
        FsmReplayApiController $controller,
        string $modelClass,
        string $modelId,
        string $columnName
    ): void {
        $this->info('🧪 Testing replay endpoint...');

        $request = new Request([
            'model_class' => $modelClass,
            'model_id' => $modelId,
            'column_name' => $columnName,
        ]);

        $response = $controller->replayTransitions($request);
        $content = $response->getContent();
        if ($content === false) {
            $this->error('Failed to get response content for replay endpoint');

            return;
        }
        $data = json_decode($content, true);

        if (! $data['success']) {
            $this->error('Replay endpoint failed: '.$data['error']);

            return;
        }

        $replayData = $data['data'];
        $this->line("  ✅ Replayed {$replayData['transition_count']} transitions");
        $this->line('  📍 Initial state: '.($replayData['initial_state'] ?? 'null'));
        $this->line('  📍 Final state: '.($replayData['final_state'] ?? 'null'));
    }

    private function testValidateEndpoint(
        FsmReplayApiController $controller,
        string $modelClass,
        string $modelId,
        string $columnName
    ): void {
        $this->info('🧪 Testing validate endpoint...');

        $request = new Request([
            'model_class' => $modelClass,
            'model_id' => $modelId,
            'column_name' => $columnName,
        ]);

        $response = $controller->validateHistory($request);
        $content = $response->getContent();
        if ($content === false) {
            $this->error('Failed to get response content for validate endpoint');

            return;
        }
        $data = json_decode($content, true);

        if (! $data['success']) {
            $this->error('Validate endpoint failed: '.$data['error']);

            return;
        }

        $validation = $data['data'];
        if ($validation['valid']) {
            $this->line('  ✅ Transition history is valid');
        } else {
            $this->line('  ❌ Transition history has errors:');
            foreach ($validation['errors'] as $error) {
                $this->line("    • {$error}");
            }
        }
    }

    private function testStatisticsEndpoint(
        FsmReplayApiController $controller,
        string $modelClass,
        string $modelId,
        string $columnName
    ): void {
        $this->info('🧪 Testing statistics endpoint...');

        $request = new Request([
            'model_class' => $modelClass,
            'model_id' => $modelId,
            'column_name' => $columnName,
        ]);

        $response = $controller->getStatistics($request);
        $content = $response->getContent();
        if ($content === false) {
            $this->error('Failed to get response content for statistics endpoint');

            return;
        }
        $data = json_decode($content, true);

        if (! $data['success']) {
            $this->error('Statistics endpoint failed: '.$data['error']);

            return;
        }

        $stats = $data['data'];
        $this->line("  ✅ Total transitions: {$stats['total_transitions']}");
        $this->line("  ✅ Unique states: {$stats['unique_states']}");

        if (! empty($stats['state_frequency'])) {
            $this->line('  📊 State frequencies:');
            foreach ($stats['state_frequency'] as $state => $count) {
                $this->line("    • {$state}: {$count}");
            }
        }
    }
}
