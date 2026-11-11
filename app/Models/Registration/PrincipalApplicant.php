<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrincipalApplicant extends Model
{
    use HasFactory;
    protected $table = 'principal_applicant';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = [
        'family_no'
    ];

    protected $fillable = [
        'family_no',
        'member_no',
        'surname',
        'first_name',
        'other_names',
        'agent_id',
        'corp_id',
        'employer',
        'tel_no',
        'mobile_no',
        'postal_add',
        'town',
        'email',
        'phy_loc',
        'relation_to_family',
        'individual',
        'date_form_received',
        'witnessed',
        'date_witnessed',
        'user_id',
        'date_entered',
        'province',
        'form_filled',
        'department',
        'idx',
        'category',
        'marital_status',
        'date_employed',
        'previous_insurer',
        'period_insured',
        'beneficiary',
        'beneficiary_id',
        'beneficiary_relation',
        'family_size',
        'plan',
        'country',
        'sanctioned',
        'sanctioned_status',
        'smart_sync',
        'sync'
    ];
}
