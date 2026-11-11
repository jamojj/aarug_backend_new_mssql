<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResendBills extends Model
{
    use HasFactory;
    protected $table = 'resend_bills';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = [
        'idx',
        'batch_no',
        'batch_serial_no',
        'claim_no',
        'amount',
        'resend_reason',
        'notes',
        'user_id',
        'date_entered',
        'provider'
    ];
}
