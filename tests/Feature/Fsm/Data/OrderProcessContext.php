<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Data;

use Fsm\Data\Dto;

/**
 * Rich context DTO used by behavioral tests to mimic real-world transition data.
 *
 * @property-read string $message
 * @property-read int $actorId
 * @property-read array<string, mixed> $metadata
 * @property-read string|null $approvalCode
 * @property-read bool $triggerFailure
 */
class OrderProcessContext extends Dto
{
    public string $message;

    public int $actorId;

    /** @var array<string, mixed> */
    public array $metadata;

    public ?string $approvalCode;

    public bool $triggerFailure;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        string $message,
        int $actorId,
        array $metadata = [],
        ?string $approvalCode = null,
        bool $triggerFailure = false
    ) {
        parent::__construct([
            'message' => $message,
            'actorId' => $actorId,
            'metadata' => $metadata,
            'approvalCode' => $approvalCode,
            'triggerFailure' => $triggerFailure,
        ]);
    }

    public static function from(mixed $payload): static
    {
        $attributes = is_array($payload) ? $payload : [];

        return new self(
            message: (string) ($attributes['message'] ?? ''),
            actorId: (int) ($attributes['actorId'] ?? 0),
            metadata: is_array($attributes['metadata'] ?? null) ? $attributes['metadata'] : [],
            approvalCode: isset($attributes['approvalCode']) ? (string) $attributes['approvalCode'] : null,
            triggerFailure: (bool) ($attributes['triggerFailure'] ?? false)
        );
    }
}
