<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareCorpVisit extends Model
{
    use HasFactory;
    protected $table = 'care_corp_visit';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'visit_no',
        'corp_id',
        'visit_date',
        'visited_by',
        'met_with',
        'next_visit_date',
        'met_with_title',
    ];
}
