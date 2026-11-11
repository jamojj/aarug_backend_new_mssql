<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancelReason extends Model
{
    use HasFactory;
    protected $table = 'CANCEL_REASON';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'CODE',
        'CANCEL_REASON'
    ];
}
