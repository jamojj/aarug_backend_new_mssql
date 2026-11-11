<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactRelation extends Model
{
    use HasFactory;
    protected $table = 'contact_relation';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'relation'
    ];
}
