<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnresolvedClaims extends Model
{
    use HasFactory;
     protected $table = 'unresolved_claims';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'invoice_no',
        'member_no',
        'member_name',
        'corporate',
        'provider',
        'dos',
        'claimed_amt',
        'deduction_amt',
        'amount_payable',
        'issue',
        'report',
        'batch_no'
    ];
}
