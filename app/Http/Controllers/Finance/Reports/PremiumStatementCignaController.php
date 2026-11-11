<?php

namespace App\Http\Controllers\Finance\Reports;

use App\Http\Controllers\Controller;
use App\Models\CignaMemberInfo;
use App\Models\CignaPremiumReceipt;
use App\Models\PremiumInvoiceCigna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PremiumStatementCignaController extends Controller
{
    public function premiumStatementCignaReport($family_no)
    {
        $ttl_invoices = $ttl_receipts = 0;
        $invoices = PremiumInvoiceCigna::select(
            DB::raw("FORMAT(date_entered,'dd/mm/YYYY') as date_entered"),
            'family_no',
            'invoice_no',
            'premium_net',
        )
            ->orderBy('invoice_no')
            ->where('family_no', $family_no)
            ->get();
        foreach ($invoices as $inv) {
            $ttl_invoices += $inv->premium_net;
        }
        $receipts = CignaPremiumReceipt::select(
            DB::raw("FORMAT(receipt_date,'dd/mm/YYYY') as receipt_date"),
            'family_no',
            'receipt_no',
            'receipt_amount',
        )
            ->orderBy('receipt_no')
            ->where('family_no', $family_no)
            ->get();
        foreach ($receipts as $rcpts) {
            $ttl_receipts += $rcpts->receipt_amount;
        }

        //balance 

        $balance = $ttl_invoices - $ttl_receipts;

        return [
            'invoices' => $invoices,
            'receipts' => $receipts,
            'totals' => [
                'ttl_invoices' => $ttl_invoices,
                'ttl_receipts' => $ttl_receipts,
                'balance' => $balance
            ],
        ];
    }
}
