<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorpVisitIssue extends Model
{
    use HasFactory;
    protected $table = 't_corp_visit_issue';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'comments'
    ];
}
