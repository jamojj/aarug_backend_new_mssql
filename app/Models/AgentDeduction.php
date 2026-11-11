<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentDeduction extends Model
{
    use HasFactory;

    protected $table = 't_agent_deduction';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'ded_reason',
    ];
}
