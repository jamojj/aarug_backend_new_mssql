<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberBenefits extends Model
{
    use HasFactory;
    use HasFactory;
    protected $table = 'member_benefits';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'member_no',
        'benefit',
        'limit',
        'sharing',
        'anniv',
        'sub_limit_of',
        'suspended',
        'suspended_date',
        'capitated',
        'fund',
        'family_limit',
        'quantity',
        'product_name',
        'prorata_days',
        'mea_a',
        'mea_ab',
        'mea_c',
        'mea_cd',
        'mea_e',
        'mea_ef',
        'category',
        'waiting_period',
        'smart_sync',
        'sync'
    ];
}
