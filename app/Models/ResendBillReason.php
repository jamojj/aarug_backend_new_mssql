<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResendBillReason extends Model
{
    use HasFactory;
    protected $table = 't_resend_bill_reason';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['code', 'resend_reason'];
}
