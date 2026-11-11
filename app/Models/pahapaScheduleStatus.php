<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pahapaScheduleStatus extends Model
{
    use HasFactory;
     protected $table = 'pahapa_schedule_status';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name'       
    ];
}
