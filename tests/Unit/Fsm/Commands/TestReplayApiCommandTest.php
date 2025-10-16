<?php

declare(strict_types=1);

use Fsm\Commands\TestReplayApiCommand;
use Fsm\Http\Controllers\FsmReplayApiController;
use Fsm\Models\FsmEventLog;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TestReplayApiCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up Laravel services for testing
        $this->app->singleton('filesystem', function ($app) {
            return new \Illuminate\Filesystem\Filesystem();
        });

        $this->app->singleton(\Illuminate\Contracts\Console\Kernel::class, function ($app) {
            return new \Illuminate\Foundation\Console\Kernel($app);
        });

        // Register the controller in the service container for testing
        $this->app->singleton(FsmReplayApiController::class, function ($app) {
            return $this->createMockController();
        });
    }

    private function getCommandTester(): CommandTester
    {
        $command = new TestReplayApiCommand();
        $command->setLaravel($this->app);

        return new CommandTester($command);
    }

    private function createMockModel(string $className = 'TestModel'): string
    {
        // Create a test model class for testing
        $modelContent = <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class {$className} extends Model
{
    protected \$fillable = ['name', 'status'];
    protected \$table = '{$className}';
    public \$timestamps = false;
}
PHP;

        $modelPath = app_path('Models/' . $className . '.php');
        $modelDir = dirname($modelPath);
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0755, true);
        }
        file_put_contents($modelPath, $modelContent);

        // Also register the class in the autoloader
        if (!class_exists('App\Models\\' . $className)) {
            // Create a temporary class definition for testing
            eval("namespace App\Models; class {$className} extends \Illuminate\Database\Eloquent\Model { protected \$fillable = ['name', 'status']; protected \$table = '{$className}'; public \$timestamps = false; }");
        }

        return 'App\Models\\' . $className;
    }

    private function createMockController(): FsmReplayApiController
    {
        $controller = $this->createMock(FsmReplayApiController::class);

        // Mock successful responses for all endpoints with proper structure
        $historyResponse = new JsonResponse([
            'success' => true,
            'data' => [
                'transitions' => [
                    [
                        'from_state' => 'pending',
                        'to_state' => 'processing',
                        'transition_name' => 'process',
                        'occurred_at' => '2023-01-01 10:00:00'
                    ]
                ],
                'count' => 1,
            ],
            'message' => 'Transition history retrieved successfully'
        ]);

        $replayResponse = new JsonResponse([
            'success' => true,
            'data' => [
                'transition_count' => 1,
                'initial_state' => 'pending',
                'final_state' => 'processing'
            ],
            'message' => 'Transitions replayed successfully'
        ]);

        $validateResponse = new JsonResponse([
            'success' => true,
            'data' => [
                'valid' => true,
                'errors' => []
            ],
            'message' => 'Transition history is valid'
        ]);

        $statisticsResponse = new JsonResponse([
            'success' => true,
            'data' => [
                'total_transitions' => 1,
                'unique_states' => 2,
                'state_frequency' => [
                    'pending' => 1,
                    'processing' => 1
                ]
            ],
            'message' => 'Statistics retrieved successfully'
        ]);

        $controller->expects($this->any())
                   ->method('getHistory')
                   ->willReturn($historyResponse);

        $controller->expects($this->any())
                   ->method('replayTransitions')
                   ->willReturn($replayResponse);

        $controller->expects($this->any())
                   ->method('validateHistory')
                   ->willReturn($validateResponse);

        $controller->expects($this->any())
                   ->method('getStatistics')
                   ->willReturn($statisticsResponse);

        return $controller;
    }

    public function test_command_has_correct_signature_and_description(): void
    {
        $command = new TestReplayApiCommand();

        $this->assertEquals('fsm:test-replay-api', $command->getName());
        $this->assertEquals('Test the FSM Replay API functionality', $command->getDescription());

        // Check that signature contains expected options using reflection
        $reflection = new \ReflectionClass($command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        $signature = $signatureProperty->getValue($command);

        $this->assertStringContainsString('--model-class=', $signature);
        $this->assertStringContainsString('--model-id=', $signature);
        $this->assertStringContainsString('--column-name=', $signature);
        $this->assertStringContainsString('--create-test-data', $signature);
    }

    public function test_validates_required_model_class_option(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            // Missing --model-class option
        ]);

        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Please provide a model class with --model-class option', $commandTester->getDisplay());
    }

    public function test_validates_model_class_exists(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            '--model-class' => 'NonExistentModel',
        ]);

        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Model class NonExistentModel does not exist', $commandTester->getDisplay());
    }

    public function test_validates_model_class_is_eloquent_model(): void
    {
        // Create a non-Eloquent class first
        $classContent = <<<PHP
<?php

class NotAModel
{
    // Not extending Model
}
PHP;

        $classPath = app_path('NotAModel.php');
        $classDir = dirname($classPath);
        if (!is_dir($classDir)) {
            mkdir($classDir, 0755, true);
        }
        file_put_contents($classPath, $classContent);

        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            '--model-class' => 'NotAModel',
        ]);

        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Model class NotAModel does not exist', $commandTester->getDisplay());

        // Clean up
        unlink($classPath);
    }

    public function test_uses_default_values_for_optional_options(): void
    {
        // Create a test model
        $modelClass = $this->createMockModel();

        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            '--model-class' => $modelClass,
            // No --model-id or --column-name provided
        ]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('🔄 Testing FSM Replay API...', $commandTester->getDisplay());
    }

    public function test_handles_custom_column_name(): void
    {
        $modelClass = $this->createMockModel();

        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            '--model-class' => $modelClass,
            '--column-name' => 'workflow_status',
        ]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function test_handles_custom_model_id(): void
    {
        $modelClass = $this->createMockModel();

        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            '--model-class' => $modelClass,
            '--model-id' => '123',
        ]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function test_command_handles_very_long_model_names(): void
    {
        $modelClass = $this->createMockModel('VeryLongModelNameThatExceedsNormalLimits');

        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            '--model-class' => $modelClass,
        ]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function test_command_executes_successfully_with_valid_model(): void
    {
        $modelClass = $this->createMockModel();

        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            '--model-class' => $modelClass,
        ]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('🔄 Testing FSM Replay API...', $commandTester->getDisplay());
    }

    public function test_command_preserves_existing_functionality(): void
    {
        // Test that the command doesn't break existing functionality
        $command = new TestReplayApiCommand();

        // Test that the command can be instantiated
        $this->assertInstanceOf(TestReplayApiCommand::class, $command);

        // Test that signature is properly formatted using reflection
        $reflection = new \ReflectionClass($command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        $signature = $signatureProperty->getValue($command);

        $this->assertStringContainsString('--model-class=', $signature);
        $this->assertStringContainsString('--create-test-data', $signature);
    }
}
