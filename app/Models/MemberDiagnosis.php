<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberDiagnosis extends Model
{
    use HasFactory;
    protected $table = 'member_diagnosis';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'claim_no',
        'member_no',
        'diagnosis',
        'diag_code'
    ];
}
