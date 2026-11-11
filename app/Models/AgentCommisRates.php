<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentCommisRates extends Model
{
    use HasFactory;
    protected $table = 'agent_commis_rates';
    public $timestamp = false;
    public $increments = false;
    
    protected $fillable = [
        'idx',
        'agent_id',
        'benefit',
        'commission_rate_newbuz',
        'commission_rate_renewalbuz',
        'commission_withholding_tax',
        'override_rate_newbuz_intermediary',
        'override_rate_newbuz_direct',
        'override_rate_renewalbuz_intermediary',
        'override_rate_renewalbuz_direct',
        'override_withholding_tax'
    ];
}
