<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pahapaMessageType extends Model
{
    use HasFactory;
     protected $table = 'pahapa_message_type';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'type',
        'messages'
    ];
}
