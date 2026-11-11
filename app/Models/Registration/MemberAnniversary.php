<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberAnniversary extends Model
{
    use HasFactory;
    protected $table = 'member_anniversary';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'member_no',
        'start_date',
        'end_date',
        'renewal_date',
        'anniv',
        'user_id',
        'date_entered',
        'sync',
        'renewal_notified',
        'smart_sync',
        'nov',
        'agent_commis_rate',
        'commis_whtax_rate',
        'health_plan',
        'h_option',
        'premium',
        'money_transaction_id',
        'premium_installment',
        'premium_type',
        'airtel_transaction_date',
    ];
}
