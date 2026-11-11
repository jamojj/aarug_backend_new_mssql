<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareAdmission extends Model
{
    use HasFactory;
    protected $table = 'care_admission';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'member_no',
        'pre_auth_no',
        'provider',
        'date_admitted',
        'admitting_doc',
        'admission_no',
        'ward',
        'room_no',
        'bed_no',
        'diagnosis',
        'date_discharged',
    ];
}
