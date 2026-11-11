<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exclusion extends Model
{
    use HasFactory;
    protected $table = 'exclusions';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'code',
        'exclusion'
    ];
}
