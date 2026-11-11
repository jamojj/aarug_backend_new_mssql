<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimsBatch extends Model
{
    use HasFactory;
    protected $table = 'claims_batch';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = [
        'id',
        'batch_no',
        'batch_count',
        'batch_amount',
        'user_id',
        'date_entered',
        'provider',
        'serial_no',
        'date_received',
        'original_amt',
        'original_cnt',
        'actual_amt',
        'batch_type',
        'date_submitted'
    ];
}
