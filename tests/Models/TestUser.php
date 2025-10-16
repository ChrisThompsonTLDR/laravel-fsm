<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Orchestra\Testbench\Factories\UserFactory;

class TestUser extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['id', 'name', 'email', 'password'];

    protected $table = 'users';

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
