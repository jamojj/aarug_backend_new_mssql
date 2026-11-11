<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    use HasFactory;
    protected $table = 't_user_group';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'user_group'
    ];
}
