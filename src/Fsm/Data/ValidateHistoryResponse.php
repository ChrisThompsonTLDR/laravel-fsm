<?php

declare(strict_types=1);

namespace Fsm\Data;

/**
 * Response DTO for FSM transition history validation API.
 *
 * Structures the response data for history validation requests,
 * providing validation results and any consistency errors found.
 */
class ValidateHistoryResponse extends Dto
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
        // Array-based initialization: new ValidateHistoryResponse(['success' => ..., 'data' => ...])
        // Use array-based construction when first parameter is an array
        if (is_array($success)) {
            static::validateArrayForConstruction($success, ['success', 'data', 'message', 'error', 'details']);
            parent::__construct(static::prepareAttributes($success));

            return;
        }

        // Named parameter initialization: new ValidateHistoryResponse(success: ..., data: ...)
        parent::__construct([
            'success' => $success,
            'data' => $data ?? [],
            'message' => $message ?? '',
            'error' => $error,
            'details' => $details,
        ]);
    }
}
