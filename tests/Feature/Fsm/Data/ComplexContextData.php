<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Data;

use Fsm\Data\Dto;

class NestedContextData extends Dto
{
    public string $secret;

    public string $public;

    /**
     * @param  array<string, mixed>|string  $secret
     */
    public function __construct(string|array $secret, ?string $public = null)
    {
        if (is_array($secret) && func_num_args() === 1 && static::isAssociative($secret)) {
            parent::__construct($secret);

            return;
        }

        parent::__construct([
            'secret' => $secret,
            'public' => $public ?? '',
        ]);
    }
}

class ComplexContextData extends Dto
{
    public string $message;

    public ?int $userId;

    public NestedContextData $extra;

    protected array $casts = [
        'extra' => NestedContextData::class,
    ];

    /**
     * @param  array<string, mixed>|string  $message
     */
    public function __construct(string|array $message, ?int $userId = null, ?NestedContextData $extra = null)
    {
        if (is_array($message) && func_num_args() === 1 && static::isAssociative($message)) {
            parent::__construct($message);

            return;
        }

        parent::__construct([
            'message' => $message,
            'userId' => $userId,
            'extra' => $extra ? $extra->toArray() : [],
        ]);
    }
}
