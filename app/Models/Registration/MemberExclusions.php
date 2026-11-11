<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberExclusions extends Model
{
    use HasFactory;
    protected $table = 'member_exclusions';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'member_no',
        'diag_code',
        'diagnosisdt',
        'exclfromdt',
        'excltodt',
        'permanent',
        'nov'
    ];
}
