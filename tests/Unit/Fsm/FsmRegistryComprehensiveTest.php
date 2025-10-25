<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm;

use Fsm\Data\FsmRuntimeDefinition;
use Fsm\FsmRegistry;
use Fsm\Services\BootstrapDetector;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Fsm\Models\TestModel;

class FsmRegistryComprehensiveTest extends TestCase
{
    private FsmRegistry $registry;

    private BootstrapDetector $bootstrapDetector;

    private ConfigRepository $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootstrapDetector = $this->createMock(BootstrapDetector::class);
        $this->config = $this->createMock(ConfigRepository::class);

        // Mock config to avoid facade issues
        $this->config->method('get')->willReturn(false);

        $this->registry = new FsmRegistry($this->bootstrapDetector, $this->config);
    }

    public function test_constructor_initializes_properties(): void
    {
        $this->assertInstanceOf(FsmRegistry::class, $this->registry);
    }

    public function test_register_definition_adds_to_collection(): void
    {
        $definition = $this->createMock(FsmRuntimeDefinition::class);

        $this->registry->registerDefinition(TestModel::class, 'status', $definition);

        $result = $this->registry->getDefinition(TestModel::class, 'status');
        $this->assertSame($definition, $result);
    }

    public function test_register_definition_overwrites_existing(): void
    {
        $definition1 = $this->createMock(FsmRuntimeDefinition::class);
        $definition2 = $this->createMock(FsmRuntimeDefinition::class);

        $this->registry->registerDefinition(TestModel::class, 'status', $definition1);
        $this->registry->registerDefinition(TestModel::class, 'status', $definition2);

        $result = $this->registry->getDefinition(TestModel::class, 'status');
        $this->assertSame($definition2, $result);
    }

    public function test_get_definition_returns_correct_definition(): void
    {
        $definition = $this->createMock(FsmRuntimeDefinition::class);

        $this->registry->registerDefinition(TestModel::class, 'status', $definition);

        $result = $this->registry->getDefinition(TestModel::class, 'status');

        $this->assertSame($definition, $result);
    }

    public function test_get_definition_returns_null_for_nonexistent_column(): void
    {
        $definition = $this->createMock(FsmRuntimeDefinition::class);
        $this->registry->registerDefinition(TestModel::class, 'status', $definition);

        $result = $this->registry->getDefinition(TestModel::class, 'other_column');

        $this->assertNull($result);
    }

    public function test_get_definition_returns_null_for_nonexistent_model(): void
    {
        $definition = $this->createMock(FsmRuntimeDefinition::class);
        $this->registry->registerDefinition(TestModel::class, 'status', $definition);

        $result = $this->registry->getDefinition('OtherModel', 'status');

        $this->assertNull($result);
    }

    public function test_get_definition_handles_case_sensitive_model_names(): void
    {
        $definition = $this->createMock(FsmRuntimeDefinition::class);

        $this->registry->registerDefinition(TestModel::class, 'status', $definition);

        // Should not find with different case
        $result = $this->registry->getDefinition('testmodel', 'status');
        $this->assertNull($result);

        // Should find with exact case
        $result = $this->registry->getDefinition(TestModel::class, 'status');
        $this->assertSame($definition, $result);
    }

    public function test_get_definition_handles_case_sensitive_column_names(): void
    {
        $definition = $this->createMock(FsmRuntimeDefinition::class);

        $this->registry->registerDefinition(TestModel::class, 'status', $definition);

        // Should not find with different case
        $result = $this->registry->getDefinition(TestModel::class, 'Status');
        $this->assertNull($result);

        // Should find with exact case
        $result = $this->registry->getDefinition(TestModel::class, 'status');
        $this->assertSame($definition, $result);
    }

    public function test_register_definition_handles_empty_model_class(): void
    {
        $definition = $this->createMock(FsmRuntimeDefinition::class);

        $this->registry->registerDefinition('', 'status', $definition);

        $result = $this->registry->getDefinition('', 'status');

        $this->assertSame($definition, $result);
    }

    public function test_register_definition_handles_empty_column_name(): void
    {
        $definition = $this->createMock(FsmRuntimeDefinition::class);

        $this->registry->registerDefinition(TestModel::class, '', $definition);

        $result = $this->registry->getDefinition(TestModel::class, '');

        $this->assertSame($definition, $result);
    }

    public function test_register_definition_preserves_existing_definitions_when_overwriting(): void
    {
        $definition1 = $this->createMock(FsmRuntimeDefinition::class);
        $definition2 = $this->createMock(FsmRuntimeDefinition::class);

        $this->registry->registerDefinition(TestModel::class, 'status', $definition1);
        $this->registry->registerDefinition(TestModel::class, 'other', $definition2);
        $this->registry->registerDefinition(TestModel::class, 'status', $definition1); // Overwrite

        $result1 = $this->registry->getDefinition(TestModel::class, 'status');
        $result2 = $this->registry->getDefinition(TestModel::class, 'other');

        $this->assertSame($definition1, $result1);
        $this->assertSame($definition2, $result2);
    }

    public function test_registry_handles_large_number_of_definitions(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $definition = $this->createMock(FsmRuntimeDefinition::class);
            $this->registry->registerDefinition("Model{$i}", "column{$i}", $definition);

            $result = $this->registry->getDefinition("Model{$i}", "column{$i}");
            $this->assertSame($definition, $result);
        }

        // Verify some random ones
        $result1 = $this->registry->getDefinition('Model50', 'column50');
        $result2 = $this->registry->getDefinition('Model99', 'column99');

        $this->assertNotNull($result1);
        $this->assertNotNull($result2);
    }

    public function test_registry_handles_complex_model_names(): void
    {
        $complexNames = [
            'App\Models\User',
            'App\Models\Order\Item',
            'Modules\Inventory\Models\Product',
            'Acme\Package\Models\CustomModel',
        ];

        foreach ($complexNames as $modelName) {
            $definition = $this->createMock(FsmRuntimeDefinition::class);
            $this->registry->registerDefinition($modelName, 'status', $definition);

            $result = $this->registry->getDefinition($modelName, 'status');
            $this->assertSame($definition, $result);
        }
    }

    public function test_registry_handles_complex_column_names(): void
    {
        $complexColumns = [
            'status',
            'workflow_status',
            'order_status',
            'payment_status',
            'shipping_status',
            'custom_workflow_state',
        ];

        foreach ($complexColumns as $columnName) {
            $definition = $this->createMock(FsmRuntimeDefinition::class);
            $this->registry->registerDefinition(TestModel::class, $columnName, $definition);

            $result = $this->registry->getDefinition(TestModel::class, $columnName);
            $this->assertSame($definition, $result);
        }
    }

    public function test_registry_handles_repeated_registration_of_same_definition(): void
    {
        $definition = $this->createMock(FsmRuntimeDefinition::class);

        $this->registry->registerDefinition(TestModel::class, 'status', $definition);
        $this->registry->registerDefinition(TestModel::class, 'status', $definition);
        $this->registry->registerDefinition(TestModel::class, 'status', $definition);

        $result = $this->registry->getDefinition(TestModel::class, 'status');

        $this->assertSame($definition, $result);
    }
}
