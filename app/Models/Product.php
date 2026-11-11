<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 't_products';
    public $timestamp = false;
    public $increments = false;
    
    protected $fillable = [
        'id',
        'product_name',
        'benefit',
        'family_limit',
        'limit',
        'sharing',
        'sub_limit_of',
        'fund',
        'capitated',
        'quantity',
        'health_plan'
    ];
}
