<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumInvoicingMembers extends Model
{
    use HasFactory;
    protected $table = 'premium_invoicing_members';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'inv_no',
        'idx',
        'member_names',
        'products',
        'total_cost',
        'date_entered',
        'user_id',
    ];
}
