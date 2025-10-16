<?php

declare(strict_types=1);

namespace Fsm\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * Event fired when a state transition has successfully completed.
 *
 * This event indicates that:
 * - All guards have passed
 * - The model's state has been persisted to the database
 * - All pre-transition callbacks and actions have executed
 *
 * Note: This event is NOT fired during dry-run validations (canTransition/dryRunTransition).
 * Dry runs only validate guards without changing state or firing success events.
 */
class TransitionSucceeded
{
    public function __construct(
        public Model $model,
        public string $columnName,
        public ?string $fromState,
        public string $toState,
    ) {}
}
