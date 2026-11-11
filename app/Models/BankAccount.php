<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $table = 'bank_account';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'bank_code',
        'bank_id',
        'bank_account',
        'currencyid',
        'status',
        'inactive_date',
        'user_id',
        'date_entered'
    ];
}
