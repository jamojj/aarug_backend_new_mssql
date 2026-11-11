<?php

namespace App\Http\Controllers\Reusables;

use App\Http\Controllers\Controller;
use App\Models\AdminFeeType;
use Illuminate\Http\Request;

class AdminFeeController extends Controller
{
    public function fetchAdminFee()
    {
        return AdminFeeType::all();
    }
}
