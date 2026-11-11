<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberMedical extends Model
{
    use HasFactory;
    protected $table = 'member_medical_new';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'member_no',
        'conditiondt',
        'illnes',
        'anniv'
    ];
}
