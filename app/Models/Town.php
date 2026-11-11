<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Town extends Model
{
    use HasFactory;
    protected $table = 'town';
    public $timestamp = false;
    public $increments = false;
    
    protected $fillable = [
        'code',
        'name',
        'tel_code'
    ];
}
