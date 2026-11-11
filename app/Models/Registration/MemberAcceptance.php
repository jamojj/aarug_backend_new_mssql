<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberAcceptance extends Model
{
    use HasFactory;
    protected $table = 'member_acceptance';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'MEMBER_NO',
        'STATUS',
        'STATUS_DATE',
        'COMMENTS',
        'USER_ID',
        'DATE_ENTERED',
        'DEF_REJ',
        'STATUS_AFTER_APPROVAL'
    ];
}
