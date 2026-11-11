<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rules extends Model
{
    use HasFactory;
    protected $table = 'RULES';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'CODE',
        'RULE'
    ];
}
