<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pahapaMemberType extends Model
{
    use HasFactory;
    protected $table = 'pahapa_member_type';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'type'
    ];
}
