<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Benefit extends Model
{
    use HasFactory;
    protected $table = 'benefit';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'code',
        'benefit',
        'smart_id',
        'bene_class',
        'gl_code'
    ];
}
