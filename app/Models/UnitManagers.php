<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitManagers extends Model
{
    use HasFactory;
    protected $table = 'unit_managers';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'unit',
        'bdmid'
    ];
}
