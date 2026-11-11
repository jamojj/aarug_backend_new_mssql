<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;
    protected $table = 'agent';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'agent_id',
        'agent_name',
        'agent_type',
        'tel_no',
        'mobile_no',
        'fax_no',
        'postal_add',
        'town',
        'phy_loc',
        'email',
        'contact_person',
        'contact_mobile',
        'contact_tel',
        'user_id',
        'date_entered',
        'unit_manager',
        'agency',
        'unit',
        'bdm',
        'code',
        'status'
    ];
}
