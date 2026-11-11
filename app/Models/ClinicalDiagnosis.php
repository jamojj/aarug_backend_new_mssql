<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalDiagnosis extends Model
{
    use HasFactory;
    protected $table = 'clinical_diagnosis';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['idx','diagnosis_class','clinical_diagnosis', 'diag_code', 'diag_category', 'diag_title', 'type'];

}
