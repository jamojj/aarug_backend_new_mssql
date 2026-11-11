<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InpatientComments extends Model
{
    use HasFactory;
    protected $table = 't_inpatient_comment';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'inpatient_comments'
    ];
}
