<?php

namespace Tests\Feature\TrafficLight\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Feature\TrafficLight\Models\TrafficLight;

class TrafficLightFactory extends Factory
{
    protected $model = TrafficLight::class;

    public function definition()
    {
        return [
            'name' => $this->faker->streetName().' & '.$this->faker->streetName(),
            'state' => 'red',
        ];
    }
}
