<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberProcedure extends Model
{
    use HasFactory;
    protected $table = 'member_procedure';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'claim_no',
        'member_no',
        'clinical_procedure'
    ];
}
