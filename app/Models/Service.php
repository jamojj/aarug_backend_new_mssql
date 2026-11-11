<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $table = 'SERVICE';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['CODE', 'SERVICE', 'smart_id'];
}
