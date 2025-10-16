<?php

declare(strict_types=1);

namespace Fsm\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

// use Symfony\Component\Console\Input\InputOption; // Keep for reference if options are added later

class MakeFsmCommand extends GeneratorCommand
{
    protected $name = 'make:fsm';

    protected $description = 'Create a new FSM definition, state enum, and feature test for a given model.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'FSM Definition'; // Used in messages, e.g., "FSM Definition created successfully."

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        // This command generates multiple files, so this method is primarily for the main FSM definition.
        return $this->resolveStubPath('fsm.definition.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @return string
     */
    protected function resolveStubPath(string $stub)
    {
        return __DIR__.'/stubs/'.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        // This is for the main FSM definition class
        return $rootNamespace.'\\Fsm';
    }

    /**
     * Get the desired FSM Definition class name from the input.
     */
    protected function getNameInput(): string
    {
        // The 'name' argument will now be the FSM descriptive name (e.g., Order, Payment)
        $name = $this->argument('name');
        if (! is_string($name)) {
            throw new \InvalidArgumentException('Name argument must be a string');
        }

        return Str::studly(trim($name)).'Fsm';
    }

    /**
     * Get the desired enum class name from the input.
     */
    protected function getEnumNameInput(): string
    {
        $name = $this->argument('name');
        if (! is_string($name)) {
            throw new \InvalidArgumentException('Name argument must be a string');
        }

        return Str::studly(trim($name)).'Status';
    }

    /**
     * Get the desired test class name from the input.
     */
    protected function getTestNameInput(): string
    {
        $name = $this->argument('name');
        if (! is_string($name)) {
            throw new \InvalidArgumentException('Name argument must be a string');
        }

        return Str::studly(trim($name)).'FsmTest';
    }

    /**
     * Get the FSM column name (lowercase version of the name argument).
     */
    protected function getFsmColumnName(): string
    {
        // This will be used for {{statusColumnPlaceholder}}
        $name = $this->argument('name');
        if (! is_string($name)) {
            throw new \InvalidArgumentException('Name argument must be a string');
        }

        return Str::snake(trim($name));
    }

    /**
     * Get the model class name and namespace details from the 'model' argument.
     */
    /**
     * @return array{string, string}
     */
    protected function getModelDetails(): array
    {
        $model = $this->argument('model');
        if (! is_string($model)) {
            throw new \InvalidArgumentException('Model argument must be a string');
        }
        $modelInput = trim($model);
        if (Str::contains($modelInput, '/')) {
            $modelInput = str_replace('/', '\\', $modelInput);
        }

        if (! Str::startsWith($modelInput, $this->laravel->getNamespace())) {
            $modelFqn = $this->laravel->getNamespace().'Models\\'.Str::studly($modelInput);
        } else {
            $modelFqn = $modelInput;
        }
        // Ensure it's fully qualified if it was a short name like "User"
        if (! class_exists($modelFqn) && class_exists($this->qualifyModel($modelInput))) {
            $modelFqn = $this->qualifyModel($modelInput);
        }

        $modelClass = class_basename($modelFqn);

        return [$modelFqn, $modelClass];
    }

    /**
     * Build the class with the given name.
     *
     * Remove the models/ FSM definition and enum specific logic from parent::buildClass().
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        [$modelFqn, $modelClass] = $this->getModelDetails();

        $stub = $this->replaceNamespace($stub, $name)
            ->replaceClass($stub, $name);

        // Custom replacements for fsm.definition.stub
        $this->replaceDefinitionPlaceholders($stub, $modelFqn, $modelClass);

        return $stub;
    }

    protected function replaceDefinitionPlaceholders(string &$stub, string $modelFqn, string $modelClass): self
    {
        $enumName = $this->getEnumNameInput();
        // The default FSM definition namespace is App\Fsm, Enum namespace is App\Enums
        $enumNamespace = $this->rootNamespace().'Enums'; // Assuming Enums are in root App\Enums
        $fsmColumnName = $this->getFsmColumnName();

        $stub = str_replace(
            [
                '{{modelFqnPlaceholder}}', '{{ modelFqnPlaceholder }}',
                '{{modelClassPlaceholder}}', '{{ modelClassPlaceholder }}',
                '{{fsmEnumClass}}', '{{ fsmEnumClass }}',
                '{{enumNamespace}}', '{{ enumNamespace }}', // For the use statement
                '{{statusColumnPlaceholder}}', '{{ statusColumnPlaceholder }}',
            ],
            [
                $modelFqn, $modelFqn,
                $modelClass, $modelClass,
                $enumName, $enumName,
                $enumNamespace, $enumNamespace,
                $fsmColumnName, $fsmColumnName,
            ],
            $stub
        );

        return $this;
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return static::FAILURE;
        }

        $this->generateEnum();
        $this->generateTest();

        // Output messages moved to individual generator methods for clarity

        return static::SUCCESS;
    }

    /**
     * Generate the State Enum class.
     */
    protected function generateEnum(): void
    {
        $enumName = $this->getEnumNameInput();
        // Enums are placed in App\Enums directory relative to app path
        $path = app_path('Enums/'.$enumName.'.php');
        $this->makeDirectory($path);

        if (! $this->option('force') && $this->alreadyExists(basename($path))) { // Check by basename in default enum path
            $this->error($enumName.' Enum already exists!');

            return;
        }

        $stub = $this->files->get($this->resolveStubPath('fsm.enum.stub'));

        $enumNamespace = $this->rootNamespace().'Enums';

        // Replace namespace and class for Enum
        $stub = str_replace(
            ['{{enumNamespace}}', '{{ enumNamespace }}', '{{fsmEnumClass}}', '{{ fsmEnumClass }}'],
            [$enumNamespace, $enumNamespace, $enumName, $enumName],
            $stub
        );

        $this->files->put($path, $this->sortImports($stub));
        $this->info($enumName.' Enum created successfully at '.$path);
    }

    /**
     * Get the base name for the enum type (e.g., 'Status').
     */
    protected function getEnumBaseName(): string
    {
        $name = $this->argument('name');
        if (! is_string($name)) {
            throw new \InvalidArgumentException('Name argument must be a string');
        }

        return Str::studly(trim($name));
    }

    /**
     * Qualify the enum class name.
     */
    protected function qualifyEnumClass(string $name): string
    {
        // This was for the old getDefaultNamespace logic, not directly used now for enums
        return $this->rootNamespace().'Enums\\'.$name;
    }

    /**
     * Generate the Feature Test class.
     */
    protected function generateTest(): void
    {
        $testName = $this->getTestNameInput();
        $path = base_path('tests/Feature/Fsm/'.$testName.'.php');
        $this->makeDirectory($path);

        if (! $this->option('force') && $this->alreadyExists(basename($path))) { // Check by basename in default test path
            $this->error($testName.' Test already exists!');

            return;
        }

        $stub = $this->files->get($this->resolveStubPath('fsm.test.stub'));

        [$modelFqn, $modelClass] = $this->getModelDetails();
        $enumName = $this->getEnumNameInput();
        $fsmDefinitionClassName = $this->getNameInput(); // e.g., PaymentFsm
        $fsmDefinitionNamespace = $this->getDefaultNamespace($this->rootNamespace()); // e.g., App\Fsm
        $enumNamespace = $this->rootNamespace().'Enums';
        $fsmColumnName = $this->getFsmColumnName();

        // Replace placeholders for Test
        $stub = str_replace(
            [
                '{{testNamespace}}', '{{ testNamespace }}',
                '{{class}}', '{{ class }}', // For the test class name itself
                '{{definitionNamespace}}', '{{ definitionNamespace }}',
                '{{fsmDefinitionClass}}', '{{ fsmDefinitionClass }}',
                '{{enumNamespace}}', '{{ enumNamespace }}',
                '{{fsmEnumClass}}', '{{ fsmEnumClass }}',
                '{{modelFqnPlaceholder}}', '{{ modelFqnPlaceholder }}',
                '{{modelClassPlaceholder}}', '{{ modelClassPlaceholder }}',
                '{{statusColumnPlaceholder}}', '{{ statusColumnPlaceholder }}',
            ],
            [
                'Tests\\Feature\\Fsm', 'Tests\\Feature\\Fsm',
                $testName, $testName,
                $fsmDefinitionNamespace, $fsmDefinitionNamespace,
                $fsmDefinitionClassName, $fsmDefinitionClassName,
                $enumNamespace, $enumNamespace,
                $enumName, $enumName,
                $modelFqn, $modelFqn,
                $modelClass, $modelClass,
                $fsmColumnName, $fsmColumnName,
            ],
            $stub
        );

        $this->files->put($path, $this->sortImports($stub));
        $this->info($testName.' Test created successfully at '.$path);
    }

    /**
     * Get the base name for the test (e.g., 'PaymentFsm').
     */
    protected function getTestBaseName(): string
    {
        $name = $this->argument('name');
        if (! is_string($name)) {
            throw new \InvalidArgumentException('Name argument must be a string');
        }

        return Str::studly(trim($name)).'Fsm';
    }

    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The descriptive name for the FSM (e.g., Payment, OrderStatus).'],
            ['model', InputArgument::REQUIRED, 'The model class this FSM applies to (e.g., Order or Models/Order).'],
        ];
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return $this->laravel->getNamespace();
    }
}
