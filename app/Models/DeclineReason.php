<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeclineReason extends Model
{
    use HasFactory;
    protected $table = 'decline_reason';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'decline_reason'
    ];
}
