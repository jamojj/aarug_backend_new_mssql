<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilySize extends Model
{
    use HasFactory;
    protected $table = 't_rates_fam_size';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'fam_size',
        'fam_lives'
    ];
}
