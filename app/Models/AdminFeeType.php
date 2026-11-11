<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminFeeType extends Model
{
    use HasFactory;
    protected $table = 't_admin_fee_type';
    public $timestamp = false;
    public $increments = false;
    
    protected $fillable = ['code', 'admin_fee_type'];
}
