<?php

declare(strict_types=1);

namespace Fsm\Commands;

use Illuminate\Console\Command;

/**
 * Command to publish modular FSM configuration and examples.
 */
class PublishModularConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fsm:publish-modular 
                            {--config : Publish the modular configuration}
                            {--examples : Publish example extension classes}
                            {--all : Publish both config and examples}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish modular FSM configuration and example extension classes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $publishConfig = $this->option('config') || $this->option('all');
        $publishExamples = $this->option('examples') || $this->option('all');

        if (! $publishConfig && ! $publishExamples) {
            $this->info('Please specify what to publish: --config, --examples, or --all');

            return self::INVALID;
        }

        if ($publishConfig) {
            $this->publishConfig();
        }

        if ($publishExamples) {
            $this->publishExamples();
        }

        return self::SUCCESS;
    }

    /**
     * Publish the modular configuration file.
     */
    private function publishConfig(): void
    {
        $configPath = config_path('fsm-modular.php');

        if (file_exists($configPath)) {
            if (! $this->confirm("Config file {$configPath} already exists. Overwrite?")) {
                $this->info('Skipped publishing modular config.');

                return;
            }
        }

        $stubContent = $this->getModularConfigStub();
        file_put_contents($configPath, $stubContent);

        $this->info("Published modular FSM config to: {$configPath}");
    }

    /**
     * Publish example extension classes.
     */
    private function publishExamples(): void
    {
        $examplesPath = app_path('Fsm/Extensions');

        if (! is_dir($examplesPath)) {
            if (! mkdir($examplesPath, 0755, true)) {
                throw new \RuntimeException("Failed to create directory: {$examplesPath}. Check filesystem permissions.");
            }
        }

        $examples = [
            'ExampleFsmExtension.php' => $this->getExampleExtensionStub(),
            'ExampleStateDefinition.php' => $this->getExampleStateDefinitionStub(),
            'ExampleTransitionDefinition.php' => $this->getExampleTransitionDefinitionStub(),
        ];

        foreach ($examples as $filename => $content) {
            $filePath = $examplesPath.DIRECTORY_SEPARATOR.$filename;

            if (file_exists($filePath)) {
                if (! $this->confirm("File {$filePath} already exists. Overwrite?")) {
                    continue;
                }
            }

            file_put_contents($filePath, $content);
            $this->info("Published example: {$filePath}");
        }
    }

    /**
     * Get the modular configuration stub.
     */
    private function getModularConfigStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | FSM Modular Configuration
    |--------------------------------------------------------------------------
    |
    | This file demonstrates how to use modular FSM definitions to extend
    | or override existing FSM configurations without modifying the original
    | definition classes.
    |
    */

    /*
    | FSM Extensions
    |
    | Register extension classes that implement the FsmExtension interface.
    | Extensions can add states, transitions, or modify existing ones.
    */
    'extensions' => [
        // App\Fsm\Extensions\ExampleFsmExtension::class,
    ],

    /*
    | State Overrides
    |
    | Override specific state definitions for FSMs.
    | Structure: [ModelClass => [columnName => [stateName => config]]]
    */
    'state_overrides' => [
        // App\Models\Order::class => [
        //     'status' => [
        //         'pending' => [
        //             'override' => true,
        //             'priority' => 100,
        //             'definition' => [
        //                 'description' => 'Enhanced pending state with timeout',
        //                 'type' => 'initial',
        //                 'metadata' => [
        //                     'timeout_hours' => 48,
        //                     'auto_cancel' => true,
        //                 ],
        //             ],
        //         ],
        //         'escalated' => [
        //             'override' => false, // Add new state
        //             'priority' => 50,
        //             'definition' => [
        //                 'description' => 'Order requires manager approval',
        //                 'type' => 'intermediate',
        //                 'category' => 'special_handling',
        //                 'metadata' => [
        //                     'requires_approval' => true,
        //                     'escalation_level' => 1,
        //                 ],
        //             ],
        //         ],
        //     ],
        // ],
    ],

    /*
    | Transition Overrides
    |
    | Override or add transition definitions for FSMs.
    | Structure: [ModelClass => [columnName => [array of transition configs]]]
    */
    'transition_overrides' => [
        // App\Models\Order::class => [
        //     'status' => [
        //         [
        //             'from' => 'pending',
        //             'to' => 'confirmed',
        //             'event' => 'confirm',
        //             'override' => true,
        //             'priority' => 100,
        //             'definition' => [
        //                 'description' => 'Enhanced confirmation with fraud detection',
        //                 'guards' => [
        //                     'fraud_check',
        //                     'payment_validation',
        //                 ],
        //                 'actions' => [
        //                     'log_confirmation',
        //                     'send_fraud_alert_if_needed',
        //                 ],
        //             ],
        //         ],
        //         [
        //             'from' => 'pending',
        //             'to' => 'escalated',
        //             'event' => 'escalate',
        //             'override' => false, // Add new transition
        //             'priority' => 50,
        //             'definition' => [
        //                 'description' => 'Escalate order for manager review',
        //                 'guards' => ['escalation_required'],
        //                 'actions' => ['notify_manager'],
        //             ],
        //         ],
        //     ],
        // ],
    ],
];
PHP;
    }

    /**
     * Get the example extension stub.
     */
    private function getExampleExtensionStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Fsm\Extensions;

use App\Models\Order;
use Fsm\Contracts\FsmExtension;
use Fsm\FsmBuilder;

/**
 * Example FSM extension that adds manager approval workflow to orders.
 */
class ExampleFsmExtension implements FsmExtension
{
namespace App\Fsm\Extensions;

use App\Models\Order;
use Fsm\Contracts\FsmExtension;
use Fsm\TransitionBuilder;
    {
        // Add a new state for manager approval
        $builder->state('manager_review', function ($state) {
            return $state
                ->description('Order requires manager approval')
                ->type('intermediate')
                ->category('review')
                ->metadata([
                    'requires_manager' => true,
                    'timeout_hours' => 24,
                ]);
        });

        // Add transition from pending to manager review
        $builder->transition('pending', 'manager_review')
            ->event('escalate')
            ->description('Escalate order for manager review')
            ->guards(['escalation_required'])
            ->actions(['notify_manager']);

        // Add transition from manager review to confirmed
        $builder->transition('manager_review', 'confirmed')
            ->event('approve')
            ->description('Manager approves the order')
            ->guards(['manager_approval'])
            ->actions(['log_manager_approval']);
    }

    public function appliesTo(string $modelClass, string $columnName): bool
    {
        return $modelClass === Order::class && $columnName === 'status';
    }

    public function getPriority(): int
    {
        return 100;
    }

    public function getName(): string
    {
        return 'order_manager_approval';
    }
}
PHP;
    }

    /**
     * Get the example state definition stub.
     */
    private function getExampleStateDefinitionStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Fsm\Extensions;

use Fsm\Contracts\FsmStateEnum;
use Fsm\Contracts\ModularStateDefinition;

/**
 * Example modular state definition.
 */
class ExampleStateDefinition implements ModularStateDefinition
{
    public function __construct(
        private readonly string|FsmStateEnum $stateName,
        private readonly array $definition,
        private readonly bool $override = false,
        private readonly int $priority = 50
    ) {}

    public function getStateName(): string|FsmStateEnum
    {
        return $this->stateName;
    }

    public function getDefinition(): array
    {
        return $this->definition;
    }

    public function shouldOverride(): bool
    {
        return $this->override;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
PHP;
    }

    /**
     * Get the example transition definition stub.
     */
    private function getExampleTransitionDefinitionStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Fsm\Extensions;

use Fsm\Contracts\FsmStateEnum;
use Fsm\Contracts\ModularTransitionDefinition;

/**
 * Example modular transition definition.
 */
class ExampleTransitionDefinition implements ModularTransitionDefinition
{
    public function __construct(
        private readonly string|FsmStateEnum|null $fromState,
        private readonly string|FsmStateEnum $toState,
        private readonly string $event,
        private readonly array $definition,
        private readonly bool $override = false,
        private readonly int $priority = 50
    ) {}

    public function getFromState(): string|FsmStateEnum|null
    {
        return $this->fromState;
    }

    public function getToState(): string|FsmStateEnum
    {
        return $this->toState;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getDefinition(): array
    {
        return $this->definition;
    }

    public function shouldOverride(): bool
    {
        return $this->override;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
PHP;
    }
}
