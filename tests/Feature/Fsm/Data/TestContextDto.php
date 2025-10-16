<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Data;

use Fsm\Data\Dto;

class TestContextDto extends Dto
{
    public string $info;

    /**
     * @param  array<string, mixed>|string  $info
     */
    public function __construct(string|array $info)
    {
        if (is_array($info) && func_num_args() === 1 && static::isAssociative($info)) {
            parent::__construct($info);

            return;
        }

        parent::__construct(['info' => $info]);
    }
}
