<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TAgentBdmStatus extends Model
{
    use HasFactory;
     protected $table = 't_agent_bdm_status';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'id',
        'code',
        'name'        
    ];
}