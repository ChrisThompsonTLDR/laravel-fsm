<?php

declare(strict_types=1);

namespace Fsm\Data;

/**
 * Response DTO for FSM transition history API.
 *
 * Structures the response data for transition history requests,
 * providing a consistent API format with success indicators and data.
 */
class ReplayHistoryResponse extends Dto
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $details
     */
    public bool $success;

    /** @var array<string, mixed> */
    public array $data = [];

    public string $message;

    public ?string $error = null;

    /** @var array<string, mixed>|null */
    public ?array $details = null;

    /**
     * @param  array<string, mixed>|bool  $success
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>|null  $details
     */
    public function __construct(
        bool|array $success,
        ?array $data = null,
        ?string $message = null,
        ?string $error = null,
        ?array $details = null,
    ) {
        // Array-based initialization: new ReplayHistoryResponse(['success' => ..., 'data' => ...])
        if (is_array($success) && func_num_args() === 1) {
            static::validateArrayForConstruction($success, ['success', 'data', 'message', 'error', 'details']);
            parent::__construct(static::prepareAttributes($success));

            return;
        }

        // Array with additional parameters: new ReplayHistoryResponse(['success' => ...], ...)
        // Use the array data and ignore additional parameters (fixes original bug)
        if (is_array($success)) {
            static::validateArrayForConstruction($success, ['success', 'data', 'message', 'error', 'details']);

            // Ensure message has a default value if not provided in array
            $attributes = static::prepareAttributes($success, ['message' => '']);

            parent::__construct($attributes);

            return;
        }

        parent::__construct([
            'success' => $success,
            'data' => $data ?? [],
            'message' => $message ?? '',
            'error' => $error,
            'details' => $details,
        ]);
    }
}
