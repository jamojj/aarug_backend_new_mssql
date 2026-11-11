<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditedFund extends Model
{
    use HasFactory;
    protected $table = 'credited_fund';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'corp_id',
        'date_credited',
        'fund_amount',
        'user_id',
        'date_entered',
        'cheque_no'
    ];
}
