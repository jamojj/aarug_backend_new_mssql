<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pahapaSmsSchedules extends Model
{
    use HasFactory;
     protected $table = 'pahapa_sms_schedules';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'member_type',
        'corporate',
        'member_text',
        'message_type',
        'message_text',
        'schedule_status',
        'schedule_date',
        'date_entered',
        'schedule_message',
        'user_id'
    ];
}
