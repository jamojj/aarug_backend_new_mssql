<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionReason extends Model
{
    use HasFactory;
    protected $table = 'deduction_reason';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'deduct_reason',
        'category'
    ];
}
