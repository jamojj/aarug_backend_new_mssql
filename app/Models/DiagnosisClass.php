<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagnosisClass extends Model
{
    use HasFactory;
    protected $table = 't_diagnosis_class';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'diagnosis_class',
        'class_description'
    ];
}
