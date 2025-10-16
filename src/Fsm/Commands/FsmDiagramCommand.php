<?php

declare(strict_types=1);

namespace Fsm\Commands;

use Fsm\Contracts\FsmStateEnum;
use Fsm\Data\FsmRuntimeDefinition;
use Fsm\FsmRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FsmDiagramCommand extends Command
{
    protected $signature = 'fsm:diagram {path? : Output directory} {--format=plantuml : plantuml or dot}';

    protected $description = 'Generate PlantUML or DOT diagrams for all registered FSMs.';

    public function __construct(private readonly FsmRegistry $registry)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $directoryArg = $this->argument('path');
        $directory = is_string($directoryArg) ? $directoryArg : base_path('fsm-diagrams');

        $formatOption = $this->option('format');
        $format = is_string($formatOption) ? strtolower($formatOption) : 'plantuml';
        if (! in_array($format, ['plantuml', 'dot'], true)) {
            $this->error('Format must be "plantuml" or "dot".');

            return self::FAILURE;
        }

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Use reflection to access private compiledDefinitions and get all model classes
        $ref = new \ReflectionClass($this->registry);
        $prop = $ref->getProperty('compiledDefinitions');
        $prop->setAccessible(true);
        $compiledDefinitions = $prop->getValue($this->registry);
        foreach (array_keys($compiledDefinitions) as $model) {
            $modelClass = (string) $model;
            foreach ($this->registry->getDefinitionsForModel($modelClass) as $column => $definition) {
                $content = $format === 'dot'
                    ? $this->toDot($definition)
                    : $this->toPlantUml($definition);

                $fileName = str_replace('\\', '_', $modelClass)."_{$column}.".($format === 'dot' ? 'dot' : 'puml');
                File::put($directory.DIRECTORY_SEPARATOR.$fileName, $content);
                $this->info("Generated {$fileName}");
            }
        }

        return self::SUCCESS;
    }

    private function stateName(FsmStateEnum|string|null $state): ?string
    {
        if ($state === null) {
            return null;
        }
        if ($state instanceof FsmStateEnum) {
            return $state->value;
        }

        return (string) $state;
    }

    /**
     * Helper to build edge lines for transitions using a provided formatter.
     *
     * @param  callable  $formatter  function($from, $to, $label): string
     * @return array<string>
     */
    private function buildTransitionEdges(FsmRuntimeDefinition $definition, callable $formatter): array
    {
        $edges = [];
        foreach ($definition->transitions as $transition) {
            $from = $transition->fromState === null ? null : $this->stateName($transition->fromState);
            $to = $this->stateName($transition->toState);
            $label = $transition->event ?? '';
            $labelStr = $label !== '' ? " : $label" : '';

            $edges[] = $formatter($from, $to, $label);
        }

        return $edges;
    }

    private function toPlantUml(FsmRuntimeDefinition $definition): string
    {
        $lines = ['@startuml'];
        if ($definition->initialState !== null) {
            $lines[] = '[*] --> '.$this->stateName($definition->initialState);
        }
        $edges = $this->buildTransitionEdges(
            $definition,
            function ($from, $to, $label) {
                $fromStr = $from === null ? '[*]' : $from;
                $toStr = $to === null ? '[*]' : $to; // Use [*] for terminal state
                $labelStr = $label !== '' ? " : $label" : '';

                return "$fromStr --> $toStr$labelStr";
            }
        );
        $lines = array_merge($lines, $edges);
        $lines[] = '@enduml';

        return implode(PHP_EOL, $lines);
    }

    private function toDot(FsmRuntimeDefinition $definition): string
    {
        $lines = ['digraph fsm {', '    rankdir=LR;'];
        $edges = $this->buildTransitionEdges(
            $definition,
            function ($from, $to, $label) {
                $fromStr = $from === null ? 'start' : $from;
                $toStr = $to === null ? 'end' : $to; // Use end for terminal state
                $labelStr = $label !== '' ? $label : '';

                return "    \"$fromStr\" -> \"$toStr\" [label=\"$labelStr\"]";
            }
        );
        $lines = array_merge($lines, $edges);
        $lines[] = '}';

        return implode(PHP_EOL, $lines);
    }
}
