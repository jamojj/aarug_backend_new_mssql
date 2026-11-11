<?php

namespace App\Http\Controllers;

use App\Models\Corporate\Corporate;
use App\Models\pahapaSmsSchedules;
use App\Models\Registration\PrincipalApplicant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PahapaMessageSchedules extends Controller
{
    public function fetchScheduledMessages()
    {
        // $scheduled_date = Carbon::tomorrow()->addMinute()->format('Y-m-d H:i:s');
        $scheduled_date = Carbon::tomorrow()->format('Y-m-d H:i:s');
        $data = pahapaSmsSchedules::select(
            'pahapa_member_type.type as member_type',
            'pahapa_sms_schedules.member_text',
            'pahapa_message_type.type as message_type',
            DB::raw("FORMAT(pahapa_sms_schedules.schedule_date, 'yyyy-MM-dd hh:mm:ss') as schedule_date"),
            'pahapa_sms_schedules.user_id',
            'pahapa_schedule_status.name as schedule_status',
            'pahapa_sms_schedules.schedule_status as schedule_status_code',
            'pahapa_sms_schedules.schedule_message',
            DB::raw("CASE WHEN pahapa_message_type.id = 3 THEN pahapa_sms_schedules.message_text ELSE pahapa_message_type.messages END as final_message")
        )
            ->leftJoin('pahapa_message_type', 'pahapa_message_type.id', '=', 'pahapa_sms_schedules.message_type')
            ->leftJoin('pahapa_member_type', 'pahapa_member_type.id', '=', 'pahapa_sms_schedules.member_type')
            ->leftJoin('pahapa_schedule_status', 'pahapa_schedule_status.id', '=', 'pahapa_sms_schedules.schedule_status')
            ->where('pahapa_sms_schedules.schedule_date', $scheduled_date)
            ->get();

        return $data;
    }
    public function savePahapaSmsSchedules(Request $request)
    {
        // return $request;
        try {
            // $scheduledDate = Carbon::tomorrow()->addMinute()->format('Y-m-d H:i:s');
            $scheduledDate = Carbon::tomorrow()->format('Y-m-d H:i:s');
            $now = Carbon::now()->format('Y-m-d H:i:s');
            $max_id = pahapaSmsSchedules::max('id') + 1;
            DB::transaction(function () use ($request, $scheduledDate, $now, $max_id) {
                pahapaSmsSchedules::create(
                    [
                        'id' => $max_id,
                        'member_type' => $request->member_type,
                        'corporate' => $request->corporate,
                        'member_text' => $request->member_type == 9 ? trim(str_replace(["\r\n", "\r", "\n"], '', $request->members))  : null,
                        'message_type' => $request->message_type,
                        'message_text' => $request->message_type == 3 ? $request->message : null,
                        'schedule_message'=> $request->message_type == 1 ? 1 : null,
                        'schedule_status' => 1,
                        'schedule_date' => $scheduledDate,
                        'date_entered' => $now,
                        'user_id' => $request->username
                    ]
                );
            });
            return ['success'];
        } catch (\Throwable $th) {
            return ['Error ' . $th->getMessage()];
        }
    }
    public function savePahapaSmsSchedules2(Request $request)
    {
        return $request;
        try {
            $phone_numbers = [];
            $message = "";

            //get members
            switch ($request->member_type) {
                case '1': //corporate
                    $phone_numbers = Corporate::select(
                        DB::raw("case when tel_no is null then mobile_no  end as corp_contact")
                    )
                        ->where('individual', '!=', 2)
                        ->get();
                    break;
                case '2': //retail
                    $phone_numbers = PrincipalApplicant::select(
                        DB::raw("
                        case when member_info.tel_no is not null then member_info.tel_no 
                        else
                            case when principal_applicant.tel_no is not null then principal_applicant.tel_no else principal_applicant.mobile_no end
                        end as member_contacts
                        ")
                    )
                        ->join('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->where('principal_applicant.individual', 2)
                        ->get();
                    break;
                case '3': //specific members
                    //explode the members using a comma
                    $members = explode(',', $request->members);
                    //check if the members exist
                    // Get existing IDs from database
                    $existing = PrincipalApplicant::join('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->whereIn('member_info.member_no', $members)
                        ->pluck('member_no')
                        ->toArray();

                    // Find missing IDs
                    $missing = array_diff($members, $existing);

                    if (!empty($missing)) {
                        $message = "These members do not exist: " . implode(', ', $missing);
                    }

                    $phone_numbers = PrincipalApplicant::select(
                        DB::raw("
                        case when member_info.tel_no is not null then member_info.tel_no 
                        else
                            case when principal_applicant.tel_no is not null then principal_applicant.tel_no else principal_applicant.mobile_no end
                        end as member_contacts
                        ")
                    )
                        ->join('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->whereIn('member_info.member_no', $members)
                        ->get();

                    break;
            }

            if ($message !== "") {
                return [$message];
            }
        } catch (\Throwable $th) {
            return ['Error ' . $th->getMessage()];
        }
    }
}
