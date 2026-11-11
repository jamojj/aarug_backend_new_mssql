<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RejectReason extends Model
{
    use HasFactory;
    protected $table = 'reject_reason';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'reject_reason',
    ];
}
