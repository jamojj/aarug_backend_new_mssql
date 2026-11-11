<?php

namespace App\Models\Corporate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorpAnniversary extends Model
{
    use HasFactory;
    protected $table = 'CORP_ANNIVERSARY';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'CORP_ID',
        'ANNIV',
        'START_DATE',
        'END_DATE',
        'RENEWAL_DATE',
        'AGENT_ID',
        'USER_ID',
        'DATE_ENTERED',
        'renewal_notified',
        'smart_sync',
        'sync',
        'corp_code',
        'nov'
    ];
}
