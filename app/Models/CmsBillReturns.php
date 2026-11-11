<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsBillReturns extends Model
{
    use HasFactory;
    protected $table = 'cms_bill_returns';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'smart_bill_id',
        'return_amt',
        'return_code',
        'return_reason',
        'date_entered',
        'smart_picked',
        'smart_picked_date',
        'member_no',
        'smart_benefit',
        'anniv',
        'service',
        'provider',
        'invoice_no',
        'action_code',
        'action_name',
        'smart_provider',
        'benefit',
        'user_id',
        'sent_to_smart',
        'product_name',
        'corp_id',
        'family_no',
        'invoice_date',
        'Time_stampCMS',
        'CMS_read_status',
        'Error_mesg',
        'Time_stampHAIS'
    ];
}
