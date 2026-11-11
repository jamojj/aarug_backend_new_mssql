<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberCancellation extends Model
{
    use HasFactory;
    protected $table = 'MEMBER_CANCELLATION';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['MEMBER_NO', 'CANCELLED', 'DATE_CAN'];

    protected $fillable = [
        'MEMBER_NO',
        'CANCELLED',
        'DATE_CAN',
        'ANNIV',
        'REASON',
        'user_id',
        'date_entered',
        'smart_sync',
        'sync',
        'nov',
        'status_date'
    ];
}
