<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class DownloadTemplateController extends Controller
{
    public function downloadTemplates($file)
    {
        $path = storage_path("app\public\download_templates\\" . $file . ".xlsx");
        if (file_exists($path)) {
            $headers  = array(
                'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );
    
            return Response::download($path, $file . '.xlsx', $headers);
        } else {
            return ["File $file doesn't exist!"];
        }
    }
}
