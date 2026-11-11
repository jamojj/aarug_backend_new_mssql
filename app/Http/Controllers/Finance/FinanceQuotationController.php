<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteDetails;
use App\Models\QuoteRates;
use Exception;
use Illuminate\Http\Request;

class FinanceQuotationController extends Controller
{
    public function fetchQuotes($agent_id)
    {
        $quotes = Quote::where('agent_id', $agent_id)->orderBy('prospect')->get();
        return $quotes;
    }

    public function fetchQuoteData($quote_no)
    {
        $quote = Quote::where('quote_no', $quote_no)->get();
        $quote_details = QuoteDetails::where('quote_no', $quote_no)->get();
        $quote_rates = QuoteRates::where('quote_no', $quote_no)->get();
        $data = [
            'quote' => $quote,
            'quote_details' => $quote_details,
            'quote_rates' => $quote_rates
        ];
        return $data;
    }

    public function saveQuote(Request $request)
    {
        // return $request;
        $response = array();
        try {
            $max_quote_no = Quote::max('quote_no') + 1;

            //set inserted, updated, and deleted items
            $inserted_item = "quote_no : $max_quote_no, prospect : $request->prospect";
            $updated_item = null;
            $deleted_item = null;
            
            $save_quote = Quote::create([
                'quote_no' => $max_quote_no,
                'prospect' => $request->prospect,
                'agent_id' => $request->agent,
                'quote_total' => floatval(preg_replace('/[^\d.]/', '', $request->quote_total)),
                'premium_net' => floatval(preg_replace('/[^\d.]/', '', $request->premium_net)),
                'quote_net' => floatval(preg_replace('/[^\d.]/', '', $request->quote_net)),
                'quote_date' => $request->quote_date,
                'physical_location' => $request->physical_location,
                'date_entered' => $request->date_entered,
                'user_id' => $request->username,
                'ira' => floatval(preg_replace('/[^\d.]/', '', $request->ira)),
                'iiu' => floatval(preg_replace('/[^\d.]/', '', $request->iiu)),
                'individual' => $request->client_type,
                'address' => $request->postal_address,
                'sd' => floatval(preg_replace('/[^\d.]/', '', $request->stamp_duty))
            ]);

            if ($save_quote->wasRecentlyCreated === true) {
                array_push($response, "Quote saved successfully");
                $this->saveQuoteRates($request, $max_quote_no, $response);

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    

            } else {
                array_push($response, "Error saving Quote");
            }
        } catch (Exception $e) {
            array_push($response, $e->getMessage());
        }

        return $response;
    }

    public function saveQuoteRates($request, $max_quote_no, $response)
    {
        if (isset($request->category_rate)) {
            $max_idx = QuoteRates::max('idx');

            for ($i = 0; $i < count($request->category_rate); $i++) {
                $max_idx++;
                $save_quote_rates = QuoteRates::create([
                    'idx' => $max_idx,
                    'product_name' => $request->product_name_rate[$i],
                    'family_size' => $request->family_size_rate[$i],
                    'premium' => $request->premium_rate[$i],
                    'min_age' => $request->min_age_rate[$i],
                    'max_age' => $request->max_age_rate[$i],
                    're_rate' => $max_idx,
                    'individual' => $request->individual_rate[$i],
                    'family_title' => $request->family_title_rate[$i],
                    'health_plan' => $request->category_rate[$i],
                    'quote_no' => $max_quote_no,
                    'family_count' => $request->family_count_rate[$i],
                    'total' => $request->total_rate[$i],
                    'adjusted' => $request->adjusted_rate[$i],
                    'individual' => $request->individual_rate[$i]
                ]);

                if ($save_quote_rates->wasRecentlyCreated !== true) {
                    array_push($response, "Error saving Quote rate ($request->product_name_rate[$i] - $request->category_rate[$i])");
                }
            }

            if (str_contains($response[0], 'Error')) {
                array_push($response, "Error saving Quotes");
            } else {
                array_push($response, "Quote rates saved successfully.");
                $this->saveQuoteDetails($request, $max_quote_no, $response);
            }
        }
    }

    public function saveQuoteDetails($request, $max_quote_no, $response)
    {

        if (isset($request->category_details)) {
            $max_key_id = QuoteDetails::max('key_id');

            for ($i = 0; $i < count($request->category_details); $i++) {
                $max_key_id++;
                $save_quote_details = QuoteDetails::create([
                    'key_id'=>$max_key_id,
                    'quote_no'=>$max_quote_no,
                    'benefit'=>$request->benefit_details[$i],
                    'limit'=>$request->limit_details[$i],
                    'category'=>$request->category_details[$i],
                    'product_name'=>$request->product_name_details[$i],
                    'sharing'=>$request->sharing_details[$i],
                    'sub_limit_of'=>$request->sub_limit_of_details[$i],
                ]);

                if ($save_quote_details->wasRecentlyCreated !== true) {
                    array_push($response, "Error saving Quote details ($request->product_name_details[$i] - $request->category_details[$i])");
                }
            }

            if (str_contains($response[0], 'Error')) {
                array_push($response, "Error saving Quote details");
            } else {
                array_push($response, "Quote details saved successfully.");
            }
        }
        return $response;
    }
}
