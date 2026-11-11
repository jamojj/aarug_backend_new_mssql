<?php

namespace App\Http\Controllers\Corporate;

use App\Http\Controllers\Controller;
use App\Models\AdminFeeType;
use App\Models\Benefit;
use App\Models\BenefitSharing;
use App\Models\Category;
use App\Models\ContactRelation;
use App\Models\Corporate\CorpAdminFee;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\CorpContactPerson;
use App\Models\Corporate\CorpGroups;
use App\Models\Corporate\Corporate;
use App\Models\Corporate\CorpProvider;
use App\Models\NewUserAccessLog;
use App\Models\Products;
use App\Models\ProductName;
use App\Models\Provider;
use App\Models\Title;
use App\Models\Town;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CorporateController extends Controller
{
    //fetch corporates
    public function fetchAllCorporates()
    {
        $corporates = Corporate::orderBy('CORPORATE')->get();
        return $corporates;
    }
    //fetch all active corporates
    public function fetchActiveCorporates()
    {
        $corporates = Corporate::where(DB::raw("isnull(cancelled, 0)"), 0)
            ->orderBy('CORPORATE')->get();
        return $corporates;
    }
    //fetch corporate where individual is either one, two, or three
    public function fetchSpecificCorporate($individual)
    {
        $corporate = Corporate::whereNull('enhanced')->where('individual', $individual)->orderBy('CORPORATE')->get();
        return $corporate;
    }
    //fetch kitanda corporates
    public function fetchKitandaCorporates()
    {
        $corporate = Corporate::where(DB::raw("isnull(enhanced, 0)"), 4)->orderBy('CORPORATE')->get();
        return $corporate;
    }
    //fetch hospital cash corporates
    public function fetchHospitalCashCorporates()
    {
        $corporate = Corporate::select('corp_id', 'corporate')->where(DB::raw("isnull(product_category, 0)"), 1)->orderBy('corporate')->get();
        return $corporate;
    }
    //fetch liberty blue corporates
    public function fetchLibertyBlueCorporates()
    {
        $corporate = Corporate::select('corp_id', 'corporate')->where(DB::raw("isnull(product_category, 0)"), 5)->orderBy('corporate')->get();
        return $corporate;
    }


    //fetch corporate agent
    public function fetchCorporateAgent($corp_id)
    {
        $max_anniv = CorpAnniversary::where('corp_id', $corp_id)->max('anniv');
        $corporate_agent = Corporate::select('agent.agent_name', 'agent.agent_id', 'corporate.corporate', 'corp_anniversary.anniv')
            ->leftJoin('agent', 'agent.agent_id', '=', 'corporate.agent_id')
            ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
            ->where('corporate.corp_id', $corp_id)
            ->where('corp_anniversary.anniv', $max_anniv)
            ->get();

        return $corporate_agent;
    }

    //fetch towns
    public function fetchAllTowns()
    {
        $towns = Town::all();
        return $towns;
    }

    //fetch all titles
    public function fetchAllTitles()
    {
        $titles = Title::all();
        return $titles;
    }

    //fetch all contact relations
    public function fetchAllContactRelations()
    {
        $contact_relations = ContactRelation::all();
        return $contact_relations;
    }

    //fetch all admin fee types
    public function fetchAllAdminFeeTypes()
    {
        $admin_fee_types = AdminFeeType::all();
        return $admin_fee_types;
    }

    //fetch all categories
    public function fetchAllCategories()
    {
        $categories = Category::all();
        return $categories;
    }

    //fetch product names
    public function fetchAllProducts()
    {
        $product_names = ProductName::all();
        return $product_names;
    }

    //fetch all benefits
    public function fetchAllBenefits()
    {
        $benefits = Benefit::all();
        return $benefits;
    }

    //fetch all sharing types
    public function fetchAllSharingTypes()
    {
        $sharing_types = BenefitSharing::all();
        return $sharing_types;
    }

    //fetch all providers
    public function fetchAllProviders()
    {
        $providers = Provider::select("*")->orderBy('provider')->get();
        return $providers;
    }

    //fetch all benefits after selecting certain product
    public function fetchProductBenefits($selected_health_plan, $selected_product_name)
    {
        $product_benefit_data = Products::select(
            't_products.id',
            't_products.product_name as product_code',
            't_product_name.product_name',
            't_products.benefit as benefit_code',
            'benefit.benefit',
            't_products.family_limit',
            't_products.limit',
            't_products.sharing as sharing_code',
            'benefit_sharing.sharing',
            't_products.fund',
            't_products.capitated',
            't_products.quantity',
            't_products.health_plan',
            't_products.sub_limit_of as sub_limit_of_code',
            DB::raw('(select benefit from benefit where t_products.sub_limit_of = benefit.code) as sub_limit_of')
        )
            ->leftJoin('t_product_name', 't_products.product_name', '=', 't_product_name.code')
            ->leftJoin('benefit', 't_products.benefit', '=', 'benefit.code')
            ->leftJoin('benefit_sharing', 't_products.sharing', '=', 'benefit_sharing.code')
            ->orderBy('t_products.id', 'ASC')
            ->where('t_products.health_plan', $selected_health_plan)
            ->where('t_products.product_name', $selected_product_name)
            ->get();


        $health_plan = Category::all();
        $product_name = ProductName::select('code', 'product_name')->get();
        $benefit = Benefit::select('code', 'benefit')->get();
        $sharing = BenefitSharing::all();

        $data = [
            'fetched_benefits' => $product_benefit_data,
            'health_plan' => $health_plan,
            'product_name' => $product_name,
            'benefit' => $benefit,
            'sharing' => $sharing
        ];

        return $data;
    }

    public function saveAddCorporateData(Request $request)
    {

        // return $request;
        //variables for access log
        $max_idx = NewUserAccessLog::max('idx');
        if (is_null($max_idx)) {
            $max_idx = 0;
        }
        $max_idx++;
        $log_date = date("Y-m-d");
        $log_time = date("H:i:s");

        //variables for add corporate
        $ls_corp_cnt = '';
        $error_arr = array();
        $idx = Corporate::select('idx')->max('idx');
        if (is_null($idx) || $idx == 0) {
            $idx = 1;
        } else {
            $idx = $idx + 1;
        }
        $corp_id = (string)$idx;
        //generate policy code
        $ls_idx_count = strlen($corp_id);
        if ($ls_idx_count == 1) {
            $ls_corp_cnt = '00000' . $corp_id;
        } elseif ($ls_idx_count == 2) {
            $ls_corp_cnt = '0000' . $corp_id;
        } elseif ($ls_idx_count == 3) {
            $ls_corp_cnt = '000' . $corp_id;
        } elseif ($ls_idx_count == 4) {
            $ls_corp_cnt = '00' . $corp_id;
        } elseif ($ls_idx_count == 5) {
            $ls_corp_cnt = '0' . $corp_id;
        } elseif ($ls_idx_count == 6) {
            $ls_corp_cnt = $corp_id;
            array_push($error_arr, 'Notice ! Sorry corporate allocation number too large...
                    Consult the System Administrator');
        }
        $ls_policy_no = $ls_corp_cnt . ' - ' . date('Y');

        if (count($error_arr) > 0) {
            return ['errors' => $error_arr];
        } else {
            try {
                //DB transact rolls back incase of an error/exception
                DB::transaction(function () use ($ls_policy_no, $idx, $corp_id, $request) {
                    //set anniv to 1
                    $anniv = 1;
                    //set inserted, updated, and deleted items
                    $inserted_item = $request->corporate . ' (corp_id : ' . $corp_id . ')';
                    $updated_item = $deleted_item = null;

                    //Corporate tab data
                    $corporate_data = [
                        'CORP_ID' => $corp_id,
                        'CORPORATE' => $request->corporate,
                        'TEL_NO' => $request->tel_no,
                        'FAX_NO' => $request->fax_no,
                        'MOBILE_NO' => $request->mobile_no,
                        'POSTAL_ADD' => $request->postal_add,
                        'TOWN' => (int)$request->town,
                        'PHY_LOC' => $request->phy_loc,
                        'EMAIL' => $request->email,
                        'USER_ID' => $request->user,
                        'AGENT_ID' => $request->agent_id,
                        'product_category' => $request->product_category,
                        'idx' => $idx,
                        'date_entered' => $request->input('date_entered'),
                        'CANCELLED' => 0,
                        'policy_no' => $ls_policy_no,
                        'individual' => (int)$request->class_type,
                        'corporate_identity' => $request->identity,
                        'smart_sync' => $request->smart == 1 ? 1 : null,
                        'sync' => $request->smart == 1 ? null : 1,
                    ];
                    Corporate::create($corporate_data);

                    //Corp Anniversary dates
                    CorpAnniversary::create([
                        'CORP_ID' => $corp_id,
                        'ANNIV' => $anniv,
                        'START_DATE' => $request->input('start_date'),
                        'END_DATE' => $request->input('end_date'),
                        'RENEWAL_DATE' => $request->input('renewal_date'),
                        'AGENT_ID' => $request->input('agent_id'),
                        'USER_ID' => $request->user,
                        'DATE_ENTERED' => today(),
                        'smart_sync' => $request->smart == 1 ? 1 : null,
                        'sync' => $request->smart == 1 ? null : 1,
                    ]);

                    //Corporate contact person
                    if (isset($request->corp_contact_person_title)) {
                        $contact_person_count = count($request->input('corp_contact_person_title'));
                        for ($i = 0; $i < $contact_person_count; $i++) {
                            CorpContactPerson::create([
                                'CORP_ID' => $corp_id,
                                'TITLE' => (int)$request->input('corp_contact_person_title')[$i],
                                'SURNAME' => $request->input('corp_contact_person_surname')[$i],
                                'FIRST_NAME' => $request->input('corp_contact_person_first_name')[$i],
                                'OTHER_NAMES' => $request->input('corp_contact_person_other_names')[$i],
                                'JOB_TITLE' => (int)$request->input('corp_contact_person_relation')[$i],
                                'MOBILE_NO' => $request->input('corp_contact_person_mobile_no')[$i],
                                'TEL_NO' => $request->input('corp_contact_person_tel_no')[$i],
                                'EMAIL' => $request->input('corp_contact_person_email')[$i]
                            ]);
                        }
                    }

                    //Corporate admin fee regulation
                    if (isset($request->corp_admin_fee_reg_admin_fee_type)) {
                        $corp_admin_fee_type_data = [
                            'corp_id' => $corp_id,
                            'anniv' => $anniv,
                            'admin_fee_type' => $request->input('corp_admin_fee_reg_admin_fee_type'),
                            'admin_fee_rate' => $request->input('corp_admin_fee_reg_admin_fee_rate'),
                            'upfront_copay' => $request->input('corp_admin_fee_reg_upfront_copay'),
                            'agent_commis_rate' => $request->input('corp_admin_fee_reg_agent_commis_rate'),
                            'commis_whtax_rate' => $request->input('corp_admin_fee_reg_commis_whtax_rate'),
                            'unit_manager_rate' => $request->input('corp_admin_fee_reg_unit_manager_rate'),
                            'bdm_rate' => $request->input('corp_admin_fee_reg_bdm_rate')
                        ];
                        CorpAdminFee::create($corp_admin_fee_type_data);
                    }

                    //corporate benefits data
                    if (isset($request->benefit)) {
                        $corp_benefits_count = count($request->input('benefit'));
                        for ($i = 0; $i < $corp_benefits_count; $i++) {
                            CorpGroups::create([
                                'idx' => $this->fetchLatestCorpGroupsIdx(),
                                'corp_id' => $corp_id,
                                'anniv' => $anniv,
                                'category' => $request->health_plan[$i],
                                'benefit' => (int)$request->benefit[$i],
                                'fund' => (int)$request->fund[$i],
                                'limit' => (int)$request->limit[$i],
                                'sharing' => (int)$request->sharing[$i],
                                'sub_limit_of' => (int)$request->sub_limit_of[$i],
                                'capitated' => (int)$request->capitated[$i],
                                'quantity' => (int)$request->quantity[$i],
                                'product_name' => (int)$request->product_name[$i],
                                'smart_sync' => $request->smart == 1 ? 1 : null,
                                'sync' => $request->smart == 1 ? null : 1,

                            ]);
                        }
                    }
                    //Corporate providers
                    if (isset($request->appended_provider)) {
                        for ($i = 0; $i < count($request->appended_provider); $i++) {
                            //check if provider exists
                            $provider_exists = Provider::where('code', $request->appended_provider[$i])->get();
                            if (count($provider_exists) != 0) {
                                //check for dublication                              
                                $corp_provider_exists = CorpProvider::where('provider', $request->appended_provider[$i])->where('corp_id', $corp_id)->get();
                                if (count($corp_provider_exists) == 0) {
                                    CorpProvider::create([
                                        'corp_id' => $corp_id,
                                        'anniv' => $anniv,
                                        'provider' => (int)$request->appended_provider[$i],
                                        'copay_amt' => (float)$request->appended_copay_amount[$i],
                                    ]);
                                }
                            }
                        }
                    }

                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                });
                return ['Corporate Saved Successfully !'];
            } catch (Exception $e) {
                return ["Error " . $e->getMessage()];
            }
        }
    }

    //generate new corp id
    private function generateCorpId()
    {
        $numberOfSchemes = Corporate::max('idx');
        return (int)$numberOfSchemes + 1;
    }

    //
    public function fetchLatestCorpGroupsIdx()
    {

        $idx = CorpGroups::max('idx');
        return $idx += 1;
    }
}
