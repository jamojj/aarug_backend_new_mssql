<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberAllergy extends Model
{
    use HasFactory;
    protected $table = 'member_allergy';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'member_no',
        'allergy'
    ];
}
