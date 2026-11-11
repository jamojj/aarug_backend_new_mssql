<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientType extends Model
{
    use HasFactory;
    protected $table = 't_client_type';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['code', 'client_type'];
}
