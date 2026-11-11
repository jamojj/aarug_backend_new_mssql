<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreAuthorization extends Model
{
    use HasFactory;
    protected $table = 'pre_authorization';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'member_no',
        'provider',
        'date_reported',
        'reported_by',
        'date_authorized',
        'authorized_by',
        'pre_diagnosis',
        'authority_type',
        'ward',
        'available_limit',
        'admit_days',
        'reserve',
        'notes',
        'co_signee',
        'anniv',
        'provider_code',
        'bene_code',
        'benefit_name',
        'first_diagnosed',
        'treament_due',
        'pre_auth_code',
        'cancelled',
        'last_replaced',
        'auth_batch_no',
        'admission_date',
        'discharge_date',
        'doc_fee',
        'reserve_exceeded_limit_reason'
        ];
}
