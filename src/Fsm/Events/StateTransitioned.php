<?php

declare(strict_types=1);

namespace Fsm\Events;

use Illuminate\Database\Eloquent\Model;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

class StateTransitioned
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public Model $model,
        public string $columnName,
        public ?string $fromState,
        public string $toState,
        public ?string $transitionName,
        public \DateTimeInterface $timestamp,
        public ?ArgonautDTOContract $context = null,
        public array $metadata = [],
    ) {}
}
