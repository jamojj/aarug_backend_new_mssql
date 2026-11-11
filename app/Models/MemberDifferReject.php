<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberDifferReject extends Model
{
    use HasFactory;
    protected $table = 'member_differ_reject';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'code',
        'reason'
    ];
}
