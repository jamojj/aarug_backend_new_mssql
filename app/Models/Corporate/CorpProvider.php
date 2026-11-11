<?php

namespace App\Models\Corporate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorpProvider extends Model
{
    use HasFactory;
    protected $table = 'corp_provider';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'provider',
        'corp_id',
        'all_provider',
        'category',
        'benefit',
        'co_pay',
        'copay_amt',
        'service',
        'anniv',
        'idx',
        'smart_sync',
        'sync',
        'provider_class',
        'care_picked',
    ];
}
