<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartMemberEdits extends Model
{
    use HasFactory;
    protected $table = 'smart_member_edits';
    public $timestamps = false;
    public $increments = false;

    protected $fillable = [
        'id',
        'member_no',
        'card_reprint',
        'edit_date',
        'user_id',
        'sync',
        'card_serial_no',
        'print_date'
    ];
}
