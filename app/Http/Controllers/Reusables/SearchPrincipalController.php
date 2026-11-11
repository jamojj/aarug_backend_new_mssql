<?php

namespace App\Http\Controllers\Reusables;

use App\Http\Controllers\Controller;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\PrincipalApplicant;
use Illuminate\Http\Request;

class SearchPrincipalController extends Controller
{
   public function queryPrincipal($family_no)
   {

      $member_no = PrincipalApplicant::select('member_no')
         ->where('family_no', $family_no)->get();

      $max_anniv = MemberAnniversary::select('anniv')
      ->where('member_no', $member_no)->max('anniv');

      // $query_principal = PrincipalApplicant::select('family_no')
      //    ->leftJoin('agent', 'agent.agent_id', '=', 'principal_applicant.agent_id')
      //    ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'principal_applicant.member_no')
      //    ->where('principal_applicant.family_no', $family_no)
      //    ->where('member_anniversary.anniv', $max_anniv)
      //    ->get();

      return $max_anniv;

      // $corporate_agent = Corporate::select('agent.agent_name', 'agent.agent_id', 'corporate.corporate', 'corp_anniversary.anniv')
      // ->leftJoin('agent', 'agent.agent_id', '=', 'corporate.agent_id')
      // ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
      // ->where('corporate.corp_id', $corp_id)
      //    ->where('corp_anniversary.anniv', $max_anniv)
      //    ->get();
   }
}
