<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientComment extends Model
{
    use HasFactory;
     protected $table = 'patient_comment';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'visit_no',
        'member_no',
        'patient_comment'
    ];
}
