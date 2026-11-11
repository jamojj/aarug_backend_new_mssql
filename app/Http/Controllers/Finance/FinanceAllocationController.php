<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use App\Models\PaymentMode;
use App\Models\PremiumInvoice;
use App\Models\PremiumInvoiceDetailsSammary;
use App\Models\PremiumReceipt;
use App\Models\Registration\PrincipalApplicant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDOException;

use function PHPUnit\Framework\isNull;

class FinanceAllocationController extends Controller
{
    public function fetchFinanceFamilies()
    {
        $principals = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as names"),
            'premium_receipt.member_no'
        )
            ->join('premium_receipt', 'principal_applicant.member_no', '=', 'premium_receipt.member_no')
            ->where('principal_applicant.individual', 2)
            ->orderBy('names', "ASC")
            ->distinct()
            ->get();

        return $principals;
    }

    public function fetchReceiptData(Request $request)
    {
        $status = "";
        $receipt_details = array();

        switch ($request->client_type) {
            case '1':
                //fetch receipts corporate
                $rows = PremiumReceipt::where('corp_id', $request->option)
                    ->where(DB::raw("isnull(reversed,0)"), 0)
                    ->orderBy('receipt_date', 'DESC')
                    ->orderBy('receipt_no', 'DESC')
                    ->get();
                foreach ($rows as $row) {
                    $lc_rct_amt = $row->RECEIPT_AMOUNT;
                    $payment_mode = PaymentMode::where('code', $row->PAYMENT_MODE)->pluck('payment_mode');
                    $lc_allocated_amt = PremiumInvoiceDetailsSammary::select(
                        DB::raw("isnull(sum(isnull(allocated_amt, 0)) 
                        + sum(isnull(levied, 0)) 
                         + sum(isnull(contigency, 0))
                        + sum(isnull(smart_cost, 0))
                        + sum(isnull(gift_items, 0))
                         + sum(isnull(first_aid, 0))
                        , 0) as alloc_amt")
                    )
                        ->where('receipt_no', $row->RECEIPT_NO)
                        ->pluck('alloc_amt');
                    $lc_bal =  $lc_rct_amt - number_format((float)$lc_allocated_amt[0], 2, '.', '');
                    if ($lc_bal != 0) {
                        if ($lc_bal == $lc_rct_amt) {
                            $status = "Un Allocated";
                        } elseif ($lc_allocated_amt[0] < $lc_rct_amt) {
                            $status = "Partialy";
                        }

                        $dt = [
                            'receipt_no' => $row->RECEIPT_NO,
                            'receipt_amount' => $row->RECEIPT_AMOUNT,
                            'receipt_date' => $row->RECEIPT_DATE,
                            'payment_mode' => $payment_mode[0],
                            'cheque_no' => $row->CHEQUE_NO,
                            'lc_allocated_amt' => $lc_allocated_amt[0],
                            'lc_bal' =>  number_format((float)$lc_bal, 2, '.', ''),
                            'status' => $status
                        ];


                        array_push($receipt_details, $dt);
                    }
                }

                //fetch debits
                $debits = PremiumInvoiceDetailsSammary::select(
                    DB::raw("isnull(premium_invoice_details_sammary.net_premium, 0) as net_premium"),
                    DB::raw("isnull(premium_invoice_details_sammary.allocated, 0) as allocated"),
                    DB::raw("isnull(premium_invoice_details_sammary.allocated_amt, 0) as allocated_amt"),
                    DB::raw("isnull(premium_invoice_details_sammary.contigency, 0) as contigency"),
                    DB::raw("isnull(premium_invoice_details_sammary.smart_cost, 0) as smart_cost"),
                    DB::raw("isnull(premium_invoice_details_sammary.gift_items, 0) as gift_items"),
                    DB::raw("isnull(premium_invoice_details_sammary.levied, 0) as levied"),
                    DB::raw("isnull(premium_invoice_details_sammary.first_aid, 0) as first_aid"),
                    'premium_invoice_details_sammary.id_key',
                    'premium_invoice_details_sammary.invoice_no',
                    'premium_invoice_details_sammary.class',
                    'premium_invoice_details_sammary.receipt_no',
                    'premium_invoice.invoice_date',

                )
                    ->leftJoin('premium_invoice', 'premium_invoice_details_sammary.invoice_no', '=', 'premium_invoice.invoice_no')
                    ->where('premium_invoice.corp_id', $request->option)
                    ->orderBy('premium_invoice_details_sammary.invoice_no', 'DESC')
                    ->orderBy('net_premium', 'DESC')
                    ->get();


                $data = [
                    'receipts' => $receipt_details,
                    'debits' => $debits
                ];
                return $data;

                break;
            case '2':
                //receipt family
                $rows = PremiumReceipt::where('member_no', $request->option)
                    ->where(DB::raw("isnull(reversed,0)"), 0)
                    ->orderBy('receipt_date', 'DESC')
                    ->orderBy('receipt_no', 'ASC')
                    ->get();
                foreach ($rows as $row) {
                    $lc_rct_amt = $row->RECEIPT_AMOUNT;
                    $payment_mode = PaymentMode::where('code', $row->PAYMENT_MODE)->pluck('code');
                    $lc_allocated_amt = PremiumInvoiceDetailsSammary::select(
                        DB::raw("isnull(sum(isnull(allocated_amt, 0)) 
                        + sum(isnull(levied, 0)) 
                         + sum(isnull(contigency, 0))
                        + sum(isnull(smart_cost, 0))
                        + sum(isnull(gift_items, 0))
                         + sum(isnull(first_aid, 0))
                        , 0) as alloc_amt")
                    )->where('receipt_no', $row->RECEIPT_NO)
                        ->pluck('alloc_amt');
                    $lc_bal =  $lc_rct_amt - number_format((float)$lc_allocated_amt[0], 2, '.', '');

                    if ($lc_bal != 0) {
                        if ($lc_bal == $lc_rct_amt) {
                            $status = "Un Allocated";
                        } elseif ($lc_allocated_amt[0] < $lc_rct_amt) {
                            $status = "Partialy";
                        }

                        $dt = [
                            'receipt_no' => $row->RECEIPT_NO,
                            'receipt_amount' => $row->RECEIPT_AMOUNT,
                            'receipt_date' => $row->RECEIPT_DATE,
                            'payment_mode' => $payment_mode[0],
                            'cheque_no' => $row->CHEQUE_NO,
                            'lc_allocated_amt' => $lc_allocated_amt[0],
                            'lc_bal' =>  number_format((float)$lc_bal, 2, '.', ''),
                            'status' => $status
                        ];


                        array_push($receipt_details, $dt);
                    }
                }

                //fetch debits
                $debits = PremiumInvoiceDetailsSammary::select(
                    DB::raw("isnull(premium_invoice_details_sammary.net_premium, 0) as net_premium"),
                    DB::raw("isnull(premium_invoice_details_sammary.allocated, 0) as allocated"),
                    DB::raw("isnull(premium_invoice_details_sammary.allocated_amt, 0) as allocated_amt"),
                    DB::raw("isnull(premium_invoice_details_sammary.contigency, 0) as contigency"),
                    DB::raw("isnull(premium_invoice_details_sammary.smart_cost, 0) as smart_cost"),
                    DB::raw("isnull(premium_invoice_details_sammary.gift_items, 0) as gift_items"),
                    DB::raw("isnull(premium_invoice_details_sammary.levied, 0) as levied"),
                    DB::raw("isnull(premium_invoice_details_sammary.first_aid, 0) as first_aid"),
                    'premium_invoice_details_sammary.id_key',
                    'premium_invoice_details_sammary.invoice_no',
                    'premium_invoice_details_sammary.class',
                    'premium_invoice_details_sammary.receipt_no',
                    'premium_invoice.invoice_date',
                )
                    ->join('premium_invoice', 'premium_invoice_details_sammary.invoice_no', '=', 'premium_invoice.invoice_no')
                    ->where('premium_invoice.member_no', $request->option)
                    ->orderBy('premium_invoice_details_sammary.invoice_no', 'ASC')
                    ->orderBy('net_premium', 'DESC')
                    ->get();


                $data = [
                    'receipts' => $receipt_details,
                    'debits' => $debits
                ];
                return $data;

                break;
        }
    }

    public function allocateReceiptsToDebits(Request $request)
    {
        try {
            $ic_balance = $request->un_allocated;
            $lc_for_allocation = 0.0;
            $ic_levy = $ic_smrt = $ic_gift = $ic_firstaidk = $ic_conts = 0.00;
            $response = array();
            $inv_ttl = PremiumInvoice::where('invoice_no', $request[1])->sum(DB::raw("isnull(invoice_total, 0)"));
            $lc_net = PremiumInvoiceDetailsSammary::where('invoice_no', $request[1])->sum(DB::raw("isnull(net_premium, 0)"));
            $lc_alloc = PremiumInvoiceDetailsSammary::where('invoice_no', $request[1])->sum(DB::raw("isnull(allocated_amt, 0)"));
            $lc_levied = PremiumInvoiceDetailsSammary::where('invoice_no', $request[1])->sum(DB::raw("isnull(levied, 0)"));
            $lc_vat = PremiumInvoice::select(DB::raw("isnull(phcf, 0) + isnull(tl, 0) + isnull(stamp_duty, 0) as lc_vat"))->where('invoice_no', $request[1])->get();
            $lc_smart_alloc = PremiumInvoiceDetailsSammary::select(DB::raw("isnull(sum(isnull(smart_cost,0)),0) as lc_smart_alloc"))->where('invoice_no', $request[1])->get();
            $lc_smart_cost = PremiumInvoice::select(DB::raw("isnull(smart_cost, 0) + isnull(access_fee, 0) as lc_smart_cost"))->where('invoice_no', $request[1])->get();
            $lc_gifts_alloc = PremiumInvoiceDetailsSammary::select(DB::raw("isnull(sum(isnull(gift_items,0)),0) as gift_items"))->where('invoice_no', $request[1])->get();
            $lc_gift_items = PremiumInvoice::select(DB::raw("isnull(gift_items, 0) as lc_gift_items"))->where('invoice_no', $request[1])->get();
            $lc_firstaid_alloc = PremiumInvoiceDetailsSammary::select(DB::raw("isnull(sum(isnull(first_aid,0)),0) as lc_firstaid_alloc"))->where('invoice_no', $request[1])->get();
            $lc_firstaid = PremiumInvoice::select(DB::raw("isnull(first_aid, 0) as lc_firstaid"))->where('invoice_no', $request[1])->get();
            $lc_cont_alloc = PremiumInvoiceDetailsSammary::select(DB::raw("isnull(sum(isnull(contigency,0)),0) as lc_cont_alloc"))->where('invoice_no', $request[1])->get();
            $lc_cont = PremiumInvoice::select(DB::raw("isnull(contigency, 0) as lc_cont"))->where('invoice_no', $request[1])->get();


            $ttl_alloc = $lc_alloc + $lc_levied + $lc_smart_alloc[0]->lc_smart_alloc + $lc_gifts_alloc[0]->gift_items + $lc_firstaid_alloc[0]->lc_firstaid_alloc + $lc_cont_alloc[0]->lc_cont_alloc;


            if ($inv_ttl == $ttl_alloc) {
                array_push($response, ['Allocated' => 'fully allocated']);
            } else {

                //check if levies have been deducted
                //levies not deducted
                if ($lc_levied == 0 || is_null($lc_levied)) {
                    $ic_levy = $lc_vat[0]->lc_vat;
                    $ic_balance -= $ic_levy;
                } else {
                    if ($lc_vat[0]->lc_vat > $lc_levied) {
                        $ic_levy = $lc_vat[0]->lc_vat - $lc_levied;
                        $ic_balance -= $ic_levy;
                    }
                }

                //allocate premium and others 
                //if premium is fully allocated just allocate others 
                if ($lc_net == $lc_alloc) {
                    //smart cost allocation
                    if ($lc_smart_cost[0]->lc_smart_cost > $lc_smart_alloc[0]->lc_smart_alloc) {
                        $ic_smrt = $lc_smart_cost[0]->lc_smart_cost - $lc_smart_alloc[0]->lc_smart_alloc;
                        if ($ic_balance >= $ic_smrt) {
                            $ic_balance = $ic_balance - $ic_smrt;
                        } else {
                            $ic_smrt = $ic_balance;
                            $ic_balance = 0.00;
                        }
                    }

                    //gift Items allocation
                    if ($ic_balance > 0) {
                        if ($lc_gift_items[0]->lc_gift_items > $lc_gifts_alloc[0]->gift_items) {
                            $ic_gift = $lc_gift_items[0]->lc_gift_items - $lc_gifts_alloc[0]->gift_items;
                            if ($ic_balance >= $ic_gift) {
                                $ic_balance = $ic_balance - $ic_gift;
                            } else {
                                $ic_gift = $ic_balance;
                                $ic_balance = 0.00;
                            }
                        }
                    }

                    //first aid kit allocation
                    if ($ic_balance > 0) {

                        if ($lc_firstaid[0]->lc_firstaid > $lc_firstaid_alloc[0]->lc_firstaid_alloc) {
                            $ic_firstaidk = $lc_firstaid[0]->lc_firstaid - $lc_firstaid_alloc[0]->lc_firstaid_alloc;
                            if ($ic_balance >= $ic_firstaidk) {
                                $ic_balance = $ic_balance - $ic_firstaidk;
                            } else {
                                $ic_firstaidk = $ic_balance;
                                $ic_balance = 0.00;
                            }
                        }
                    }

                    //contigency allocation
                    if ($ic_balance > 0) {

                        if ($lc_cont[0]->lc_cont > $lc_cont_alloc[0]->lc_cont_alloc) {
                            $ic_conts = $lc_cont[0]->lc_cont - $lc_cont_alloc[0]->lc_cont_alloc;
                            if ($ic_balance >= $ic_conts) {
                                $ic_balance = $ic_balance - $ic_conts;
                            } else {
                                $ic_conts = $ic_balance;
                                $ic_balance = 0.00;
                            }
                        }
                    }

                    $dt = ['DebitNo' => $request[1], 'Allocated' => '1', 'AllocatedAmt' => 0, 'ReceiptNo' => $request->receipt_no, 'Levied' => $ic_levy, 'contigency' => $ic_conts, 'smart_cost' => $ic_smrt, 'gift_items' => $ic_gift, 'first_aid' => $ic_firstaidk, 'premium' => $lc_net];
                    array_push($response, $dt);
                } else {
                    if ($lc_alloc != 0) {
                        $lc_net -= $lc_alloc;
                    }

                    if ($ic_balance <= $lc_net) {
                        $lc_for_allocation = $ic_balance;
                        $dt = ['DebitNo' => $request[1], 'Allocated' => '1', 'AllocatedAmt' => number_format((float)$lc_for_allocation, 2, '.', ''), 'ReceiptNo' => $request->receipt_no, 'Levied' => $ic_levy, 'contigency' => $ic_conts, 'smart_cost' => $ic_smrt, 'gift_items' => $ic_gift, 'first_aid' => $ic_firstaidk, 'premium' => $lc_net];
                        array_push($response, $dt);
                    } else {
                        $lc_for_allocation = $lc_net;
                        $ic_balance -= $lc_net;

                        //smart cost allocation

                        if ($lc_smart_cost[0]->lc_smart_cost > $lc_smart_alloc[0]->lc_smart_alloc) {
                            $ic_smrt = $lc_smart_cost[0]->lc_smart_cost - $lc_smart_alloc[0]->lc_smart_alloc;
                            if ($ic_balance >= $ic_smrt) {
                                $ic_balance = $ic_balance - $ic_smrt;
                            } else {
                                $ic_smrt = $ic_balance;
                                $ic_balance = 0.00;
                            }
                        }

                        //gift Items allocation
                        if ($ic_balance > 0) {

                            if ($lc_gift_items[0]->lc_gift_items > $lc_gifts_alloc[0]->gift_items) {
                                $ic_gift = $lc_gift_items[0]->lc_gift_items - $lc_gifts_alloc[0]->gift_items;
                                if ($ic_balance >= $ic_gift) {
                                    $ic_balance = $ic_balance - $ic_gift;
                                } else {
                                    $ic_gift = $ic_balance;
                                    $ic_balance = 0.00;
                                }
                            }
                        }

                        //first aid kit allocation
                        if ($ic_balance > 0) {

                            if ($lc_firstaid[0]->lc_firstaid > $lc_firstaid_alloc[0]->lc_firstaid_alloc) {
                                $ic_firstaidk = $lc_firstaid[0]->lc_firstaid - $lc_firstaid_alloc[0]->lc_firstaid_alloc;
                                if ($ic_balance >= $ic_firstaidk) {
                                    $ic_balance = $ic_balance - $ic_firstaidk;
                                } else {
                                    $ic_firstaidk = $ic_balance;
                                    $ic_balance = 0.00;
                                }
                            }
                        }

                        //contigency allocation
                        if ($ic_balance > 0) {

                            if ($lc_cont[0]->lc_cont > $lc_cont_alloc[0]->lc_cont_alloc) {
                                $ic_conts = $lc_cont[0]->lc_cont - $lc_cont_alloc[0]->lc_cont_alloc;
                                if ($ic_balance >= $ic_conts) {
                                    $ic_balance = $ic_balance - $ic_conts;
                                } else {
                                    $ic_conts = $ic_balance;
                                    $ic_balance = 0.00;
                                }
                            }
                        }

                        $dt = ['DebitNo' => $request[1], 'Allocated' => '1', 'AllocatedAmt' => number_format((float)$lc_for_allocation, 2, '.', ''), 'ReceiptNo' => $request->receipt_no, 'Levied' => $ic_levy, 'contigency' => $ic_conts, 'smart_cost' => $ic_smrt, 'gift_items' => $ic_gift, 'first_aid' => $ic_firstaidk, 'premium' => $lc_net];
                        array_push($response, $dt);
                    }
                }
            }
        } catch (Exception $e) {
            array_push($response,  $e->getMessage());
        }
        return $response;
    }

    public function saveAllocations(Request $request)
    {
        $response = [];
        $max_index = PremiumInvoiceDetailsSammary::max('id_key');
        try {
            if (isset($request->DebitNo)) {
                for ($i = 0; $i < count($request->DebitNo); $i++) {
                    $max_index++;
                    //set inserted, updated, and deleted items
                    $inserted_item = "id_key : $max_index, invoice_no : " . $request->DebitNo[$i] . "receipt_no : " . $request->ReceiptNo[$i] . "allocated_amt : " . $request->AllocatedAmt[$i];
                    $updated_item = null;
                    $deleted_item = null;
                    $save_allocations = PremiumInvoiceDetailsSammary::create([
                        'id_key' => $max_index,
                        'invoice_no' => $request->DebitNo[$i],
                        'receipt_no' => $request->ReceiptNo[$i],
                        'allocated' => $this->format($request->Allocated[$i]),
                        'allocated_amt' => $this->format($request->AllocatedAmt[$i]),
                        'contigency' => $this->format($request->contigency[$i]),
                        'smart_cost' => $this->format($request->smart_cost[$i]),
                        'gift_items' => $this->format($request->gift_items[$i]),
                        'first_aid' => $this->format($request->first_aid[$i]),
                        'levied' => $this->format($request->lvd[$i]),
                    ]);

                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);


                    if ($save_allocations->wasRecentlyCreated === true) {
                        array_push($response, "Allocations saved successfully");
                    } else {
                        array_push($response, "Error saving Allocation $request->DebitNo[$i]");
                    }
                }
            }
        } catch (Exception $e) {
            array_push($response, $e->getMessage());
        }

        return $response;
    }

    public function format($num)
    {
        return   number_format((float)$num, 2, '.', '');
    }
}
