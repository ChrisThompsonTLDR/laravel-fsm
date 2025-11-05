<?php

declare(strict_types=1);

namespace Fsm\Listeners;

use Fsm\Events\StateTransitioned;
use Fsm\Models\FsmEventLog;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Event listener to persist StateTransitioned events to the database.
 *
 * This listener can optionally be queued for better performance in high-throughput scenarios.
 */
class PersistStateTransitionedEvent
{
    public function __construct(
        private readonly ConfigRepository $config
    ) {}

    /**
     * Handle the StateTransitioned event.
     */
    public function handle(StateTransitioned $event): void
    {
        // Check if event logging is enabled
        if (! $this->config->get('fsm.event_logging.enabled', true)) {
            return;
        }

        try {
            FsmEventLog::create([
                'model_id' => (string) $event->model->getKey(),
                'model_type' => $event->model->getMorphClass(),
                'column_name' => $event->columnName,
                'from_state' => $event->fromState,
                'to_state' => $event->toState,
                'transition_name' => $event->transitionName,
                'occurred_at' => $event->timestamp,
                'context' => $event->context?->toArray(),
                'metadata' => $event->metadata,
            ]);
        } catch (\Throwable $e) {
            // Log the error but don't fail the transition
            // Wrap report() in try-catch to prevent it from throwing in test environments
            try {
                report($e);
            } catch (\Throwable $reportException) {
                // Silently ignore if report() itself throws (e.g., in test environments)
            }
        }
    }
}
