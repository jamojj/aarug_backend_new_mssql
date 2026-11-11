<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\CorpVisitIssue;
use Illuminate\Http\Request;

class CorporateVisitIssueController extends Controller
{
    //save added corporate visit issue
    public function saveCorporateVisitIssue(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = CorpVisitIssue::max('code');

        //update product names
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_visit_issue = CorpVisitIssue::where(
                    'code', $request->code[$i]
                )->update(
                    [
                        'comments' => $request->visit_issue[$i]
                    ]);

                if ($update_visit_issue == 0) {
                    $errors_update++;
                }
            }
        }
        //save visit issues
        if (isset($request->visit_issue_added)) {
            $count = count($request->visit_issue_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_visit_issue = CorpVisitIssue::create([
                    'code' => $max_code,
                    'comments' => $request->visit_issue_added[$i]
                ]);

                if ($save_visit_issue->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Comment Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Comment Issue");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Comment Issue");
            }
        }
        return $response;
    }

}
