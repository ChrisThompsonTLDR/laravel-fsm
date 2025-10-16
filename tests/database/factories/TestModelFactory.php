<?php

namespace Tests\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Feature\Fsm\Models\TestModel;

class TestModelFactory extends Factory
{
    protected $model = TestModel::class;

    public function definition()
    {
        return [
            'status' => 'idle',
            'secondary_status' => 'pending',
        ];
    }
}
