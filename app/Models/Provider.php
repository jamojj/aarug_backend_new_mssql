<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;
    protected $table = 'provider';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'provider',
        'tel_no',
        'mobile_no',
        'fax_no',
        'email',
        'address',
        'town',
        'physical_loc',
        'contact_person',
        'class',
        'level',
        'cancel',
        'practice_no',
        'vendor_id',
        'user_name',
        'date_entered',
        'cancellation_reason',
        'cancel_suspend_date',
        'start_date',
        'end_date',
        'classification'
    ];
}
