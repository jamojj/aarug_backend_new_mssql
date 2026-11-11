<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Finance\CommissionRate;
use Illuminate\Http\Request;

class AgentManagementController extends Controller
{
    public function fetchAgentManagementData(){
        $agents = Agent::select('agent.*', 't_agent_type.agent_type as type', 'agency.name as agency_name', 'bdm.name as bdm_name')
        ->leftJoin('t_agent_type', 'agent.agent_type', '=', 't_agent_type.code')
        ->leftJoin('agency', 'agent.agency', '=', 'agency.code')
        ->leftJoin('bdm', 'agent.bdm', '=', 'bdm.code')
        ->get();
        // return $agents;

        $commission_rates = CommissionRate::select('commission_rate.*', 't_agent_type.agent_type as type')
        ->leftJoin('t_agent_type', 'commission_rate.agent_type', '=', 't_agent_type.code')
        ->get();

        $data = [
            'agents' => $agents,
            'commission_rates' => $commission_rates
        ];
        return $data;
    }

    public function saveAgents(Request $request)
    {
        // return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Agent::max('code');
        $max_code = $max_code == null ? 0: $max_code;
      
        //update agents
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_agents = Agent::where('code', $request->code[$i])->update([
                    'agent_name' => $request->agent_name[$i],
                    'agent_type' => $request->agent_type[$i],
                    'agency' => $request->agency[$i],
                    'bdm' => $request->bdm[$i],
                    'tel_no' => $request->tel_no[$i],
                    'mobile_no' => $request->mobile_no[$i],
                    'fax_no' => $request->fax_no[$i],
                    'postal_add' => $request->postal_add[$i],
                    'phy_loc' => $request->phy_loc[$i],
                    'email' => $request->email[$i],
                    'contact_person' => $request->contact_person[$i],
                    'contact_mobile' => $request->contact_mobile[$i],
                    'contact_tel' => $request->contact_tel[$i],
                    'user_id' => $request->user_id[$i],
                    'date_entered' => $request->date_entered[$i],
                    'status' => $request->agent_status[$i]
                ]);

                if ($update_agents == 0) {
                    $errors_update++;
                }
            }
        }
        //save agents
        if (isset($request->agent_name_added)) {
            $count = count($request->agent_name_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_agents = Agent::create([
                    'code' => $max_code,
                    'agent_id' => $max_code,
                    'agent_name' => $request->agent_name_added[$i],
                    'agent_type' => $request->agent_type_added[$i],
                    'agency' => $request->agency_added[$i],
                    'bdm' => $request->bdm_added[$i],
                    'tel_no' => $request->tel_no_added[$i],
                    'mobile_no' => $request->mobile_no_added[$i],
                    'fax_no' => $request->fax_no_added[$i],
                    'postal_add' => $request->postal_add_added[$i],
                    'phy_loc' => $request->phy_loc_added[$i],
                    'email' => $request->email_added[$i],
                    'contact_person' => $request->contact_person_added[$i],
                    'contact_mobile' => $request->contact_mobile_added[$i],
                    'contact_tel' => $request->contact_tel_added[$i],
                    'user_id' => $request->user_id_added[$i],
                    'date_entered' => $request->date_entered_added[$i],
                    'status' => $request->agent_status_added[$i]
                ]);

                if ($save_agents->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Agents saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving agents");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating agents");
            }
        }


        return $response;
    }

    public function saveCommissionRates(Request $request)
    {
        // return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = CommissionRate::max('code');
      
        //update commission rates
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_commis_rates = CommissionRate::where('code', $request->code[$i])->update([
                    'agent_type' => $request->agent_type[$i],
                    'new_business' => $request->new_business[$i],
                    'organic_growth' => $request->organic_growth[$i],
                    'renewal' => $request->renewal[$i],
                    'premium_from' => $request->premium_from[$i],
                    'premium_to' => $request->premium_to[$i],
                    'lives' => $request->lives[$i],
                    'effective_from' => $request->effective_from[$i],
                    'effective_to' => $request->effective_to[$i]
                ]);

                if ($update_commis_rates == 0) {
                    $errors_update++;
                }
            }
        }
        //save commission rates
        if (isset($request->agent_type_appended)) {
            $count = count($request->agent_type_appended);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_commis_rates = CommissionRate::create([
                    'code' => $max_code,
                    'agent_type' => $request->agent_type_appended[$i],
                    'new_business' => $request->new_business_appended[$i],
                    'organic_growth' => $request->organic_growth_appended[$i],
                    'renewal' => $request->renewal_appended[$i],
                    'premium_from' => $request->premium_from_appended[$i],
                    'premium_to' => $request->premium_to_appended[$i],
                    'lives' => $request->lives_appended[$i],
                    'effective_from' => $request->effective_from_appended[$i],
                    'effective_to' => $request->effective_to_appended[$i]
                ]);

                if ($save_commis_rates->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Commission Rates saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Commission Rates");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Commission Rates");
            }
        }


        return $response;
    }
}
