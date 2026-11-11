<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BenefitSharing extends Model
{
    use HasFactory;
    protected $table = 'benefit_sharing';
    public $timestamp = false;
    public $increments = false;
    
    protected $fillable = [
        'code',
        'sharing'
    ];
}
