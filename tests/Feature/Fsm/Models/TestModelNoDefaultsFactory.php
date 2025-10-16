<?php

namespace Tests\Feature\Fsm\Models;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestModelNoDefaultsFactory extends Factory
{
    protected $model = TestModel::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'lifecycle' => $this->faker->randomElement(['new', 'processing', 'completed']),
            // Do NOT set 'status' or 'payment_status' here
        ];
    }
}
