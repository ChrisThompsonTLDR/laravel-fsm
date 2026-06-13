<?php

declare(strict_types=1);

namespace Fsm\Support;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Fsm\Contracts\FsmDefinition;

class DefinitionDiscoverer
{
    /**
     * @param  array<int, string>  $paths
     * @return array<int, class-string<FsmDefinition>>
     */
    public static function discover(array $paths): array
    {
        $definitions = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $generator = new ClassMapGenerator;
            $generator->scanPaths($path);
            $classMap = $generator->getClassMap()->getMap();

            foreach ($classMap as $class => $file) {
                try {
                    if (! class_exists($class, false)) {
                        require_once $file;
                    }

                    if (is_subclass_of($class, FsmDefinition::class) && ! (new \ReflectionClass($class))->isAbstract()) {
                        $definitions[] = $class;
                    }
                } catch (\Throwable $e) {
                    // Skip classes that cannot be loaded due to syntax errors, missing dependencies, etc.
                    continue;
                }
            }
        }

        return array_values(array_unique($definitions));
    }
}
