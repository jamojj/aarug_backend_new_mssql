<?php

namespace App\Models\Corporate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorpAdminFee extends Model
{
    use HasFactory;
    protected $table = 'corp_admin_fee';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['anniv', 'corp_id'];

    protected $fillable = [
        'corp_id',
        'anniv',
        'admin_fee_type',
        'admin_fee_rate',
        'admin_fee_amt',
        'employer_copay',
        'employee_copay',
        'upfront_copay',
        'agent_commis_rate',
        'commis_whtax_rate',
        'unit_manager_rate',
        'bdm_rate'
    ];
}
