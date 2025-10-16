<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class TestUser extends Authenticatable
{
    protected $fillable = ['id', 'name'];

    protected $table = 'users';
}
