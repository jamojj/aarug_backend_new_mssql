<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductChange extends Model
{
    use HasFactory;
    protected $table = 'product_change';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = [
        'member_no',
        'corp_id',
        'anniv',
        'prev_hplan',
        'prev_poption',
        'cur_hplan',
        'cur_poption',
        'smart_sync',
        'sync',
        'create_date',
        'user_id'
    ];
}
