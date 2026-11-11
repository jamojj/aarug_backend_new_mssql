<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tProviderCancellationReason extends Model
{
    use HasFactory;
    protected $table = 't_provider_cancellation_reason';
    public $timestamp = false;
    public $increments = false;
    
    protected $fillable = [
        'id',
        'code',
        'name'
    ];
}
