<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Occupation extends Model
{
    use HasFactory;
    protected $table = 'occupation';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'occupation'
    ];
}
