<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillVet extends Model
{
    use HasFactory;
    protected $table = 'bill_vet';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'claim_no',
        'invoice_no',
        'service',
        'provider',
        'vet_status',
        'reasons',
        'remarks',
        'user_id',
        'date_entered',
        'approval_vet_status',
        'approval_reason',
        'approval_user_id',
        'approval_date',
        'bill_approval_id'
    ];
}
