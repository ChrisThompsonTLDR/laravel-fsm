<?php

declare(strict_types=1);

namespace Fsm\Jobs;

use Fsm\Data\TransitionInput;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class RunActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string,mixed>  $parameters
     * @param  array<string,mixed>  $inputData
     */
    public function __construct(
        public string $callable,
        public array $parameters,
        public array $inputData,
    ) {}

    public function handle(): void
    {
        $modelClass = $this->inputData['model_class'];
        $model = $modelClass::find($this->inputData['model_id']);
        if (! $model) {
            \Log::warning('[FSM] Queued action skipped: model not found', [
                'model_class' => $modelClass,
                'model_id' => $this->inputData['model_id'],
                'callable' => $this->callable,
            ]);

            return;
        }

        $data = $this->inputData;
        $data['model'] = $model;
        unset($data['model_class'], $data['model_id']);

        // Capture context before deserialization for debugging
        $originalContext = $this->inputData['context'] ?? null;

        $input = TransitionInput::from($data);

        // Warn if context was lost during deserialization
        if ($originalContext !== null && $input->context === null) {
            \Log::warning('[FSM] Context was lost during queued action deserialization', [
                'model_class' => $modelClass,
                'model_id' => $model->getKey(),
                'callable' => $this->callable,
                'original_context' => $originalContext,
            ]);
        }

        // Normalize callable: convert ClassName::method to ClassName@method for App::call
        $callable = $this->callable;
        if (str_contains($callable, '::')) {
            $callable = str_replace('::', '@', $callable);
        }
        App::call($callable, array_merge($this->parameters, ['input' => $input]));
    }
}
