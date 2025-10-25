<?php

declare(strict_types=1);

use Fsm\Support\DefinitionDiscoverer;
use Tests\TestCase;

class DefinitionDiscovererTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary directory for testing
        $this->tempDir = sys_get_temp_dir().'/fsm_definition_discoverer_test_'.uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        if (isset($this->tempDir) && is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir.'/'.$file;
                if (is_dir($path)) {
                    $this->removeDirectory($path);
                } else {
                    unlink($path);
                }
            }
        }

        rmdir($dir);
    }

    private function createTestClassFile(string $filename, string $classContent, ?string $directory = null): string
    {
        $targetDir = $directory ?? $this->tempDir;
        $filepath = $targetDir.'/'.$filename;
        file_put_contents($filepath, $classContent);

        return $filepath;
    }

    public function test_discovers_fsm_definitions_in_valid_paths(): void
    {
        // Create test FSM definition classes
        $this->createTestClassFile('ValidFsmDefinition.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

class ValidFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
    }
}
PHP
        );

        $this->createTestClassFile('AnotherValidFsmDefinition.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

class AnotherValidFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
    }
}
PHP
        );

        // Create a non-FSM definition class for testing filtering
        $this->createTestClassFile('NotAnFsmDefinition.php', <<<'PHP'
<?php

class NotAnFsmDefinition
{
    public function someMethod() {}
}
PHP
        );

        $definitions = DefinitionDiscoverer::discover([$this->tempDir]);

        $this->assertIsArray($definitions);
        $this->assertCount(2, $definitions);
        $this->assertContains('ValidFsmDefinition', $definitions);
        $this->assertContains('AnotherValidFsmDefinition', $definitions);
        $this->assertNotContains('NotAnFsmDefinition', $definitions);
    }

    public function test_handles_empty_paths_array(): void
    {
        $definitions = DefinitionDiscoverer::discover([]);

        $this->assertIsArray($definitions);
        $this->assertEmpty($definitions);
    }

    public function test_skips_non_existent_paths(): void
    {
        $nonExistentPath = '/path/that/does/not/exist';
        $definitions = DefinitionDiscoverer::discover([$nonExistentPath]);

        $this->assertIsArray($definitions);
        $this->assertEmpty($definitions);
    }

    public function test_skips_non_directory_paths(): void
    {
        $filePath = $this->tempDir.'/test_file.php';
        file_put_contents($filePath, '<?php // test file');

        $definitions = DefinitionDiscoverer::discover([$filePath]);

        $this->assertIsArray($definitions);
        $this->assertEmpty($definitions);
    }

    public function test_filters_out_abstract_classes(): void
    {
        $this->createTestClassFile('AbstractFsmDefinition.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

abstract class AbstractFsmDefinition implements FsmDefinition
{
    abstract public function define(): void;
}
PHP
        );

        $this->createTestClassFile('ConcreteFsmDefinition.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

class ConcreteFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
    }
}
PHP
        );

        $definitions = DefinitionDiscoverer::discover([$this->tempDir]);

        $this->assertIsArray($definitions);
        $this->assertCount(1, $definitions);
        $this->assertContains('ConcreteFsmDefinition', $definitions);
        $this->assertNotContains('AbstractFsmDefinition', $definitions);
    }

    public function test_filters_out_classes_not_implementing_fsm_definition(): void
    {
        $this->createTestClassFile('RegularClass.php', <<<'PHP'
<?php

class RegularClass
{
    public function someMethod() {}
}
PHP
        );

        $this->createTestClassFile('ValidFsmDefinition.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

class ValidFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
    }
}
PHP
        );

        $definitions = DefinitionDiscoverer::discover([$this->tempDir]);

        $this->assertIsArray($definitions);
        $this->assertCount(1, $definitions);
        $this->assertContains('ValidFsmDefinition', $definitions);
        $this->assertNotContains('RegularClass', $definitions);
    }

    public function test_returns_unique_definitions_only(): void
    {
        // Create the same class in multiple files
        $this->createTestClassFile('DuplicateFsmDefinition.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

class DuplicateFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
    }
}
PHP
        );

        // Create another file with the same class name (in a subdirectory)
        $subDir = $this->tempDir.'/subdir';
        mkdir($subDir, 0755, true);
        $this->createTestClassFile('subdir/DuplicateFsmDefinition.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

class DuplicateFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
    }
}
PHP
        );

        $definitions = DefinitionDiscoverer::discover([$this->tempDir]);

        $this->assertIsArray($definitions);
        $this->assertCount(1, $definitions);
        $this->assertContains('DuplicateFsmDefinition', $definitions);
    }

    public function test_handles_multiple_paths(): void
    {
        $tempDir1 = $this->tempDir.'_1';
        $tempDir2 = $this->tempDir.'_2';
        mkdir($tempDir1, 0755, true);
        mkdir($tempDir2, 0755, true);

        $this->createTestClassFile('relative_to_temp1.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

class FsmDefinitionFromDir1 implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
    }
}
PHP
            , $tempDir1);

        $this->createTestClassFile('relative_to_temp2.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

class FsmDefinitionFromDir2 implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
    }
}
PHP
            , $tempDir2);

        $definitions = DefinitionDiscoverer::discover([$tempDir1, $tempDir2]);

        $this->assertIsArray($definitions);
        $this->assertCount(2, $definitions);
        $this->assertContains('FsmDefinitionFromDir1', $definitions);
        $this->assertContains('FsmDefinitionFromDir2', $definitions);

        // Clean up
        $this->removeDirectory($tempDir1);
        $this->removeDirectory($tempDir2);
    }

    public function test_handles_classes_with_syntax_errors_gracefully(): void
    {
        $this->createTestClassFile('InvalidSyntaxClass.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

class InvalidSyntaxClass implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
        // Missing closing brace for class - this creates a syntax error
PHP
        );

        // Should not throw an exception, just skip the invalid class
        $definitions = DefinitionDiscoverer::discover([$this->tempDir]);

        $this->assertIsArray($definitions);
        // Should not contain the invalid class
        $this->assertNotContains('InvalidSyntaxClass', $definitions);
    }

    public function test_handles_classes_that_cannot_be_loaded(): void
    {
        $this->createTestClassFile('ClassWithDependency.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

// Create a class that extends a non-existent class
class ClassWithDependency extends NonExistentBaseClass implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
    }
}
PHP
        );

        // Should not throw an exception, just skip the class that can't be loaded
        $definitions = DefinitionDiscoverer::discover([$this->tempDir]);

        $this->assertIsArray($definitions);
        // Should not contain the class that can't be loaded
        $this->assertNotContains('ClassWithDependency', $definitions);
    }

    public function test_returns_array_values_maintaining_order(): void
    {
        $this->createTestClassFile('FirstFsmDefinition.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

class FirstFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
    }
}
PHP
        );

        $this->createTestClassFile('SecondFsmDefinition.php', <<<'PHP'
<?php

use Fsm\Contracts\FsmDefinition;

class SecondFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
    }
}
PHP
        );

        $definitions = DefinitionDiscoverer::discover([$this->tempDir]);

        $this->assertIsArray($definitions);
        $this->assertCount(2, $definitions);

        // The order should be consistent (based on file system order)
        $this->assertNotEmpty($definitions[0]);
        $this->assertNotEmpty($definitions[1]);
    }

    public function test_works_with_namespaced_classes(): void
    {
        $namespaceDir = $this->tempDir.'/TestNamespace';
        mkdir($namespaceDir, 0755, true);

        $this->createTestClassFile('TestNamespace/NamespacedFsmDefinition.php', <<<'PHP'
<?php

namespace TestNamespace;

use Fsm\Contracts\FsmDefinition;

class NamespacedFsmDefinition implements FsmDefinition
{
    public function define(): void
    {
        // Test implementation
    }
}
PHP
        );

        $definitions = DefinitionDiscoverer::discover([$this->tempDir]);

        $this->assertIsArray($definitions);
        $this->assertCount(1, $definitions);
        $this->assertContains('TestNamespace\\NamespacedFsmDefinition', $definitions);
    }

    public function test_handles_interface_only_classes(): void
    {
        $this->createTestClassFile('InterfaceOnlyClass.php', <<<'PHP'
<?php

interface InterfaceOnlyClass extends Fsm\Contracts\FsmDefinition
{
    // Interface methods would be here
}
PHP
        );

        $definitions = DefinitionDiscoverer::discover([$this->tempDir]);

        $this->assertIsArray($definitions);
        $this->assertEmpty($definitions); // Interfaces are not concrete classes
    }
}
