<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeclineLetter extends Model
{
    use HasFactory;
    protected $table = 'decline_letter';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'member_no',
        'provider',
        'decline_date',
        'date_admitted',
        'decline_reason',
        'user_id',
        'date_entered',
        'notes'
    ];
}
