<?php

namespace App\Models\Corporate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Corporate extends Model
{
    use HasFactory;
    protected $table = 'CORPORATE';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'CORP_ID',
        'CORPORATE',
        'TEL_NO',
        'FAX_NO',
        'MOBILE_NO',
        'POSTAL_ADD',
        'TOWN',
        'PHY_LOC',
        'EMAIL',
        'USER_ID',
        'AGENT_ID',
        'SCHEME',
        'CANCELLED',
        'insurer',
        'underwriter',
        'waiting_period',
        'enhanced',
        'acct_no',
        'policy_no',
        'individual',
        'idx',
        'date_entered',
        'smart_sync',
        'sync',
        'corp_code',
        'corporate_identity',
        'underwrite',
        'branch',
        'abbreviation',
        'corp_pin',
        'age_cut_off',
        'currency',
        'care_picked',
        'client_code',
        'cancellation_reason',
        'cancellation_date',
        'sanctioned',
        'sanctioned_status',
        'product_category',
    ];
}
