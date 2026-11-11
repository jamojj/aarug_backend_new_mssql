<?php

namespace App\Http\Controllers\Corporate;

use App\Http\Controllers\Controller;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\Corporate;
use App\Models\Registration\PrincipalApplicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CorporateReportsController extends Controller
{
    //corporate agents
    public function corporateAgents()
    {
        $result = Corporate::select(
            DB::raw("max(corp_anniversary.anniv) as anniv"),
            DB::raw("case when corporate.individual = 1 then 'CORPORATE' else 'SME' end as corp_type"),
            'corporate.corporate',
            'corp_anniversary.start_date',
            'corp_anniversary.end_date',
            'agent.agent_name',

        )
            ->join('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
            ->join('agent', 'agent.agent_id', '=', 'corp_anniversary.agent_id')
            ->orderBy('corporate.corporate', 'ASC')
            ->orderBy('anniv', 'ASC')
            ->groupBy(
                'corporate.corporate',
                'corp_anniversary.start_date',
                'corp_anniversary.end_date',
                'agent.agent_name',
                'corporate.individual'
            )
            ->whereIn('corporate.individual', [1, 3])
            ->get();

        return $result;
    }

    //Retail Agents
    public function retailAgents()
    {

        $result = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ', principal_applicant.other_names) as member_name"),
            DB::raw("max(member_anniversary.anniv) as anniv"),
            'member_anniversary.start_date',
            'member_anniversary.end_date',
            'agent.agent_name'
        )
            ->join('member_anniversary', 'member_anniversary.member_no', '=', 'principal_applicant.member_no')
            ->join('agent', 'agent.agent_id', '=', 'principal_applicant.agent_id')
            ->orderBy('member_name', 'ASC')
            ->groupBy(
                'principal_applicant.surname',
                'principal_applicant.first_name',
                'principal_applicant.other_names',
                'member_anniversary.start_date',
                'member_anniversary.end_date',
                'agent.agent_name'
            )
            ->where('principal_applicant.individual', 2)
            ->get();

        return json_encode($result, JSON_INVALID_UTF8_IGNORE);
    }
}
