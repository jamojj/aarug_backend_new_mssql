<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentChange extends Model
{
    use HasFactory;
    protected $table = 'agent_change';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'client_no',
        'anniv',
        'prev_rep',
        'cur_rep',
        'create_date',
        'user_id'
    ];
}
