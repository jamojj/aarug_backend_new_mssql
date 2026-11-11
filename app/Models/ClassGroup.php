<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassGroup extends Model
{
    use HasFactory;
    protected $table = 't_class_group';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = [
        'class_group',
        'class_group_desc'
    ];
}
