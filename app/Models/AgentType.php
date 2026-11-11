<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentType extends Model
{
    use HasFactory;
    protected $table = 't_agent_type';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'agent_type'
    ];
}
