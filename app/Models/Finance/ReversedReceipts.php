<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReversedReceipts extends Model
{
    use HasFactory;
    protected $table = 'reversed_receipts';
    public $timestamp = false;
    public $increments = false;
    
    protected $fillable = [
        'reversal_no',
        'transaction_no',
        'reversal_rsn',
        'reversal_dt',
        'user_id',
        'type'
    ];
}
