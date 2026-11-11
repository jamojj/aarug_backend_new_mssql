<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctors extends Model
{
    use HasFactory;
    protected $table = 'doctors';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'doctor',
        'tel_no',
        'mobile_no',
        'fax_no'
    ];
}
