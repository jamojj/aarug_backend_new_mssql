<?php

namespace App\Http\Controllers;

use App\Models\Corporate\CorpAnniversary;
use App\Models\PremiumInvoice;
use App\Models\Registration\MemberAnniversary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

class IfrsController extends Controller
{
    public function fetchIfrsReport(Request $request)
    {
        $data = [];
        $categories = $request->categories;
        $from = $request->from;
        $to = $request->to;
        $today = date("Y-m-d");
        switch ($categories) {
            case '1': //corporates
                $corp = $request->corporates;
                $cat = "CORPORATE";

                // Get the maximum anniversary for this corporate
                $max_anniv = CorpAnniversary::where(
                    'corp_id',
                    $corp
                )->max('anniv');


                // Get payment information per invoice
                $pids = DB::table('premium_invoice_details_sammary')
                    ->select(
                        'invoice_no',
                        DB::raw('SUM(ISNULL(allocated_amt, 0)) as allocated_amt'),
                        DB::raw("LIST(receipt_no, ', ') as receipt_no")
                    )
                    ->where('allocated', 1)
                    ->groupBy('invoice_no');


                $data = PremiumInvoice::select(
                    'premium_invoice.invoice_date',
                    'premium_invoice.invoice_no',
                    'corporate.corporate as client',

                    DB::raw("
                    CASE
                        WHEN product_category.category IS NOT NULL
                        THEN product_category.category
                        ELSE ''
                    END AS category
                "),

                    DB::raw("'$cat' as business_class"),

                    DB::raw("'$today' as today"),

                    DB::raw("
                    ISNULL(premium_invoice.premium_net, 0)
                    AS premium_invoiced
                "),

                    DB::raw("
                    ISNULL(pids.allocated_amt, 0)
                    AS premium_received
                "),

                    'pids.receipt_no',

                    'corp_anniversary.start_date',
                    'corp_anniversary.end_date',
                    'corp_anniversary.renewal_date',

                    'premium_invoice.agent_rate',
                    'premium_invoice.bdm_rate',

                    DB::raw("'$to' as end_date_of_report")
                )

                    // Get the actual anniversary row for this corporate's maximum anniversary
                    ->join('corp_anniversary', function ($join) use ($max_anniv) {
                        $join->on(
                            'corp_anniversary.corp_id',
                            '=',
                            'premium_invoice.corp_id'
                        )
                            ->where(
                                'corp_anniversary.anniv',
                                '=',
                                $max_anniv
                            );
                    })

                    // Payment information
                    ->leftJoinSub($pids, 'pids', function ($join) {
                        $join->on(
                            'pids.invoice_no',
                            '=',
                            'premium_invoice.invoice_no'
                        );
                    })

                    // Corporate information
                    ->join(
                        'corporate',
                        'corporate.corp_id',
                        '=',
                        'premium_invoice.corp_id'
                    )

                    // Product category
                    ->leftJoin(
                        'product_category',
                        'corporate.product_category',
                        '=',
                        'product_category.code'
                    )

                    // Report date range
                    ->whereBetween(
                        'premium_invoice.invoice_date',
                        [$from, $to]
                    )

                    // Specific corporate
                    ->where(
                        'premium_invoice.corp_id',
                        $corp
                    )

                    ->get();

                break;
            case '2': //smes
                $cat = "SME";

                $smes = $request->smes;


                // Get the maximum anniversary for this specific SME
                $max_canniv = DB::table('corp_anniversary')
                    ->select(
                        'corp_id',
                        DB::raw('MAX(anniv) as max_anniv')
                    )
                    ->where('corp_id', $smes)
                    ->groupBy('corp_id');


                // Get payment information per invoice
                $pids = DB::table('premium_invoice_details_sammary')
                    ->select(
                        'invoice_no',
                        DB::raw('SUM(ISNULL(allocated_amt, 0)) as allocated_amt'),
                        DB::raw("LIST(receipt_no, ', ') as receipt_no")
                    )
                    ->where('allocated', 1)
                    ->groupBy('invoice_no');


                $data = PremiumInvoice::select(
                    'premium_invoice.invoice_date',
                    'premium_invoice.invoice_no',
                    'corporate.corporate as client',

                    DB::raw("
                        CASE
                            WHEN product_category.category IS NOT NULL
                            THEN product_category.category
                            ELSE ''
                        END AS category
                    "),

                    DB::raw("'$cat' as business_class"),

                    DB::raw("
                        ISNULL(premium_invoice.premium_net, 0)
                        AS premium_invoiced
                    "),

                    DB::raw("
                        ISNULL(pids.allocated_amt, 0)
                        AS premium_received
                    "),

                    'pids.receipt_no',

                    'corp_anniversary.start_date',
                    'corp_anniversary.end_date',
                    'corp_anniversary.renewal_date',

                    'premium_invoice.agent_rate',
                    'premium_invoice.bdm_rate',

                    DB::raw("'$from' as start_date_of_report"),
                    DB::raw("'$to' as end_date_of_report")
                )

                    // Maximum anniversary for this specific SME
                    ->joinSub($max_canniv, 'max_canniv', function ($join) {
                        $join->on(
                            'max_canniv.corp_id',
                            '=',
                            'premium_invoice.corp_id'
                        );
                    })

                    // Actual anniversary row
                    ->join('corp_anniversary', function ($join) {
                        $join->on(
                            'corp_anniversary.corp_id',
                            '=',
                            'premium_invoice.corp_id'
                        )
                            ->on(
                                'corp_anniversary.anniv',
                                '=',
                                'max_canniv.max_anniv'
                            );
                    })

                    // Payment information
                    ->leftJoinSub($pids, 'pids', function ($join) {
                        $join->on(
                            'pids.invoice_no',
                            '=',
                            'premium_invoice.invoice_no'
                        );
                    })

                    // Corporate information
                    ->join(
                        'corporate',
                        'corporate.corp_id',
                        '=',
                        'premium_invoice.corp_id'
                    )

                    // Product category
                    ->leftJoin(
                        'product_category',
                        'corporate.product_category',
                        '=',
                        'product_category.code'
                    )

                    // Report date range
                    ->whereBetween(
                        'premium_invoice.invoice_date',
                        [$from, $to]
                    )

                    // Specific SME
                    ->where(
                        'premium_invoice.corp_id',
                        $smes
                    )

                    // SME
                    ->where(
                        'corporate.individual',
                        3
                    )

                    ->get();

                break;
            case '3': //retails
                $cat = "RETAILS";

                $family_no = $request->family_no;
                $principal = $request->family_no . "-00";

                // Get the maximum anniversary for the principal member
                $max_anniv = MemberAnniversary::where(
                    'member_no',
                    $principal
                )->max('anniv');


                // Get payment information per invoice
                $pids = DB::table('premium_invoice_details_sammary')
                    ->select(
                        'invoice_no',
                        DB::raw('SUM(ISNULL(allocated_amt, 0)) as allocated_amt'),
                        DB::raw("LIST(receipt_no, ', ') as receipt_no")
                    )
                    ->where('allocated', 1)
                    ->groupBy('invoice_no');


                $data = PremiumInvoice::select(
                    'premium_invoice.invoice_date',
                    'premium_invoice.invoice_no',
                    'member_info.member_no',

                    DB::raw("
                    CASE
                        WHEN product_category.category IS NOT NULL
                        THEN product_category.category
                        ELSE ''
                    END AS category
                "),

                    DB::raw("'$cat' as business_class"),

                    DB::raw("
                    CONCAT
                        member_info.first_name,
                        ' ',
                        member_info.other_names,
                        ' ',
                        member_info.surname
                    ) AS client
                "),

                    DB::raw("
                    ISNULL(premium_invoice.premium_net, 0)
                    AS premium_invoiced
                "),

                    DB::raw("
                    ISNULL(pids.allocated_amt, 0)
                    AS premium_received
                "),

                    'pids.receipt_no',

                    'member_anniversary.start_date',
                    'member_anniversary.end_date',
                    'member_anniversary.renewal_date',

                    'premium_invoice.agent_rate',
                    'premium_invoice.bdm_rate',

                    DB::raw("'$from' as start_date_of_report"),
                    DB::raw("'$to' as end_date_of_report")
                )

                    // Member information
                    ->join(
                        'member_info',
                        'member_info.member_no',
                        '=',
                        'premium_invoice.member_no'
                    )

                    // Get the anniversary row for the principal's maximum anniversary
                    ->join('member_anniversary', function ($join) use ($max_anniv) {
                        $join->on(
                            'member_anniversary.member_no',
                            '=',
                            'premium_invoice.member_no'
                        )
                            ->where(
                                'member_anniversary.anniv',
                                '=',
                                $max_anniv
                            );
                    })

                    // Payment information
                    ->leftJoinSub($pids, 'pids', function ($join) {
                        $join->on(
                            'pids.invoice_no',
                            '=',
                            'premium_invoice.invoice_no'
                        );
                    })

                    // Corporate/category information
                    ->leftJoin(
                        'corporate',
                        'corporate.corp_id',
                        '=',
                        'premium_invoice.corp_id'
                    )

                    ->leftJoin(
                        'product_category',
                        'corporate.product_category',
                        '=',
                        'product_category.code'
                    )

                    // Report date range
                    ->whereBetween(
                        'premium_invoice.invoice_date',
                        [$from, $to]
                    )

                    // All members belonging to this family
                    ->where(
                        'member_info.family_no',
                        $family_no
                    )

                    ->get();

                break;
            case '4': // all corporates
                $cat = "CORPORATE";

                // Get the maximum anniversary for each corporate
                $max_canniv = DB::table('corp_anniversary')
                    ->select(
                        'corp_id',
                        DB::raw('MAX(anniv) as max_anniv')
                    )
                    ->groupBy('corp_id');


                // Get payment information per invoice
                $pids = DB::table('premium_invoice_details_sammary')
                    ->select(
                        'invoice_no',
                        DB::raw('SUM(ISNULL(allocated_amt, 0)) as allocated_amt'),
                        DB::raw("LIST(receipt_no, ', ') as receipt_no")
                    )
                    ->where('allocated', 1)
                    ->groupBy('invoice_no');


                $data = PremiumInvoice::select(
                    'premium_invoice.invoice_date',
                    'premium_invoice.invoice_no',
                    'corporate.corporate as client',

                    DB::raw("
                    CASE
                        WHEN product_category.category IS NOT NULL
                        THEN product_category.category
                        ELSE ''
                    END AS category
                "),

                    DB::raw("'$cat' as business_class"),

                    DB::raw("
                    ISNULL(premium_invoice.premium_net, 0)
                    AS premium_invoiced
                "),

                    DB::raw("
                    ISNULL(pids.allocated_amt, 0)
                    AS premium_received
                "),

                    'pids.receipt_no',

                    'corp_anniversary.start_date',
                    'corp_anniversary.end_date',
                    'corp_anniversary.renewal_date',

                    'premium_invoice.agent_rate',
                    'premium_invoice.bdm_rate',

                    DB::raw("'$from' as start_date_of_report"),
                    DB::raw("'$to' as end_date_of_report")
                )

                    // Get maximum anniversary for this corporate
                    ->joinSub($max_canniv, 'max_canniv', function ($join) {
                        $join->on(
                            'max_canniv.corp_id',
                            '=',
                            'premium_invoice.corp_id'
                        );
                    })

                    // Get the actual row for the maximum anniversary
                    ->join('corp_anniversary', function ($join) {
                        $join->on(
                            'corp_anniversary.corp_id',
                            '=',
                            'premium_invoice.corp_id'
                        )
                            ->on(
                                'corp_anniversary.anniv',
                                '=',
                                'max_canniv.max_anniv'
                            );
                    })

                    // Payment information
                    ->leftJoinSub($pids, 'pids', function ($join) {
                        $join->on(
                            'pids.invoice_no',
                            '=',
                            'premium_invoice.invoice_no'
                        );
                    })

                    // Corporate information
                    ->join(
                        'corporate',
                        'corporate.corp_id',
                        '=',
                        'premium_invoice.corp_id'
                    )

                    // Product category
                    ->leftJoin(
                        'product_category',
                        'corporate.product_category',
                        '=',
                        'product_category.code'
                    )

                    // Report date range
                    ->whereBetween(
                        'premium_invoice.invoice_date',
                        [$from, $to]
                    )

                    // Corporate
                    ->where('corporate.individual', 1)

                    ->orderBy('corporate.corporate', 'asc')

                    ->get();

                break;
            case '5': // all smes
                $cat = "SME";

                // Get the maximum anniversary for each corporate/SME
                $max_canniv = DB::table('corp_anniversary')
                    ->select(
                        'corp_id',
                        DB::raw('MAX(anniv) as max_anniv')
                    )
                    ->groupBy('corp_id');


                // Get payment information per invoice
                $pids = DB::table('premium_invoice_details_sammary')
                    ->select(
                        'invoice_no',
                        DB::raw('SUM(ISNULL(allocated_amt, 0)) as allocated_amt'),
                        DB::raw("LIST(receipt_no, ', ') as receipt_no")
                    )
                    ->where('allocated', 1)
                    ->groupBy('invoice_no');


                $data = PremiumInvoice::select(
                    'premium_invoice.invoice_date',
                    'premium_invoice.invoice_no',
                    'corporate.corporate as client',

                    DB::raw("
                    CASE
                        WHEN product_category.category IS NOT NULL
                        THEN product_category.category
                        ELSE ''
                    END AS category
                "),

                    DB::raw("'$cat' as business_class"),

                    DB::raw("
                    ISNULL(premium_invoice.premium_net, 0)
                    AS premium_invoiced
                "),

                    DB::raw("
                    ISNULL(pids.allocated_amt, 0)
                    AS premium_received
                "),

                    'pids.receipt_no',

                    'corp_anniversary.start_date',
                    'corp_anniversary.end_date',
                    'corp_anniversary.renewal_date',

                    'premium_invoice.agent_rate',
                    'premium_invoice.bdm_rate',

                    DB::raw("'$from' as start_date_of_report"),
                    DB::raw("'$to' as end_date_of_report")
                )

                    // Get maximum anniversary for this corporate/SME
                    ->joinSub($max_canniv, 'max_canniv', function ($join) {
                        $join->on(
                            'max_canniv.corp_id',
                            '=',
                            'premium_invoice.corp_id'
                        );
                    })

                    // Get the actual anniversary row corresponding to the maximum
                    ->join('corp_anniversary', function ($join) {
                        $join->on(
                            'corp_anniversary.corp_id',
                            '=',
                            'premium_invoice.corp_id'
                        )
                            ->on(
                                'corp_anniversary.anniv',
                                '=',
                                'max_canniv.max_anniv'
                            );
                    })

                    // Payment information
                    ->leftJoinSub($pids, 'pids', function ($join) {
                        $join->on(
                            'pids.invoice_no',
                            '=',
                            'premium_invoice.invoice_no'
                        );
                    })

                    // Corporate information
                    ->join(
                        'corporate',
                        'corporate.corp_id',
                        '=',
                        'premium_invoice.corp_id'
                    )

                    // Product category
                    ->leftJoin(
                        'product_category',
                        'corporate.product_category',
                        '=',
                        'product_category.code'
                    )

                    // Report date range
                    ->whereBetween(
                        'premium_invoice.invoice_date',
                        [$from, $to]
                    )

                    // SME
                    ->where('corporate.individual', 3)

                    ->orderBy('corporate.corporate', 'asc')

                    ->get();

                break;
            case '6': // all retails

                $cat = "RETAIL";

                // Get the maximum anniversary for each member
                $max_anniv = MemberAnniversary::select(
                    'member_no',
                    DB::raw('MAX(anniv) as max_anniv')
                )
                    ->groupBy('member_no');


                // Get payment information per invoice
                $pids = DB::table('premium_invoice_details_sammary')
                    ->select(
                        'invoice_no',
                        DB::raw('SUM(ISNULL(allocated_amt, 0)) as allocated_amt'),
                        DB::raw("LIST(receipt_no, ', ') as receipt_no")
                    )
                    ->where('allocated', 1)
                    ->groupBy('invoice_no');


                $data = PremiumInvoice::select(
                    'premium_invoice.invoice_date',
                    'premium_invoice.invoice_no',
                    'member_info.member_no',

                    DB::raw("
                        CASE
                            WHEN product_category.category IS NOT NULL
                            THEN product_category.category
                            ELSE ''
                        END AS category
                    "),

                    DB::raw("'$cat' as business_class"),

                    DB::raw("
                        CONCAT
                            member_info.first_name,
                            ' ',
                            member_info.other_names,
                            ' ',
                            member_info.surname
                        ) AS client
                    "),

                    DB::raw("
                        ISNULL(premium_invoice.premium_net, 0)
                        AS premium_invoiced
                    "),

                    DB::raw("
                        ISNULL(pids.allocated_amt, 0)
                        AS premium_received
                    "),

                    'pids.receipt_no',

                    'member_anniversary.start_date',
                    'member_anniversary.end_date',
                    'member_anniversary.renewal_date',

                    'premium_invoice.agent_rate',
                    'premium_invoice.bdm_rate',

                    DB::raw("'$from' as start_date_of_report"),
                    DB::raw("'$to' as end_date_of_report")
                )



                    // Member information
                    ->join(
                        'member_info',
                        'member_info.member_no',
                        '=',
                        'premium_invoice.member_no'
                    )

                    // Get maximum anniversary for this member
                    ->joinSub($max_anniv, 'max_anniv', function ($join) {
                        $join->on(
                            'max_anniv.member_no',
                            '=',
                            'premium_invoice.member_no'
                        );
                    })

                    // Get the actual row for the maximum anniversary
                    ->join('member_anniversary', function ($join) {
                        $join->on(
                            'member_anniversary.member_no',
                            '=',
                            'premium_invoice.member_no'
                        )
                            ->on(
                                'member_anniversary.anniv',
                                '=',
                                'max_anniv.max_anniv'
                            );
                    })

                    // Payment information
                    ->leftJoinSub($pids, 'pids', function ($join) {
                        $join->on(
                            'pids.invoice_no',
                            '=',
                            'premium_invoice.invoice_no'
                        );
                    })

                    // Corporate/category information
                    ->leftJoin(
                        'corporate',
                        'corporate.corp_id',
                        '=',
                        'premium_invoice.corp_id'
                    )

                    ->leftJoin(
                        'product_category',
                        'corporate.product_category',
                        '=',
                        'product_category.code'
                    )
                    // This is a member/retail invoice
                    ->whereNotNull('premium_invoice.member_no')

                    ->whereBetween(
                        'premium_invoice.invoice_date',
                        [$from, $to]
                    )
                    ->orderBy('business_class', 'asc')
                    ->orderBy('client', 'asc')
                    ->orderBy('category', 'asc')

                    ->get();

                break;
            case '7': // all classes
                // =========================================================
                // Get the maximum anniversary for each member
                // =========================================================
                $max_anniv = MemberAnniversary::select(
                    'member_no',
                    DB::raw('MAX(anniv) as max_anniv')
                )
                    ->groupBy('member_no');


                // =========================================================
                // Get the maximum anniversary for each corporate
                // =========================================================
                $max_canniv = DB::table('corp_anniversary')
                    ->select(
                        'corp_id',
                        DB::raw('MAX(anniv) as max_anniv')
                    )
                    ->groupBy('corp_id');


                // =========================================================
                // Get payment information per invoice
                // =========================================================
                $pids = DB::table('premium_invoice_details_sammary')
                    ->select(
                        'invoice_no',
                        DB::raw('SUM(ISNULL(allocated_amt, 0)) as allocated_amt'),
                        DB::raw("LIST(receipt_no, ', ') as receipt_no")
                    )
                    ->where('allocated', 1)
                    ->groupBy('invoice_no');


                // =========================================================
                // Main query
                // =========================================================
                $data = PremiumInvoice::select(

                    'premium_invoice.invoice_date',

                    'premium_invoice.invoice_no',


                    // -----------------------------------------------------
                    // Category
                    // -----------------------------------------------------
                    DB::raw("
                        CASE
                            WHEN product_category.category IS NOT NULL
                            THEN product_category.category
                            ELSE ''
                        END AS category
                    "),


                    // -----------------------------------------------------
                    // Client
                    // Retail  -> member name
                    // Corporate/SME -> corporate name
                    // -----------------------------------------------------
                    DB::raw("
                        CASE
                            WHEN premium_invoice.member_no IS NULL
                            THEN corporate.corporate

                            ELSE CONCAT
                                member_info.first_name,
                                ' ',
                                member_info.other_names,
                                ' ',
                                member_info.surname
                            )
                        END AS client
                    "),


                    // -----------------------------------------------------
                    // Business class
                    // -----------------------------------------------------
                    DB::raw("
                        CASE
                            WHEN premium_invoice.member_no IS NULL THEN
                                CASE
                                    WHEN corporate.individual = 1
                                    THEN 'CORPORATE'
                                    ELSE 'SME'
                                END
                            ELSE 'RETAIL'
                        END AS business_class
                    "),


                    // -----------------------------------------------------
                    // Premium invoiced
                    // -----------------------------------------------------
                    DB::raw("
                            ISNULL(premium_invoice.premium_net, 0)
                            AS premium_invoiced
                        "),


                    // -----------------------------------------------------
                    // Premium received
                    // -----------------------------------------------------
                    DB::raw("
                        ISNULL(pids.allocated_amt, 0)
                        AS premium_received
                    "),


                    'pids.receipt_no',


                    // -----------------------------------------------------
                    // Anniversary dates
                    //
                    // Retail:
                    //     member_anniversary
                    //
                    // Corporate/SME:
                    //     corp_anniversary
                    // -----------------------------------------------------
                    DB::raw("
                            CASE
                                WHEN premium_invoice.member_no IS NOT NULL
                                THEN member_anniversary.start_date
                                ELSE corp_anniversary.start_date
                            END AS start_date
                        "),

                    DB::raw("
                            CASE
                                WHEN premium_invoice.member_no IS NOT NULL
                                THEN member_anniversary.end_date
                                ELSE corp_anniversary.end_date
                            END AS end_date
                        "),

                    DB::raw("
                            CASE
                                WHEN premium_invoice.member_no IS NOT NULL
                                THEN member_anniversary.renewal_date
                                ELSE corp_anniversary.renewal_date
                            END AS renewal_date
                        "),


                    // -----------------------------------------------------
                    // Rates
                    // -----------------------------------------------------
                    'premium_invoice.agent_rate',
                    'premium_invoice.bdm_rate',
                    'premium_invoice.hos_rate',


                    // -----------------------------------------------------
                    // Report period
                    // -----------------------------------------------------
                    DB::raw("'$from' as start_date_of_report"),
                    DB::raw("'$to' as end_date_of_report")
                )


                    // =========================================================
                    // CORPORATE
                    // =========================================================
                    ->leftJoin(
                        'corporate',
                        'corporate.corp_id',
                        '=',
                        'premium_invoice.corp_id'
                    )

                    ->leftJoin(
                        'product_category',
                        'corporate.product_category',
                        '=',
                        'product_category.code'
                    )


                    // =========================================================
                    // MEMBER INFORMATION
                    // =========================================================
                    ->leftJoin(
                        'member_info',
                        'member_info.member_no',
                        '=',
                        'premium_invoice.member_no'
                    )


                    // =========================================================
                    // MEMBER MAX ANNIVERSARY
                    //
                    // This only finds the maximum anniv for the member.
                    //
                    // It does NOT eliminate corporate invoices because it is
                    // a LEFT JOIN.
                    // =========================================================
                    ->leftJoinSub($max_anniv, 'max_anniv', function ($join) {
                        $join->on(
                            'max_anniv.member_no',
                            '=',
                            'premium_invoice.member_no'
                        );
                    })


                    // =========================================================
                    // ACTUAL MEMBER ANNIVERSARY RECORD
                    //
                    // Match the member's maximum anniv to the actual row
                    // containing the dates.
                    // =========================================================
                    ->leftJoin(
                        'member_anniversary',
                        function ($join) {
                            $join->on(
                                'member_anniversary.member_no',
                                '=',
                                'premium_invoice.member_no'
                            )
                                ->on(
                                    'member_anniversary.anniv',
                                    '=',
                                    'max_anniv.max_anniv'
                                );
                        }
                    )


                    // =========================================================
                    // CORPORATE MAX ANNIVERSARY
                    //
                    // This finds the maximum anniv for each corporation.
                    // =========================================================
                    ->leftJoinSub($max_canniv, 'max_canniv', function ($join) {
                        $join->on(
                            'max_canniv.corp_id',
                            '=',
                            'premium_invoice.corp_id'
                        );
                    })


                    // =========================================================
                    // ACTUAL CORPORATE ANNIVERSARY RECORD
                    //
                    // Match the corporation's maximum anniv to the actual
                    // corp_anniversary row containing the dates.
                    // =========================================================
                    ->leftJoin(
                        'corp_anniversary',
                        function ($join) {
                            $join->on(
                                'corp_anniversary.corp_id',
                                '=',
                                'premium_invoice.corp_id'
                            )
                                ->on(
                                    'corp_anniversary.anniv',
                                    '=',
                                    'max_canniv.max_anniv'
                                );
                        }
                    )


                    // =========================================================
                    // PAYMENTS
                    // =========================================================
                    ->leftJoinSub($pids, 'pids', function ($join) {
                        $join->on(
                            'pids.invoice_no',
                            '=',
                            'premium_invoice.invoice_no'
                        );
                    })


                    // =========================================================
                    // REPORT DATE RANGE
                    // =========================================================
                    ->whereBetween(
                        'premium_invoice.invoice_date',
                        [$from, $to]
                    )


                    // =========================================================
                    // SORTING
                    // =========================================================
                    ->orderBy('business_class', 'asc')
                    ->orderBy('client', 'asc')
                    ->orderBy('category', 'asc')


                    ->get();

                break;






                // // Get the maximum anniversary for each member
                // $max_anniv = MemberAnniversary::select(
                //     'member_no',
                //     DB::raw('MAX(anniv) as max_anniv')
                // )
                //     ->groupBy('member_no');


                // // Get the maximum anniversary for each corporate
                // $cannivs = DB::table('corp_anniversary')
                //     ->select(
                //         'corp_id',
                //         DB::raw('MAX(anniv) as anniv')
                //     )
                //     ->groupBy('corp_id');

                // $pids = DB::table('premium_invoice_details_sammary')
                //     ->select(
                //         'invoice_no',
                //         DB::raw('SUM(ISNULL(allocated_amt,0)) as allocated_amt'),
                //         DB::raw("LIST(receipt_no, ', ') as receipt_no")
                //     )
                //     ->where('allocated', 1)
                //     ->groupBy('invoice_no');

                // $data = PremiumInvoice::select(
                //     'premium_invoice.invoice_date',
                //     'premium_invoice.invoice_no',
                //     DB::raw("CASE WHEN product_category.category IS NOT NULL THEN product_category.category ELSE '' END AS category"),
                //     DB::raw("CASE 
                //                 WHEN premium_invoice.member_no is null THEN corporate.corporate                                        
                //                 ELSE CONCAT(member_info.first_name, ' ', member_info.other_names, ' ', member_info.surname)                              
                //                 END AS client"),

                //     DB::raw("CASE 
                //                 WHEN premium_invoice.member_no is null THEN 
                //                         CASE WHEN corporate.individual = 1 THEN 'CORPORATE' ELSE 'SME' END
                //                 ELSE 'RETAIL'                               
                //                 END AS business_class"),

                //     DB::raw("(ISNULL(premium_invoice.premium_net, 0)) as premium_invoiced"),
                //     DB::raw("(ISNULL(pids.allocated_amt, 0)) as premium_received"),
                //     'pids.receipt_no',
                //     'member_anniversary.start_date',
                //     'member_anniversary.end_date',
                //     'member_anniversary.renewal_date',
                //     'premium_invoice.agent_rate',
                //     'premium_invoice.bdm_rate',
                //     'premium_invoice.hos_rate',
                //     DB::raw("'$from' as start_date_of_report"),
                //     DB::raw("'$to' as end_date_of_report")
                // )
                //     ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
                //     ->leftJoin('product_category', 'corporate.product_category', '=', 'product_category.code')

                //     ->leftJoin(
                //         'member_anniversary',
                //         'member_anniversary.member_no',
                //         '=',
                //         'premium_invoice.member_no'
                //     )

                //     ->leftJoin(
                //         'member_info',
                //         'member_info.member_no',
                //         '=',
                //         'premium_invoice.member_no'
                //     )

                //     // Match each member to their own maximum anniversary
                //     ->leftJoinSub($max_anniv, 'max_anniv', function ($join) {
                //         $join->on(
                //             'max_anniv.member_no',
                //             '=',
                //             'member_anniversary.member_no'
                //         )
                //             ->on(
                //                 'max_anniv.max_anniv',
                //                 '=',
                //                 'member_anniversary.anniv'
                //             );
                //     })

                //     ->leftJoinSub($cannivs, 'cannivs', function ($join) {
                //         $join->on('cannivs.anniv', '=', 'premium_invoice.anniv')
                //             ->on('cannivs.corp_id', '=', 'premium_invoice.corp_id');
                //     })

                //     ->leftJoinSub($pids, 'pids', function ($join) {
                //         $join->on(
                //             'pids.invoice_no',
                //             '=',
                //             'premium_invoice.invoice_no'
                //         );
                //     })

                //     ->whereBetween(
                //         'premium_invoice.invoice_date',
                //         [$from, $to]
                //     )
                //     ->orderBy('business_class', 'asc')
                //     ->orderBy('client', 'asc')
                //     ->orderBy('category', 'asc')

                //     ->get();

                // break;
        }



        //loop the data to make calculations
        $data = $data->map(function ($row) use ($from, $to) {

            /*
    |--------------------------------------------------------------------------
    | Policy & Report Dates
    |--------------------------------------------------------------------------
    */

            $start = Carbon::parse($row->start_date);
            $end   = Carbon::parse($row->end_date);


            $fromDate = Carbon::parse($from)->startOfYear();
            $toDate = Carbon::parse($to);

            /*
    |--------------------------------------------------------------------------
    | Base Values
    |--------------------------------------------------------------------------
    |
    | These are reused throughout all financial calculations.
    |--------------------------------------------------------------------------
    */

            $premium          = $row->premium_invoiced ?? 0;
            $premiumReceived  = $row->premium_received ?? 0;
            $agentRate        = $row->agent_rate ?? 0;
            $bdmRate          = $row->bdm_rate ?? 0;
            $hosRate          = $row->hos_rate ?? 0;

            /*
    |--------------------------------------------------------------------------
    | start date of report 
    |--------------------------------------------------------------------------
    */

            // $row->start_date_of_report = $fromDate->format('Y-m-d');
            $row->start_date_of_report = $from;
            /*
    |--------------------------------------------------------------------------
    | Period of Cover in Days
    |--------------------------------------------------------------------------
    */

            $row->period_of_cover_in_days = $start->diffInDays($end) + 1;

            /*
    |--------------------------------------------------------------------------
    | Outstanding Premium
    |--------------------------------------------------------------------------
    |
    | Formula:
    |
    | Premium Invoiced
    |          -
    | Premium Received
    |--------------------------------------------------------------------------
    */

            $row->outstanding_premium =
                $premium - $premiumReceived;

            /*
    |--------------------------------------------------------------------------
    | Contract Serviced Days
    |--------------------------------------------------------------------------
    |
    | Calculates the number of policy days falling within the
    | reporting period.
    |
    | Equivalent to the Excel formula:
    |
    | =MAX(0, MIN(PolicyEnd, ReportEnd)
    |          - MAX(PolicyStart, ReportStart)
    |          + 1)
    |--------------------------------------------------------------------------
    */
            if ($fromDate->gt($toDate)) { // check erreneously if start date of report is greater than end date of report
                $row->contract_serviced_days = 0;
            } else {

                $overlapStart = $start->copy()->greaterThan($fromDate)
                    ? $start->copy()
                    : $fromDate->copy();

                $overlapEnd = $end->copy()->lessThan($toDate)
                    ? $end->copy()
                    : $toDate->copy();

                if ($overlapEnd->lt($overlapStart)) {
                    $row->contract_serviced_days = 0;
                } else {
                    $row->contract_serviced_days =
                        $overlapStart->diffInDays($overlapEnd) + 1;
                }
            }
            /*
    |--------------------------------------------------------------------------
    | Gross Earned Premium (GEP)
    |--------------------------------------------------------------------------
    |
    | Formula:
    |
    | (Premium Invoiced / Period of Cover Days)
    |              ×
    | Contract Serviced Days
    |--------------------------------------------------------------------------
    */

            if ($row->period_of_cover_in_days == 0) {
                $row->gep = 0;
            } else {
                $row->gep = ($premium == 0 || $row->period_of_cover_in_days == 0)
                    ? 0
                    : ($premium / $row->period_of_cover_in_days)
                    * $row->contract_serviced_days;
            }

            /*
    |--------------------------------------------------------------------------
    | Unearned days
    |--------------------------------------------------------------------------
 
    */

            $unearned_days = $end > $toDate ? $toDate->diffInDays($end) : 0;

            /*
    |--------------------------------------------------------------------------
    | Unearned days
    |--------------------------------------------------------------------------
 
    */

            $daily_rate = ($premium == 0 || $row->contract_serviced_days == 0)
                ? 0
                : $premium / $row->contract_serviced_days;


            /*
    |--------------------------------------------------------------------------
    | Unearned Premium Reserve (UPR)
    |--------------------------------------------------------------------------
    |
    | Formula:
    |
    | Premium Invoiced
    |      -
    | Gross Earned Premium
    |--------------------------------------------------------------------------
    */

            $row->upr = $daily_rate * $unearned_days;

            /*
    |--------------------------------------------------------------------------
    | Gross Commission - Agent
    |--------------------------------------------------------------------------
    */

            $row->gross_commission_agent =
                $premium * ($agentRate / 100);

            /*
    |--------------------------------------------------------------------------
    | Gross Commission - Override (BDM)
    |--------------------------------------------------------------------------
    */

            $row->gross_commission_override =
                ($premium * ($bdmRate / 100)) + ($premium * ($hosRate / 100));

            /*
    |--------------------------------------------------------------------------
    | Total Gross Commission
    |--------------------------------------------------------------------------
    */

            $row->total_gross_commision =
                ($premium * ($agentRate / 100)) +
                ($premium * ($bdmRate / 100)) + ($premium * ($hosRate / 100));

            /*
    |--------------------------------------------------------------------------
    | Commission Expense
    |--------------------------------------------------------------------------
    |
    | Formula:
    |
    | (Gross Commission Agent / Period of Cover Days)
    |                ×
    | Contract Serviced Days
    |--------------------------------------------------------------------------
    */

            if ($row->period_of_cover_in_days == 0) {
                $row->commission_expense = 0;
            } else {
                $row->commission_expense =
                    ($row->total_gross_commision / $row->period_of_cover_in_days)
                    * $row->contract_serviced_days;
            }

            /*
    |--------------------------------------------------------------------------
    | Deferred Acquisition Cost (DAC)
    |--------------------------------------------------------------------------
    */

            $row->deferred_acquisition_cost =
                $row->total_gross_commision -
                $row->commission_expense;

            /*
    |--------------------------------------------------------------------------
    | Liability for Remaining Coverage (LRC)
    |--------------------------------------------------------------------------
    |
    | Formula:
    |
    | UPR
    |      -
    | Outstanding Premium
    |      -
    | Deferred Acquisition Cost
    |--------------------------------------------------------------------------
    */

            $row->liability_for_remaining_coverage =
                $row->upr
                - $row->outstanding_premium
                - $row->deferred_acquisition_cost;

            return $row;
        });
        return $data;
    }
}
