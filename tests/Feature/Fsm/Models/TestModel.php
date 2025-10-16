<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Models;

use Fsm\Traits\HasFsm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tests\database\factories\TestModelFactory;

class TestModel extends Model
{
    use HasFactory;
    use HasFsm;

    protected $table = 'test_models';

    protected $guarded = [];

    protected static function newFactory()
    {
        return TestModelFactory::new();
    }
}
