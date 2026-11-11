<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearMonth extends Model
{
    use HasFactory;
    protected $table = 'year_month';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['month_no', 'month_name', 'date_format', 'month_abbrv'];
}
