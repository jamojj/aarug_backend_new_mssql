<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HaisSmartTriggers extends Model
{
    use HasFactory;
    protected $table = 'hais_smart_triggers';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'date_added',
        'user_id',
        'trigger_id',
        'trigger_ref',
        'reason',
        'reprint_reason',
        'smart_synced',
    ];
}
