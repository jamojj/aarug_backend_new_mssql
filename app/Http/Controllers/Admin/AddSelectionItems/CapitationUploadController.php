<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ProviderCapitation;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\TryCatch;

class CapitationUploadController extends Controller
{
    public function importCapitation(Request $request)
    {
        $response = [];
        try {
            if (isset($request->provider)) {
                for ($i = 0; $i < count($request->provider); $i++) {
                    $validator = Validator::make($request->all(), [
                        "index.$i" => 'required',
                        "provider.$i" => 'required',
                        "capitation.$i" => 'required',
                        "start_date.$i" => 'required|date|before_or_equal:today',
                    ]);
                    if (!$validator->fails()) {
                        //check provider existence
                        $provider_exists = Provider::where('code', $request->provider[$i])->count() > 0;
                        if ($provider_exists) {
                            //check if capitation already exists
                            $capitation_exists = ProviderCapitation::where('provider', $request->provider[$i])->count() > 0;
                            if (!$capitation_exists) {
                                DB::transaction(function () use ($request, $i) {
                                    ProviderCapitation::create([
                                        'provider' => $request->provider[$i],
                                        'capitation' => $request->capitation[$i],
                                        'start_date' => $request->start_date[$i],
                                        'user_id' => $request->user_id,
                                    ]);
                                });
                                $dt = [
                                    'index' => $request->index[$i],
                                    'res' => "Capitation saved successfully"
                                ];

                                array_push($response, $dt);
                            } else {
                                $dt = [
                                    'index' => $request->index[$i],
                                    'res' => "Notice, Provider already has capitation"
                                ];
                                array_push($response, $dt);
                            }
                        } else {
                            $dt = [
                                'index' => $request->index[$i],
                                'res' => "Notice, Provider does not exists"
                            ];

                            array_push($response, $dt);
                        }
                    } else {
                        // Collect all error messages
                        $errors = $validator->errors();

                        // Flatten the error messages and transform them to a string
                        $errorString = collect($errors->all())->map(function ($message) {
                            return str_replace(['.0', '.1', '.2', '.3', '.4', '.5', '.6', '.7', '.8', '.9', 'field is required.', 'The'], '', $message);
                        })->join(', ');
                        $errorString = "Error : (" .  $errorString . ")!";
                        $dt = [
                            'index' => $request->index[$i],
                            'res' => $errorString
                        ];

                        array_push($response, $dt);
                    }
                }
            }
        } catch (Exception $e) {
            $dt = [
                'index' => $request->index[$i],
                'res' => 'Error ' . $e->getMessage()
            ];

            array_push($response, $dt);
        }
        return $response;
    }


  private function fixFlippedDate($dateString)
{
    // Only fix if format is YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
        // Extract parts
        [$year, $month, $day] = explode('-', $dateString);

        // Flip month and day back (assuming original input was d/m/Y)
        try {
            return Carbon::createFromFormat('d/m/Y', "$month/$day/$year")->format('Y-m-d');
        } catch (\Exception $e) {
            return null; // Or return original if needed
        }
    }

    // If not in Y-m-d format, return as-is or handle other formats
   return $this->convertToYMD($dateString);
}


    private function convertToYMD($date)
    {
        $formats = [
            'Y-m-d',
            'd/m/Y',
            'm-d-Y',
            'd-m-Y',
            'Y/m/d',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Exception $e) {
                continue; // Try next format
            }
        }

        // If none matched, try Carbon::parse as fallback
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null; // or throw error/log it
        }
    }
}
