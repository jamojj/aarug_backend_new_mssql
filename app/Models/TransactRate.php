<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactRate extends Model
{
    use HasFactory;
    protected $table = 'transact_rate';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'description',
        'rate'
    ];
}
