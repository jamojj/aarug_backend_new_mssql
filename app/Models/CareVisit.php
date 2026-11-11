<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareVisit extends Model
{
    use HasFactory;
    protected $table = 'care_visit';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'visit_no',
        'member_no',
        'pre_auth_no',
        'visit_date',
        'visited_by',
        'presented',
        'incurred_amt',
        'exp_discharge_date',
        'care_nurse_comments',
        'doc_comments',
    ];
}
