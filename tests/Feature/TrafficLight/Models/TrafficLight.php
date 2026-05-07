<?php

declare(strict_types=1);

namespace Tests\Feature\TrafficLight\Models;

use Fsm\Traits\HasFsm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tests\Feature\TrafficLight\Database\Factories\TrafficLightFactory;
use Tests\Feature\TrafficLight\Enums\TrafficLightState;

class TrafficLight extends Model
{
    use HasFactory;
    use HasFsm;

    protected $fillable = ['name', 'state'];

    protected $casts = [
        'state' => TrafficLightState::class,
    ];

    protected static function newFactory()
    {
        return TrafficLightFactory::new();
    }
}
