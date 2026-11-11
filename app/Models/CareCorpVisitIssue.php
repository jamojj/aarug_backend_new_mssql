<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareCorpVisitIssue extends Model
{
    use HasFactory;
    protected $table = 'care_corp_visit_issue';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'issue_no',
        'visit_no',
        'issue',
        'assign_to',
        'completed_by',
        'completed_on',
    ];
}
