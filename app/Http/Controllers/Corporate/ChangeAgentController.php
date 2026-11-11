<?php

namespace App\Http\Controllers\Corporate;

use App\Http\Controllers\Controller;
use App\Models\AgentChange;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\PrincipalApplicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChangeAgentController extends Controller
{
    //fetch principal
    public function fetchPrincipalInformation($family_no)
    {
        $data = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ', principal_applicant.other_names) as full_name"),
            'agent.agent_name',
            'corporate.corporate',
            'principal_applicant.member_no'
        )
            ->join('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
            ->join('agent', 'principal_applicant.agent_id', '=', 'agent.agent_id')
            ->where('principal_applicant.individual', 2)
            ->where('family_no', $family_no)->get();
        return $data;
    }

    //save change corporate agent
    public function saveCorporateChangeAgent(Request $request)
    {
        $corp_id = $request->input('corp_id');
        $new_agent = $request->input('agent_id');
        $today = date("Y-m-d");
        $anniv = CorpAnniversary::where('corp_id', $corp_id)->max('anniv');
        $previous_agent = CorpAnniversary::select('agent_id')
            ->where('corp_id', $corp_id)
            ->where('anniv', $anniv)->pluck('agent_id');

        $prev_agent = $previous_agent[0];

        //set inserted, updated, and deleted items
        $inserted_item = "agent_id : $new_agent, client_no : $corp_id";
        $updated_item = "agent_id : $new_agent, corp_id : $corp_id";
        $deleted_item = null;

        $agent_change = [
            'client_no' => $corp_id,
            'anniv' => $anniv,
            'prev_rep' => $prev_agent,
            'cur_rep' => $new_agent,
            'create_date' => $today,
            'user_id' => $request->user_id
        ];

        AgentChange::create($agent_change);

        DB::table('corporate')
            ->where('corp_id', $corp_id)
            ->update(['agent_id' => $new_agent]);

        DB::table('principal_applicant')
            ->where('corp_id', $corp_id)
            ->update(['agent_id' => $new_agent]);

        DB::table('corp_anniversary')
            ->where('corp_id', $corp_id)
            ->where('anniv', $anniv)
            ->update(['agent_id' => $new_agent]);

        DB::table('premium_invoice')
            ->where('corp_id', $corp_id)
            ->where('anniv', $anniv)
            ->update(['agent_id' => $new_agent]);

        //save access log
        app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    

        return response()->json([
            "response" => "Corporate agent changed successfully"
        ]);
    }

    //save change retail agent
    public function saveRetailChangeAgent(Request $request)
    {

        $member_no = $request->input('member_no');
        $new_agent = $request->input('agent_id');
        $today = today();
        $anniv = MemberAnniversary::where('member_no', $member_no)->max('anniv');
        $previous_agent = PrincipalApplicant::select('agent_id')
            ->where('member_no', $member_no)->pluck('agent_id');

        $prev_agent = $previous_agent[0];

        //set inserted, updated, and deleted items
        $inserted_item = "agent_id : $new_agent, client_no : $member_no";
        $updated_item =  "agent_id : $new_agent, member_no : $member_no";
        $deleted_item = null;

        $agent_change = [
            'client_no' => $member_no,
            'anniv' => $anniv,
            'prev_rep' => $prev_agent,
            'cur_rep' => $new_agent,
            'create_date' => $today,
            'user_id' => $request->user_id
        ];

        AgentChange::create($agent_change);

        DB::table('principal_applicant')
            ->where('member_no', $member_no)
            ->update(['agent_id' => $new_agent]);

        DB::table('premium_invoice')
            ->where('member_no', $member_no)
            ->where('anniv', $anniv)
            ->update(['agent_id' => $new_agent]);

        //save access log
        app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    

        return response()->json([
            "response" => "Retail Agent Changed Successfully"
        ]);
    }
}
