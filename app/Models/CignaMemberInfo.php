<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CignaMemberInfo extends Model
{
    use HasFactory;
    protected $table = 'cigna_member_info';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'name',
        'family_no',
        'person_no',
        'location',
        'DOB',
        'address_1',
        'address_2',
        'address_3',
        'address_4',
        'email',      
        'date_entered',
        'user_id'
    ];
}
