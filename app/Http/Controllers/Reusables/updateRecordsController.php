<?php

namespace App\Http\Controllers\Reusables;

use App\Http\Controllers\Controller;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Exception;

class updateRecordsController extends Controller
{
    public function updateAllBlankCorpIds()
    {
        ini_set('max_execution_time', 80000);
        $index = 0;
        try {
            $values = MemberInfo::select('family_no')->where('corp_id', '')->orWhere('corp_id', null)->distinct()->get();
            if (count($values) !== 0) {
                foreach ($values as $val) {
                    $corp_id = PrincipalApplicant::where('family_no', $val->family_no)->pluck('corp_id');
                    $corp_id = $corp_id[0];
                    MemberInfo::where('family_no', $val->family_no)
                        ->update([
                            'corp_id' => $corp_id
                        ]);

                    $index++;
                }
                return [$index . ' corp_ids updated successfully'];
            } else {
                return ['No corp_ids to update!'];
            }
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
