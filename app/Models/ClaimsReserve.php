<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimsReserve extends Model
{
    use HasFactory;
    protected $table = 'claims_reserve';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = [
        'id',
        'pre_auth_no',
        'member_no',
        'trans_type',
        'debit',
        'credit',
        'user_id',
        'date_entered',
        'benefit',
        'anniv',
        'auth_batch_no'
    ];
}
