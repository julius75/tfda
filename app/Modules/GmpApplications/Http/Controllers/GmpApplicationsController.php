<?php

namespace App\Modules\GmpApplications\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GmpApplicationsController extends Controller
{
    protected $user_id;

    public function __construct(Request $req)
    {
        $is_mobile = $req->input('is_mobile');
        if (is_numeric($is_mobile) && $is_mobile > 0) {
            $this->user_id = $req->input('user_id');
        } else {
            $this->middleware(function ($request, $next) {
                if (!\Auth::check()) {
                    $res = array(
                        'success' => false,
                        'message' => '<p>NO SESSION, SERVICE NOT ALLOWED!!<br>PLEASE RELOAD THE SYSTEM!!</p>'
                    );
                    echo json_encode($res);
                    exit();
                }
                $this->user_id = \Auth::user()->id;
                return $next($request);
            });
        }
    }

    public function index()
    {
        return view('gmpapplications::index');
    }

    public function getGmpApplicationParamFromModel(Request $request)
    {
        $model_name = $request->input('model_name');
        $strict_mode = $request->input('strict_mode');
        try {
            $model = 'App\\Modules\\GmpApplications\\Entities\\' . $model_name;
            if (isset($strict_mode) && $strict_mode == 1) {
                $results = $model::where('is_enabled', 1)
                    ->get()
                    ->toArray();
            } else {
                $results = $model::all()
                    ->toArray();
            }
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    public function getGmpCommonParams(Request $request)
    {
        $section_id = $request->input('section_id');
        $model_name = $request->input('model_name');
        try {
            $model = 'App\\Modules\\GmpApplications\\Entities\\' . $model_name;
            if (isset($section_id) && $section_id != '') {
                $qry = $model::where('section_id', $section_id)->get();
            } else {
                $qry = $model::all();
            }
            $results = $qry->toArray();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    public function saveGmpApplicationCommonData(Request $req)
    {
        try {
            $user_id = \Auth::user()->id;
            $post_data = $req->all();
            $table_name = $post_data['table_name'];
            $id = $post_data['id'];
            //unset unnecessary values
            unset($post_data['_token']);
            unset($post_data['table_name']);
            unset($post_data['model']);
            unset($post_data['id']);
            unset($post_data['unset_data']);
            $unsetData = $req->input('unset_data');
            if (isset($unsetData)) {
                $unsetData = explode(",", $unsetData);
                $post_data = unsetArrayData($post_data, $unsetData);
            }

            $table_data = $post_data;
            //add extra params
            $table_data['created_on'] = Carbon::now();
            $table_data['created_by'] = $user_id;
            $where = array(
                'id' => $id
            );
            $res = array();
            if (isset($id) && $id != "") {
                if (recordExists($table_name, $where)) {
                    unset($table_data['created_on']);
                    unset($table_data['created_by']);
                    $table_data['dola'] = Carbon::now();
                    $table_data['altered_by'] = $user_id;
                    $previous_data = getPreviousRecords($table_name, $where);
                    if ($previous_data['success'] == false) {
                        return $previous_data;
                    }
                    $previous_data = $previous_data['results'];
                    $res = updateRecord($table_name, $previous_data, $where, $table_data, $user_id);
                }
            } else {
                $res = insertRecord($table_name, $table_data, $user_id);
            }
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    public function deleteGmpApplicationRecord(Request $req)
    {
        try {
            $record_id = $req->input('id');
            $table_name = $req->input('table_name');
            $user_id = \Auth::user()->id;
            $where = array(
                'id' => $record_id
            );
            $previous_data = getPreviousRecords($table_name, $where);
            if ($previous_data['success'] == false) {
                return $previous_data;
            }
            $previous_data = $previous_data['results'];
            $res = deleteRecord($table_name, $previous_data, $where, $user_id);
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    public function getManufacturingSitesList(Request $request)
    {
        $premise_id = $request->input('premise_id');
        $section_id = $request->input('section_id');
        $gmp_type_id = $request->input('gmp_type_id');
        $filter = $request->input('filter');
        $whereClauses = array();
        $filter_string = '';
        if (isset($filter)) {
            $filters = json_decode($filter);
            if ($filters != NULL) {
                foreach ($filters as $filter) {
                    switch ($filter->property) {
                        case 'name' :
                            $whereClauses[] = "t1.name like '%" . ($filter->value) . "%'";
                            break;
                        case 'applicant_name' :
                            $whereClauses[] = "t3.name like '%" . ($filter->value) . "%'";
                            break;
                        case 'premise_reg_no' :
                            $whereClauses[] = "t1.premise_reg_no like '%" . ($filter->value) . "%'";
                            break;
                        case 'permit_no' :
                            $whereClauses[] = "t2.permit_no like '%" . ($filter->value) . "%'";
                            break;
                    }
                }
                $whereClauses = array_filter($whereClauses);
            }
            if (!empty($whereClauses)) {
                $filter_string = implode(' AND ', $whereClauses);
            }
        }
        try {
            $qry = DB::table('registered_manufacturing_sites as t0')
                ->join('tra_manufacturing_sites as t1', 't0.tra_site_id', '=', 't1.id')
                ->join('tra_approval_recommendations as t2', 't1.permit_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('gmplocation_details as t4', 't1.gmp_type_id', '=', 't4.id')
                ->select('t1.id as premise_id', 't1.id as manufacturing_site_id', 't1.*', 't2.permit_no', 't3.name as applicant_name',
                    't3.id as applicant_id', 't3.name as applicant_name', 't3.contact_person', 't3.tin_no', 't2.permit_no as gmp_cert_no',
                    't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id',
                    't3.physical_address as app_physical_address', 't3.postal_address as app_postal_address',
                    't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't4.name as gmp_type_txt', 't0.id as registered_id')
                ->whereIn('t0.status_id', array(2, 4));
            if (isset($section_id) && $section_id != '') {
                $qry->where('t1.section_id', $section_id);
            }
            if (isset($gmp_type_id) && $gmp_type_id > 0) {
                $qry->where('t1.gmp_type_id', $gmp_type_id);
            }
            if ($filter_string != '') {
                $qry->whereRAW($filter_string);
            }
            if (isset($premise_id) && $premise_id != '') {
                $qry->where('ta.id', $premise_id);
            }
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getGmpApplications(Request $request)
    {
        $module_id = $request->input('module_id');
        $section_id = $request->input('section_id');
        $sub_module_id = $request->input('sub_module_id');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $gmp_type_id = $request->input('gmp_type_id');
        $user_id = $this->user_id;
        $assigned_groups = getUserGroups($user_id);
        $is_super = belongsToSuperGroup($assigned_groups);
        try {
            $assigned_stages = getAssignedProcessStages($user_id, $module_id);
            $qry = DB::table('tra_gmp_applications as t1')
                ->join('tra_manufacturing_sites as t2', 't1.manufacturing_site_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('wf_tfdaprocesses as t4', 't1.process_id', '=', 't4.id')
                ->join('wf_workflow_stages as t5', 't1.workflow_stage_id', '=', 't5.id')
                ->join('par_system_statuses as t6', 't1.application_status_id', '=', 't6.id')
                ->join('gmplocation_details as t7', 't1.gmp_type_id', '=', 't7.id')
                ->select(DB::raw("t1.id as active_application_id, t1.application_code, t4.module_id, t4.sub_module_id, t4.section_id, t2.name as premise_name,
                    t6.name as application_status, t3.name as applicant_name, t4.name as process_name, t5.name as workflow_stage, t5.is_general, t3.contact_person,
                    t3.tin_no, t3.country_id as app_country_id, t3.region_id as app_region_id, t3.district_id as app_district_id, t3.physical_address as app_physical_address,
                    t3.postal_address as app_postal_address, t3.telephone_no as app_telephone, t3.fax as app_fax, t3.email as app_email, t3.website as app_website,
                    t2.*, t1.*,t7.name as gmp_type_txt"));
            $is_super ? $qry->whereRaw('1=1') : $qry->whereIn('t1.workflow_stage_id', $assigned_stages);
            if (isset($section_id) && $section_id != '') {
                $qry->where('t1.section_id', $section_id);
            }
            if (isset($sub_module_id) && $sub_module_id != '') {
                $qry->where('t1.sub_module_id', $sub_module_id);
            }
            if (isset($workflow_stage_id) && $workflow_stage_id != '') {
                $qry->where('t1.workflow_stage_id', $workflow_stage_id);
            }
            if (isset($gmp_type_id) && $gmp_type_id != '') {
                $qry->where('t1.gmp_type_id', $gmp_type_id);
            }
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getManagerApplicationsGeneric(Request $request)
    {
        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        $section_id = $request->input('section_id');
        $gmp_type_id = $request->input('gmp_type_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('tra_manufacturing_sites as t2', 't1.manufacturing_site_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', function ($join) {
                    $join->on('t1.application_status_id', '=', 't4.id');
                })
                ->leftJoin('tra_approval_recommendations as t5', function ($join) {
                    $join->on('t1.id', '=', 't5.application_id')
                        ->on('t1.application_code', '=', 't5.application_code');
                })
                ->leftJoin('par_gmpapproval_decisions as t6', 't5.decision_id', '=', 't6.id')
                ->join('gmplocation_details as t7', 't1.gmp_type_id', '=', 't7.id')
                ->select('t1.*', 't2.id as premise_id', 't2.name as premise_name', 't3.name as applicant_name', 't4.name as application_status',
                    't6.name as approval_status', 't5.decision_id', 't1.id as active_application_id', 't7.name as gmp_type_txt')
                ->where('t1.workflow_stage_id', $workflow_stage);
            if (isset($section_id) && $section_id != '') {
                $qry->where('t1.section_id', $section_id);
            }
            if (isset($gmp_type_id) && $gmp_type_id != '') {
                $qry->where('t1.gmp_type_id', $gmp_type_id);
            }
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getGmpApplicationsAtApproval(Request $request)
    {
        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        $gmp_type_id = $request->input('gmp_type_id');
        $section_id = $request->input('section_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('tra_manufacturing_sites as t2', 't1.manufacturing_site_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->leftjoin('par_system_statuses as t4', function ($join) {//should be inner
                    $join->on('t1.application_status_id', '=', 't4.id');
                    //->on('t1.process_id', '=', 't4.process_id');
                })
                ->leftJoin('tra_approval_recommendations as t5', function ($join) {
                    $join->on('t1.id', '=', 't5.application_id')
                        ->on('t1.application_code', '=', 't5.application_code');
                })
                ->leftJoin('par_gmpapproval_decisions as t6', 't5.decision_id', '=', 't6.id')
                ->join('wf_tfdaprocesses as t7', 't1.process_id', '=', 't7.id')
                ->join('wf_workflow_stages as t8', 't1.workflow_stage_id', '=', 't8.id')
                ->join('gmplocation_details as t9', 't1.gmp_type_id', '=', 't9.id')
                ->select('t1.*', 't1.id as active_application_id', 't2.name as premise_name', 't3.name as applicant_name', 't4.name as application_status', 't6.name as approval_status',
                    't2.id as premise_id', 't2.init_premise_id', 't7.name as process_name', 't8.name as workflow_stage', 't8.is_general', 't5.id as recommendation_id', 't6.name as recommendation',
                    't9.name as gmp_type_txt')
                ->where('t1.workflow_stage_id', $workflow_stage);
            if (isset($gmp_type_id) && $gmp_type_id != '') {
                $qry->where('t1.gmp_type_id', $gmp_type_id);
            }
            if (isset($section_id) && $section_id != '') {
                $qry->where('t1.section_id', $section_id);
            }
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getGmpApplicationsForInspection(Request $request)
    {
        $table_name = $request->input('table_name');
        $inspection_id = $request->input('inspection_id');
        $section_id = $request->input('section_id');
        $gmp_type_id = $request->input('gmp_type_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('tra_manufacturing_sites as t2', 't1.manufacturing_site_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', function ($join) {
                    $join->on('t1.application_status_id', '=', 't4.id');
                })
                ->join('sub_modules as t5', 't1.sub_module_id', '=', 't5.id')
                ->join('gmplocation_details as t7', 't1.gmp_type_id', '=', 't7.id')
                ->leftJoin('assigned_gmpinspections as t8', 't1.application_code', '=', 't8.application_code')
                ->select('t1.*', 't2.id as premise_id', 't2.name as premise_name', 't3.name as applicant_name', 't4.name as application_status',
                    't1.id as active_application_id', 't7.name as gmp_type_txt', 't5.name as sub_module_name')
                ->whereIn('t1.workflow_stage_id', array(120, 121, 130, 131))
                ->whereNull('t8.id');
            if (isset($section_id) && $section_id != '') {
                $qry->where('t1.section_id', $section_id);
            }
            if (isset($gmp_type_id) && $gmp_type_id != '') {
                $qry->where('t1.gmp_type_id', $gmp_type_id);
            }
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getGmpApplicationMoreDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $applicant_id = $request->input('applicant_id');
        $site_id = $request->input('premise_id');
        try {
            $zone_id = DB::table('tra_premises_applications')
                ->where('id', $application_id)
                ->value('zone_id');
            $qryApplicant = DB::table('wb_trader_account as t1')
                ->join('par_countries as t2', 't1.country_id', '=', 't2.id')
                ->join('par_regions as t3', 't1.region_id', '=', 't3.id')
                ->leftJoin('par_districts as t4', 't1.district_id', '=', 't4.id')
                ->select('t1.id as applicant_id', 't1.name as applicant_name', 't1.contact_person', 't1.tin_no',
                    't1.country_id as app_country_id', 't1.region_id as app_region_id', 't1.district_id as app_district_id',
                    't1.physical_address as app_physical_address', 't1.postal_address as app_postal_address', 't1.telephone_no as app_telephone',
                    't1.fax as app_fax', 't1.email as app_email', 't1.website as app_website', 't2.name as country_name', 't3.name as region_name', 't4.name as district_name')
                ->where('t1.id', $applicant_id);
            $applicantDetails = $qryApplicant->first();

            $sharedQry = DB::table('tra_manufacturing_sites as t1')
                ->where('t1.id', $site_id);

            $qryPremise = clone $sharedQry;
            $qryPremise->join('par_countries as t2', 't1.country_id', '=', 't2.id')
                ->join('par_regions as t3', 't1.region_id', '=', 't3.id')
                ->leftJoin('par_districts as t4', 't1.district_id', '=', 't4.id')
                ->select('t1.name as premise_name', 't1.id as premise_id', 't1.id as manufacturing_site_id', 't1.*');
            $premiseDetails = $qryPremise->first();

            $qryLtr = clone $sharedQry;
            $qryLtr->join('wb_trader_account as t2', 't1.ltr_id', '=', 't2.id')
                ->select('t2.id as ltr_id', 't2.name as applicant_name', 't2.tin_no',
                    't2.physical_address as app_physical_address', 't2.postal_address as app_postal_address', 't2.telephone_no as app_telephone',
                    't2.fax as app_fax', 't2.email as app_email');
            $ltrDetails = $qryLtr->first();

            $res = array(
                'success' => true,
                'applicant_details' => $applicantDetails,
                'premise_details' => $premiseDetails,
                'ltr_details' => $ltrDetails,
                'zone_id' => $zone_id,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function saveNewGmpReceivingBaseDetails(Request $request)
    {
        $gmp_type_id = $request->input('gmp_type_id');
        if ($gmp_type_id == 1) {//Oversea
            return $this->saveNewOverseaReceivingBaseDetails($request);
        } else if ($gmp_type_id == 2) {//Domestic
            return $this->saveNewDomesticReceivingBaseDetails($request);
        } else {
            $res = array(
                'success' => false,
                'message' => 'Unknown GMP type'
            );
            return \response()->json($res);
        }
    }

    public function saveNewDomesticReceivingBaseDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $premise_id = $request->input('premise_id');
        $site_id = $request->input('manufacturing_site_id');
        $applicant_id = $request->input('applicant_id');
        $ltr_id = $request->input('ltr_id');
        $process_id = $request->input('process_id');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $gmp_type_id = $request->input('gmp_type_id');
        $zone_id = $request->input('zone_id');
        $user_id = $this->user_id;

        try {
            $premise_table = 'tra_premises';
            $site_table = 'tra_manufacturing_sites';
            $applications_table = 'tra_gmp_applications';

            $where_premise = array(//risky
                'id' => $site_id
            );
            $where_app = array(
                'id' => $application_id
            );

            if (isset($application_id) && $application_id != "") {//Edit
                $where_site = array(
                    'id' => $site_id
                );
                //Application_edit
                $application_params = array(
                    'applicant_id' => $applicant_id,
                    'gmp_type_id' => $gmp_type_id,
                    'manufacturing_site_id' => $site_id,
                    'zone_id' => $zone_id,
                );
                $app_details = array();
                if (recordExists($applications_table, $where_app)) {
                    $app_details = getPreviousRecords($applications_table, $where_app);
                    if ($app_details['success'] == false) {
                        return $app_details;
                    }
                    $app_details = $app_details['results'];
                    updateRecord($applications_table, $app_details, $where_app, $application_params, $user_id);
                }
                $application_code = $app_details[0]['application_code'];
                $ref_number = $app_details[0]['reference_no'];
                //Site edit
                $site_params['dola'] = Carbon::now();
                $site_params['altered_by'] = $user_id;
                $site_params['ltr_id'] = $ltr_id;
                $previous_data = getPreviousRecords($site_table, $where_site);
                if ($previous_data['success'] == false) {
                    return $previous_data;
                }
                $previous_data = $previous_data['results'];
                $res = updateRecord($site_table, $previous_data, $where_site, $site_params, $user_id);
            } else {//Create
                //Manufacturing Site
                $site_params = getTableData($premise_table, $where_premise);
                if (is_null($site_params)) {
                    $res = array(
                        'success' => false,
                        'message' => 'Problem encountered while fetching selected manufacturing site details!!'
                    );
                    return \response()->json($res);
                }
                //$zone_id = $site_params->zone_id;
                $section_id = $site_params->section_id;
                $site_params = convertStdClassObjToArray($site_params);
                unset($site_params['id']);
                unset($site_params['portal_id']);
                unset($site_params['permit_id']);
                $site_params['premise_id'] = $premise_id;
                $site_params['ltr_id'] = $ltr_id;
                $site_params['status_id'] = 1;
                $site_params['gmp_type_id'] = $gmp_type_id;

                $site_res = insertRecord($site_table, $site_params, $user_id);
                if ($site_res['success'] == false) {
                    return \response()->json($site_res);
                }
                $site_id = $site_res['record_id'];
                //Application_create
                $zone_code = getSingleRecordColValue('par_zones', array('id' => $zone_id), 'zone_code');
                $section_code = getSingleRecordColValue('par_sections', array('id' => $section_id), 'code');
                $gmp_code = getSingleRecordColValue('gmplocation_details', array('id' => $gmp_type_id), 'location_code');
                $codes_array = array(
                    'section_code' => $section_code,
                    'zone_code' => $zone_code,
                    'gmp_type' => $gmp_code
                );
                $view_id = generateApplicationViewID();
                $ref_number = generatePremiseRefNumber(9, $codes_array, date('Y'), $process_id, $zone_id, $user_id);
                $application_code = generateApplicationCode($sub_module_id, $applications_table);
                $application_status = getApplicationInitialStatus($module_id, $sub_module_id);
                $application_params = array(
                    'applicant_id' => $applicant_id,
                    'view_id' => $view_id,
                    'module_id' => $module_id,
                    'sub_module_id' => $sub_module_id,
                    'gmp_type_id' => $gmp_type_id,
                    'zone_id' => $zone_id,
                    'section_id' => $section_id,
                    'application_code' => $application_code,
                    'manufacturing_site_id' => $site_id,
                    'process_id' => $process_id,
                    'workflow_stage_id' => $workflow_stage_id,
                    'reference_no' => $ref_number,
                    'application_status_id' => $application_status->status_id
                );
                $res = insertRecord($applications_table, $application_params, $user_id);
                if ($res['success'] == false) {
                    return $res;
                }
                $application_id = $res['record_id'];

                //insert registration table
                $reg_params = array(
                    'tra_site_id' => $site_id,
                    'status_id' => 1,
                    'created_by' => $user_id
                );
                createInitialRegistrationRecord('registered_manufacturing_sites', $applications_table, $reg_params, $application_id, 'reg_site_id');
                //DMS
                initializeApplicationDMS($section_id, $module_id, $sub_module_id, $application_code, $ref_number, $user_id);
                //add to submissions table
                $submission_params = array(
                    'application_id' => $application_id,
                    'view_id' => $view_id,
                    'process_id' => $process_id,
                    'application_code' => $application_code,
                    'reference_no' => $ref_number,
                    'usr_from' => $user_id,
                    'usr_to' => $user_id,
                    'previous_stage' => $workflow_stage_id,
                    'current_stage' => $workflow_stage_id,
                    'module_id' => $module_id,
                    'sub_module_id' => $sub_module_id,
                    'section_id' => $section_id,
                    'application_status_id' => $application_status->status_id,
                    'urgency' => 1,
                    'applicant_id' => $applicant_id,
                    'remarks' => 'Initial save of the application',
                    'date_received' => Carbon::now(),
                    'created_on' => Carbon::now(),
                    'created_by' => $user_id
                );
                DB::table('tra_submissions')
                    ->insert($submission_params);
            }
            $res['record_id'] = $application_id;
            $res['application_code'] = $application_code;
            $res['premise_id'] = $site_id;
            $res['ref_no'] = $ref_number;
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return $res;
    }

    public function saveNewOverseaReceivingBaseDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $site_id = $request->input('manufacturing_site_id');
        $applicant_id = $request->input('applicant_id');
        $ltr_id = $request->input('ltr_id');
        $process_id = $request->input('process_id');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $zone_id = $request->input('zone_id');
        $section_id = $request->input('section_id');
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $gmp_type_id = $request->input('gmp_type_id');
        $user_id = $this->user_id;
        $site_params = array(
            'name' => $request->input('name'),
            'applicant_id' => $applicant_id,
            'section_id' => $section_id,
            //'zone_id' => $zone_id,
            'ltr_id' => $ltr_id,
            'gmp_type_id' => $gmp_type_id,
            'country_id' => $request->input('country_id'),
            'region_id' => $request->input('region_id'),
            'district_id' => $request->input('district_id'),
            'street' => $request->input('street'),
            'telephone' => $request->input('telephone'),
            'fax' => $request->input('fax'),
            'email' => $request->input('email'),
            'website' => $request->input('website'),
            'physical_address' => $request->input('physical_address'),
            'postal_address' => $request->input('postal_address'),
            'business_scale_id' => $request->input('business_scale_id'),
            'longitude' => $request->input('longitude'),
            'latitude' => $request->input('latitude')
        );
        try {
            $site_table = 'tra_manufacturing_sites';
            $applications_table = 'tra_gmp_applications';

            $where_site = array(
                'id' => $site_id
            );
            $where_app = array(
                'id' => $application_id
            );
            //$portal_applicant_id = getSingleRecordColValue('wb_trader_account', array('id' => $applicant_id), 'portal_id');
            if (isset($application_id) && $application_id != "") {//Edit
                //Application_edit
                $application_params = array(
                    'applicant_id' => $applicant_id,
                    'gmp_type_id' => $gmp_type_id,
                    'zone_id' => $zone_id
                );
                $app_details = array();
                if (recordExists($applications_table, $where_app)) {
                    //$app_details = getTableData($applications_table, $where_app);
                    $app_details = getPreviousRecords($applications_table, $where_app);
                    if ($app_details['success'] == false) {
                        return $app_details;
                    }
                    $app_details = $app_details['results'];
                    $app_res = updateRecord($applications_table, $app_details, $where_app, $application_params, $user_id);
                    if ($app_res['success'] == false) {
                        return $app_res;
                    }
                }
                $application_code = $app_details[0]['application_code'];//$app_details->application_code;
                $ref_number = $app_details[0]['reference_no'];//$app_details->reference_no;
                //Premise_edit
                if (recordExists($site_table, $where_site)) {
                    $site_params['dola'] = Carbon::now();
                    $site_params['altered_by'] = $user_id;
                    $previous_data = getPreviousRecords($site_table, $where_site);
                    if ($previous_data['success'] == false) {
                        return $previous_data;
                    }
                    $previous_data = $previous_data['results'];
                    $res = updateRecord($site_table, $previous_data, $where_site, $site_params, $user_id);
                    //update portal also
                    /* unset($site_params['created_by']);
                     unset($site_params['created_on']);
                     unset($site_params['dola']);
                     unset($site_params['altered_by']);
                     $premise_params['mis_dola'] = Carbon::now();
                     $premise_params['mis_altered_by'] = $user_id;
                     $premise_params['applicant_id'] = $portal_applicant_id;
                     $portal_premise_id = getSingleRecordColValue('tra_premises', $where_premise, 'portal_id');
                     $portal_db = DB::connection('portal_db');
                     $portal_db->table('wb_premises')
                         ->where('id', $portal_premise_id)
                         ->update($premise_params);*/
                }
            } else {//Create
                //Premise_create
                $site_res = insertRecord($site_table, $site_params, $user_id);
                if ($site_res['success'] == false) {
                    return \response()->json($site_res);
                }
                $site_id = $site_res['record_id'];
                //Application_create
                $zone_code = getSingleRecordColValue('par_zones', array('id' => $zone_id), 'zone_code');
                $section_code = getSingleRecordColValue('par_sections', array('id' => $section_id), 'code');
                $gmp_code = getSingleRecordColValue('gmplocation_details', array('id' => $gmp_type_id), 'location_code');
                $codes_array = array(
                    'section_code' => $section_code,
                    'zone_code' => $zone_code,
                    'gmp_type' => $gmp_code
                );
                $view_id = generateApplicationViewID();
                $ref_number = generatePremiseRefNumber(9, $codes_array, date('Y'), $process_id, $zone_id, $user_id);
                $application_code = generateApplicationCode($sub_module_id, $applications_table);
                $application_status = getApplicationInitialStatus($module_id, $sub_module_id);
                $application_params = array(
                    'applicant_id' => $applicant_id,
                    'view_id' => $view_id,
                    'module_id' => $module_id,
                    'gmp_type_id' => $gmp_type_id,
                    'sub_module_id' => $sub_module_id,
                    'section_id' => $section_id,
                    'application_code' => $application_code,
                    'zone_id' => $zone_id,
                    'manufacturing_site_id' => $site_id,
                    'process_id' => $process_id,
                    'workflow_stage_id' => $workflow_stage_id,
                    'reference_no' => $ref_number,
                    'application_status_id' => $application_status->status_id
                );
                $res = insertRecord($applications_table, $application_params, $user_id);
                $application_id = $res['record_id'];

                //insert registration table
                $reg_params = array(
                    'tra_site_id' => $site_id,
                    'status_id' => 1,
                    'created_by' => $user_id
                );
                createInitialRegistrationRecord('registered_manufacturing_sites', $applications_table, $reg_params, $application_id, 'reg_site_id');
                //DMS
                initializeApplicationDMS($section_id, $module_id, $sub_module_id, $application_code, $ref_number, $user_id);
                //add to submissions table
                $submission_params = array(
                    'application_id' => $application_id,
                    'view_id' => $view_id,
                    'process_id' => $process_id,
                    'application_code' => $application_code,
                    'reference_no' => $ref_number,
                    'usr_from' => $user_id,
                    'usr_to' => $user_id,
                    'previous_stage' => $workflow_stage_id,
                    'current_stage' => $workflow_stage_id,
                    'module_id' => $module_id,
                    'sub_module_id' => $sub_module_id,
                    'section_id' => $section_id,
                    'application_status_id' => $application_status->status_id,
                    'urgency' => 1,
                    'applicant_id' => $applicant_id,
                    'remarks' => 'Initial save of the application',
                    'date_received' => Carbon::now(),
                    'created_on' => Carbon::now(),
                    'created_by' => $user_id
                );
                DB::table('tra_submissions')
                    ->insert($submission_params);
            }
            $res['record_id'] = $application_id;
            $res['application_code'] = $application_code;
            $res['premise_id'] = $site_id;
            $res['ref_no'] = $ref_number;
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function saveRenewalGmpReceivingBaseDetails(Request $request)
    {
        try {
            $res = array();
            DB::transaction(function () use ($request, &$res) {
                $application_id = $request->input('application_id');
                $registered_id = $request->input('registered_id');
                $init_site_id = $request->input('manufacturing_site_id');
                $gmp_type_id = $request->input('gmp_type_id');
                $applicant_id = $request->input('applicant_id');
                $ltr_id = $request->input('ltr_id');
                $process_id = $request->input('process_id');
                $workflow_stage_id = $request->input('workflow_stage_id');
                $zone_id = $request->input('zone_id');
                $section_id = $request->input('section_id');
                $module_id = $request->input('module_id');
                $sub_module_id = $request->input('sub_module_id');
                $user_id = $this->user_id;
                $site_params = array(
                    'name' => $request->input('name'),
                    'applicant_id' => $applicant_id,
                    'registered_id' => $registered_id,
                    'section_id' => $section_id,
                    //'zone_id' => $zone_id,
                    'ltr_id' => $ltr_id,
                    'gmp_type_id' => $gmp_type_id,
                    'country_id' => $request->input('country_id'),
                    'region_id' => $request->input('region_id'),
                    'district_id' => $request->input('district_id'),
                    'street' => $request->input('street'),
                    'telephone' => $request->input('telephone'),
                    'fax' => $request->input('fax'),
                    'email' => $request->input('email'),
                    'website' => $request->input('website'),
                    'physical_address' => $request->input('physical_address'),
                    'postal_address' => $request->input('postal_address'),
                    'business_scale_id' => $request->input('business_scale_id'),
                    'longitude' => $request->input('longitude'),
                    'latitude' => $request->input('latitude')
                );

                $site_table = 'tra_manufacturing_sites';
                $applications_table = 'tra_gmp_applications';

                $where_site = array(
                    'id' => $init_site_id
                );
                $where_app = array(
                    'id' => $application_id
                );
                $portal_applicant_id = getSingleRecordColValue('wb_trader_account', array('id' => $applicant_id), 'portal_id');
                if (isset($application_id) && $application_id != "") {//Edit
                    $site_id = $init_site_id;
                    //Application_edit
                    $application_params = array(
                        'applicant_id' => $applicant_id,
                        'zone_id' => $zone_id,
                        'gmp_type_id' => $gmp_type_id,
                        'reg_site_id' => $registered_id
                    );
                    $app_details = array();
                    if (recordExists($applications_table, $where_app)) {
                        //$app_details = getTableData($applications_table, $where_app);
                        $app_details = getPreviousRecords($applications_table, $where_app);
                        if ($app_details['success'] == false) {
                            return $app_details;
                        }
                        $app_details = $app_details['results'];
                        $app_res = updateRecord($applications_table, $app_details, $where_app, $application_params, $user_id);
                        if ($app_res['success'] == false) {
                            return $app_res;
                        }
                    }
                    $application_code = $app_details[0]['application_code'];//$app_details->application_code;
                    $ref_number = $app_details[0]['reference_no'];//$app_details->reference_no;
                    //Premise_edit
                    if (recordExists($site_table, $where_site)) {
                        $site_params['dola'] = Carbon::now();
                        $site_params['altered_by'] = $user_id;
                        $previous_data = getPreviousRecords($site_table, $where_site);
                        if ($previous_data['success'] == false) {
                            return $previous_data;
                        }
                        $previous_data = $previous_data['results'];
                        $res = updateRecord($site_table, $previous_data, $where_site, $site_params, $user_id);
                        //update portal also
                        unset($site_params['created_by']);
                        unset($site_params['created_on']);
                        unset($site_params['dola']);
                        unset($site_params['altered_by']);
                        $site_params['mis_dola'] = Carbon::now();
                        $site_params['mis_altered_by'] = $user_id;
                        $site_params['applicant_id'] = $portal_applicant_id;
                        $portal_site_id = getSingleRecordColValue('tra_manufacturing_sites', $where_site, 'portal_id');
                        $portal_db = DB::connection('portal_db');
                        /* $portal_db->table('wb_manufacturing_sites')
                             ->where('id', $portal_site_id)
                             ->update($site_params);*/
                    }
                } else {//Create
                    $anyOngoingApps = checkForOngoingApplications($registered_id, $applications_table, 'reg_site_id', $process_id);
                    if ($anyOngoingApps['exists'] == true) {
                        $res = array(
                            'success' => false,
                            'message' => 'There is an ongoing application of the same nature on the selected manufacturing site with reference number ' . $anyOngoingApps['ref_no']
                        );
                        return \response()->json($res);
                    }
                    $init_site_params = getTableData($site_table, $where_site);
                    if (is_null($init_site_params)) {
                        $res = array(
                            'success' => false,
                            'message' => 'Problem encountered while fetching target site details!!'
                        );
                        return \response()->json($res);
                    }
                    $site_params['permit_id'] = $init_site_params->permit_id;
                    $site_params['premise_reg_no'] = $init_site_params->premise_reg_no;
                    $site_params['certificate_issue_date'] = $init_site_params->certificate_issue_date;
                    $site_params['portal_id'] = $init_site_params->portal_id;
                    //Premise_create
                    $site_params['init_site_id'] = $init_site_id;
                    $site_res = insertRecord($site_table, $site_params, $user_id);
                    if ($site_res['success'] == false) {
                        return \response()->json($site_res);
                    }
                    $site_id = $site_res['record_id'];
                    //copy site personnel details, business details and product line details
                    $init_personnelDetails = DB::table('tra_manufacturing_sites_personnel as t1')
                        ->select(DB::raw("t1.name,t1.telephone,t1.email_address,t1.postal_address,t1.fax,t1.position_id,t1.portal_id,t1.status_id,
                           $user_id as created_by,t1.manufacturing_site_id as init_site_id,$site_id as manufacturing_site_id"))
                        ->where('manufacturing_site_id', $init_site_id)
                        ->get();
                    $init_personnelDetails = convertStdClassObjToArray($init_personnelDetails);
                    $init_businessDetails = DB::table('tra_mansite_otherdetails as t2')
                        ->select(DB::raw("t2.business_type_id,t2.business_type_detail_id,t2.portal_id,
                           $user_id as created_by,t2.manufacturing_site_id as init_site_id,$site_id as manufacturing_site_id"))
                        ->where('manufacturing_site_id', $init_site_id)
                        ->get();
                    $init_businessDetails = convertStdClassObjToArray($init_businessDetails);
                    $init_productLineDetails = DB::table('gmp_product_details as t3')
                        ->select(DB::raw("t3.product_line_id,t3.category_id,t3.prodline_description_id,
                           $user_id as created_by,t3.manufacturing_site_id as init_site_id,$site_id as manufacturing_site_id"))
                        ->where('manufacturing_site_id', $init_site_id)
                        ->get();
                    $init_productLineDetails = convertStdClassObjToArray($init_productLineDetails);
                    DB::table('tra_manufacturing_sites_personnel')
                        ->insert($init_personnelDetails);
                    DB::table('tra_mansite_otherdetails')
                        ->insert($init_businessDetails);
                    DB::table('gmp_product_details')
                        ->insert($init_productLineDetails);
                    //Application_create
                    $zone_code = getSingleRecordColValue('par_zones', array('id' => $zone_id), 'zone_code');
                    $section_code = getSingleRecordColValue('par_sections', array('id' => $section_id), 'code');
                    $gmp_code = getSingleRecordColValue('gmplocation_details', array('id' => $gmp_type_id), 'location_code');
                    $codes_array = array(
                        'section_code' => $section_code,
                        'zone_code' => $zone_code,
                        'gmp_type' => $gmp_code
                    );
                    $ref_id = 11;
                    $view_id = generateApplicationViewID();
                    $ref_number = generatePremiseRefNumber($ref_id, $codes_array, date('Y'), $process_id, $zone_id, $user_id);
                    $application_code = generateApplicationCode($sub_module_id, $applications_table);
                    $application_status = getApplicationInitialStatus($module_id, $sub_module_id);
                    $application_params = array(
                        'applicant_id' => $applicant_id,
                        'view_id' => $view_id,
                        'module_id' => $module_id,
                        'sub_module_id' => $sub_module_id,
                        'section_id' => $section_id,
                        'application_code' => $application_code,
                        'zone_id' => $zone_id,
                        'manufacturing_site_id' => $site_id,
                        'reg_site_id' => $registered_id,
                        'gmp_type_id' => $gmp_type_id,
                        'process_id' => $process_id,
                        'workflow_stage_id' => $workflow_stage_id,
                        'reference_no' => $ref_number,
                        'application_status_id' => $application_status->status_id
                    );
                    $res = insertRecord($applications_table, $application_params, $user_id);
                    $application_id = $res['record_id'];
                    //DMS
                    initializeApplicationDMS($section_id, $module_id, $sub_module_id, $application_code, $ref_number, $user_id);
                    //add to submissions table
                    $submission_params = array(
                        'application_id' => $application_id,
                        'view_id' => $view_id,
                        'process_id' => $process_id,
                        'application_code' => $application_code,
                        'reference_no' => $ref_number,
                        'usr_from' => $user_id,
                        'usr_to' => $user_id,
                        'previous_stage' => $workflow_stage_id,
                        'current_stage' => $workflow_stage_id,
                        'module_id' => $module_id,
                        'sub_module_id' => $sub_module_id,
                        'section_id' => $section_id,
                        'application_status_id' => $application_status->status_id,
                        'urgency' => 1,
                        'applicant_id' => $applicant_id,
                        'remarks' => 'Initial save of the application',
                        'date_received' => Carbon::now(),
                        'created_on' => Carbon::now(),
                        'created_by' => $user_id
                    );
                    DB::table('tra_submissions')
                        ->insert($submission_params);
                }
                $res['record_id'] = $application_id;
                $res['application_code'] = $application_code;
                $res['premise_id'] = $site_id;
                $res['ref_no'] = $ref_number;
            }, 5);
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function prepareNewGmpOnlineReceivingStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('aon_pplicaticode');
        $table_name = $request->input('table_name');
        try {
            $portal_db = DB::connection('portal_db');

            $main_qry = $portal_db->table('wb_gmp_applications as t1')
                ->join('wb_manufacturing_sites as t2', 't1.manufacturing_site_id', '=', 't2.id')
                ->where('t1.id', $application_id);

            $qry = clone $main_qry;
            $qry->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->select('t1.*', 't1.id as active_application_id', 't2.name as premise_name',
                    't3.id as applicant_id', 't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't2.*');
            $results = $qry->first();

            $qry2 = clone $main_qry;
            $qry2->join('wb_trader_account as t3', 't2.ltr_id', '=', 't3.id')
                ->select('t3.id as ltr_id', 't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website');
            $ltrDetails = $qry2->first();

            $res = array(
                'success' => true,
                'results' => $results,
                'ltrDetails' => $ltrDetails,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function prepareNewGmpReceivingStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $main_qry = DB::table('tra_gmp_applications as t1')
                ->join('tra_manufacturing_sites as t2', 't1.manufacturing_site_id', '=', 't2.id')
                ->where('t1.id', $application_id);

            $qry1 = clone $main_qry;
            $qry1->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->leftJoin('tra_application_invoices as t4', function ($join) use ($application_code) {
                    $join->on('t1.id', '=', 't4.application_id')
                        ->on('t4.application_code', '=', 't4.application_code');
                })
                ->leftJoin('tra_approval_recommendations as t5', 't2.permit_id', '=', 't5.id')
                ->join('gmplocation_details as t6', 't1.gmp_type_id', '=', 't6.id')
                ->select('t1.*', 't1.id as active_application_id', 't2.name as premise_name',
                    't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't2.*', 't4.id as invoice_id', 't4.invoice_no', 't5.permit_no', 't5.permit_no as gmp_cert_no', 't6.name as gmp_type_txt');
            $results = $qry1->first();

            $qry2 = clone $main_qry;
            $qry2->join('wb_trader_account as t3', 't2.ltr_id', '=', 't3.id')
                ->select('t3.id as ltr_id', 't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website');
            $ltrDetails = $qry2->first();
            $res = array(
                'success' => true,
                'results' => $results,
                'ltrDetails' => $ltrDetails,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function prepareNewGmpInvoicingStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t2', 't1.applicant_id', '=', 't2.id')
                ->leftJoin('tra_application_invoices as t3', function ($join) use ($application_code) {
                    $join->on('t1.id', '=', 't3.application_id')
                        ->on('t3.application_code', '=', DB::raw($application_code));
                })
                ->join('tra_manufacturing_sites as t4', 't1.manufacturing_site_id', '=', 't4.id')
                ->join('gmplocation_details as t5', 't1.gmp_type_id', '=', 't5.id')
                ->select(DB::raw("t1.applicant_id,t1.manufacturing_site_id as premise_id,t1.gmp_type_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details, t3.id as invoice_id, t3.invoice_no,
                    t1.section_id,t1.module_id,CONCAT_WS(',',t4.name,t4.postal_address) as premise_details,t5.name as gmp_type_txt,t1.is_fast_track"))
                ->where('t1.id', $application_id);
            $results = $qry->first();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function prepareNewGmpPaymentStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t2', 't1.applicant_id', '=', 't2.id')
                ->leftJoin('tra_application_invoices as t3', function ($join) use ($application_code) {
                    $join->on('t1.id', '=', 't3.application_id')
                        ->on('t3.application_code', '=', DB::raw($application_code));
                })
                ->join('tra_manufacturing_sites as t4', 't1.manufacturing_site_id', '=', 't4.id')
                ->join('gmplocation_details as t5', 't1.gmp_type_id', '=', 't5.id')
                ->select(DB::raw("t1.applicant_id,t1.manufacturing_site_id as premise_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details, t3.id as invoice_id, t3.invoice_no,
                t1.section_id,t1.module_id,CONCAT_WS(',',t4.name,t4.postal_address) as premise_details,t5.name as gmp_type_txt"))
                ->where('t1.id', $application_id);
            $results = $qry->first();
            $payment_details = getApplicationPaymentsRunningBalance($application_id, $application_code, $results->invoice_id);
            $res = array(
                'success' => true,
                'results' => $results,
                'balance' => formatMoney($payment_details['running_balance']),
                'invoice_amount' => formatMoney($payment_details['invoice_amount']),
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function prepareNewGmpChecklistsStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t2', 't1.applicant_id', '=', 't2.id')
                ->join('tra_manufacturing_sites as t3', 't1.manufacturing_site_id', '=', 't3.id')
                ->join('gmplocation_details as t4', 't1.gmp_type_id', '=', 't4.id')
                ->select(DB::raw("t1.applicant_id,t3.id as premise_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details,
                     t1.gmp_type_id,t1.section_id,t1.module_id,CONCAT_WS(',',t3.name,t3.postal_address) as premise_details,t4.name as gmp_type_txt"))
                ->where('t1.id', $application_id);
            $results = $qry->first();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getSitePersonnelDetails(Request $request)
    {
        $site_id = $request->input('site_id');
        try {
            $qry = DB::table('tra_manufacturing_sites_personnel as t1')
                ->join('par_personnel_positions as t2', 't1.position_id', 't2.id')
                ->select('t1.*', 't2.name as position_name')
                ->where('manufacturing_site_id', $site_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getSiteOtherDetails(Request $request)
    {
        $site_id = $request->input('site_id');
        try {
            $qry = DB::table('tra_mansite_otherdetails as t1')
                ->join('par_business_types as t2', 't1.business_type_id', 't2.id')
                ->join('par_business_type_details as t3', 't1.business_type_detail_id', 't3.id')
                ->select('t1.*', 't2.name as business_type', 't3.name as business_type_detail')
                ->where('manufacturing_site_id', $site_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function saveSiteOtherDetails(Request $req)
    {
        try {
            $user_id = \Auth::user()->id;
            $post_data = $req->all();
            $table_name = $post_data['table_name'];
            $id = $post_data['id'];
            $site_id = $post_data['manufacturing_site_id'];
            $business_type_id = $post_data['business_type_id'];
            $business_type_detail_id = $post_data['business_type_detail_id'];
            //unset unnecessary values
            unset($post_data['_token']);
            unset($post_data['table_name']);
            unset($post_data['model']);
            unset($post_data['id']);
            $table_data = $post_data;
            //add extra params
            $table_data['created_on'] = Carbon::now();
            $table_data['created_by'] = $user_id;
            $where = array(
                'id' => $id
            );
            $where2 = array(
                'manufacturing_site_id' => $site_id,
                'business_type_id' => $business_type_id,
                'business_type_detail_id' => $business_type_detail_id
            );
            if (DB::table($table_name)
                    ->where($where2)
                    ->count() > 0) {
                $res = array(
                    'success' => false,
                    'message' => 'This combination already exists!!'
                );
                return \response()->json($res);
            };
            if (isset($id) && $id != "") {
                if (recordExists($table_name, $where)) {
                    unset($table_data['created_on']);
                    unset($table_data['created_by']);
                    $table_data['dola'] = Carbon::now();
                    $table_data['altered_by'] = $user_id;
                    $previous_data = getPreviousRecords($table_name, $where);
                    if ($previous_data['success'] == false) {
                        return $previous_data;
                    }
                    $previous_data = $previous_data['results'];
                    $res = updateRecord($table_name, $previous_data, $where, $table_data, $user_id);
                }
            } else {
                $res = insertRecord($table_name, $table_data, $user_id);
            }
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    public function saveGmpInspectionLineDetails(Request $req)
    {
        $res = array();
        try {
            $user_id = \Auth::user()->id;
            $post_data = $req->all();
            $table_name = $post_data['table_name'];
            $id = $post_data['id'];
            $site_id = $post_data['manufacturing_site_id'];
            $inspection_stage = $req->input('inspection_stage');
            $product_line = $post_data['product_line_id'];
            $product_category = $post_data['category_id'];
            $product_description = $post_data['prodline_description_id'];
            //unset unnecessary values
            unset($post_data['_token']);
            unset($post_data['table_name']);
            unset($post_data['model']);
            unset($post_data['id']);
            $table_data = $post_data;
            //add extra params
            $table_data['created_on'] = Carbon::now();
            $table_data['created_by'] = $user_id;
            $where = array(
                'id' => $id
            );
            $where2 = array(
                'manufacturing_site_id' => $site_id,
                'product_line_id' => $product_line,
                'category_id' => $product_category,
                'prodline_description_id' => $product_description
            );
            if (isset($inspection_stage) && $inspection_stage == 1) {
                unset($table_data['inspection_stage']);
            } else {
                if (DB::table($table_name)
                        ->where($where2)
                        ->count() > 0) {
                    $res = array(
                        'success' => false,
                        'message' => 'This combination already exists!!'
                    );
                    return \response()->json($res);
                };
            }
            if (isset($id) && $id != "") {
                if (recordExists($table_name, $where)) {
                    unset($table_data['created_on']);
                    unset($table_data['created_by']);
                    $table_data['dola'] = Carbon::now();
                    $table_data['altered_by'] = $user_id;
                    $previous_data = getPreviousRecords($table_name, $where);
                    if ($previous_data['success'] == false) {
                        return $previous_data;
                    }
                    $previous_data = $previous_data['results'];
                    $res = updateRecord($table_name, $previous_data, $where, $table_data, $user_id);
                }
            } else {
                $res = insertRecord($table_name, $table_data, $user_id);
            }
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    public function getGmpInspectionLineDetails(Request $request)
    {
        $site_id = $request->input('site_id');
        try {
            $qry = DB::table('gmp_product_details as t1')
                ->join('gmp_product_lines as t2', 't1.product_line_id', '=', 't2.id')
                ->join('gmp_product_categories as t3', 't1.category_id', '=', 't3.id')
                ->join('gmp_product_descriptions as t4', 't1.prodline_description_id', '=', 't4.id')
                ->leftJoin('gmp_productlinestatus as t5', 't1.prodline_status_id', '=', 't5.id')
                ->leftJoin('gmp_prodlinerecommenddesc as t6', 't1.prod_recommendation_id', '=', 't6.id')
                ->where('t1.manufacturing_site_id', $site_id)
                ->select('t1.*', 't2.name as product_line_name', 't3.name as product_line_category', 't4.name as product_line_description',
                    't5.name as product_line_status', 't6.name as product_line_recommendation');
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    public function getOnlineApplications(Request $request)
    {
        $section_id = $request->input('section_id');
        $sub_module_id = $request->input('sub_module_id');
        try {
            $portal_db = DB::connection('portal_db');
            //get process details
            $qry = $portal_db->table('wb_gmp_applications as t1')
                ->join('wb_manufacturing_sites as t2', 't1.manufacturing_site_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('wb_statuses as t4', 't1.application_status_id', '=', 't4.id')
                ->select('t1.*', 't1.id as active_application_id', 't1.application_code', 't2.name as premise_name',
                    't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't2.*', 't4.name as application_status', 't4.is_manager_query')
                ->whereIn('application_status_id', array(2, 13, 15, 17));
            if (isset($sub_module_id) && $sub_module_id != '') {
                $qry->where('t1.sub_module_id', $sub_module_id);
            }
            if (isset($section_id) && $section_id != '') {
                $qry->where('t1.section_id', $section_id);
            }
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getOnlineAppGmpPersonnelDetails(Request $request)
    {
        $site_id = $request->input('site_id');
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_manufacturing_sites_personnel as t1')
                ->where('manufacturing_site_id', $site_id);
            $results = $qry->get();
            foreach ($results as $key => $result) {
                $results[$key]->position_name = getSingleRecordColValue('par_personnel_positions', array('id' => $result->position_id), 'name');
            }
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getOnlineAppGmpOtherDetails(Request $request)
    {
        $site_id = $request->input('site_id');
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_mansite_otherdetails as t1')
                ->where('manufacturing_site_id', $site_id);
            $results = $qry->get();
            foreach ($results as $key => $result) {
                $results[$key]->business_type = getSingleRecordColValue('par_business_types', array('id' => $result->business_type_id), 'name');
                $results[$key]->business_type_detail = getSingleRecordColValue('par_business_type_details', array('id' => $result->business_type_detail_id), 'name');
            }
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getOnlineProductLineDetails(Request $request)
    {
        $site_id = $request->input('site_id');
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_gmp_product_details as t1')
                ->where('manufacturing_site_id', $site_id);
            $results = $qry->get();
            foreach ($results as $key => $result) {
                $results[$key]->product_line_name = getSingleRecordColValue('gmp_product_lines', array('id' => $result->product_line_id), 'name');
                $results[$key]->product_line_category = getSingleRecordColValue('gmp_product_categories', array('id' => $result->category_id), 'name');
                $results[$key]->product_line_description = getSingleRecordColValue('gmp_product_descriptions', array('id' => $result->prodline_description_id), 'name');
            }
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getGmpScheduleTeamDetails(Request $request)
    {
        $section_id = $request->input('section_id');
        $application_code = $request->input('application_code');
        try {
            $qry = DB::table('inspectionteam_details as t1')
                ->join('par_sections as t2', 't1.section_id', '=', 't2.id')
                ->select('t1.*', 't2.name as section_name', 't3.id as assigned_id')
                ->leftJoin('assigned_gmpinspections as t3', function ($join) use ($application_code) {
                    $join->on('t1.id', '=', 't3.inspection_id')
                        ->where('t3.application_code', '=', $application_code);
                });
            if (isset($section_id) && $section_id != '') {
                $qry->where('t1.section_id', $section_id);
            }
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function saveGmpScheduleInspectionTypes(Request $request)
    {
        $inspection_id = $request->input('inspection_id');
        $inspection_type = $request->input('inspection_type_id');
        $user_id = $this->user_id;
        try {
            $where_check = array(
                'inspection_id' => $inspection_id,
                'inspection_type_id' => $inspection_type
            );
            if (DB::table('gmpschedule_ispection_types')->where($where_check)->count() > 0) {
                $res = array(
                    'success' => false,
                    'message' => 'Inspection type added already!!'
                );
                return \response()->json($res);
            }
            $params = $where_check;
            $params['created_by'] = $user_id;
            $res = insertRecord('gmpschedule_ispection_types', $params, $user_id);
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getGmpScheduleInspectionTypes(Request $request)
    {
        $inspection_id = $request->input('inspection_id');
        try {
            $qry = DB::table('gmpschedule_ispection_types as t1')
                ->join('inspection_types as t2', 't1.inspection_type_id', '=', 't2.id')
                ->select('t1.*', 't2.name as inspection_type_name')
                ->where('t1.inspection_id', $inspection_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function saveGmpScheduleInspectors(Request $request)
    {
        $inspection_id = $request->input('inspection_id');
        $inspector_id = $request->input('inspector_id');
        $user_id = $this->user_id;
        try {
            $where_check = array(
                'inspection_id' => $inspection_id,
                'inspector_id' => $inspector_id
            );
            if (DB::table('gmp_inspectorsdetails')->where($where_check)->count() > 0) {
                $res = array(
                    'success' => false,
                    'message' => 'Inspector added already!!'
                );
                return \response()->json($res);
            }
            $params = $where_check;
            $params['created_by'] = $user_id;
            $res = insertRecord('gmp_inspectorsdetails', $params, $user_id);
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getGmpScheduleInspectors(Request $request)
    {
        $inspection_id = $request->input('inspection_id');
        try {
            $qry = DB::table('gmp_inspectorsdetails as t1')
                ->join('users as t2', 't1.inspector_id', '=', 't2.id')
                ->select(DB::raw("t1.*, CONCAT_WS(' ',decrypt(t2.first_name),decrypt(t2.last_name)) as inspector_name"))
                ->where('t1.inspection_id', $inspection_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getAssignedGmpInspections(Request $request)
    {
        $inspection_id = $request->input('inspection_id');
        try {
            $qry = DB::table('assigned_gmpinspections as t1')
                ->join('tra_gmp_applications as t2', 't1.application_code', '=', 't2.application_code')
                ->join('tra_manufacturing_sites as t3', 't2.manufacturing_site_id', '=', 't3.id')
                ->join('wb_trader_account as t4', 't2.applicant_id', '=', 't4.id')
                ->join('gmplocation_details as t5', 't2.gmp_type_id', '=', 't5.id')
                ->join('sub_modules as t6', 't2.sub_module_id', '=', 't6.id')
                ->select('t2.*', 't3.name as premise_name', 't4.name as applicant_name', 't5.name as gmp_type_txt',
                    't6.name as sub_module_name', 't1.id')
                ->where('t1.inspection_id', $inspection_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function addGmpApplicationsIntoInspectionSchedule(Request $request)//batch
    {
        //$inspection_id = $request->input('inspection_id');
        $details = $request->input();
        $inspection_id = $details['inspection_id'];
        unset($details['inspection_id']);
        try {
            /* DB::table('assigned_gmpinspections')
                 ->where('inspection_id', $inspection_id)
                 ->delete();*/
            DB::table('assigned_gmpinspections')
                ->insert($details);
            $res = array(
                'success' => true,
                'message' => 'Request executed successfully!!'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function addGmpApplicationIntoInspectionSchedule(Request $request)//single...application based
    {
        $application_code = $request->input('application_code');
        $inspection_id = $request->input('inspection_id');
        $user_id = $this->user_id;
        $params = array(
            'inspection_id' => $inspection_id,
            'application_id' => $request->input('application_id'),
            'application_code' => $application_code,
            'created_by' => $user_id
        );
        try {
            $qry = DB::table('assigned_gmpinspections')
                ->where('application_code', $application_code);
            $exists_qry = clone $qry;
            $count = $exists_qry->count();
            if ($count > 0) {
                $update_qry = clone $qry;
                $update_qry->update(array('inspection_id' => $inspection_id, 'altered_by' => $user_id));
            } else {
                DB::table('assigned_gmpinspections')
                    ->insert($params);
            }
            $res = array(
                'success' => true,
                'message' => 'Request executed successfully!!'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }
}
