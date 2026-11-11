<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAddress extends Model
{
    use HasFactory;
    protected $table = 't_client_address';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['code', 'client_name', 'physical_location', 'client_name', 'box_no', 'tel_cell', 'fax', 'email', 'url'];
}
