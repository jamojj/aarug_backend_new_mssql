<?php

namespace App\Http\Controllers;

use App\Models\CignaPremiumInvoiceDetailsSummary;
use App\Models\PremiumInvoiceCigna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CignaInvoiceReportController extends Controller
{
    public function cignaInvoiceReport()
    {
        ini_set('max_execution_time', '0');
        $ttl_invoices  = 0;
        $invoices = PremiumInvoiceCigna::select(
            DB::raw("FORMAT(date_entered,'dd/mm/YYYY') as date_entered"),
            DB::raw("family_no || '/' || person_no as member_no"),
            'family_no',
            'person_no',
            'invoice_no',
            'premium_net',
            'policy_no as principal',
            'user_id as installment',
            'idx'
        )
            ->orderBy('idx', 'asc')
            ->get();
        foreach ($invoices as $inv) {
            $principal = CignaPremiumInvoiceDetailsSummary::select(
                DB::raw("case 
                            when payment_frequency = 'A' then 'Annual'
                            when payment_frequency = 'Q' then 'Quarterly'
                            when payment_frequency = 'H' then 'Half year'
                        else
                                'Annual'
                        end as installment    
                
                "),
                'name',
            )
                ->where('family_no', $inv->family_no)->where('person_no', 1)
                ->get();
                
            $name = count($principal) !== 0 ? $principal[0]->name : "";
            $installment = count($principal) !== 0 ? $principal[0]->installment : "";
            $inv->principal = $name;
            $inv->installment = $installment;

            $ttl_invoices += $inv->premium_net;
        }


        return [
            'invoices' => $invoices,
            'totals' => $ttl_invoices
        ];
    }
}
