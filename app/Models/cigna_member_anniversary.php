<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cigna_member_anniversary extends Model
{
    use HasFactory;
    protected $table = 'cigna_member_anniversary';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'new_or_renewal',
        'plan',
        'dental',
        'AOC',
        'start_date',
        'end_date',
        'payment_frequency',
        'premium_loading',
        'premium_deduction',
        'date_entered',
        'user_id',
        'anniv',
        'family_no',
        'person_no'
    ];
}
