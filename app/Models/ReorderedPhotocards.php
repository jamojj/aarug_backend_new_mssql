<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReorderedPhotocards extends Model
{
    use HasFactory;
    protected $table = 'reordered_photocards';
    public $timestamp = false;
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
        'print_date',
        'smart_sync',
    ];
}
