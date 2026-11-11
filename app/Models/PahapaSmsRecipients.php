<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PahapaSmsRecipients extends Model
{
    use HasFactory;
     protected $table = 'pahapa_sms_recipients';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'sms_schedule_id',
        'phone',
        'member_no',
        'status',
        'retry_count',
        'last_attempt_at',
        'created_at', 
        'message_date'
    ];
}
