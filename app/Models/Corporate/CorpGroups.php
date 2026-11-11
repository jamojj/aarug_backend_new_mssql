<?php

namespace App\Models\Corporate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorpGroups extends Model
{
    use HasFactory;
    protected $table = 'corp_groups';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'corp_id',
        'anniv',
        'category',
        'benefit',
        'fund',
        'limit',
        'sub_limit_of',
        'sharing',
        'capitated',
        'sync',
        'smart_sync',
        'family_limit',
        'quantity',
        'product_name',
        'mea_a',
        'mea_ab',
        'mea_c',
        'mea_cd',
        'mea_e',
        'mea_ef',
        'msa',
        'nov',
        'utilize',
        'suspend_at',
        'waiting_period'
    ];
}
