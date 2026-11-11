<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionRate extends Model
{
    use HasFactory;
    protected $table = 'commission_rate';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'code',
        'agent_type',
        'new_business',
        'organic_growth',
        'renewal',
        'premium_from',
        'premium_to',
        'lives',
        'effective_from',
        'effective_to'
    ];
}
