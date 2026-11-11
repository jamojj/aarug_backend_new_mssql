<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReorderedPhotocards extends Model
{
    use HasFactory;
    protected $table = 'reordered_photocards';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'member_no',
        'info_to_printer',
        'card_from_printer',
        'card_to_member',
        'anniv',
        'sync',
        'user_id',
        'date_entered',
        'reorder_reason',
        'serial_no',
        'smart_sync'
    ];
}
