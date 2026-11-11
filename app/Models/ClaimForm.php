<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimForm extends Model
{
    use HasFactory;
     protected $table = 'claim_form';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = [
        'CLAIM_NO',
        'VISIT_DATE',
        'ATTENDING_DOC',
        'DOCTOR_SIGN',
        'DOCTOR_DATE',
        'CLAIM_FORM_SIGNED',
        'DATE_ADMITTED',
        'DATE_DISCHARGED',
        'USER_ID',
        'DATE_ENTERED',
        'id'
    ];
}
