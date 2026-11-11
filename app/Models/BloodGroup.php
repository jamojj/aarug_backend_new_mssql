<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodGroup extends Model
{
    use HasFactory;
    protected $table = 'blood_group';
    public $timestamp = false;
    public $increments = false;
    
    protected $fillable = [
        'code',
        'blood_group'
    ];
}
