<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Data;

use Fsm\Data\Dto;

class TestContextData extends Dto
{
    public string $message;

    public ?int $userId = null;

    public bool $triggerFailure = false; // For testing callback failures

    /**
     * @param  array<string, mixed>|string  $message
     */
    public function __construct(string|array $message, ?int $userId = null, bool $triggerFailure = false)
    {
        if (is_array($message) && func_num_args() === 1) {
            $expectedKeys = ['message', 'userId', 'triggerFailure'];

            // If it's a callable array, treat it as positional parameter
            if (static::isCallableArray($message)) {
                throw new \InvalidArgumentException('Callable arrays are not valid for TestContextData construction.');
            }

            // If it's a DTO property array, use it for construction
            if (static::isDtoPropertyArray($message, $expectedKeys)) {
                parent::__construct(static::prepareAttributes($message));

                return;
            }

            // If it's not a DTO property array, it's invalid
            throw new \InvalidArgumentException('Array parameter must be an associative array with DTO property keys used as single argument for array-based construction.');
        }

        parent::__construct([
            'message' => $message,
            'userId' => $userId,
            'triggerFailure' => $triggerFailure,
        ]);
    }
}
