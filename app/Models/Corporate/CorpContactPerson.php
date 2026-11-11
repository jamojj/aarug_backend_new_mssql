<?php

namespace App\Models\Corporate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorpContactPerson extends Model
{
    use HasFactory;
    protected $table = 'CORP_CONTACT_PERSON';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'CORP_ID',
        'TITLE',
        'SURNAME',
        'FIRST_NAME',
        'OTHER_NAMES',
        'JOB_TITLE',
        'MOBILE_NO',
        'TEL_NO',
        'EMAIL'
    ];
}
