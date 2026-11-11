<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\TAgentBdmStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{
    //fetch all agents
    public function fetchAllAgents()
    {
        $agents = Agent::select('*')->orderBy('agent_name')->get();
        return $agents;
    }

    //fetch active agents
    public function fetchActiveAgents()
    {
        $agents = Agent::select('*')->orderBy('agent_name')->where(DB::raw("isnull(status, 0)"), 1)->get();
        return $agents;
    }

    //get agent and bdm statuses
    public function fetchAgentBdmStatuses()
    {
        $statuses = TAgentBdmStatus::select('*')->orderBy('name')->get();
        return $statuses;
    }
    //get agent data
    public function fetchAgentData($agent_id)
    {
        $agent = Agent::where('agent_id', $agent_id)->get();
        return $agent;
    }
}
