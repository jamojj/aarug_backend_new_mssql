<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewTAccessed extends Model
{
    use HasFactory;
    protected $table = 'new_t_accessed';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'accessed'
    ];
}
