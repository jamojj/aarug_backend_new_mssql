<?php

namespace App\Http\Controllers\Reinsurance;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\PremiumInvoice;
use App\Models\ReinsurerExcessOfLoss;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExcessOfLossController extends Controller
{
    public function fetchExcessOfLossCalculations($code)
    {
        // ini_set('memory_limit', '1524M');
        ini_set('max_execution_time', 80000);

        $dt1 = $dt2 = $dt3 = $dt4 = $dt5 = [];
        $data1 = $data2 = $data3 = $data4 = $data5 = $data6 = [];
        $response = [];
        $amt_paid = $ttl_claims_chargable = $insurer_loss = $loss_ratio = $ttl_premium_overall = $ttl_claims_overall = 0;
        $year6 = date('Y' . '0101');
        $future = date('Y' . '0101', strtotime(' + 1 year'));
        $year5 = date('Y' . '0101', strtotime(' - 1 year'));
        $year4 = date('Y' . '0101', strtotime(' - 2 year'));
        $year3 = date('Y' . '0101', strtotime(' - 3 year'));
        $year2 = date('Y' . '0101', strtotime(' - 4 year'));
        $year1 = date('Y' . '0101', strtotime(' - 5 year'));
        $upper_limit = ReinsurerExcessOfLoss::where('code', $code)->pluck('upper_limit');
        $upper_limit = $upper_limit[0];


        //year 1
        $ttl_premium = PremiumInvoice::select(
            DB::raw("SUM(ISNULL(PREMIUM_NET,0)) as prem_net")
        )
            ->whereBetween(DB::raw("FORMAT(INVOICE_DATE, 'yyyyMMdd')"), [$year1, $year2])
            ->get();
        $ttl_premium = $ttl_premium[0]['prem_net'];

        $cnt = DB::select("SELECT COUNT(DISTINCT CLAIM_NATURE) cnt FROM BILLS WHERE (ISNULL(VET_STATUS, 0) IN (0, 1)) and (FORMAT(invoice_date,'yyyyMMdd') between '$year1' and '$year2')");
        $rows = DB::select("SELECT DISTINCT CLAIM_NATURE AS BENE,(SELECT BENEFIT FROM BENEFIT WHERE CODE = BENE and FORMAT(invoice_date,'yyyyMMdd') between '$year1' and '$year2'),(SELECT SUM(ISNULL(INVOICED_AMOUNT,0)) FROM BILLS WHERE CLAIM_NATURE = BENE) AMT_PAYABLE FROM BILLS WHERE (ISNULL(VET_STATUS, 0) IN (0, 1)) and (FORMAT(invoice_date,'yyyyMMdd') between '$year1' and '$year2')");

        foreach ($rows as $row) {
            if ($row['AMT_PAYABLE'] > $upper_limit) {
                $amt_paid = $upper_limit;
                $insurer_loss = $row['AMT_PAYABLE'] - $amt_paid;
                $ttl_claims_chargable += $amt_paid;
            } else if ($row['AMT_PAYABLE'] <= $upper_limit) {
                $amt_paid = 0.00;
                $insurer_loss = $row['AMT_PAYABLE'] - $amt_paid;
                $ttl_claims_chargable += $amt_paid;
            }
            $d = [
                'benefit' => $row['BENEFIT'],
                'amt_payable' => $row['AMT_PAYABLE'],
                'amt_paid' => $amt_paid,
                'insurer_loss' => $insurer_loss
            ];
            array_push($dt1, $d);
        }
        $loss_ratio = ($ttl_claims_chargable / $ttl_premium) * 100;
        $ttl_premium_overall += $ttl_premium;
        $ttl_claims_overall += $ttl_claims_chargable;
        $data1 = [
            'dt1' => $dt1,
            'cnt' => $cnt[0]['cnt'],
            'period' => $this->FORMAT($year1) . " - " . $this->FORMAT($year2),
            'ttl_claims_chargable' => $ttl_claims_chargable,
            'loss_ratio' => $loss_ratio,
            'ttl_premium' => $ttl_premium
        ];

        //year 2
        $ttl_premium = PremiumInvoice::select(
            DB::raw("SUM(ISNULL(PREMIUM_NET,0)) as prem_net")
        )
            ->whereBetween(DB::raw("FORMAT(INVOICE_DATE, 'yyyyMMdd')"), [$year2, $year3])
            ->get();
        $ttl_premium = $ttl_premium[0]['prem_net'];

        $cnt = DB::select("SELECT COUNT(DISTINCT CLAIM_NATURE) cnt FROM BILLS WHERE (ISNULL(VET_STATUS, 0) IN (0, 1)) and (FORMAT(invoice_date,'yyyyMMdd') between '$year2' and '$year3')");
        $rows = DB::select("SELECT DISTINCT CLAIM_NATURE AS BENE,(SELECT BENEFIT FROM BENEFIT WHERE CODE = BENE and FORMAT(invoice_date,'yyyyMMdd') between '$year2' and '$year3'),(SELECT SUM(ISNULL(INVOICED_AMOUNT,0)) FROM BILLS WHERE CLAIM_NATURE = BENE) AMT_PAYABLE FROM BILLS WHERE (ISNULL(VET_STATUS, 0) IN (0, 1)) and (FORMAT(invoice_date,'yyyyMMdd') between '$year2' and '$year3')");

        foreach ($rows as $row) {
            if ($row['AMT_PAYABLE'] > $upper_limit) {
                $amt_paid = $upper_limit;
                $insurer_loss = $row['AMT_PAYABLE'] - $amt_paid;
                $ttl_claims_chargable += $amt_paid;
            } else if ($row['AMT_PAYABLE'] <= $upper_limit) {
                $amt_paid = 0.00;
                $insurer_loss = $row['AMT_PAYABLE'] - $amt_paid;
                $ttl_claims_chargable += $amt_paid;
            }
            $d = [
                'benefit' => $row['BENEFIT'],
                'amt_payable' => $row['AMT_PAYABLE'],
                'amt_paid' => $amt_paid,
                'insurer_loss' => $insurer_loss
            ];
            array_push($dt2, $d);
        }
        $loss_ratio = ($ttl_claims_chargable / $ttl_premium) * 100;
        $ttl_premium_overall += $ttl_premium;
        $ttl_claims_overall += $ttl_claims_chargable;
        $data2 = [
            'dt2' => $dt2,
            'cnt' => $cnt[0]['cnt'],
            'period' => $this->FORMAT($year2) . " - " . $this->FORMAT($year3),
            'ttl_claims_chargable' => $ttl_claims_chargable,
            'loss_ratio' => $loss_ratio,
            'ttl_premium' => $ttl_premium
        ];

        //year 3
        $ttl_premium = PremiumInvoice::select(
            DB::raw("SUM(ISNULL(PREMIUM_NET,0)) as prem_net")
        )
            ->whereBetween(DB::raw("FORMAT(INVOICE_DATE, 'yyyyMMdd')"), [$year3, $year4])
            ->get();
        $ttl_premium = $ttl_premium[0]['prem_net'];
        $cnt = DB::select("SELECT COUNT(DISTINCT CLAIM_NATURE) cnt FROM BILLS WHERE (ISNULL(VET_STATUS, 0) IN (0, 1)) and (FORMAT(invoice_date,'yyyyMMdd') between '$year3' and '$year4')");
        $rows = DB::select("SELECT DISTINCT CLAIM_NATURE AS BENE,(SELECT BENEFIT FROM BENEFIT WHERE CODE = BENE and FORMAT(invoice_date,'yyyyMMdd') between '$year3' and '$year4'),(SELECT SUM(ISNULL(INVOICED_AMOUNT,0)) FROM BILLS WHERE CLAIM_NATURE = BENE) AMT_PAYABLE FROM BILLS WHERE (ISNULL(VET_STATUS, 0) IN (0, 1)) and (FORMAT(invoice_date,'yyyyMMdd') between '$year3' and '$year4')");

        foreach ($rows as $row) {
            if ($row['AMT_PAYABLE'] > $upper_limit) {
                $amt_paid = $upper_limit;
                $insurer_loss = $row['AMT_PAYABLE'] - $amt_paid;
                $ttl_claims_chargable += $amt_paid;
            } else if ($row['AMT_PAYABLE'] <= $upper_limit) {
                $amt_paid = 0.00;
                $insurer_loss = $row['AMT_PAYABLE'] - $amt_paid;
                $ttl_claims_chargable += $amt_paid;
            }
            $d = [
                'benefit' => $row['BENEFIT'],
                'amt_payable' => $row['AMT_PAYABLE'],
                'amt_paid' => $amt_paid,
                'insurer_loss' => $insurer_loss
            ];
            array_push($dt3, $d);
        }
        $loss_ratio = ($ttl_claims_chargable / $ttl_premium) * 100;
        $ttl_premium_overall += $ttl_premium;
        $ttl_claims_overall += $ttl_claims_chargable;
        $data3 = [
            'dt3' => $dt3,
            'cnt' => $cnt[0]['cnt'],
            'period' => $this->FORMAT($year3) . " - " . $this->FORMAT($year4),
            'ttl_claims_chargable' => $ttl_claims_chargable,
            'loss_ratio' => $loss_ratio,
            'ttl_premium' => $ttl_premium
        ];

        //year 4
        $ttl_premium = PremiumInvoice::select(
            DB::raw("SUM(ISNULL(PREMIUM_NET,0)) as prem_net")
        )
            ->whereBetween(DB::raw("FORMAT(INVOICE_DATE, 'yyyyMMdd')"), [$year1, $year2])
            ->get();
        $ttl_premium = $ttl_premium[0]['prem_net'];
        $cnt = DB::select("SELECT COUNT(DISTINCT CLAIM_NATURE) cnt FROM BILLS WHERE (ISNULL(VET_STATUS, 0) IN (0, 1)) and (FORMAT(invoice_date,'yyyyMMdd') between '$year4' and '$year5')");
        $rows = DB::select("SELECT DISTINCT CLAIM_NATURE AS BENE,(SELECT BENEFIT FROM BENEFIT WHERE CODE = BENE and FORMAT(invoice_date,'yyyyMMdd') between '$year4' and '$year5'),(SELECT SUM(ISNULL(INVOICED_AMOUNT,0)) FROM BILLS WHERE CLAIM_NATURE = BENE) AMT_PAYABLE FROM BILLS WHERE (ISNULL(VET_STATUS, 0) IN (0, 1)) and (FORMAT(invoice_date,'yyyyMMdd') between '$year4' and '$year5')");

        foreach ($rows as $row) {
            if ($row['AMT_PAYABLE'] > $upper_limit) {
                $amt_paid = $upper_limit;
                $insurer_loss = $row['AMT_PAYABLE'] - $amt_paid;
                $ttl_claims_chargable += $amt_paid;
            } else if ($row['AMT_PAYABLE'] <= $upper_limit) {
                $amt_paid = 0.00;
                $insurer_loss = $row['AMT_PAYABLE'] - $amt_paid;
                $ttl_claims_chargable += $amt_paid;
            }
            $d = [
                'benefit' => $row['BENEFIT'],
                'amt_payable' => $row['AMT_PAYABLE'],
                'amt_paid' => $amt_paid,
                'insurer_loss' => $insurer_loss
            ];
            array_push($dt4, $d);
        }
        $loss_ratio = ($ttl_claims_chargable / $ttl_premium) * 100;
        $ttl_premium_overall += $ttl_premium;
        $ttl_claims_overall += $ttl_claims_chargable;
        $data4 = [
            'dt4' => $dt4,
            'cnt' => $cnt[0]['cnt'],
            'period' => $this->FORMAT($year4) . " - " . $this->FORMAT($year5),
            'ttl_claims_chargable' => $ttl_claims_chargable,
            'loss_ratio' => $loss_ratio,
            'ttl_premium' => $ttl_premium
        ];

        //year 5
        $ttl_premium = PremiumInvoice::select(
            DB::raw("SUM(ISNULL(PREMIUM_NET,0)) as prem_net")
        )
            ->whereBetween(DB::raw("FORMAT(INVOICE_DATE, 'yyyyMMdd')"), [$year5, $year6])
            ->get();
        $ttl_premium = $ttl_premium[0]['prem_net'];
        $cnt = DB::select("SELECT COUNT(DISTINCT CLAIM_NATURE) cnt FROM BILLS WHERE (ISNULL(VET_STATUS, 0) IN (0, 1)) and (FORMAT(invoice_date,'yyyyMMdd') between '$year5' and '$year6')");
        $rows = DB::select("SELECT DISTINCT CLAIM_NATURE AS BENE,(SELECT BENEFIT FROM BENEFIT WHERE CODE = BENE and FORMAT(invoice_date,'yyyyMMdd') between '$year5' and '$year6'),(SELECT SUM(ISNULL(INVOICED_AMOUNT,0)) FROM BILLS WHERE CLAIM_NATURE = BENE) AMT_PAYABLE FROM BILLS WHERE (ISNULL(VET_STATUS, 0) IN (0, 1)) and (FORMAT(invoice_date,'yyyyMMdd') between '$year5' and '$year6')");

        foreach ($rows as $row) {
            if ($row['AMT_PAYABLE'] > $upper_limit) {
                $amt_paid = $upper_limit;
                $insurer_loss = $row['AMT_PAYABLE'] - $amt_paid;
                $ttl_claims_chargable += $amt_paid;
            } else if ($row['AMT_PAYABLE'] <= $upper_limit) {
                $amt_paid = 0.00;
                $insurer_loss = $row['AMT_PAYABLE'] - $amt_paid;
                $ttl_claims_chargable += $amt_paid;
            }
            $d = [
                'benefit' => $row['BENEFIT'],
                'amt_payable' => $row['AMT_PAYABLE'],
                'amt_paid' => $amt_paid,
                'insurer_loss' => $insurer_loss
            ];
            array_push($dt5, $d);
        }
        $loss_ratio = ($ttl_claims_chargable / $ttl_premium) * 100;
        $ttl_premium_overall += $ttl_premium;
        $ttl_claims_overall += $ttl_claims_chargable;
        $data5 = [
            'dt5' => $dt5,
            'cnt' => $cnt[0]['cnt'],
            'period' => $this->FORMAT($year5) . " - " . $this->FORMAT($year6),
            'ttl_claims_chargable' => $ttl_claims_chargable,
            'loss_ratio' => $loss_ratio,
            'ttl_premium' => $ttl_premium
        ];

        $pbc = ($ttl_claims_overall / $ttl_premium_overall) * 100;

        $dtTotal = [
            'ttl_premium_overall' => $ttl_premium_overall,
            'ttl_claims_overall' => $ttl_claims_overall,
            'pbc' => $pbc
        ];

        //future or coming period of one year

        $ttl_premium = DB::select("SELECT SUM(ISNULL(PREMIUM_NET,0)) prem_net FROM PREMIUM_INVOICE WHERE FORMAT(INVOICE_DATE, 'yyyyMMdd') between $year6 and $future");
        $ttl_premium = $ttl_premium[0]['prem_net'];

        $data6 = [
            'period' => $this->FORMAT($year6) . " - " . $this->FORMAT($future),
            'premium' => $ttl_premium
        ];

        array_push($response, $data1, $data2, $data3, $data4, $data5, $data6, $dtTotal);
        return $response;
    }

    public function FORMAT($dt)
    {
        return date("d/m/Y", strtotime($dt));
    }   
}
