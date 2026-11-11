<?php

use App\Http\Controllers\AccessLogs\AccessLogController;
use App\Http\Controllers\Admin\AddSelectionItems\ProviderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AgentManagementController;
use App\Http\Controllers\Admin\AddSelectionItems\AllergyController;
use App\Http\Controllers\Admin\AddSelectionItems\BankAccountController;
use App\Http\Controllers\Admin\ChangePasswordController;
use App\Http\Controllers\Admin\AddSelectionItems\ExclusionsController;
use App\Http\Controllers\Admin\AddSelectionItems\IllnessController;
use App\Http\Controllers\Admin\RateSheetController;
use App\Http\Controllers\Admin\ReinsurerExcessOfLossController;
use App\Http\Controllers\Admin\ReinsurerQuotaShareController;
use App\Http\Controllers\Bills\BillPayment\QueryVoucherController;
use App\Http\Controllers\Bills\BillPayment\ReimburseMemberController;
use App\Http\Controllers\Bills\BillPayment\PayBatchController;
use App\Http\Controllers\Bills\BillPayment\QueryPaidVoucherController;
use App\Http\Controllers\Bills\BillPayment\ReimburseCorporateController;
use App\Http\Controllers\Bills\BillProcess\QueryClaimsController;
use App\Http\Controllers\Bills\BillProcess\VetBillController;
use App\Http\Controllers\Corporate\ChangeAgentController;
use App\Http\Controllers\Corporate\CorporateController;
use App\Http\Controllers\Corporate\CorporateReportsController;
use App\Http\Controllers\Corporate\QueryCorporateController;
use App\Http\Controllers\Corporate\RenewCorporateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\FinanceAllocationController;
use App\Http\Controllers\Registration\ImportMemberInformation;
use App\Http\Controllers\Registration\EnquiryPagesController;
use App\Http\Controllers\Registration\ImportPreloadedMembers;
use App\Http\Controllers\Registration\MemberManagement\UnderwriteMemberController;
use App\Http\Controllers\Registration\MemberManagement\CancelMemberController;
use App\Http\Controllers\Registration\MemberManagement\ProductChangeController;
use App\Http\Controllers\Reusables\AdminFeeController;
use App\Http\Controllers\Reusables\MemberDropdownsController;
use App\Http\Controllers\Reusables\SearchPrincipalController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\Reusables\ServiceController;
use App\Http\Controllers\Registration\MembershipRenewalController;
use App\Http\Controllers\Registration\Reports\CorporatePopulationController;
use App\Http\Controllers\Reusables\FetchAnyItemController;
use App\Http\Controllers\Finance\PremiumDebitingController;
use App\Http\Controllers\Finance\PremiumReceiptController;
use App\Http\Controllers\Finance\ReceiptReversalController;
use App\Http\Controllers\Finance\DebitReversalController;
use App\Http\Controllers\Finance\FinanceQuotationController;
use App\Http\Controllers\Registration\MemberManagement\CardsController;
use App\Http\Controllers\Registration\MemberManagement\SmartRequestController;
use App\Http\Controllers\Bills\BillProcess\EnterBillController;
use App\Http\Controllers\Bills\BatchClaims\BatchClaimsController;
use App\Http\Controllers\Bills\BillPayment\QueryChequeController;
use App\Http\Controllers\Bills\BillProcess\QueryBatchController;
use App\Http\Controllers\Bills\BillPayment\PayProviderController;
use App\Http\Controllers\Bills\BillPayment\PayProviderFundController;
use App\Http\Controllers\Bills\BillPayment\UnvoucherClaimsController;
use App\Http\Controllers\Bills\BillProcess\AddAnInvoiceController;
use App\Http\Controllers\Care\PreauthorizationController;
use App\Http\Controllers\Care\AdmissionVisitController;
use App\Http\Controllers\Care\CareAdmissionController;
use App\Http\Controllers\Care\DeclineController;
use App\Http\Controllers\Care\CareReports\EditPreAuthorisationController;
use App\Http\Controllers\Care\CorporateVisitController;
use App\Http\Controllers\Bills\BillProcess\ImportHospitalBillsController;
use App\Http\Controllers\Bills\Reports\BatchReportController;
use App\Http\Controllers\Bills\Reports\ClaimsExperienceController;
use App\Http\Controllers\Bills\Reports\ClaimsRegisterController;
use App\Http\Controllers\Bills\Reports\ClaimsStatusSummaryController;
use App\Http\Controllers\Bills\Reports\OutstandingBillsController;
use App\Http\Controllers\Bills\Reports\RejectionRateController;
use App\Http\Controllers\Bills\Reports\VettedBillsController;
use App\Http\Controllers\Commission\intermediary\PayCommissionController;
use App\Http\Controllers\Commission\override\PayHosController;
use App\Http\Controllers\Registration\ImportContactDetailsController;
use App\Http\Controllers\Commission\intermediary\EnterAgentDeductionsController;
use App\Http\Controllers\Commission\override\PayAssistantHosController;
use App\Http\Controllers\Commission\override\PayOverrideBDMController;
use App\Http\Controllers\Commission\reports\CommissionStatementController;
use App\Http\Controllers\Finance\Reports\ClaimsReserveController;
use App\Http\Controllers\Finance\Reports\PremiumRegisterController;
use App\Http\Controllers\Finance\Reports\FundStatementController;
use App\Http\Controllers\Registration\Reports\ValidListController;
use App\Http\Controllers\Registration\Reports\DebitedMembersController;
use App\Http\Controllers\Registration\Reports\MembershipListController;
use App\Http\Controllers\Finance\Reports\PremiumStatementController;
use App\Http\Controllers\Bills\Reports\UtilizationReportController;
use App\Http\Controllers\Bills\Reports\BatchProcessReportController;
use App\Http\Controllers\Bills\Reports\ClaimsExperienceExtractController;
use App\Http\Controllers\Bills\Reports\RejectedBillsReportController;
use App\Http\Controllers\Bills\Reports\SuspendedBillsController;
use App\Http\Controllers\Care\CareReports\AdmissionAuthorizationController;
use App\Http\Controllers\Care\CareReports\CareAdmissionsReportController;
use App\Http\Controllers\Care\CareReports\CareVisitsController;
use App\Http\Controllers\Care\CareReports\CaseManagementController;
use App\Http\Controllers\Care\CareReports\CorporateNeedsController;
use App\Http\Controllers\Care\CareReports\PatientCommentsReport;
use App\Http\Controllers\Registration\MemberManagement\updateMemberController;
use App\Http\Controllers\Reinsurance\ExcessOfLossController;
use App\Http\Controllers\Reinsurance\QuotaShare\ClaimsPaymentScheduleController;
use App\Http\Controllers\Reinsurance\QuotaShare\PremiumsQuotaShareController;
use App\Http\Controllers\Finance\QueryPremiumDebitsController;
use App\Http\Controllers\Care\CareReports\PastAdmissionsController;
use App\Http\Controllers\Care\CareReports\TasksFollowUpController;
use App\Http\Controllers\Corporate\PolicyDocumentController;
use App\Http\Controllers\Management\Reports\OperationsUserActivityController;
use App\Http\Controllers\Admin\UserManagement\DeleteLogListController;
use App\Http\Controllers\Admin\AddSelectionItems\BankController;
use App\Http\Controllers\Admin\AddSelectionItems\BenefitController;
use App\Http\Controllers\Admin\AddSelectionItems\BdmController;
use App\Http\Controllers\Admin\AddSelectionItems\ExchangeRateController;
use App\Http\Controllers\Admin\AddSelectionItems\PriceListController;
use App\Http\Controllers\Admin\AddSelectionItems\PriceListYearController;
use App\Http\Controllers\Admin\AddSelectionItems\ReversalReasonsController;
use App\Http\Controllers\Admin\AddSelectionItems\SuspendRejectReasonController;
use App\Http\Controllers\Admin\AddSelectionItems\TransactionRateController;
use App\Http\Controllers\Admin\AddSelectionItems\LevyController;
use App\Http\Controllers\Admin\AddSelectionItems\ProductNameController;
use App\Http\Controllers\Admin\AddSelectionItems\ResendReasonController;
use App\Http\Controllers\Admin\AddSelectionItems\DeclineReasonController;
use App\Http\Controllers\Admin\AddSelectionItems\CorporateVisitIssueController;
use App\Http\Controllers\Admin\AddSelectionItems\ConversionRateController;
use App\Http\Controllers\Admin\AddSelectionItems\UnitsController;
use App\Http\Controllers\Admin\AddSelectionItems\UnitManagerController;
use App\Http\Controllers\Admin\AddSelectionItems\AgencyController;
use App\Http\Controllers\Admin\AddSelectionItems\CapitationUploadController;
use App\Http\Controllers\Admin\AddSelectionItems\CignaClientRatesController;
use App\Http\Controllers\Admin\AddSelectionItems\CignaUsdExchangeRateController;
use App\Http\Controllers\Admin\AddSelectionItems\DeductionReasonsController;
use App\Http\Controllers\Admin\AddSelectionItems\DiagnosisClassController;
use App\Http\Controllers\Admin\AddSelectionItems\DiagnosisController;
use App\Http\Controllers\Admin\AddSelectionItems\DoctorsController;
use App\Http\Controllers\Admin\AddSelectionItems\RejectReasonController;
use App\Http\Controllers\Admin\AddSelectionItems\SchemeRulesController;
use App\Http\Controllers\Admin\AddSelectionItems\ServiceController as AddSelectionItemsServiceController;
use App\Http\Controllers\Admin\AddSelectionItems\PlanClassGroupController;
use App\Http\Controllers\Admin\AddSelectionItems\ProductsController;
use App\Http\Controllers\Admin\AddSelectionItems\SmartCardReprintReasonController;
use App\Http\Controllers\Admin\AddSelectionItems\UploadPriceListController;
use App\Http\Controllers\Admin\UserManagement\ManageUserController;
use App\Http\Controllers\AgeingAnalysisController;
use App\Http\Controllers\AgeingAnalysisFinanceController;
use App\Http\Controllers\AntiMoneyLaunderingController;
use App\Http\Controllers\BenefitBalanceController;
use App\Http\Controllers\Bills\BillProcess\ChronicManagementController;
use App\Http\Controllers\Bills\BillProcess\EnterBillByLineItemsController;
use App\Http\Controllers\Bills\CapitationCalculationController;
use App\Http\Controllers\Bills\Reports\BatchReportSummaryController;
use App\Http\Controllers\Bills\Reports\BillPendsController;
use App\Http\Controllers\Bills\Reports\ClaimsStatusDetailedController;
use App\Http\Controllers\Bills\Reports\OverUtilizationController;
use App\Http\Controllers\Bills\VetApprovalController;
use App\Http\Controllers\Bills\VoucherBatchingController;
use App\Http\Controllers\CapitationReportController;
use App\Http\Controllers\CignaInvoiceReportController;
use App\Http\Controllers\ClaimsAverageAmountController;
use App\Http\Controllers\ClaimsPaymentController;
use App\Http\Controllers\DataAnalysisUtilizationReportController;
use App\Http\Controllers\DownloadTemplateController;
use App\Http\Controllers\Finance\ChangeAgentRates;
use App\Http\Controllers\Finance\CignaPremiumReceiptController;
use App\Http\Controllers\Finance\PremiumDebitingCignaController;
use App\Http\Controllers\Finance\PremiumDebitingHospitalCashController;
use App\Http\Controllers\Finance\PremiumInvoicingController;
use App\Http\Controllers\Finance\QueryCignaPremiumDebitsController;
use App\Http\Controllers\Finance\RemittanceController;
use App\Http\Controllers\Finance\Reports\MapaController;
use App\Http\Controllers\Finance\Reports\PremiumStatementCignaController;
use App\Http\Controllers\Finance\Reports\ProviderReportController;
use App\Http\Controllers\Finance\UnresolvedClaimsController;
use App\Http\Controllers\IfrsController;
use App\Http\Controllers\IpopReportController;
use App\Http\Controllers\Management\Reports\CorporatePerformanceController;
use App\Http\Controllers\Management\Reports\CorporateUtilizationReport;
use App\Http\Controllers\Management\Reports\IndividualAndCompaniesController;
use App\Http\Controllers\Management\Reports\MemberVisitsController;
use App\Http\Controllers\MemberSummaryController;
use App\Http\Controllers\MomRenewalTrackerController;
use App\Http\Controllers\PahapaMessageSchedules;
use App\Http\Controllers\ProviderAverageCostController;
use App\Http\Controllers\Registration\ImportCignaMemberInformation;
use App\Http\Controllers\Registration\MemberApprovalController;
use App\Http\Controllers\Registration\MemberManagement\CancelMemberUploadController;
use App\Http\Controllers\Registration\PremiumQuotation;
use App\Http\Controllers\Registration\Reports\CardReprintController;
use App\Http\Controllers\Reusables\updateRecordsController;
use App\Http\Controllers\UserRightsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
//Login Routes
Route::post('fetch_users', [UsersController::class, 'fetchUsers']);
//generate mfa token
Route::post('generate_mfa_token', [UsersController::class, 'generateMfaToken']);
//verify mfa code login
Route::post('verify_mfa_code_automatic', [UsersController::class, 'verifyMfaCodeAutomatic']);
//verify mfa code manual
Route::post('verify_mfa_code_manual', [UsersController::class, 'verifyMfaCodeManual']);
//get short term expiry
Route::post('get_short_expiry_time', [UsersController::class, 'getShortExpiryTime']);
//update password
Route::post('update_user_password', [UsersController::class, 'updateUserPassword']);
//update email
Route::post('update_user_email', [UsersController::class, 'updateUserEmail']);
//check last password change
Route::post('check_last_password_change', [UsersController::class, 'checkLastPasswordChange']);
//check if blocked
Route::post('check_if_active_or_blocked', [UsersController::class, 'checkIfActiveOrBlocked']);
//check if user email exists
Route::get('check_email_exists/{user}', [UsersController::class, 'checkEmailExists']);



//dashboard
Route::get('dashboard', [DashboardController::class, 'dashboard']);
Route::get('dashboard_advanced/{username}', [DashboardController::class, 'dashboardAdvanced']);
Route::get('fetch_dashboard_data/{val}', [DashboardController::class, 'fetchDashboardData']);

//member summary 
Route::get('fetch_member_summary/{member_no}', [MemberSummaryController::class, 'fetchMemberSummary']);

/*
 * Corporate Routes
 * All api used by corporates
*/
//Fetch all corporates for select dropdown population
Route::get('fetch_corporates', [CorporateController::class, 'fetchAllCorporates']);
//Fetch all active corporates for select dropdown population
Route::get('fetch_active_corporates', [CorporateController::class, 'fetchActiveCorporates']);
//Fetch corporates for debiting
Route::get('fetch_corporates_to_debit', [PremiumDebitingController::class, 'fetchCorporatesToDebit']);
//Fetch client where individual is eithe one, two, or three
Route::get('fetch_specific_corporate/{individual}', [CorporateController::class, 'fetchSpecificCorporate']);
//fetch corporates under kitanda
Route::get('fetch_kitanda_corporates', [CorporateController::class, 'fetchKitandaCorporates']);
//fetch corporates under hospital cash
Route::get('fetch_hospital_cash_corporates', [CorporateController::class, 'fetchHospitalCashCorporates']);
//fetch corporates under liberty blue
Route::get('fetch_liberty_blue_corporates', [CorporateController::class, 'fetchLibertyBlueCorporates']);
//fetch hospital wards
Route::get('fetch_hospital_wards', [FetchAnyItemController::class, 'fetchHospitalWards']);
//fetch all usernames from users
Route::get('fetch_user_names', [FetchAnyItemController::class, 'fetchUserNames']);
//fetch hais triggers
Route::get('fetch_hais_triggers', [FetchAnyItemController::class, 'fetchHaisTriggers']);
//fetch smart card reprint reasons
Route::get('fetch_card_reprint_reason', [FetchAnyItemController::class, 'fetchCardReprintReason']);
//fetch family size
Route::get('fetch_family_size', [FetchAnyItemController::class, 'fetchFamilySize']);
//fetch rates relation to principal
Route::get('fetch_rate_sheet_relation', [FetchAnyItemController::class, 'fetchRateSheetRelation']);
//check if optical benefit was used in two consecutive annivs
Route::post('check_optical_used_consecutively', [FetchAnyItemController::class, 'checkOpticalUsedConsecutively']);
//check if benefit has already been changed
Route::post('check_benefit_has_already_been_changed', [FetchAnyItemController::class, 'checkBenefitHasAlreadyBeenChanged']);
//delete existing member benefits
Route::post('delete_benefit_lines', [FetchAnyItemController::class, 'deleteBenefitLines']);

//Fetch corporate agent for change agent functionality
Route::get('fetch_corporate_agent/{corp_id}', [CorporateController::class, 'fetchCorporateAgent']);
//Fetch all towns for select dropdown population
Route::get('fetch_towns', [CorporateController::class, 'fetchAllTowns']);
//Fetch all titles for select dropdown population
Route::get('fetch_titles', [CorporateController::class, 'fetchAllTitles']);
//Fetch all contact relations/job titles for contact person select dropdown population
Route::get('fetch_contact_relations', [CorporateController::class, 'fetchAllContactRelations']);
//Fetch all admin fee types for admin fees type select dropdown population
Route::get('fetch_all_admin_fee_types', [CorporateController::class, 'fetchAllAdminFeeTypes']);
//Fetch all categories/health plan for benefit tab health plan select dropdown population
Route::get('fetch_categories', [CorporateController::class, 'fetchAllCategories']);
//Fetch all products/options for benefit tab products select dropdown population
Route::get('fetch_products', [CorporateController::class, 'fetchAllProducts']);
//Fetch all benefits for benefit tab benefit select dropdown population
Route::get('fetch_benefits', [CorporateController::class, 'fetchAllBenefits']);
//Fetch all sharing options for benefit tab sharing select dropdown population
Route::get('fetch_sharing', [CorporateController::class, 'fetchAllSharingTypes']);
//Fetch all provider for provider tab providers select dropdown population
Route::get('fetch_providers', [CorporateController::class, 'fetchAllProviders']);
//Fetch benefits for each product
Route::get('fetch_product_benefits/{health_plan}/{product_name}', [CorporateController::class, 'fetchProductBenefits']);
/*
 * Service Routes
 * All api used  for Service control
*/
//Fetch all services
Route::get('fetch_services', [ServiceController::class, 'fetchServices']);
/*
* Service Routes
* All api used for Agent control
*/
//agent Routes
Route::get('fetch_agents', [AgentController::class, 'fetchAllAgents']);
Route::get('fetch_active_agents', [AgentController::class, 'fetchActiveAgents']);
Route::get('fetch_agent_bdm_statuses', [AgentController::class, 'fetchAgentBdmStatuses']);
Route::get('fetch_agent_data/{agent_id}', [AgentController::class, 'fetchAgentData']);
Route::post('save_change_corporate_agent', [ChangeAgentController::class, 'saveCorporateChangeAgent']);
Route::get('fetch_principal/{family_no}', [ChangeAgentController::class, 'fetchPrincipalInformation'])->where('family_no', '.*');
Route::post('save_change_retail_agent', [ChangeAgentController::class, 'saveRetailChangeAgent']);

// Corporate Reports
Route::get('corporate_agents', [CorporateReportsController::class, 'corporateAgents']);
Route::get('retail_agents', [CorporateReportsController::class, 'retailAgents']);

// Registration Reports routes
Route::get('corporate_population/{corp_id}', [CorporatePopulationController::class, 'corporatePopulation']);
Route::post('all_corporates_population', [CorporatePopulationController::class, 'allCorporatesPopulationDetails']);

//Save add corporate data
Route::post('save_add_corporate_data', [CorporateController::class, 'saveAddCorporateData']);

/**admin routes **/
//products
Route::get('get_all_products', [ProductsController::class, 'getAllProducts']);
Route::post('update_products', [ProductsController::class, 'updateProducts']);
Route::post('update_products_new', [ProductsController::class, 'updateProductsNew']);
Route::post('save_products', [ProductsController::class, 'saveProducts']);
Route::post('save_new_product', [ProductsController::class, 'saveNewProduct']);
//rate sheet
Route::get('fetch_rate_sheet_data/{corp_id}', [RateSheetController::class, 'fetchRateSheetData']);
Route::post('save_rate_sheet', [RateSheetController::class, 'saveRateSheet']);
//cigna rate sheet
Route::get('fetch_cigna_rate_sheet_data', [CignaClientRatesController::class, 'fetchCignaRateSheetData']);
Route::post('save_cigna_rate_sheet', [CignaClientRatesController::class, 'saveCignaRateSheet']);
//save provider
Route::post('save_provider', [ProviderController::class, 'saveProvider']);
//save provider new
Route::post('save_provider_new', [ProviderController::class, 'saveProviderNew']);
//update provider
Route::post('update_provider', [ProviderController::class, 'updateProvider']);
//upload provider docs
Route::post('upload_provider_docs', [ProviderController::class, 'uploadProviderDocs']);
//agent management
Route::get('fetch_agent_management_data', [AgentManagementController::class, 'fetchAgentManagementData']);
Route::post('save_agents', [AgentManagementController::class, 'saveAgents']);
Route::post('save_commission_rates', [AgentManagementController::class, 'saveCommissionRates']);
//password change by Admin
Route::get('fetch_usernames', [ChangePasswordController::class, 'fetchUsernames']);
Route::post('save_password_change', [ChangePasswordController::class, 'saveChangePassword']);
//password change by User
Route::post('save_profile_password_change', [ChangePasswordController::class, 'saveProfileChangePassword']);
//reinsurance quota share
Route::get('fetch_quota_share', [ReinsurerQuotaShareController::class, 'fetchQuotaShare']);
Route::post('save_quota_share', [ReinsurerQuotaShareController::class, 'saveQuotaShare']);
//reinsurance excess of loss
Route::get('fetch_excess_of_loss', [ReinsurerExcessOfLossController::class, 'fetchExcessOfLoss']);
Route::post('save_excess_of_loss', [ReinsurerExcessOfLossController::class, 'saveExcessOfLoss']);
//fetch admin diagnosis
Route::get('fetch_admin_diagnosis', [DiagnosisController::class, 'fetchDiagnosis']);
Route::post('save_admin_diagnosis', [DiagnosisController::class, 'saveDiagnosis']);
//fetch admin service
Route::get('fetch_service', [AddSelectionItemsServiceController::class, 'fetchService']);
Route::post('save_service', [AddSelectionItemsServiceController::class, 'saveService']);
//fetch admin doctors
Route::get('fetch_doctors', [DoctorsController::class, 'fetchDoctors']);
Route::post('save_doctors', [DoctorsController::class, 'saveDoctors']);
//fetch admin scheme rules
Route::get('fetch_rules', [SchemeRulesController::class, 'fetchSchemeRules']);
Route::post('save_rules', [SchemeRulesController::class, 'saveSchemeRules']);
//fetch admin deduction reasons
Route::get('fetch_deduction_reasons', [DeductionReasonsController::class, 'fetchDeductionReasons']);
Route::post('save_deduction_reasons', [DeductionReasonsController::class, 'saveDeductionReasons']);
//fetch illness
Route::get('fetch_illness', [IllnessController::class, 'fetchIllness']);
Route::post('save_illness', [IllnessController::class, 'saveIllness']);
//fetch exclusions
Route::get('fetch_exclusions', [ExclusionsController::class, 'fetchExclusions']);
Route::post('save_exclusions', [ExclusionsController::class, 'saveExclusions']);
//fetch allergies
Route::get('fetch_allergies', [AllergyController::class, 'fetchAllergies']);
Route::post('save_allergies', [AllergyController::class, 'saveAllergies']);
//fetch suspend/reject reasons
Route::get('fetch_suspend_reject_reasons', [SuspendRejectReasonController::class, 'fetchSuspendRejectReasons']);
Route::post('save_suspend_reject_reasons', [SuspendRejectReasonController::class, 'saveSuspendRejectReasons']);
//fetch price list year
Route::get('fetch_price_list_year', [PriceListYearController::class, 'fetchPriceListYear']);
Route::post('save_price_list_year', [PriceListYearController::class, 'savePriceListYear']);
//fetch bank accounts
Route::get('fetch_accounts', [BankAccountController::class, 'fetchBankAccounts']);
Route::post('save_bank_accounts', [BankAccountController::class, 'saveBankAccounts']);
//fetch exchange rates
Route::get('fetch_exchange_rates', [ExchangeRateController::class, 'fetchExchangeRates']);
Route::post('save_exchange_rates', [ExchangeRateController::class, 'saveExchangeRates']);
//fetch reversal reasons
Route::get('fetch_reversal_reasons', [ReversalReasonsController::class, 'fetchReversalReasons']);
Route::post('save_reversal_reasons', [ReversalReasonsController::class, 'saveReversalReasons']);
//fetch transaction rate
Route::get('fetch_transaction_rates', [TransactionRateController::class, 'fetchTransactionRates']);
Route::post('save_transaction_rates', [TransactionRateController::class, 'saveTransactionRates']);
//fetch bdm
Route::get('fetch_bdm', [BdmController::class, 'fetchBdm']);
Route::get('fetch_active_bdm', [BdmController::class, 'fetchActiveBdm']);
Route::post('save_bdm', [BdmController::class, 'saveBdm']);
//fetch and save smart card reprint reasons
Route::get('fetch_smart_card_reprint_reasons', [SmartCardReprintReasonController::class, 'fetchSmartCardReprintReasons']);
Route::post('save_smart_card_reprint_reasons', [SmartCardReprintReasonController::class, 'SaveSmartCardReprintReasons']);
//fetch price list
Route::post('fetch_service_item_data', [PriceListController::class, 'fetchServiceItems']);
Route::get('fetch_service_item_by_item_code/{item_code}', [PriceListController::class, 'fetchServiceItemsByItemCode']);
Route::post('save_service_items', [PriceListController::class, 'saveServiceItems']);
//upload price list
Route::post('import_price_list', [UploadPriceListController::class, 'importPriceList']);
//fetch admin diagnosis class
Route::get('fetch_diagnosis_class', [DiagnosisClassController::class, 'fetchDiagnosisClass']);
Route::post('save_diagnosis_class', [DiagnosisClassController::class, 'saveDiagnosisClass']);
//save admin added banks
Route::post('save_add_bank', [BankController::class, 'saveBank']);
//save admin added benefits
Route::post('save_add_benefit', [BenefitController::class, 'saveBenefit']);
//save admin added levies
Route::post('save_add_levy', [LevyController::class, 'saveLevy']);
//save admin added product names
Route::post('save_add_product_name', [ProductNameController::class, 'saveProductName']);
//fetch resend reason
Route::get('fetch_resend_reasons', [ResendReasonController::class, 'fetchResendReason']);
//save add resend reasons
Route::post('save_add_resend_reason', [ResendReasonController::class, 'saveResendReason']);
//save add decline reasons
Route::post('save_add_decline_reason', [DeclineReasonController::class, 'saveDeclineReason']);
//save add corporate visit issues
Route::post('save_add_corporate_visit_issue', [CorporateVisitIssueController::class, 'saveCorporateVisitIssue']);
//save add conversion rates
Route::post('save_add_conversion_rate', [ConversionRateController::class, 'saveConversionRates']);
//save add unit
Route::post('save_add_unit', [UnitsController::class, 'saveUnit']);
//save add unit manager
Route::post('save_add_unit_manager', [UnitManagerController::class, 'saveUnitManager']);
//save add agency
Route::post('save_add_agency', [AgencyController::class, 'saveAgency']);
//save added rejection reasons
Route::post('save_add_rejection_reason', [RejectReasonController::class, 'saveRejectionReason']);
//save add plan class group
Route::post('save_add_class_group_plan', [PlanClassGroupController::class, 'saveClassGroupPlan']);
//import capitation
Route::post('import_capitation', [CapitationUploadController::class, 'importCapitation']);
//fetch capitation providers 
Route::get('fetch_capitation_providers', [CapitationCalculationController::class, 'fetchCapitationProviders']);
//capitation calculation 
Route::post('fetch_capitation_calculation', [CapitationCalculationController::class, 'fetchCapitationCalculation']);
//capitation report
Route::post('fetch_capitation_report', [CapitationReportController::class, 'fetchCapitationReport']);




//user management - delete user log list
Route::post('fetch_delete_user_log_list', [DeleteLogListController::class, 'fetchUserDeleteLogList']);
//user management - manage users
Route::get('fetch_manage_users', [ManageUserController::class, 'fetchUsers']);
Route::get('fetch_user_group/{user_name}', [ManageUserController::class, 'fetchUserGroup']);
Route::get('fetch_user_role/{user_name}', [ManageUserController::class, 'fetchUserRole']);
Route::get('fetch_user_permissions/{user_name}', [ManageUserController::class, 'fetchUserPermissions']);
Route::get('fetch_default_password', [ManageUserController::class, 'setDefaultPassword']);
Route::post('save_user_management', [ManageUserController::class, 'saveUserManagement']);
Route::post('delete_user_group', [ManageUserController::class, 'deleteUserGroup']);
Route::post('delete_user_role', [ManageUserController::class, 'deleteUserRole']);
Route::post('delete_user_permission', [ManageUserController::class, 'deleteUserPermission']);

//renew corporate routes
Route::get('fetch_corporate_eligibility_for_renewal/{corp_id}', [RenewCorporateController::class, 'fetchCorporateEligibilityForRenewal']);
Route::get('fetch_data/{corp_id}', [RenewCorporateController::class, 'fetchData']);
Route::post('renew_corporate_data_modified', [RenewCorporateController::class, 'renewCorporateDataModified']);
Route::post('renew_corporate_data', [RenewCorporateController::class, 'renewCorporateData']);
Route::get('fetch_admin_fee', [AdminFeeController::class, 'fetchAdminFee']);

//query corporate routes
Route::get('query_corporate/{corp_id}', [QueryCorporateController::class, 'queryCorporate']);
Route::post('save_query_corporate_data', [QueryCorporateController::class, 'saveQueryCorporateData']);
Route::post('normalize_member_benefits', [QueryCorporateController::class, 'normalizeMemberBenefits']);
Route::post('delete_corporate_product_line', [QueryCorporateController::class, 'deleteCorporateProductLine']);

/*
 * Registration Routes
 * All Routes for member controllers
 * */
Route::get('fetch_corporate/{corp_id}', [UnderwriteMemberController::class, 'fetchCorporate']);
Route::post('fetch_principal_data', [UnderwriteMemberController::class, 'fetchPrincipalData']);
Route::post('fetch_member_data', [UnderwriteMemberController::class, 'fetchMemberData']);
Route::get('fetch_one_principal_data/{member_no}', [UnderwriteMemberController::class, 'fetchOnePrincipalData']);
Route::get('fetch_family/{family_no}', [UnderwriteMemberController::class, 'fetchFamily']);
Route::get('fetch_one_member_data/{member_no}', [UnderwriteMemberController::class, 'fetchOneMemberData']);
Route::post('save_member', [UnderwriteMemberController::class, 'saveMember']);
Route::post('update_member', [updateMemberController::class, 'updateMember']);
//enquiry pages route
Route::post('fetch_enquiry_pages_data', [EnquiryPagesController::class, 'fetchEnquiryPagesData']);
// smart request routes
Route::get('smart_request_data/{member_no}', [SmartRequestController::class, 'smartRequestData']);
Route::post('save_smart_request', [SmartRequestController::class, 'saveSmartRequest']);
// card routes
Route::get('card_data/{member_no}', [CardsController::class, 'cardData']);
//Cancel Member Routes
Route::get('fetch_cancellation_reasons', [CancelMemberController::class, 'fetchCancellationReasons']);
//Product Change route
Route::get('fetch_product_change/{member_no}', [ProductChangeController::class, 'productChange']);
Route::get('fetch_new_product_change/{member_no}/{health_plan}', [ProductChangeController::class, 'newProductChange']);
Route::post('save_product_change', [ProductChangeController::class, 'saveProductChange']);

//Fetch data of client to be cancelled
Route::get('fetch_cancel_member_data/{sel_value_rd}/{input_value}', [CancelMemberController::class, 'fetchCancelMemberData'])->where('input_value', '.*');
//Save Cancel Reinstate Member Data
Route::post('save_cancel_reinstate', [CancelMemberController::class, 'saveCancelReinstateMember']);
//cancel member uploads 
Route::post('cancel_members_upload', [CancelMemberUploadController::class, 'cancelMembersUpload']);

//Import Member Information route
Route::post('validate_member_data', [ImportMemberInformation::class, 'validateMemberData']);
Route::post('save_member_data', [ImportMemberInformation::class, 'saveMemberData']);
//save cigna member data
Route::post('save_cigna_member_data', [ImportCignaMemberInformation::class, 'saveCignaMemberData']);

//Import Preloaded Members route
Route::post('save_preloaded_members', [ImportPreloadedMembers::class, 'savePreloadedMemberData']);
//Update contact details
Route::post('update_contact_details', [ImportContactDetailsController::class, 'updateContactDetails']);
//Update member id no
Route::post('update_member_id_no', [ImportContactDetailsController::class, 'updateMemberIdNo']);

//Membership renewal - validate imported data of members
Route::post('validate_imported_data', [MembershipRenewalController::class, 'validateMembershipRenewalImportedData']);
//fetch membership renewal data where corporate selected
Route::get('fetch_corporate_membership_renewal/{corp_id}', [MembershipRenewalController::class, 'fetchCorporateData']);
//fetch
Route::get('fetch_individual_data/{member_no}', [MembershipRenewalController::class, 'fetchIndividualData']);
//fetch benefits for membership renewal
Route::post('fetch_member_benefits_for_renewal', [MembershipRenewalController::class, 'fetchMemberBenefitsForRenewal']);
//save imported membership renewal details
Route::post('save_imported_membership_renewal', [MembershipRenewalController::class, 'saveImportedMembershipRenewal']);
//renew enmass fetch benefits of members
Route::post('check_annivs_for_renew_enmass', [MembershipRenewalController::class, 'checkRenewalAnnivs']);
//renew enmass fetch benefits of members
Route::post('renew_enmass', [MembershipRenewalController::class, 'renewEnmass']);
//reusable routes
Route::get('query_principal/{family_no}', [SearchPrincipalController::class, 'queryPrincipal'])->where('family_no', '.*');

Route::get('member_dropdowns', [MemberDropdownsController::class, 'memberDropdowns']);
Route::get('search_diagnosis/{searchText}', [MemberDropdownsController::class, 'searchDiagnosis'])->where('searchText', '.*');
Route::get('get_options/{healthPlan}', [MemberDropdownsController::class, 'getOptions']);
Route::post('get_options_for_corporate', [MemberDropdownsController::class, 'getOptionsForCorporate']);
Route::post('get_benefits', [MemberDropdownsController::class, 'getBenefits']);
Route::get('fetch_annivs_for_member/{member_no}', [MemberDropdownsController::class, 'fetchAnnivsForMember']);
//member approval
Route::get('fetch_members_approval_overall', [MemberApprovalController::class, 'fetchMembersApprovalOverall']);
Route::post('fetch_members_approval_corporate', [MemberApprovalController::class, 'fetchMembersApprovalCorporate']);
Route::post('fetch_members_approval_retail', [MemberApprovalController::class, 'fetchMembersApprovalRetail']);
Route::get('fetch_members_approval_all', [MemberApprovalController::class, 'fetchMembersApprovalAll']);
Route::post('save_member_approval', [MemberApprovalController::class, 'saveMemberApproval']);
Route::post('save_member_approval_overall', [MemberApprovalController::class, 'saveMemberApprovalOverall']);

//premium quotation
Route::post('premium_quotation', [PremiumQuotation::class, 'premiumQuotationFunction']);
Route::post('premium_quotation_all_products', [PremiumQuotation::class, 'premiumQuotationAllProductsFunction']);
Route::get('fetch_premium_quotation_all_corporates', [PremiumQuotation::class, 'fetchPremiumQuotationAllCorporates']);
Route::post('fetch_premium_quotation_quotes', [PremiumQuotation::class, 'fetchPremiumQuotationQuotes']);
Route::post('fetch_premium_quotation_quote_data', [PremiumQuotation::class, 'fetchPremiumQuotationQuoteData']);
Route::post('save_premium_quotation', [PremiumQuotation::class, 'savePremiumQuotation']);


//Reusable Routes
//Fetch individuals
Route::get('fetch_individuals', [FetchAnyItemController::class, 'fetchIndividuals']);
//Fetch individuals query debit
Route::get('fetch_individuals_query_debit', [FetchAnyItemController::class, 'fetchIndividualsQueryDebit']);
//Fetch individuals for renewal
Route::get('fetch_individuals_renewal', [FetchAnyItemController::class, 'fetchIndividualsRenewal']);
//Fetch all individuals 
Route::get('fetch_all_individuals', [FetchAnyItemController::class, 'fetchAllIndividuals']);
//Fetch all hospital cash for select dropdown population
Route::get('fetch_hospital_cash_members', [FetchAnyItemController::class, 'fetchHospitalCashMembers']);
//Fetch hc members for particular hc corporate 
Route::get('fetch_hospital_cash_members_per_corporate/{corp_id}', [FetchAnyItemController::class, 'fetchHospitalCashMembersPerCorporate']);
//Fetch all kitanda for select dropdown population
Route::get('fetch_kitanda_members', [FetchAnyItemController::class, 'fetchKitandaMembers']);
//Fetch munomukabi corporates 
Route::get('fetch_munomukabi_corporates', [FetchAnyItemController::class, 'fetchMunomukabiCorporates']);
//Fetch bancasurance corporates 
Route::get('fetch_bancasurance_corporates', [FetchAnyItemController::class, 'fetchBancasuranceCorporates']);
//Fetch munomukabi retails 
Route::get('fetch_munomukabi_retails', [FetchAnyItemController::class, 'fetchMunomukabiRetails']);
//Fetch bancasurance retails 
Route::get('fetch_bancasurance_retails', [FetchAnyItemController::class, 'fetchBancasuranceRetails']);
//Fetch annivs 
Route::post('fetch_annivs', [FetchAnyItemController::class, 'fetchAnnivs']);
//Fetch retail members 
Route::post('fetch_retail_members', [FetchAnyItemController::class, 'fetchRetailMembers']);
//Fetch all I & M for select dropdown population
Route::get('fetch_iandm_members', [FetchAnyItemController::class, 'fetchIandMMembers']);
//Fetch all cigna for select dropdown population
Route::get('fetch_cigna_retail', [FetchAnyItemController::class, 'fetchCignaRetail']);
//Fetch cigna family data
Route::get('fetch_cigna_retail_data/{family_no}', [FetchAnyItemController::class, 'fetchCignaRetailData']);
//fetch payment modes
Route::get('fetch_payment_modes', [FetchAnyItemController::class, 'fetchPaymentModes']);
//fetch banks
Route::get('fetch_banks', [FetchAnyItemController::class, 'fetchBanks']);
//fetch financer
Route::get('fetch_financers', [FetchAnyItemController::class, 'fetchFinancers']);
//fetch currencies
Route::get('fetch_currencies', [FetchAnyItemController::class, 'fetchCurrencies']);
//fetch UGX currency
Route::get('fetch_ugx_currencies', [FetchAnyItemController::class, 'fetchUGXCurrencies']);
//fetch bank account with the combinations
Route::get('fetch_bank_accounts', [FetchAnyItemController::class, 'fetchBankAccounts']);
//fetch client types
Route::get('fetch_client_types', [FetchAnyItemController::class, 'fetchClientTypes']);
//fetch product categories
Route::get('fetch_product_categories', [FetchAnyItemController::class, 'fetchProductCategories']);
//fetch corp anniversary dates
Route::get('get_corporate_anniversary_dates/{task}/{corp_id}', [FetchAnyItemController::class, 'getCorporateAnniversaryDates']);
//fetch client address
Route::get('fetch_client_address/{code}', [FetchAnyItemController::class, 'fetchClientAddress']);
//fetch family_relation
Route::get('fetch_family_relation', [FetchAnyItemController::class, 'fetchFamilyRelation']);
//fetch family_relation_simple
Route::get('fetch_family_relation_simple', [FetchAnyItemController::class, 'fetchFamilyRelationSimple']);
//fetch resend bill reason
Route::get('fetch_resend_bill_reason', [FetchAnyItemController::class, 'fetchResendBillReason']);
//deduction reason
Route::get('fetch_deduction_reason', [FetchAnyItemController::class, 'fetchDeductionReason']);
//fetch member cover dates / anniversary dates
Route::get('fetch_member_cover_dates/{member_no}', [FetchAnyItemController::class, 'fetchMemberCoverDates']);
//fetch all admitting doctors
Route::get('fetch_all_admitting_doctors', [FetchAnyItemController::class, 'fetchAdmittingDoctors']);

Route::get('fetch_decline_reason', [FetchAnyItemController::class, 'fetchDeclineReason']);
//fetch hais triggers
Route::get('fetch_hais_triggers', [FetchAnyItemController::class, 'fetchHaisTriggers']);
//fetch wards
Route::get('fetch_ward', [FetchAnyItemController::class, 'fetchWards']);
//fetch all users
Route::get('fetch_all_usernames', [FetchAnyItemController::class, 'fetchAllUsersNames']);
//fetch corp visit issue
Route::get('fetch_corp_visit_issue', [FetchAnyItemController::class, 'fetchCorpVisitIssue']);
//fetch all agent deduction types
Route::get('fetch_agent_deduction_types', [FetchAnyItemController::class, 'fetchAgentDeductionTypes']);
//fetch all bdms
Route::get('fetch_bdm', [FetchAnyItemController::class, 'fetchBDM']);
//fetch all agent types
Route::get('fetch_agent_types', [FetchAnyItemController::class, 'fetchAgentTypes']);
//fetch all agency
Route::get('fetch_agency', [FetchAnyItemController::class, 'fetchAgency']);
//pay override bdm - voucher - fetch all ov numbers for dropdown
Route::get('fetch_all_bdm_voucher_number', [FetchAnyItemController::class, 'fetchVoucherOVNumber']);
//fetch all payee types
Route::get('fetch_payee_types', [FetchAnyItemController::class, 'fetchAllPayeeTypes']);
//fetch all principals
Route::get('fetch_principal_members', [FetchAnyItemController::class, 'fetchAllPrincipals']);
//fetch all family annivs
Route::get('fetch_family_annivs/{family_no}', [FetchAnyItemController::class, 'fetchAllFamilyAnnivs']);
//fetch all reinsurers
Route::get('fetch_quota_share_reinsurers', [FetchAnyItemController::class, 'fetchQuotaShareReinsurers']);
//fetch all levies
Route::get('fetch_levies', [FetchAnyItemController::class, 'fetchLevies']);
//fetch all product names
Route::get('fetch_product_names', [FetchAnyItemController::class, 'fetchProductNames']);
//fetch specific product names
Route::get('fetch_specific_product_names/{category}', [FetchAnyItemController::class, 'fetchSpecificProductNames']);
//fetch all conversion rates
Route::get('fetch_conversion_rates', [FetchAnyItemController::class, 'fetchConversionRates']);
//fetch all units
Route::get('fetch_units', [FetchAnyItemController::class, 'fetchUnits']);
//fetch all unit managers
Route::get('fetch_unit_managers', [FetchAnyItemController::class, 'fetchUnitManagers']);
//fetch all agencies - order by code
Route::get('fetch_agencies', [FetchAnyItemController::class, 'fetchAgencies']);
//fetch all rejection reasons
Route::get('fetch_rejection_reasons', [FetchAnyItemController::class, 'fetchRejectionReasons']);
//fetch all admin class group plans
Route::get('fetch_class_group_plans', [FetchAnyItemController::class, 'fetchClassGroupPlans']);
//fetch all departments
Route::get('fetch_departments', [FetchAnyItemController::class, 'fetchDepartments']);
//fetch all user group
Route::get('fetch_user_group', [FetchAnyItemController::class, 'fetchUserGroup']);
//fetch all user role
Route::get('fetch_user_role', [FetchAnyItemController::class, 'fetchUserRole']);
//fetch all permissions
Route::get('fetch_permissions', [FetchAnyItemController::class, 'fetchPermissions']);
//fetch all permission types
Route::get('fetch_permission_types', [FetchAnyItemController::class, 'fetchPermissionTypes']);
//fetch provider cancellation reason
Route::get('fetch_provider_cancellation_reason', [FetchAnyItemController::class, 'fetchProviderCancellationReason']);
//fetch pahapa member types
Route::get('fetch_pahapa_member_types', [FetchAnyItemController::class, 'fetchPahapaMemberTypes']);
//fetch pahapa message types
Route::get('fetch_pahapa_message_types', [FetchAnyItemController::class, 'fetchPahapaMessageTypes']);

/***************************************policy document */
//fetch corp or family categories
Route::post('fetch_corp_or_family_categories', [PolicyDocumentController::class, 'fetchCorpOrFamilyCategories']);
//fetch corp or family categories for max anniv
Route::post('fetch_corp_or_family_product_names', [PolicyDocumentController::class, 'fetchCorpOrFamilyProductNames']);
//fetch policy docs for corporate, sme, or retail family
Route::get('fetch_policy_docs/{client_no}/{type}', [PolicyDocumentController::class, 'fetchPolicyDocs']);
//fetch policy docs data
Route::get('fetch_policy_doc_data/{policy_no}', [PolicyDocumentController::class, 'fetchPolicyDocData'])->where('policy_no', '.*');
// fetch policy data to create
//fetch policy docs data for hospital cash
Route::get('fetch_policy_doc_data_hospital_cash/{policy_no}', [PolicyDocumentController::class, 'fetchPolicyDocDataHospitalCash'])->where('policy_no', '.*');
//fetch policy docs data for kitanda
Route::get('fetch_policy_doc_data_kitanda/{policy_no}', [PolicyDocumentController::class, 'fetchPolicyDocDataKitanda'])->where('policy_no', '.*');
//fetch policy docs data for i and m
Route::get('fetch_policy_doc_data_munamukabi/{policy_no}', [PolicyDocumentController::class, 'fetchPolicyDocDataMunamukabi'])->where('policy_no', '.*');
//fetch policy docs data for kitanda corporate
Route::get('fetch_policy_doc_data_kitanda_corporate/{policy_no}', [PolicyDocumentController::class, 'fetchPolicyDocDataKitandaCorporate'])->where('policy_no', '.*');
// fetch policy data to create
Route::post('fetch_policy_data_create', [PolicyDocumentController::class, 'fetchPolicyDataCreate']);
// save policy document
Route::post('save_policy_document', [PolicyDocumentController::class, 'savePolicyDocument']);


/*
 * * Finance Controller Routes
 * *
 */
//fetch corp anniversary dates
Route::post('get_agent_rates', [PremiumDebitingController::class, 'getAgentRates']);
//premium debiting
Route::post('fetch_corp_population', [PremiumDebitingController::class, 'fetchCorpPopulation']);
Route::post('calculate_member_premiums', [PremiumDebitingController::class, 'calculateMemberPremiums']);
//query corporate debit notes
Route::get('fetch_corporate_debit_notes/{corp_id}', [PremiumDebitingController::class, 'fetchCorporateDebitNotes']);
//fetch credit note data
Route::post('fetch_credit_note', [PremiumDebitingController::class, 'fetchCreditNote']);
//save premium debiting
Route::post('save_premium_debiting', [PremiumDebitingController::class, 'savePremiumDebiting']);
//save manual debiting
Route::post('save_manual_debiting', [PremiumDebitingController::class, 'saveManualDebiting']);
//update credit note
Route::post('update_credit_note', [PremiumDebitingController::class, 'updateCreditNote']);
//update credit note
Route::post('reverse_credit_note', [PremiumDebitingController::class, 'reverseCreditNote']);
//premium debiting hospital cash
Route::post('validate_hospital_cash_members', [PremiumDebitingHospitalCashController::class, 'validateHospitalCashMembers']);
Route::post('save_premium_debiting_hospital_cash', [PremiumDebitingHospitalCashController::class, 'savePremiumDebitingHospitalCash']);
//premium debiting cigna
Route::post('calculate_cigma_premium_debit', [PremiumDebitingCignaController::class, 'calculateCigmaPremiumDebit']);
Route::post('save_cigna_debiting', [PremiumDebitingCignaController::class, 'saveCignaDebiting']);
Route::get('generate_invoice/{invoice_no}', [PremiumDebitingCignaController::class, 'generateInvoice']);
//query premium debiting cigna
Route::get('fetch_cigna_debits/{family_no}', [QueryCignaPremiumDebitsController::class, 'fetchCignaDebits']);
Route::get('fetch_cigna_debit_details/{invoice_no}', [QueryCignaPremiumDebitsController::class, 'fetchCignaDebitDetails']);

//premium invoicing routes
Route::get('fetch_premium_invoicing_products', [PremiumInvoicingController::class, 'fetchPremiumInvoicingProducts']);
Route::get('fetch_premium_invoicing_corporates', [PremiumInvoicingController::class, 'fetchPremiumInvoicingCorporates']);
Route::post('save_premium_invoicing_product', [PremiumInvoicingController::class, 'savePremiumInvoicingProduct']);
Route::post('save_premium_invoicing_corporate', [PremiumInvoicingController::class, 'savePremiumInvoicingCorporate']);
Route::post('save_premium_invoicing_data', [PremiumInvoicingController::class, 'savePremiumInvoicingData']);
Route::get('fetch_premium_invoicing/{corporate}', [PremiumInvoicingController::class, 'savePremiumInvoicing']);
Route::get('fetch_corporates_with_invoices', [PremiumInvoicingController::class, 'fetchCorporatesWithInvoices']);
Route::get('fetch_premium_invoicing_invoices/{corporate}', [PremiumInvoicingController::class, 'fetchPremiumInvoicingInvoices']);
Route::get('print_premium_invoicing_invoice/{inv_no}', [PremiumInvoicingController::class, 'printPremiumInvoicingInvoice']);

//finance allocation
Route::get('fetch_finance_families', [FinanceAllocationController::class, 'fetchFinanceFamilies']);
Route::post('fetch_receipt_data', [FinanceAllocationController::class, 'fetchReceiptData']);
Route::post('allocate_receipts_to_debits', [FinanceAllocationController::class, 'allocateReceiptsToDebits']);
//save receipt allocation
Route::post('save_allocations', [FinanceAllocationController::class, 'saveAllocations']);
//finance quotation
Route::get('fetch_quotes/{agent_id}', [FinanceQuotationController::class, 'fetchQuotes']);
Route::get('fetch_quote_data/{quote_no}', [FinanceQuotationController::class, 'fetchQuoteData']);
Route::post('save_quote', [FinanceQuotationController::class, 'saveQuote']);
//fetch premium receipts data
Route::get('fetch_premium_receipts_data/{selected_rd_value}/{selected_value}', [PremiumReceiptController::class, 'fetchPremiumReceipts']);
//fetch exchange rates
Route::get('fetch_exchange_rates/{currency}/{receipt_date}', [PremiumReceiptController::class, 'fetchExchangeRates']);
//save premium receipts
Route::post('save_premium_receipts', [PremiumReceiptController::class, 'savePremiumReceipts']);
//save cigna premium receipts
Route::post('save_premium_receipts_cigna', [CignaPremiumReceiptController::class, 'savePremiumReceiptsCigna']);
//fetch latest receipt no from last number
Route::get('get_latest_receipt_no', [PremiumReceiptController::class, 'getLatestReceiptNumber']);
//Debit reversal routes
//fetch invoice number details
Route::get('fetch_debit_number_details/{debit_no}', [DebitReversalController::class, 'fetchDebitNumberDetails']);
//save debit reversal - invoice reversal
Route::post('save_debit_reversal', [DebitReversalController::class, 'saveDebitReversal']);
//fetch receipt reversal
Route::get('fetch_reversal_reason', [ReceiptReversalController::class, 'fetchReversalReasons']);
Route::get('fetch_receipt_reversal/{receipt_no}', [ReceiptReversalController::class, 'receiptReversal']);
Route::post('set_reversal_no', [ReceiptReversalController::class, 'setReversalNo']);
Route::post('save_receipt_reversal', [ReceiptReversalController::class, 'saveReceiptReversal']);
//print receipt route
Route::get('print_receipt_data/{receipt_no}', [ReceiptReversalController::class, 'printReceiptData']);
//fetch premium debits notes --- both corporate and individual
Route::get('fetch_premium_debits/{option}/{input}', [QueryPremiumDebitsController::class, 'fetchPremiumDebits']);
//fetch invoice number details
Route::get('fetch_debit_details/{invoice_no}', [QueryPremiumDebitsController::class, 'fetchInvoiceNoDetails']);

/************change agent rates module  manual and upload*/
//fetch debit data
Route::get('fetch_debit_data/{invoice_no}', [ChangeAgentRates::class, 'fetchDebitData']);
//save debit data
Route::post('save_change_agent_rates', [ChangeAgentRates::class, 'saveChangeAgentRates']);
//update agent rates upload
Route::post('update_agent_rates_upload', [ChangeAgentRates::class, 'updateAgentRatesUpload']);

//unresolved  claims
Route::post('save_unresolved_claims', [UnresolvedClaimsController::class, 'saveUnresolvedClaims']);
Route::post('fetch_unresolved_claims', [UnresolvedClaimsController::class, 'fetchUnresolvedClaims']);



// BILLS ROUTES
//enter bills routes
Route::post('enter_bill_search_member', [EnterBillController::class, 'searchEnterBillMembers']);
Route::get('fetch_member_details/{member_no}', [EnterBillController::class, 'fetchMemberDetails']);
//fetch benefits for selected member
Route::post('fetch_member_benefits', [EnterBillController::class, 'fetchMemberBenefits']);
//get batch number date received
Route::get('fetch_batch_date_received/{batch_no}/{provider_code}', [EnterBillController::class, 'fetchBatchNoDateReceived']);
//get batch number date received
Route::get('fetch_batch_date_received_new/{batch_no}/{provider_code}', [EnterBillController::class, 'fetchBatchNoDateReceivedNew']);
//get batch number date received for unresolved
Route::get('fetch_batch_date_received_unresolved/{batch_no}/{provider_code}', [EnterBillController::class, 'fetchBatchNoDateReceivedUnresolved']);
//fetch all batches for provider
Route::get('fetch_all_provider_batch_numbers/{provider_code}', [EnterBillController::class, 'fetchProviderBatches']);
//get batch_nos for provider
Route::get('fetch_batches_for_provider/{provider}', [EnterBillController::class, 'fetchBatchesForProvider']);
//fetch product name from benefit selected
Route::post('fetch_product_name', [EnterBillController::class, 'fetchProductName']);
//fetch frames
Route::post('fetch_frames_services', [EnterBillController::class, 'fetchFrames']);
//save enter bill
Route::post('save_enter_bill', [EnterBillController::class, 'saveEnterBill']);
//save enter bill by line items
Route::post('save_enter_bill_line_items', [EnterBillController::class, 'saveEnterBillLineItems']);
//fetch line items for enter bill by line items
Route::post('fetch_price_list', [EnterBillByLineItemsController::class, 'fetchPriceList']);
//fetch one line itemfetch_ageing_analysis_report
Route::post('fetch_one_line_item', [EnterBillByLineItemsController::class, 'fetchOneLineItem']);

//bill vet
Route::get('fetch_provider_vet_bill', [VetBillController::class, 'fetchProviderVetBill']);
Route::get('fetch_claims_per_provider/{provider}', [VetBillController::class, 'fetchClaimsPerProvider']);
Route::post('fetch_claims_per_claim_no', [VetBillController::class, 'fetchClaimsPerClaimNo']);
Route::get('fetch_claims_per_claim_no_all/{claim_no}', [VetBillController::class, 'fetchClaimsPerClaimNoAll']);
Route::get('fetch_claim_by_id/{id}', [VetBillController::class, 'fetchClaimById']);
Route::get('fetch_claim_by_id_all/{id}', [VetBillController::class, 'fetchClaimByIdAll']);
Route::post('fetch_member_diagnosis', [VetBillController::class, 'fetchMemberDiagnosis']);
Route::post('fetch_member_exclusion', [VetBillController::class, 'fetchMemberExclusion']);
Route::post('fetch_claim_balance', [VetBillController::class, 'fetchClaimBalance']);
Route::post('fetch_claim_balance_care', [VetBillController::class, 'fetchClaimBalanceCare']);
Route::post('fetch_main_balance', [VetBillController::class, 'fetchMainBalance']);
Route::post('fetch_main_balance_care', [VetBillController::class, 'fetchMainBalanceCare']);
Route::post('fetch_preauth_bills', [VetBillController::class, 'fetchPreauthBills']);
Route::get('fetch_claims_reserve_for_preauth/{preauth_no}', [VetBillController::class, 'fetchClaimsReserveForPreauth']);
Route::post('delete_diagnosis', [VetBillController::class, 'deleteDiagnosis']);
Route::post('fetch_claim_line_items', [VetBillController::class, 'fetchClaimsLineItems']);
Route::post('save_vet', [VetBillController::class, 'saveVet']);
Route::post('save_vet_enmass', [VetBillController::class, 'saveVetEnmass']);
Route::post('save_vet_anyway', [VetBillController::class, 'saveVetAnyway']);

//vet bill approval
Route::get('fetch_vetted_bills_to_approve', [VetApprovalController::class, 'fetchVettedBillsToApprove']);
Route::post('save_vetting_approval', [VetApprovalController::class, 'saveVettingApproval']);



//add an invoice
Route::get('fetch_claims_per_claim_no_add_invoice/{claim_no}', [AddAnInvoiceController::class, 'fetchClaimsPerClaimNoAddInvoice']);
Route::get('fetch_claim_by_id_add_invoice/{id}', [AddAnInvoiceController::class, 'fetchClaimByIdAddInvoice']);
Route::post('save_add_invoice', [AddAnInvoiceController::class, 'saveAddInvoice']);

//import hospital bills
Route::post('validate_imported_hospital_bills', [ImportHospitalBillsController::class, 'validateImportedHospitalBills']);
//generate claim numbers for the imported bills
Route::post('generate_claim_no_for_imported_hospital_bills', [ImportHospitalBillsController::class, 'generateClaimNumbers']);
//save save_imported_hospital_cash_bills
Route::post('save_imported_hospital_bills', [ImportHospitalBillsController::class, 'saveImportedHospitalBills']);


//query claims routes
Route::post('fetch_claims', [QueryClaimsController::class, 'fetchClaims']);
Route::post('fetch_searched_member', [QueryClaimsController::class, 'fetchSearchedMember']);
//query claim by row selected from table
Route::get('fetch_claim_row_by_id/{id}', [QueryClaimsController::class, 'fetchClaimRowById']);
//fetch billvet vet status for each bill id
Route::post('fetch_bills_vet_status', [QueryClaimsController::class, 'fetchBillVetStatus']);
Route::post('update_query_claims', [QueryClaimsController::class, 'saveQueryClaims']);
Route::get('fetch_claims_by_member_no/{member_no}', [QueryClaimsController::class, 'fetchClaimsByMemberNo']);
//fetch batch number date received for QueryClaims
Route::get('fetchclaims_batch_date_received/{batch_no}/{provider_code}', [QueryClaimsController::class, 'fetchBatchNoDateReceived']);
Route::get('fetchclaims_batch_date_received_two/{batch_no}', [QueryClaimsController::class, 'fetchBatchNoDateReceivedTwo']);
Route::get('fetchclaims_batch_date_received_three/{batch_no}', [QueryClaimsController::class, 'fetchBatchNoDateReceivedThree']);


//BATCH CLAIMS
//save claims batch
Route::post('save_batch_claims', [BatchClaimsController::class, 'saveClaimsBatch']);
//query claims batch
Route::get('query_claims_batch/{batch_no}', [BatchClaimsController::class, 'queryClaimsBatch']);
//fetch all batch number from table bills to populate bills payment - batch no dropdown
Route::get('fetch_all_batch_numbers', [QueryBatchController::class, 'fetchAllBatchNumbers']);
//query claims batch fetch_batch_no_details
Route::get('query_batch/{batch_no}', [QueryBatchController::class, 'queryBatch']);
//fetch product name from benefit selected upon query batch
Route::post('fetch_product_name_query_batch', [QueryBatchController::class, 'fetchProductName']);
//fetch member preAuths from pre_authorization
Route::post('fetch_member_preauth_query_batch', [QueryBatchController::class, 'fetchMemberPreauthsQueryBatch']);
//save / update query batch
Route::post('update_query_batch', [QueryBatchController::class, 'updateQueryBatch']);

//fetch claims for bill-payment query batch
Route::get('query_bill_payment_batch/{batch_no}', [QueryBatchController::class, 'billPaymentQueryBatch']);
//fetch claims for batch report summary
Route::get('batch_report_summary/{batch_no}', [BatchReportSummaryController::class, 'BatchReportSummary']);

//BILL PAYMENT ROUTES
//reimburse corporate routes
Route::get('fetch_corporates_to_reimburse', [ReimburseCorporateController::class, 'fetchCorporatesToReimburse']);
Route::get('fetch_corp_claim_reimbursement/{corp_id}', [ReimburseCorporateController::class, 'fetchCorpClaimReimburse']);
Route::post('save_reimburse_corporate', [ReimburseCorporateController::class, 'saveReimburseCorporate']);

//reimburse member and member fund routes
Route::get('fetch_members_reimburse/{selectedOption}', [ReimburseMemberController::class, 'FetchMemberReimburse']);
Route::get('fetch_bills_for_member_reimbursement/{fam_no}', [ReimburseMemberController::class, 'fetchBillsForMemberReimbursement']);
Route::post('reimburse_member', [ReimburseMemberController::class, 'reimburseMember']);

//Query voucher route
Route::get('fetch_voucher_bills_payment', [QueryVoucherController::class, 'fetchVoucherBillsPayment']);
Route::post('fetch_voucher_data', [QueryVoucherController::class, 'fetchVoucherData']);
Route::post('update_query_voucher', [QueryVoucherController::class, 'updateQueryVoucher']);
Route::post('split_voucher', [QueryVoucherController::class, 'splitVoucher']);

//pay batch routes
Route::get('fetch_batch_per_provider/{provider}', [PayBatchController::class, 'fetchBatchPerProvider']);
Route::get('fetch_batch_per_provider_unresolved/{provider}', [PayBatchController::class, 'fetchBatchPerProviderUnresolved']);
Route::post('fetch_claims_per_batch_no', [PayBatchController::class, 'fetchPayBatchPerBatchNo']);
Route::post('save_pay_batch', [PayBatchController::class, 'savePayBatch']);
//query paid vouchers routes
Route::get('fetch_paid_vouchers', [QueryPaidVoucherController::class, 'fetchPaidVouchers']);
Route::get('fetch_claims_per_voucher_no/{voucher_no}', [QueryPaidVoucherController::class, 'fetchClaimsPerVoucherNo'])->where('voucher_no', '.*');
//query cheque routes
Route::get('fetch_cheques', [QueryChequeController::class, 'fetchCheques']);
Route::get('fetch_claims_per_cheque_no/{cheque_no}', [QueryChequeController::class, 'fetchClaimsPerChequeNo'])->where('cheque_no', '.*');

/*
 * Bill Payment Routes
 * */
//PayProvider Routes
//Fetch all provider with fund for providers select dropdown population
Route::get('fetch_providers_fund', [PayProviderController::class, 'fetchProviderFund']);
//Fetch all claims for the provider
Route::get('fetch_provider_claims/{prov_code}', [PayProviderController::class, 'fetchPayProviderClaims']);
//save pay provider
Route::post('save_pay_provider', [PayProviderController::class, 'savePayProvider']);

//Pay Provider fund
//Fetch all corporates under fund for corporate select dropdown population
Route::get('fetch_fund_corporates', [PayProviderFundController::class, 'fetchFundCorporates']);
//Fetch all provider under selected corporate with fund for providers select dropdown population
Route::get('fetch_providers_fund/{corp_id}', [PayProviderFundController::class, 'fetchProviderFund']);
//Fetch all claims for the provider
Route::get('fetch_provider_fund_claims/{prov_code}', [PayProviderFundController::class, 'fetchPayProviderFundClaims']);

/*
 * Unvoucher claims routes
 * */
//fetch all voucher numbers not paid
Route::get('fetch_voucher_numbers_to_unvoucher', [UnvoucherClaimsController::class, 'fetchAllVoucherNumbers']);
//fetch all claims to unvoucher
Route::get('fetch_claims_to_unvoucher/{voucher_no}', [UnvoucherClaimsController::class, 'fetchClaimsToUnvoucher']);
//save unvoucher claims route
Route::post('save_unvoucher_claims', [UnvoucherClaimsController::class, 'saveUnvoucherClaims']);
/*
* voucher batching*/
Route::get('fetch_voucher_batch_batches/{provider}', [VoucherBatchingController::class, 'fetchVoucherBatchBatches']);
Route::get('fetch_voucher_batch_data/{batch_no}', [VoucherBatchingController::class, 'fetchVoucherBatchData']);

/*******************chronic management modules */
Route::get('chronic_dashboard', [ChronicManagementController::class, 'chronicDashboard']);
Route::post('save_chronic_import_members', [ChronicManagementController::class, 'saveChronicImportMembers']);
Route::get('fetch_member_details_chronic/{member_no}', [ChronicManagementController::class, 'fetchMemberDetailsChronic']);
Route::post('fetch_prescription_members', [ChronicManagementController::class, 'fetchPrescriptionMembers']);
Route::post('fetch_chronic_prescription', [ChronicManagementController::class, 'fetchChronicPrescription']);
Route::post('save_chronic_prescription', [ChronicManagementController::class, 'saveChronicPrescription']);
Route::post('update_past_prescription', [ChronicManagementController::class, 'updatePastPrescription']);
Route::get('enrolled_member_detailed_summary', [ChronicManagementController::class, 'enrolledMemberDetailedSummary']);
Route::post('fetch_prescription_report', [ChronicManagementController::class, 'fetchPrescriptionReport']);
Route::get('fetch_member_enrollement_report', [ChronicManagementController::class, 'fetchMemberEnrollementReport']);
Route::post('fetch_drug_refill_report', [ChronicManagementController::class, 'fetchDrugRefillReport']);


//  care modules routes
// preauth
Route::post('fetch_member_preauth', [PreauthorizationController::class, 'fetchMemberPreauth']);
Route::get('fetch_specific_member_data_preauth/{memno}', [PreauthorizationController::class, 'fetchSpecificMemberDataPreauth']);
Route::get('fetch_member_preauths/{memno}', [PreauthorizationController::class, 'fetchMemberPreauths']);
Route::get('fetch_member_preauth_data/{code}', [PreauthorizationController::class, 'fetchMemberPreauthData']);
Route::post('save_extension_preauth', [PreauthorizationController::class, 'saveExtensionPreauth']);
Route::post('save_preauth', [PreauthorizationController::class, 'savePreauth']);
Route::post('update_preauth', [PreauthorizationController::class, 'updatePreauth']);
Route::post('release_reserve', [PreauthorizationController::class, 'releaseReserve']);
Route::post('generate_pdf', [PreauthorizationController::class, 'generatePdf']);
Route::get('fetch_one_preauth_to_edit/{code}', [EditPreAuthorisationController::class, 'fetchOnePreauthToEdit']);

//admission visit routess
Route::get('fetch_member_care/{memno}', [AdmissionVisitController::class, 'fetchMemberCare']);
Route::post('save_care_visit', [AdmissionVisitController::class, 'saveCareVisit']);

//Care Admissions Routes
//fetch previous member care admissions
Route::get('fetch_member_care_admissions/{member_no}', [CareAdmissionController::class, 'fetchMemberCareAdmissions']);
//fetch care admission details by pre_auth code
Route::get('fetch_care_admission_details_by_code/{pre_auth_code}', [CareAdmissionController::class, 'fetchCareAdmissionsDetails']);
//save care admission
Route::post('save_care_admission', [CareAdmissionController::class, 'saveCareAdmission']);

/**
 * *
 * Care Reports *
 * *
 **/
// Care Misc Reports - Edit PreAuthorization
Route::post('fetch_pre_auths_to_edit', [EditPreAuthorisationController::class, 'fetchPreAuthsToEdit']);
//cancel preauth to release reserve amount
Route::get('cancel_pre_auth_to_release_reserve/{pre_auth_code}', [EditPreAuthorisationController::class, 'cancelPreAuthToReleaseReserve']);
//save pre_auths edit
Route::post('save_pre_auth_edit', [EditPreAuthorisationController::class, 'savePreAuthEdit']);
//generate pdf for each edit preauths
Route::post('edit_preauth_generate_pdf', [EditPreAuthorisationController::class, 'editPreauthGeneratePdf']);

//past admissions
Route::post('fetch_care_past_admissions', [PastAdmissionsController::class, 'fetchPastAdmissions']);
//fetch care task followups
Route::post('fetch_care_task_followups', [TasksFollowUpController::class, 'fetchTasksFollowUps']);

//fetch Admission Authorizations
Route::post('fetch_admission_authorisations', [AdmissionAuthorizationController::class, 'fetchAdmissionAuthorizations']);
//fetch Corporate Needs
Route::post('fetch_corporate_needs', [CorporateNeedsController::class, 'fetchCorporateNeeds']);
//care admission report
Route::post('fetch_care_admissions', [CareAdmissionsReportController::class, 'fetchCareAdmissions']);
//care admission visits report
Route::post('fetch_care_visits', [CareVisitsController::class, 'fetchCareVisits']);
//patient comment report
Route::post('fetch_patient_comments', [PatientCommentsReport::class, 'fetchPatientComments']);
//case management
Route::post('fetch_case_management', [CaseManagementController::class, 'fetchCaseManagement']);

//Care Decline Routes
Route::post('fetch_member', [DeclineController::class, 'fetchMember']);
Route::get('fetch_member_name/{member_no}', [DeclineController::class, 'fetchMemberDetails']);
Route::get('fetch_decline_code', [DeclineController::class, 'fetchMaxDeclineCode']);
Route::get('fetch_code_per_provider/{provider}', [DeclineController::class, 'fetchDeclineCodePerProvider']);
Route::post('fetch_decline_data', [DeclineController::class, 'fetchDeclineData']);
Route::post('save_decline_letter', [DeclineController::class, 'saveDeclineLetter']);
//Corporate Visit Routes
Route::get('fetch_visit_no', [CorporateVisitController::class, 'fetchMaxVisitNo']);
Route::get('fetch_issue_no', [CorporateVisitController::class, 'fetchMaxIssueNo']);
Route::post('save_corporate_visit', [CorporateVisitController::class, 'saveCorporateVisit']);

/*Commision routes
* *
*/
//pay hos routes
Route::get('fetch_hos_debits_not_paid_commission', [PayHosController::class, 'fetchHosDebitsNotPaidCommission']);
//hos_vno routes
Route::get('fetch_hos_voucher_no', [PayHosController::class, 'fetchHosVoucherNo']);
Route::get('fetch_hos_debits_per_hos_vno/{hos_vno}', [PayHosController::class, 'fetchHosDebitsPerHosVoucherNo']);
Route::post('fetch_hos_rate', [PayHosController::class, 'fetchHosRate']);
//save pay hos invoices
Route::post('save_pay_hos_invoices', [PayHosController::class, 'savePayHosInvoices']);

//pay asst hos routes
Route::get('fetch_asst_hos_voucher_no', [PayAssistantHosController::class, 'fetchAssistantHosVoucherNo']);
Route::get('fetch_asst_hos_debits_not_paid_commission', [PayAssistantHosController::class, 'fetchAsstHosDebitsNotPaidCommission']);
Route::get('fetch_asst_hos_debits_per_asst_hos_vno/{asst_hos_vno}', [PayAssistantHosController::class, 'fetchAsstHosDebitsPerAsstHosVoucherNo']);
Route::post('fetch_asst_hos_rate', [PayAssistantHosController::class, 'fetchAsstHosRate']);
//save pay assistant hos invoices
Route::post('save_pay_assistant_hos_invoices', [PayAssistantHosController::class, 'savePayAssistantHosInvoices']);

//fetch agents receipts
Route::get('fetch_agent_receipts/{agent_id}', [EnterAgentDeductionsController::class, 'fetchAgentReceipts']);
//fetch agent agent deductions for query
Route::get('fetch_agent_deductions/{agent_id}', [EnterAgentDeductionsController::class, 'fetchAgentDeductions']);
//Enter deductions routes
Route::post('save_agent_deductions', [EnterAgentDeductionsController::class, 'saveAgentDeductions']);
//update agent deductions on query
Route::post('update_agent_deductions', [EnterAgentDeductionsController::class, 'updateAgentDeductions']);

//Pay commission Routes
Route::get('fetch_agents_to_pay_commission', [PayCommissionController::class, 'fetchAgentsToPayCommission']);
Route::get('fetch_voucher_nos_to_attach_cheque', [PayCommissionController::class, 'fetchVoucherNosToAttachCheque']);
Route::get('fetch_cheques_commission', [PayCommissionController::class, 'fetchChequesCommission']);
Route::get('fetch_debits_not_paid_commission/{agent_id}', [PayCommissionController::class, 'fetchDebitsNotPaidCommission']);
Route::get('fetch_debits_paid_to_attach_cheque/{voucher_no}', [PayCommissionController::class, 'fetchDebitsPaidToAttachCheque']);
Route::get('fetch_debits_for_cheque/{payment_no}', [PayCommissionController::class, 'fetchDebitsForCheque']);
Route::post('fetch_commission_rate', [PayCommissionController::class, 'fetchCommissionRate']);
Route::post('update_commission_pay', [PayCommissionController::class, 'updateCommissionPay']);
//fetch bdm invoices
Route::get('fetch_bdm_invoices/{bdm_code}', [PayOverrideBDMController::class, 'fetchBDMInvoices']);
//pay bdm invoices per row to fetch bdm whtax rate
Route::post('fetch_bdm_whtax_rate', [PayOverrideBDMController::class, 'fetchBDMRate']);
//save pay bdm invoices
Route::post('save_pay_bdm_invoices', [PayOverrideBDMController::class, 'savePayBDMInvoices']);
//voucher - fetch invoices by ov number
Route::get('fetch_voucher_bdm_invoices_by_ov_number/{ov_number}', [PayOverrideBDMController::class, 'fetchVoucherInvoicesByOVNumber']);


/*
 * Registration reports
 *
 * */
//Registration - valid list report
Route::post('fetch_valid_list_report', [ValidListController::class, 'fetchValidListReport']);
//Registration - debited members report
Route::post('fetch_debited_members_report', [DebitedMembersController::class, 'fetchDebitedMembersReport']);
//membership list
Route::get('fetch_corp_annivs/{corp_id}', [MembershipListController::class, 'fetchCorpAnnivs']);
Route::get('fetch_max_corp_annivs/{corp_id}', [MembershipListController::class, 'fetchMaxCorpAnniv']);
Route::post('fetch_membership_list_report', [MembershipListController::class, 'fetchMembershipListReport']);
Route::post('fetch_card_reprint_data', [CardReprintController::class, 'fetchCardReprintData']);


/***********Commission reports */
Route::post('fetch_commission_statement', [CommissionStatementController::class, 'fetchCommissionStatement']);

//FINANCE REPORTS
Route::get('fetch_claims_reserves', [ClaimsReserveController::class, 'claimsReserve']);
//Premium Statement Report
Route::post('fetch_premium_statement_report', [PremiumStatementController::class, 'premiumStatementReport']);
//Premium Statement cigna Report
Route::get('fetch_premium_statement_cigna_report/{family_no}', [PremiumStatementCignaController::class, 'premiumStatementCignaReport']);
//Premium cigna invoice Report
Route::get('fetch_cigna_invoice_report', [CignaInvoiceReportController::class, 'cignaInvoiceReport']);
//Fund Statement
Route::get('fetch_fund_statement/{corp_id}', [FundStatementController::class, 'fetchFundStatement']);
//premium register
Route::post('fetch_premium_register', [PremiumRegisterController::class, 'fetchPremiumRegister']);
//mapa report
Route::post('fetch_mapa', [MapaController::class, 'fetchMapa']);
//ifrs report
Route::post('fetch_ifrs_report', [IfrsController::class, 'fetchIfrsReport']);
//remittance report
Route::post('fetch_remittance_per_batch_no', [RemittanceController::class, 'fetchRemittancePerBatchNo']);
//send remittance mail
Route::post('send_remittance_mail', [RemittanceController::class, 'sendRemittanceMail']);
//provider report
Route::post('fetch_provider_report', [ProviderReportController::class, 'fetchProviderReport']);
//ageing analysis finance report
Route::post('fetch_ageing_analysis_finance_report', [AgeingAnalysisFinanceController::class, 'fetchAgeingAnalysisFinanceReport']);

//BILLS REPORT
Route::post('fetch_rejection_rates', [RejectionRateController::class, 'fetchRejectionRate']);
Route::post('fetch_batch_report', [BatchReportController::class, 'fetchBatchReport']);
Route::post('fetch_outstanding_bills', [OutstandingBillsController::class, 'fetchOutstandingBills']);
Route::post('fetch_suspended_bills', [SuspendedBillsController::class, 'fetchSuspendedBills']);
Route::get('fetch_bill_pends', [BillPendsController::class, 'fetchBillPends']);
/******Bills reports */
//vetted bills
Route::post('fetch_vetted_bills', [VettedBillsController::class, 'fetchVettedBills']);
//claims status summary
Route::post('fetch_claims_status_summary', [ClaimsStatusSummaryController::class, 'fetchClaimsStatusSummary']);
//claims status detailed
Route::post('fetch_claims_status_detailed', [ClaimsStatusDetailedController::class, 'fetchClaimsStatusDetailed']);
//claims payment 
Route::post('fetch_claims_payment', [ClaimsPaymentController::class, 'fetchClaimsPayment']);
//claims experience
Route::post('fetch_claims_experience', [ClaimsExperienceController::class, 'fetchClaimsExperience']);
//claims Register
Route::post('fetch_claims_register', [ClaimsRegisterController::class, 'fetchClaimsRegister']);
//utilization report
Route::post('fetch_utilization_report', [UtilizationReportController::class, 'fetchUtilizationReport']);
//fetch batch process report
Route::post('fetch_batch_process_report', [BatchProcessReportController::class, 'fetchBatchProcessReport']);
//fetch claims experience extract data
Route::post('fetch_claims_experience_extract_report', [ClaimsExperienceExtractController::class, 'fetchClaimsExperienceExtractReport']);
//fetch rejected bills
Route::post('fetch_rejected_bills_report', [RejectedBillsReportController::class, 'fetchRejectedBillsReport']);
//fetch IPOP report
Route::post('ipop_report', [IpopReportController::class, 'ipopReport']);
//fetch claims average amount report
Route::post('claims_average_amount', [ClaimsAverageAmountController::class, 'claimsAverageAmount']);
//ageing analysis 
Route::post('fetch_ageing_analysis_report', [AgeingAnalysisController::class, 'fetchAgeingAnalysisReport']);

//over utilization route
Route::post('fetch_over_utilized_bills', [OverUtilizationController::class, 'fetchOverUtilizedBills']);
Route::post('fetch_over_utilized_visits', [OverUtilizationController::class, 'fetchOverUtilizedVisits']);
Route::post('update_member_visit', [OverUtilizationController::class, 'updateMemberVisit']);



// Reinsurance routes
Route::get('fetch_excess_of_loss_calculations/{code}', [ExcessOfLossController::class, 'fetchExcessOfLossCalculations']);
Route::post('fetch_claims_payment_schedule', [ClaimsPaymentScheduleController::class, 'fetchClaimsPaymentScheduleData']);
//fetch Premiums Quota Share
Route::post('fetch_premiums_quota_share_data', [PremiumsQuotaShareController::class, 'fetchPremiumsQuotaShareData']);

//Access Logs
// Route::post('fetch_new_access_logs', [AccessLogController::class, 'fetchNewAccessLogs']);
Route::get('fetch_new_access_logs', [AccessLogController::class, 'fetchNewAccessLogs']);
Route::post('save_access_logs', [AccessLogController::class, 'saveAccessLogs']);
Route::post('save_new_access_logs', [AccessLogController::class, 'saveNewAccessLogs']);


//for updating blank corp ids in member info
Route::get('update_all_blank_corp_ids', [updateRecordsController::class, 'updateAllBlankCorpIds']);


/**
 * *
 * Management *
 * *
 **/

/** * Management Reports * **/
Route::post('fetch_operations_user_activity', [OperationsUserActivityController::class, 'fetchUserActivity']);
Route::post('fetch_provider_average_cost', [MemberVisitsController::class, 'fetchProviderAverageCost']);
Route::post('fetch_individuals_and_companies', [IndividualAndCompaniesController::class, 'fetchIndividualAndCompanies']);
Route::post('fetch_corporate_performance_report', [CorporatePerformanceController::class, 'fetchCorporatePerformanceReport']);
Route::post('fetch_corporate_utilization_report', [CorporateUtilizationReport::class, 'fetchCorporateUtilizationReport']);
Route::post('fetch_corp_utilization_member_annivs', [CorporateUtilizationReport::class, 'fetchCorpUtilizationMemberAnnivs']);
Route::get('fetch_anti_money_laundering_sanctioned', [AntiMoneyLaunderingController::class, 'fetchAntiMoneyLaunderingSanctioned']);
Route::get('fetch_non_sanctioned_corporates', [AntiMoneyLaunderingController::class, 'fetchNonSanctionedCorporates']);
Route::get('fetch_non_sanctioned_principals', [AntiMoneyLaunderingController::class, 'fetchNonSanctionedPrincipals']);
Route::post('save_sanction_choosen', [AntiMoneyLaunderingController::class, 'saveSanctionChoosen']);
Route::post('save_sanction_status', [AntiMoneyLaunderingController::class, 'saveSanctionStatus']);
Route::post('save_sanction_main', [AntiMoneyLaunderingController::class, 'saveSanctionMain']);
Route::get('fetch_user_rights_report/{user}', [UserRightsController::class, 'fetchUserRightsReport']);
Route::post('fetch_mom_renewal_tracker_report', [MomRenewalTrackerController::class, 'fetchMomRenewalTrackerReport']);
Route::post('fetch_provider_average_cost', [ProviderAverageCostController::class, 'fetchProviderAverageCost']);
Route::post('fetch_data_analysis_utilization_report', [DataAnalysisUtilizationReportController::class, 'fetchDataAnalysisUtilizationReport']);

//cigna module
Route::get('fetch_cigna_rates', [CignaUsdExchangeRateController::class, 'fetchCignaRates']);
Route::post('save_cigna_rates', [CignaUsdExchangeRateController::class, 'saveCignaRates']);

//download hais templates
Route::get('download_templates/{path}', [DownloadTemplateController::class, 'downloadTemplates']);

//fetch benefit balances across the board bills, care, utilization reports
//for bills 
Route::post('fetch_benefit_balance_bills', [BenefitBalanceController::class, 'fetchBenefitBalanceBills']);

// pahapa message schedules
Route::get('fetch_scheduled_messages', [PahapaMessageSchedules::class, 'fetchScheduledMessages']);
Route::post('save_pahapa_sms_schedules', [PahapaMessageSchedules::class, 'savePahapaSmsSchedules']);


