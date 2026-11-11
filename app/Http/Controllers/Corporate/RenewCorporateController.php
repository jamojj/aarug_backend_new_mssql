<?php

namespace App\Http\Controllers\Corporate;

use App\Http\Controllers\Controller;
use App\Models\AdminFeeType;
use App\Models\Benefit;
use App\Models\BenefitSharing;
use App\Models\Category;
use App\Models\Corporate\CorpAdminFee;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\CorpGroups;
use App\Models\Corporate\Corporate;
use App\Models\ProductName;
use App\Models\Products;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RenewCorporateController extends Controller
{

    public function fetchCorporateEligibilityForRenewal($corp_id)
    {
        $today = date("Y-m-d");
        //check if corporate exists
        $corp_exists = CorpAnniversary::where('corp_id', $corp_id)->count();
        $max_anniv = CorpAnniversary::where('corp_id', $corp_id)->max('anniv');

        if ($corp_exists != 0) {
            $eligibility = CorpAnniversary::select(
                DB::raw("
            case 
            when renewal_date > '$today'
                then 'oncover'
            else
                 'expired'            
            END as eligibility")
            )
                ->where('corp_id', $corp_id)
                ->where('anniv', $max_anniv)
                ->pluck('eligibility');
            return $eligibility;
        } else {
            return ['Corporate does not exist!'];
        }
    }
    public function fetchData($corp_id)
    {
        $refined_data = array();
        $corp_name = Corporate::where('corp_id', $corp_id)->pluck('corporate');
        $max_anniv = CorpAnniversary::where('corp_id', $corp_id)->max('anniv');
        $admin_fee = CorpAdminFee::where('corp_id', $corp_id)->get();
        $prev_annivs = CorpAnniversary::select('corp_anniversary.anniv', 'agent.agent_name', 'corp_anniversary.start_date', 'corp_anniversary.end_date', 'corp_anniversary.renewal_date')
            ->join('agent', 'corp_anniversary.agent_id', '=', 'agent.agent_id')
            ->where('corp_anniversary.corp_id', $corp_id)
            ->orderBy('corp_anniversary.anniv', 'DESC')
            ->get();
        $max_prev_annivs = CorpAnniversary::select('corp_anniversary.start_date', 'corp_anniversary.end_date', 'corp_anniversary.renewal_date')
            ->where('corp_anniversary.corp_id', $corp_id)
            ->where('corp_anniversary.anniv', $max_anniv)
            ->get();
        $corp_groups = CorpGroups::select('*')
            ->where('corp_groups.anniv', $max_anniv)
            ->where('corp_groups.corp_id', $corp_id)
            ->get();
        // $health_plan = Category::all();
        $health_plan = Products::select('health_plan as category')
            ->whereNotNull('health_plan')
            ->distinct()
            ->orderBy('health_plan')
            ->get();
        $product_name = ProductName::select('code', 'product_name')->get();
        $benefit = Benefit::select('code', 'benefit')->get();
        $sharing = BenefitSharing::all();
        $agent = CorpAnniversary::where('corp_id', $corp_id)->where('anniv', $max_anniv)->pluck('agent_id');

        foreach ($prev_annivs as $annivs) {
            $start_date = date("d/m/Y", strtotime($annivs->start_date));
            $end_date = date("d/m/Y", strtotime($annivs->end_date));
            $renewal_date = date("d/m/Y", strtotime($annivs->renewal_date));

            $dt = [
                'anniv' => $annivs->anniv,
                'agent_name' => $annivs->agent_name,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'renewal_date' => $renewal_date,
            ];

            array_push($refined_data, $dt);
        }

        $data = [
            'prev_annivs' => $refined_data,
            'max_prev_annivs' => $max_prev_annivs,
            'agent_id' => $agent,
            'max_anniv' => $max_anniv,
            'corp_name' => $corp_name,
            'admin_fee' => $admin_fee,
            'corp_groups' => $corp_groups,
            'health_plan' => $health_plan,
            'product_name' => $product_name,
            'benefit' => $benefit,
            'sharing' => $sharing
        ];

        return $data;
    }


    public function renewCorporateData(Request $request)
    {
        // return $request;
        $response = array();
        $corp_anniv = $request->corp_anniv + 1;
        $corp_name = Corporate::select('corporate')->where('corp_id',$request->corp_id)->get();
         $corp_name = count( $corp_name) !== 0 ?  $corp_name[0]->corporate : '';
        //perform transaction
        try {
            DB::transaction(
                function () use ($request, $corp_anniv, $corp_name) {
                    //set inserted, updated, and deleted items
                    $updated_item =  null;
                    $inserted_item = "corp_id : $request->corp_id, previous_anniv : $request->corp_anniv, renewal_anniv : $corp_anniv";
                    $deleted_item = null;

                    //save corp anniversary
                    CorpAnniversary::create([
                        'CORP_ID' => (int)$request->corp_id,
                        'ANNIV' => $corp_anniv,
                        'START_DATE' =>  $request->start_date,
                        'END_DATE' => $request->end_date,
                        'RENEWAL_DATE' => $request->renewal_date,
                        'AGENT_ID' => $request->agent,
                        'USER_ID' => $request->user,
                        'DATE_ENTERED' => $request->date_entered,
                        'smart_sync'=>null,
                        'sync'=>1,
                    ]);

                    // save corp admin fee
                    if (isset($request->admin_fee_anniv)) {
                        CorpAdminFee::create([
                            'corp_id' => $request->corp_id,
                            'anniv' => (int)$request->admin_fee_anniv,
                            'admin_fee_type' => (int)$request->admin_fee_type,
                            'admin_fee_rate' => (int)$request->admin_fee_rate,
                            'upfront_copay' => (int)$request->upfront_copay,
                            'agent_commis_rate' => (int)$request->agent_commis_rate,
                            'commis_whtax_rate' => (int)$request->commis_whtax_rate,
                            'unit_manager_rate' => (int)$request->unit_manager_rate,
                            'bdm_rate' => (int)$request->bdm_rate
                        ]);
                    }

                    //save corp group
                    if (isset($request->corp_group_anniv)) {
                        $max_id_corp_groups = CorpGroups::max('idx');
                        for ($i = 0; $i < count($request->corp_group_anniv); $i++) {
                            $max_id_corp_groups++;
                            CorpGroups::create([
                                'idx' => (int)$max_id_corp_groups,
                                'corp_id' => $request->corp_id,
                                'anniv' => (int)$request->corp_group_anniv[$i],
                                'category' => $request->category[$i],
                                'product_name' => (int)$request->product_name[$i],
                                'benefit' => (int)$request->benefit[$i],
                                'sub_limit_of' => (int)$request->sub_limit_of[$i],
                                'limit' => (int)$request->limit[$i],
                                'sharing' => (int)$request->sharing[$i],
                                'fund' => (int)$request->fund[$i],
                                'capitated' => (int)$request->capitated[$i],
                                'quantity' => (int)$request->quantity[$i],
                                'waiting_period' => (int)$request->waiting_period[$i],
                                'smart_sync' => null,
                                'sync' => 1,
                            ]);
                        }
                    }

                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                }
            );
            return ["Corporate renewed successfully."];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
