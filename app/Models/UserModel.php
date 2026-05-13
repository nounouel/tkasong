<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'username',
        'password'
    ];

    protected $hidden = [
        'password'
    ];
}