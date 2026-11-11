<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductName extends Model
{
    use HasFactory;
    protected $table = 't_product_name';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'code',
        'product_name',
        'pro_type'
    ];
}
