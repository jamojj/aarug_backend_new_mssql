<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\LastNumber;
use App\Models\Provider;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderController extends Controller
{
    public function saveProvider(Request $request)
    {
        // return $request;

        try {
            DB::transaction(function () use ($request) {
                $code = Provider::max(DB::raw("isnull(level, 0)"));
                $max_vendor_id = LastNumber::where('task', 6)->pluck('last_no');
                $max_vendor_id = $max_vendor_id[0];
                $date_entered = date('Y-m-d');
                //update existing provider
                if (isset($request->code)) {
                    for ($i = 0; $i < count($request->code); $i++) {
                        $end_date = $request->start_date[$i] ? Carbon::parse($request->start_date[$i])
                            ->addYear()
                            ->subDay()
                            ->format('Y-m-d') : $request->start_date[$i];
                        Provider::where('code', $request->code[$i])
                            ->update([
                                'provider' => is_null($request->provider[$i]) ? '' : $request->provider[$i],
                                'tel_no' => $request->tel_no[$i],
                                'mobile_no' => $request->mobile_no[$i],
                                'fax_no' => $request->fax_no[$i],
                                'email' => $request->email[$i],
                                'address' => $request->address[$i],
                                'town' => $request->town[$i],
                                'physical_loc' => $request->physical_loc[$i],
                                'contact_person' => $request->contact_person[$i],
                                'cancel' => $request->cancel[$i],
                                'practice_no' => $request->practice_no[$i],
                                'cancellation_reason' => $request->provider_cancellation_reason[$i],
                                'cancel_suspend_date' => $request->cancel_suspend_date[$i],
                                'start_date' => $request->start_date[$i],
                                'end_date' => $end_date
                            ]);
                    }
                }

                //save new provider 
                if (isset($request->provider_appended)) {
                    for ($i = 0; $i < count($request->provider_appended); $i++) {
                        $code++;
                        $max_vendor_id++;
                        $end_date_appended = $request->start_date_appended[$i] ?  Carbon::parse($request->start_date_appended[$i])
                            ->addYear()
                            ->subDay()
                            ->format('Y-m-d') : $request->start_date_appended[$i];
                        Provider::create([
                            'code' => $code,
                            'provider' => $request->provider_appended[$i],
                            'tel_no' => $request->tel_no_appended[$i],
                            'mobile_no' => $request->mobile_no_appended[$i],
                            'fax_no' => $request->fax_no_appended[$i],
                            'email' => $request->email_appended[$i],
                            'address' => $request->address_appended[$i],
                            'town' => $request->town_appended[$i],
                            'physical_loc' => $request->physical_loc_appended[$i],
                            'contact_person' => $request->contact_person_appended[$i],
                            'level' => $code, //it is same as code
                            'cancel' => $request->cancel_appended[$i],
                            'practice_no' => $request->practice_no_appended[$i],
                            'vendor_id' => $max_vendor_id . 'HUGX',
                            'date_entered' => $date_entered,
                            'user_name' => $request->user_id,
                            'cancellation_reason' => $request->provider_cancellation_reason_appended[$i],
                            'cancel_suspend_date' => $request->cancel_suspend_date_appended[$i],
                            'start_date' => $request->start_date_appended[$i],
                            'end_date' => $end_date_appended
                        ]);
                    }
                    // update last number
                    LastNumber::where('task', 6)->update([
                        'last_no' => $max_vendor_id
                    ]);
                }
            });
            return ['Provider saved successfully.'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }


    public function saveProviderNew(Request $request)
    {
        try {
            $date_entered = date('Y-m-d');
            $code = Provider::max(DB::raw("isnull(level, 0)")) + 1;
            $max_vendor_id = LastNumber::where('task', 6)->pluck('last_no');
            $max_vendor_id = ($max_vendor_id[0] < 1 ? 1 : $max_vendor_id[0]) + 1;

            $end_date_save = $request->start_date_save ?  Carbon::parse($request->start_date_save)
                ->addYear()
                ->subDay()
                ->format('Y-m-d') : $request->start_date_save;

            Provider::create([
                'code' => $code,
                'provider' => $request->provider_save,
                'tel_no' => $request->tel_no_save,
                'mobile_no' => $request->mobile_no_save,
                'fax_no' => $request->fax_no_save,
                'email' => $request->email_save,
                'address' => $request->address_save,
                'town' => $request->town_save,
                'physical_loc' => $request->physical_loc_save,
                'contact_person' => $request->contact_person_save,
                'level' => $code, //it is same as code
                'cancel' => $request->cancel_save,
                'practice_no' => $request->practice_no_save,
                'vendor_id' => $max_vendor_id . 'HUGX',
                'date_entered' => $date_entered,
                'user_name' => $request->user_id,
                'cancellation_reason' => $request->provider_cancellation_reason_save,
                'cancel_suspend_date' => $request->cancel_suspend_date_save,
                'start_date' => $request->start_date_save,
                'classification' => $request->classification_save,
                'end_date' => $end_date_save
            ]);
            return ['Provider added successfully'];
        } catch (\Throwable $th) {
            return ['Error ' . $th->getMessage()];
        }
    }
    public function updateProvider(Request $request)
    {
        // return $request;
        try {
            DB::transaction(function () use ($request) {
                $end_date = $request->start_date ? Carbon::parse($request->start_date)
                    ->addYear()
                    ->subDay()
                    ->format('Y-m-d') : $request->start_date;
                Provider::where('code', $request->code)
                    ->update([
                        'provider' => $request->provider,
                        'tel_no' => $request->tel_no,
                        'mobile_no' => $request->mobile_no,
                        'fax_no' => $request->fax_no,
                        'email' => $request->email,
                        'address' => $request->address,
                        'town' => $request->town,
                        'physical_loc' => $request->physical_loc,
                        'contact_person' => $request->contact_person,
                        'cancel' => $request->cancel,
                        'practice_no' => $request->practice_no,
                        'cancellation_reason' => $request->provider_cancellation_reason,
                        'cancel_suspend_date' => $request->cancel_suspend_date,
                        'start_date' => $request->start_date,
                        'classification' => $request->classification,
                        'end_date' => $end_date
                    ]);
            });
            return [$request->provider . " updated successfully."];
        } catch (\Throwable $th) {
            return ['Error ' . $th->getMessage()];
        }
    }


    public function uploadProviderDocs(Request $request)
    {
        // Validate that a file was uploaded
        $request->validate([
            'document' => 'required|file|mimes:pdf|max:2048', // only allow PDFs up to 2MB. use max:5120 for 5mb
            'docType' => 'required|string',
            'providerCode' => 'required|integer'
        ]);

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            // Create a unique file name: docType + providerCode
            $fileName =  $request->docType . '_' . $request->providerCode . '.' . $file->getClientOriginalExtension();
            $filePath = storage_path('app/public/provider_docs/' . $fileName);

            // ✅ check if file already exists
            // if (file_exists($filePath)) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'File already uploaded for this provider and doc type.'
            //     ]); 
            // }


            // Store the file under storage/app/public/provider_docs
            $path = $file->storeAs('public/provider_docs', $fileName);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully (replaced if existed).',
                'file_name' => $fileName,
                'path' => $path,
                'url' => asset('storage/provider_docs/' . $fileName) // public URL
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No file uploaded'
        ]);
    }
}
