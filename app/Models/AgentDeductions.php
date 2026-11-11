<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentDeductions extends Model
{
    use HasFactory;

    protected $table = 'agent_deductions';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'agent_id',
        'deduction_type',
        'amount',
        'date_entered',
        'user_id',
        'recover',
        'vno',
        'pay_no'
    ];
}
