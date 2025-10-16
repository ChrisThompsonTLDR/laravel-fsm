<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Data;

use Fsm\Data\Dto;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

/**
 * Test context DTO that intentionally fails serialization to test error handling.
 */
class FailingContextDto extends Dto implements ArgonautDTOContract
{
    public string $message;

    public function __construct(string $message)
    {
        parent::__construct(['message' => $message]);
    }

    /**
     * Intentionally throw an exception during toArray() to test error handling.
     */
    public function toArray(int $depth = 3): array
    {
        throw new \RuntimeException('Intentional serialization failure for testing');
    }
}
