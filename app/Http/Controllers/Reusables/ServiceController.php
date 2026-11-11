<?php

namespace App\Http\Controllers\Reusables;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
  public function fetchServices()
  {
      $services = Service::select('*')->orderBy('service')->get();
      return $services;
  }
}
