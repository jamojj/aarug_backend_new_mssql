<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartLimitChange extends Model
{
    use HasFactory;
    protected $table = 'smart_limit_change';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'member_no',
        'anniv',
        'transaction_id',
        'installment',
        'limit_change',
        'smart_sync',
        'sync',
        'benefit',
        'allocated_limit',
        'benefit_limit'
    ];
}
