<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tariffs extends Model
{
    use HasFactory;
    protected $table = 't_tariffs';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'idx',
        'item_code',
        'category',
        'service_items',
        'unit_of_measure',
        'price',
        'updated',
        'provider',
        'user_id',
        'year',
        'date_entered'
    ];
}
