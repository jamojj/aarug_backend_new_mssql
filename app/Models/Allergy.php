<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allergy extends Model
{
    use HasFactory;
    protected $table = 'allergy';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'code',
        'allergy'
    ];
}
