<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderCapitation extends Model
{
    use HasFactory;
    protected $table = 'provider_capitation';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'provider',
        'capitation',
        'start_date',
        'date_entered',
        'user_id',
    ];
}
