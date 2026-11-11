<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bdm extends Model
{
    use HasFactory;
    protected $table = 'bdm';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'status'
    ];
}
