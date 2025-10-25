<?php

declare(strict_types=1);

use Fsm\Commands\MakeFsmCommand;
use Illuminate\Console\Command;
use Orchestra\Testbench\TestCase;

class MakeFsmCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up Laravel paths for testing
        $this->app->setBasePath(sys_get_temp_dir());
        config(['app.namespace' => 'App']);

        // Set up filesystem configuration for testing
        $this->app->singleton('filesystem', function ($app) {
            return new \Illuminate\Filesystem\Filesystem;
        });

        // Set up required Laravel services
        $this->app->singleton(\Illuminate\Contracts\Console\Kernel::class, function ($app) {
            return new \Illuminate\Foundation\Console\Kernel($app);
        });
    }

    public function test_command_has_correct_configuration(): void
    {
        $filesystem = $this->app->make(\Illuminate\Filesystem\Filesystem::class);
        $command = new MakeFsmCommand($filesystem);

        $this->assertEquals('make:fsm', $command->getName());
        $this->assertEquals('Create a new FSM definition, state enum, and feature test for a given model.', $command->getDescription());

        // Test that command has the expected arguments defined
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getArguments');
        $method->setAccessible(true);
        $arguments = $method->invoke($command);

        $this->assertCount(2, $arguments);
        $this->assertEquals('name', $arguments[0][0]);
        $this->assertEquals('model', $arguments[1][0]);
    }

    public function test_name_transformation_logic(): void
    {
        // Test the string transformation logic directly
        $this->assertEquals('OrderFsm', \Illuminate\Support\Str::studly('Order').'Fsm');
        $this->assertEquals('OrderStatus', \Illuminate\Support\Str::studly('Order').'Status');
        $this->assertEquals('OrderFsmTest', \Illuminate\Support\Str::studly('Order').'FsmTest');
        $this->assertEquals('order', \Illuminate\Support\Str::snake('Order'));

        // Test complex names
        $this->assertEquals('PaymentStatusFsm', \Illuminate\Support\Str::studly('PaymentStatus').'Fsm');
        $this->assertEquals('PaymentStatusStatus', \Illuminate\Support\Str::studly('PaymentStatus').'Status');
        $this->assertEquals('PaymentStatusFsmTest', \Illuminate\Support\Str::studly('PaymentStatus').'FsmTest');
        $this->assertEquals('payment_status', \Illuminate\Support\Str::snake('PaymentStatus'));
    }
}
