<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TLineItems extends Model
{
    use HasFactory;
    protected $table = 't_line_items';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'item_code',
        'service',
        'service_items',
        'unit_of_measure',
        'price',
        'updated',
        'provider',
        'user_id',
        'year',
    ];
}
