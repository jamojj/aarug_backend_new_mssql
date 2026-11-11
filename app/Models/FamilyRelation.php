<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyRelation extends Model
{
    use HasFactory;
    protected $table = 'family_relation';
    public $timestamp = false;
    public $increments = false;
    
    protected $fillable = [
        'code',
        'relation',
        'class',
        'nuclear'
    ];
}
