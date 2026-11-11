<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayeeType extends Model
{
    use HasFactory;
    protected $table = 'payee_type';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'payee'
    ];
}
