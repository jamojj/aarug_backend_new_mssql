<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReversalReason extends Model
{
    use HasFactory;
    protected $table = 'reversal_reason';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'code',
        'reason'
    ];
}
