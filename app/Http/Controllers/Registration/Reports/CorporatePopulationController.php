<?php

namespace App\Http\Controllers\Registration\Reports;

use App\Http\Controllers\Controller;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\Corporate;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CorporatePopulationController extends Controller
{
    public function corporatePopulation($corp_id)
    {
            $principals = PrincipalApplicant::select(
                DB::raw("count(principal_applicant.member_no) as principals"),

            )
                ->leftJoin('member_info', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->where(DB::raw('isnull(member_info.cancelled, 0)'), 0)
                ->where('principal_applicant.corp_id', $corp_id)
                ->pluck('principals');

                if (count($principals) == 0) {
                    $principals = 0;
                } else {
                    $principals = $principals[0];
                }

            $dependants = MemberInfo::select(
                DB::raw("count(member_info.member_no) as dependants"),

            )
                ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                ->where(DB::raw('isnull(member_info.cancelled, 0)'), 0)
                ->where('member_info.relation_to_principal', 25)
                ->where('principal_applicant.corp_id', $corp_id)
                ->pluck('dependants');

                if (count($dependants) == 0) {
                    $dependants = 0;
                } else {
                    $dependants = $dependants[0];
                }

            $members = MemberInfo::select(
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            )
                ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                ->where(DB::raw('isnull(member_info.cancelled, 0)'), 0)
                ->where('principal_applicant.corp_id', $corp_id)
                ->get();
            return [
                'principals' => $principals,
                'dependants' => $dependants,
                'total' => $principals + $dependants,
                'members' => json_encode($members, JSON_INVALID_UTF8_IGNORE)
            ];
    }

    public function allCorporatesPopulationDetails(Request $request)
    {
        // return $request;
        $ttl = 0;
        $population = [];
        $corp_id = CorpAnniversary::select('corp_id', 'anniv')
            ->where('start_date', '<=', $request->as_at)
            ->where('end_date', '>=', $request->as_at)
            ->get();

        foreach ($corp_id as $corp) {          

            $corporate = Corporate::where('corp_id', $corp->corp_id)->pluck('corporate');

            $principals_count = PrincipalApplicant::select(

                DB::raw("count(principal_applicant.member_no) as principals"),

            )
                ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->leftJoin('member_info', 'principal_applicant.member_no', '=', 'member_info.member_no')
                ->leftJoin('member_anniversary', 'member_info.member_no', '=', 'member_anniversary.member_no')
                ->where(DB::raw('isnull(member_info.cancelled, 0)'), 0)
                ->where('member_anniversary.anniv', $corp->anniv)
                ->where('member_anniversary.start_date', '<=', $request->as_at)
                ->where('principal_applicant.corp_id', $corp->corp_id)
                ->groupBy('corporate.corporate')
                ->pluck('principals');

            if (count($principals_count) == 0) {
                $principals_count = 0;
            } else {
                $principals_count = $principals_count[0];
            }


            $dependants_count = MemberInfo::select(

                DB::raw("count(member_info.member_no) as dependants"),

            )
                ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                ->leftJoin('member_anniversary', 'member_info.member_no', '=', 'member_anniversary.member_no')
                ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->where(DB::raw('isnull(member_info.cancelled, 0)'), 0)
                ->where('member_info.relation_to_principal', 25)
                ->where('member_anniversary.anniv', $corp->anniv)
                ->where('member_anniversary.start_date', '<=', $request->as_at)
                ->where('principal_applicant.corp_id', $corp->corp_id)
                ->groupBy('corporate.corporate')
                ->pluck('dependants');


            if (count($dependants_count) == 0) {
                $dependants_count = 0;
            } else {
                $dependants_count = $dependants_count[0];
            }

            $ttl = $principals_count + $dependants_count;

            $dt = [
                'corporate' => $corporate[0],
                'principals' => $principals_count,
                'dependants' => $dependants_count,
                'total_lives' => $ttl
            ];

            array_push($population, $dt);
        }
        return $population;
    }
}
