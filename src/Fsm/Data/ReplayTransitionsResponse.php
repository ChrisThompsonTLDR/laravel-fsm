<?php

declare(strict_types=1);

namespace Fsm\Data;

/**
 * Response DTO for FSM transition replay API.
 *
 * Structures the response data for transition replay requests,
 * providing comprehensive replay results with initial/final states
 * and complete transition sequence.
 */
class ReplayTransitionsResponse extends Dto
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
        // Array-based initialization: new ReplayTransitionsResponse(['success' => ..., 'data' => ...])
        if (is_array($success) && func_num_args() === 1) {
            static::validateArrayForConstruction($success, ['success', 'data', 'message', 'error', 'details']);
            parent::__construct(static::prepareAttributes($success, ['message' => '']));

            return;
        }

        // Array with additional parameters: new ReplayTransitionsResponse(['success' => ...], ...)
        // Use the array data and ignore additional parameters (fixes original bug)
        if (is_array($success)) {
            static::validateArrayForConstruction($success, ['success', 'data', 'message', 'error', 'details']);
            parent::__construct(static::prepareAttributes($success, ['message' => '']));

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

    /**
     * Create a ReplayTransitionsResponse from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        static::validateArrayForConstruction($data, ['success', 'data', 'message', 'error', 'details']);

        $instance = new self(
            success: $data['success'] ?? false,
            data: $data['data'] ?? null,
            message: $data['message'] ?? null,
            error: $data['error'] ?? null,
            details: $data['details'] ?? null,
        );

        return $instance;
    }
}
