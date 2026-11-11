<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardReprintReason extends Model
{
    use HasFactory;
    protected $table = 't_card_reprint_reason';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'reprint_reason'
    ];
}
