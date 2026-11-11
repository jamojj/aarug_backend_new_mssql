<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Title extends Model
{
    use HasFactory;
    protected $table = 'title';
    public $timestamp = false;
    public $increments = false;
    
    protected $fillable = [
        'code',
        'title'
    ];
}
