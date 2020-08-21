<?php

namespace App\Modules\ClinicalTrial\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClinicalTrialController extends Controller
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
        return view('clinicaltrial::index');
    }

    public function saveClinicalTrialCommonData(Request $req)
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

    public function getClinicalTrialParamFromModel(Request $request)
    {
        $model_name = $request->input('model_name');
        $strict_mode = $request->input('strict_mode');
        try {
            $model = 'App\\Modules\\ClinicalTrial\\Entities\\' . $model_name;
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

    public function deleteClinicalTrialRecord(Request $req)
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

    public function softDeleteClinicalTrialRecord(Request $req)
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
            $res = softDeleteRecord($table_name, $previous_data, $where, $user_id);
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

    public function undoClinicalTrialSoftDeletes(Request $req)
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
            $res = undoSoftDeletes($table_name, $previous_data, $where, $user_id);
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

    public function getClinicalTrialApplications(Request $request)
    {
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $user_id = $this->user_id;
        $assigned_groups = getUserGroups($user_id);
        $is_super = belongsToSuperGroup($assigned_groups);
        try {
            $assigned_stages = getAssignedProcessStages($user_id, $module_id);
            $qry = DB::table('tra_clinical_trial_applications as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('wf_tfdaprocesses as t4', 't1.process_id', '=', 't4.id')
                ->join('wf_workflow_stages as t5', 't1.workflow_stage_id', '=', 't5.id')
                ->join('par_system_statuses as t6', 't1.application_status_id', '=', 't6.id')
                ->select(DB::raw("t1.id as active_application_id, t1.application_code, t4.module_id, t4.sub_module_id, t4.section_id,
                    t6.name as application_status, t3.name as applicant_name, t4.name as process_name, t5.name as workflow_stage, t5.is_general, t3.contact_person,
                    t3.tin_no, t3.country_id as app_country_id, t3.region_id as app_region_id, t3.district_id as app_district_id, t3.physical_address as app_physical_address,
                    t3.postal_address as app_postal_address, t3.telephone_no as app_telephone, t3.fax as app_fax, t3.email as app_email, t3.website as app_website,
                    t1.*"));
            $is_super ? $qry->whereRaw('1=1') : $qry->whereIn('t1.workflow_stage_id', $assigned_stages);
            if (isset($sub_module_id) && $sub_module_id != '') {
                $qry->where('t1.sub_module_id', $sub_module_id);
            }
            if (isset($workflow_stage_id) && $workflow_stage_id != '') {
                $qry->where('t1.workflow_stage_id', $workflow_stage_id);
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

    public function getClinicalTrialsList(Request $request)
    {
        $filter = $request->input('filter');
        $whereClauses = array();
        $filter_string = '';
        if (isset($filter)) {
            $filters = json_decode($filter);
            if ($filters != NULL) {
                foreach ($filters as $filter) {
                    switch ($filter->property) {
                        case 'reference_no' :
                            $whereClauses[] = "t2.reference_no like '%" . ($filter->value) . "%'";
                            break;
                        case 'permit_no' :
                            $whereClauses[] = "t6.permit_no like '%" . ($filter->value) . "%'";
                            break;
                        case 'applicant_name' :
                            $whereClauses[] = "t3.name like '%" . ($filter->value) . "%'";
                            break;
                        case 'study_title' :
                            $whereClauses[] = "t2.study_title like '%" . ($filter->value) . "%'";
                            break;
                        case 'protocol_no' :
                            $whereClauses[] = "t2.protocol_no=" . ($filter->value) . "";
                            break;
                        case 'version_no' :
                            $whereClauses[] = "t2.version_no=" . ($filter->value) . "";
                            break;
                        case 'sponsor' :
                            $whereClauses[] = "t4.name like '%" . ($filter->value) . "%'";
                            break;
                        case 'investigator' :
                            $whereClauses[] = "t5.name like '%" . ($filter->value) . "%'";
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
            $qry = DB::table('registered_clinical_trials as t1')
                ->join('tra_clinical_trial_applications as t2', 't1.tra_clinical_trial_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't2.applicant_id', '=', 't3.id')
                ->join('clinical_trial_personnel as t4', 't2.sponsor_id', '=', 't4.id')
                ->join('clinical_trial_personnel as t5', 't2.investigator_id', '=', 't5.id')
                ->join('tra_approval_recommendations as t6', 't2.permit_id', '=', 't6.id')
                ->select(DB::raw("t1.id as registered_id,t2.*,t2.id as previous_id,t6.permit_no,t3.name as applicant_name,t4.name as sponsor,t5.name as investigator,
                    t3.id as applicant_id, t3.name as applicant_name, t3.contact_person, t3.tin_no,
                    t3.country_id as app_country_id, t3.region_id as app_region_id, t3.district_id as app_district_id,
                    t3.physical_address as app_physical_address, t3.postal_address as app_postal_address,
                    t3.telephone_no as app_telephone,t3.fax as app_fax, t3.email as app_email, t3.website as app_website"));
            if ($filter_string != '') {
                $qry->whereRAW($filter_string);
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

    public function getStudySitesList(Request $request)
    {
        $study_site_id = $request->input('study_site_id');
        $application_id = $request->input('application_id');
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
            $qry = DB::table('study_sites as t1')
                ->join('par_countries as t2', 't1.country_id', '=', 't2.id')
                ->join('par_regions as t3', 't1.region_id', '=', 't3.id')
                ->leftJoin('clinical_trial_sites as t4', function ($join) use ($application_id) {
                    $join->on('t1.id', '=', 't4.study_site_id')
                        ->where('t4.application_id', '=', $application_id);
                })
                ->select('t1.*', 't2.name as country_name', 't3.name as region_name');
            if ($filter_string != '') {
                $qry->whereRAW($filter_string);
            }
            if (isset($study_site_id) && $study_site_id != '') {
                $qry->where('ta.id', $study_site_id);
            }
            if (isset($application_id) && $application_id != '') {
                $qry->whereNull('t4.id');
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

    public function saveNewReceivingBaseDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $applicant_id = $request->input('applicant_id');
        $process_id = $request->input('process_id');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $zone_id = $request->input('zone_id');
        $section_id = $request->input('section_id');
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            $applications_table = 'tra_clinical_trial_applications';

            $where_app = array(
                'id' => $application_id
            );
            if (isset($application_id) && $application_id != "") {//Edit
                //Application_edit
                $application_params = array(
                    'applicant_id' => $applicant_id,
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
                    $res = updateRecord($applications_table, $app_details, $where_app, $application_params, $user_id);
                    if ($res['success'] == false) {
                        return $res;
                    }
                }
                $application_code = $app_details[0]['application_code'];//$app_details->application_code;
                $ref_number = $app_details[0]['reference_no'];//$app_details->reference_no;
            } else {//Create
                //Application_create
                $zone_code = getSingleRecordColValue('par_zones', array('id' => $zone_id), 'zone_code');
                $section_code = getSingleRecordColValue('par_sections', array('id' => $section_id), 'code');
                $codes_array = array(
                    'section_code' => $section_code,
                    'zone_code' => $zone_code
                );
                $view_id = generateApplicationViewID();
                $ref_number = generatePremiseRefNumber(12, $codes_array, date('Y'), $process_id, $zone_id, $user_id);
                $application_code = generateApplicationCode($sub_module_id, $applications_table);
                $application_status = getApplicationInitialStatus($module_id, $sub_module_id);
                $application_params = array(
                    'applicant_id' => $applicant_id,
                    'view_id'=>$view_id,
                    'module_id' => $module_id,
                    'sub_module_id' => $sub_module_id,
                    'section_id' => $section_id,
                    'application_code' => $application_code,
                    'zone_id' => $zone_id,
                    'process_id' => $process_id,
                    'workflow_stage_id' => $workflow_stage_id,
                    'reference_no' => $ref_number,
                    'application_status_id' => $application_status->status_id
                );
                $res = insertRecord($applications_table, $application_params, $user_id);
                if ($res['success'] == false) {
                    DB::rollBack();
                    return \response()->json($res);
                }
                $application_id = $res['record_id'];

                //insert registration table
                $reg_params = array(
                    'tra_clinical_trial_id' => $application_id,
                    'registration_status' => 1,
                    'validity_status' => 1,
                    'created_by' => $user_id
                );
                createInitialRegistrationRecord('registered_clinical_trials', $applications_table, $reg_params, $application_id, 'reg_clinical_trial_id');
                //DMS
                initializeApplicationDMS($section_id, $module_id, $sub_module_id, $application_code, $ref_number, $user_id);
                //add to submissions table
                $submission_params = array(
                    'application_id' => $application_id,
                    'view_id'=>$view_id,
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
            DB::commit();
            $res['record_id'] = $application_id;
            $res['application_code'] = $application_code;
            $res['ref_no'] = $ref_number;
        } catch (\Exception $exception) {
            DB::rollBack();
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            DB::rollBack();
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function saveAmendmentReceivingBaseDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $previous_id = $request->input('previous_id');
        $applicant_id = $request->input('applicant_id');
        $process_id = $request->input('process_id');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $zone_id = $request->input('zone_id');
        $section_id = $request->input('section_id');
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            $applications_table = 'tra_clinical_trial_applications';

            $where_app = array(
                'id' => $application_id
            );
            if (isset($application_id) && $application_id != "") {//Edit
                //Application_edit
                $application_params = array(
                    'applicant_id' => $applicant_id,
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
                    $res = updateRecord($applications_table, $app_details, $where_app, $application_params, $user_id);
                    if ($res['success'] == false) {
                        return $res;
                    }
                }
                $application_code = $app_details[0]['application_code'];
                $ref_number = $app_details[0]['reference_no'];
            } else {//Create
                //Application_create
                //prev details
                $previous_details = DB::table($applications_table)
                    ->where('id', $previous_id)
                    ->first();
                if (is_null($previous_details)) {
                    $res = array(
                        'success' => false,
                        'message' => 'Problem encountered fetching previous application details!!'
                    );
                    return \response()->json($res);
                }
                $registered_id = $previous_details->reg_clinical_trial_id;
                $anyOngoingApps = checkForOngoingApplications($registered_id, $applications_table, 'reg_clinical_trial_id', $process_id);
                if ($anyOngoingApps['exists'] == true) {
                    $res = array(
                        'success' => false,
                        'message' => 'There is an ongoing application of the same nature on the selected Application with reference number ' . $anyOngoingApps['ref_no']
                    );
                    return \response()->json($res);
                }
                $initial_ref_no = DB::table($applications_table)
                    ->where('reg_clinical_trial_id', $registered_id)
                    ->where('sub_module_id', 10)
                    ->value('reference_no');
                $alt_counter = DB::table($applications_table)
                    ->where('reg_clinical_trial_id', $registered_id)
                    ->where('sub_module_id', 11)
                    ->count();
                $alt_count = $alt_counter + 1;
                $codes_array = array(
                    'prev_refno' => $initial_ref_no,
                    'alt_count' => $alt_count
                );
                $view_id = generateApplicationViewID();
                $ref_number = generatePremiseRefNumber(14, $codes_array, date('Y'), $process_id, $zone_id, $user_id);
                $application_code = generateApplicationCode($sub_module_id, $applications_table);
                $application_status = getApplicationInitialStatus($module_id, $sub_module_id);

                $application_params = convertStdClassObjToArray($previous_details);
                $application_params['sub_module_id'] = $sub_module_id;
                $application_params['application_code'] = $application_code;
                $application_params['zone_id'] = $zone_id;
                $application_params['process_id'] = $process_id;
                $application_params['workflow_stage_id'] = $workflow_stage_id;
                $application_params['reference_no'] = $ref_number;
                $application_params['view_id'] = $view_id;
                $application_params['application_status_id'] = $application_status->status_id;
                $application_params['permit_id'] = null;
                unset($application_params['id']);

                $res = insertRecord($applications_table, $application_params, $user_id);
                if ($res['success'] == false) {
                    DB::rollBack();
                    return \response()->json($res);
                }
                $application_id = $res['record_id'];
                //prev study sites
                $prev_sites = DB::table('clinical_trial_sites as t1')
                    ->select(DB::raw("t1.*,$application_id as application_id"))
                    ->where('application_id', $previous_id)
                    ->get();
                $prev_sites = convertStdClassObjToArray($prev_sites);
                $prev_sites = unsetPrimaryIDsInArray($prev_sites);
                //prev other investigators
                $prev_investigators = DB::table('clinical_trial_investigators as t1')
                    ->select(DB::raw("t1.*,$application_id as application_id"))
                    ->where('application_id', $previous_id)
                    ->get();
                $prev_investigators = convertStdClassObjToArray($prev_investigators);
                $prev_investigators = unsetPrimaryIDsInArray($prev_investigators);
                //prev Imp Products
                $prev_products = DB::table('clinical_trial_products as t2')
                    ->select(DB::raw("t2.*,$application_id as application_id"))
                    ->where('application_id', $previous_id)
                    ->get();
                $prev_products = convertStdClassObjToArray($prev_products);
                $prev_products = unsetPrimaryIDsInArray($prev_products);

                DB::table('clinical_trial_sites')->insert($prev_sites);
                DB::table('clinical_trial_investigators')->insert($prev_investigators);
                DB::table('clinical_trial_products')->insert($prev_products);
                //DMS
                initializeApplicationDMS($section_id, $module_id, $sub_module_id, $application_code, $ref_number, $user_id);
                //add to submissions table
                $submission_params = array(
                    'application_id' => $application_id,
                    'view_id'=>$view_id,
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
            DB::commit();
            $res['record_id'] = $application_id;
            $res['application_code'] = $application_code;
            $res['ref_no'] = $ref_number;
        } catch (\Exception $exception) {
            DB::rollBack();
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            DB::rollBack();
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function saveNewApplicationClinicalTrialDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $study_title = $request->input('study_title');
        $protocol_no = $request->input('protocol_no');
        $version_no = $request->input('version_no');
        $date_of_protocol = $request->input('date_of_protocol');
        $sponsor_id = $request->input('sponsor_id');
        $investigator_id = $request->input('investigator_id');
        $study_start_date = $request->input('study_start_date');
        $study_duration = $request->input('study_duration');
        $duration_desc = $request->input('duration_desc');
        $clearance_no = $request->input('clearance_no');
        $user_id = $this->user_id;
        $table_name = 'tra_clinical_trial_applications';
        $where = array(
            'id' => $application_id
        );
        try {
            $params = array(
                'study_title' => $study_title,
                'protocol_no' => $protocol_no,
                'version_no' => $version_no,
                'date_of_protocol' => $date_of_protocol,
                'sponsor_id' => $sponsor_id,
                'investigator_id' => $investigator_id,
                'study_duration' => $study_duration,
                'study_start_date' => $study_start_date,
                'duration_desc' => $duration_desc,
                'clearance_no' => $clearance_no,
                'altered_by' => $user_id
            );
            $prev_data = getPreviousRecords($table_name, $where);
            if ($prev_data['success'] == false) {
                return $prev_data;
            }
            $prev_data = $prev_data['results'];
            $res = updateRecord($table_name, $prev_data, $where, $params, $user_id);
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

    public function saveNewApplicationClinicalTrialOtherDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $study_duration = $request->input('study_duration');
        $duration_desc = $request->input('duration_desc');
        $clearance_no = $request->input('clearance_no');
        $user_id = $this->user_id;
        $table_name = 'tra_clinical_trial_applications';
        $where = array(
            'id' => $application_id
        );
        try {
            $params = array(
                'study_duration' => $study_duration,
                'duration_desc' => $duration_desc,
                'clearance_no' => $clearance_no,
                'altered_by' => $user_id
            );
            $prev_data = getPreviousRecords($table_name, $where);
            if ($prev_data['success'] == false) {
                return $prev_data;
            }
            $prev_data = $prev_data['results'];
            $res = updateRecord($table_name, $prev_data, $where, $params, $user_id);
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

    public function prepareOnlineClinicalTrialPreview(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_clinical_trial_applications as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->select('t1.*', 't1.id as active_application_id',
                    't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website')
                ->where('t1.id', $application_id);
            $results = $qry->first();

            $sponsorQry = DB::table('clinical_trial_personnel')
                ->where('id', $results->sponsor_id);
            $sponsorDetails = $sponsorQry->first();

            $investigatorQry = DB::table('clinical_trial_personnel')
                ->where('id', $results->investigator_id);
            $investigatorDetails = $investigatorQry->first();

            $res = array(
                'success' => true,
                'results' => $results,
                'sponsorDetails' => $sponsorDetails,
                'investigatorDetails' => $investigatorDetails,
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

    public function prepareNewClinicalTrialReceivingStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table('tra_clinical_trial_applications as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->leftJoin('tra_application_invoices as t4', function ($join) use ($application_code) {
                    $join->on('t1.id', '=', 't4.application_id')
                        ->on('t4.application_code', '=', 't4.application_code');
                })
                //->leftJoin('tra_approval_recommendations as t5', 't2.permit_id', '=', 't5.id')
                ->select('t1.*', 't1.id as active_application_id',
                    't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't4.id as invoice_id', 't4.invoice_no')
                ->where('t1.id', $application_id);
            $results = $qry->first();

            $sponsorQry = DB::table('tra_clinical_trial_applications as t1')
                ->join('clinical_trial_personnel as t2', 't1.sponsor_id', '=', 't2.id')
                ->select('t2.*')
                ->where('t1.id', $application_id);
            $sponsorDetails = $sponsorQry->first();

            $investigatorQry = DB::table('tra_clinical_trial_applications as t1')
                ->join('clinical_trial_personnel as t2', 't1.investigator_id', '=', 't2.id')
                ->select('t2.*')
                ->where('t1.id', $application_id);
            $investigatorDetails = $investigatorQry->first();

            $res = array(
                'success' => true,
                'results' => $results,
                'sponsorDetails' => $sponsorDetails,
                'investigatorDetails' => $investigatorDetails,
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

    public function prepareNewClinicalTrialInvoicingStage(Request $request)
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
                ->select(DB::raw("t1.applicant_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details, t3.id as invoice_id, t3.invoice_no,
                t1.section_id,t1.module_id,t1.is_fast_track,
                CONCAT(t1.study_title,'(',CONCAT_WS(',',CONCAT('Protocol No:',t1.protocol_no),CONCAT('Version No:',t1.version_no)),')') as trial_details"))
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

    public function prepareNewClinicalTrialPaymentStage(Request $request)
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
                ->select(DB::raw("t1.applicant_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details, t3.id as invoice_id, t3.invoice_no,
                t1.section_id,t1.module_id,
                CONCAT(t1.study_title,'(',CONCAT_WS(',',CONCAT('Protocol No:',t1.protocol_no),CONCAT('Version No:',t1.version_no)),')') as trial_details"))
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

    public function prepareNewClinicalTrialAssessmentStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t2', 't1.applicant_id', '=', 't2.id')
                ->select(DB::raw("t1.applicant_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details,
                t1.section_id,t1.module_id,
                CONCAT(t1.study_title,'(',CONCAT_WS(',',CONCAT('Protocol No:',t1.protocol_no),CONCAT('Version No:',t1.version_no)),')') as trial_details"))
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

    public function prepareNewClinicalTrialManagerMeetingStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('tc_meeting_applications as t2', function ($join) use ($application_code) {
                    $join->on('t1.application_code', '=', 't2.application_code');
                })
                ->join('tc_meeting_details as t3', 't2.meeting_id', '=', 't3.id')
                ->select(DB::raw("t3.*"))
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

    public function addClinicalStudySite(Request $request)
    {
        $application_id = $request->input('application_id');
        $site_id = $request->input('site_id');
        $user_id = $this->user_id;
        try {
            $params = array(
                'study_site_id' => $site_id,
                'application_id' => $application_id,
                'created_by' => $user_id
            );
            $res = insertRecord('clinical_trial_sites', $params, $user_id);
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

    public function getClinicalStudySites(Request $request)
    {
        $application_id = $request->input('application_id');
        try {
            $qry = DB::table('clinical_trial_sites as t1')
                ->join('study_sites as t2', 't1.study_site_id', '=', 't2.id')
                ->join('par_countries as t3', 't2.country_id', '=', 't3.id')
                ->join('par_regions as t4', 't2.region_id', '=', 't4.id')
                ->select('t2.*', 't1.id', 't1.study_site_id', 't3.name as country_name', 't4.name as region_name')
                ->where('t1.application_id', $application_id);
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

    public function getOnlineClinicalStudySites(Request $request)
    {
        $application_id = $request->input('application_id');
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_clinical_trial_sites as t1')
                ->where('t1.application_id', $application_id);
            $results = $qry->get();
            foreach ($results as $key => $result) {
                $site_details = getTableData('study_sites', array('id' => $result->study_site_id));
                $results[$key]->country_name = getSingleRecordColValue('par_countries', array('id' => $site_details->country_id), 'name');
                $results[$key]->region_name = getSingleRecordColValue('par_regions', array('id' => $site_details->region_id), 'name');
                $results[$key]->name = $site_details->name;
                $results[$key]->physical_address = $site_details->physical_address;
                $results[$key]->postal_address = $site_details->postal_address;
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

    public function getClinicalTrialPersonnelList(Request $request)
    {
        $application_id = $request->input('application_id');
        $personnel_type = $request->input('personnel_type');
        try {
            $qry = DB::table('clinical_trial_personnel as t1')
                ->join('par_countries as t2', 't1.country_id', '=', 't2.id')
                ->join('par_regions as t3', 't1.region_id', '=', 't3.id')
                ->select('t1.*', 't2.name as country_name', 't3.name as region_name');
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

    public function getClinicalTrialOtherInvestigators(Request $request)
    {
        $application_id = $request->input('application_id');
        try {
            $qry = DB::table('clinical_trial_investigators as t1')
                ->join('clinical_trial_personnel as t2', 't1.investigator_id', '=', 't2.id')
                ->join('par_countries as t3', 't2.country_id', '=', 't3.id')
                ->join('par_regions as t4', 't2.region_id', '=', 't4.id')
                ->join('clinical_investigator_cat as t5', 't1.category_id', '=', 't5.id')
                ->join('study_sites as t6', 't1.study_site_id', '=', 't6.id')
                ->select('t2.*', 't1.*', 't3.name as country_name', 't4.name as region_name',
                    't5.category_name as category', 't6.name as study_site');
            //if (isset($application_id) && $application_id != '') {
                $qry->where('t1.application_id', $application_id);
            //}
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

    public function getOnlineClinicalTrialOtherInvestigators(Request $request)
    {
        $application_id = $request->input('application_id');
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_clinical_trial_investigators as t1')
                ->where('t1.application_id', $application_id);
            $results = $qry->get();
            foreach ($results as $key => $result) {
                $personnel_details = getTableData('clinical_trial_personnel', array('id' => $result->investigator_id));
                $results[$key]->name = $personnel_details->name;
                $results[$key]->contact_person = $personnel_details->contact_person;
                $results[$key]->tin_no = $personnel_details->tin_no;
                $results[$key]->telephone = $personnel_details->telephone;
                $results[$key]->physical_address = $personnel_details->physical_address;
                $results[$key]->postal_address = $personnel_details->postal_address;
                $results[$key]->country_name = getSingleRecordColValue('par_countries', array('id' => $personnel_details->country_id), 'name');
                $results[$key]->region_name = getSingleRecordColValue('par_regions', array('id' => $personnel_details->region_id), 'name');
                $results[$key]->category = getSingleRecordColValue('clinical_investigator_cat', array('id' => $result->category_id), 'category_name');
                $results[$key]->study_site = getSingleRecordColValue('study_sites', array('id' => $result->study_site_id), 'name');
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

    public function addApplicationOtherInvestigators(Request $request)
    {
        $application_id = $request->input('application_id');
        $category_id = $request->input('category_id');
        $study_site_id = $request->input('study_site_id');
        $investigator_id = $request->input('personnel_id');
        $user_id = $this->user_id;
        $table_name = 'clinical_trial_investigators';
        try {
            $params = array(
                'category_id' => $category_id,
                'investigator_id' => $investigator_id,
                'application_id' => $application_id,
                'study_site_id' => $study_site_id,
                'created_by' => $user_id
            );
            $where = array(
                'category_id' => $category_id,
                'investigator_id' => $investigator_id,
                'application_id' => $application_id,
                'study_site_id' => $study_site_id
            );
            if (DB::table($table_name)
                    ->where($where)
                    ->count() > 0) {
                $res = array(
                    'success' => false,
                    'message' => 'The combination has been added!!'
                );
                return \response()->json($res);
            }
            $res = insertRecord($table_name, $params, $user_id);
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

    public function getImpProducts(Request $request)
    {
        $application_id = $request->input('application_id');
        try {
            $qry = DB::table('clinical_trial_products as t1')
                ->join('clinical_product_categories as t2', 't1.product_category_id', '=', 't2.id')
                ->join('par_common_names as t3', 't1.common_name_id', '=', 't3.id')
                ->join('pharmaceutical_dosage_forms as t4', 't1.dosage_form_id', '=', 't4.id')
                ->join('routes_of_administration as t5', 't1.routes_of_admin_id', '=', 't5.id')
                ->join('si_units as t6', 't1.si_unit_id', '=', 't6.id')
                ->join('par_countries as t7', 't1.country_id', '=', 't7.id')
                ->join('imp_sources as t8', 't1.market_location_id', '=', 't8.id')
                ->select(DB::raw('t1.*,CONCAT(t1.product_strength,t6.name) as product_strength_txt,t2.category_name,t3.name as common_name,t4.name as dosage_form,t5.name as admin_route,t8.name as market_location'))
                ->where('t1.application_id', $application_id);
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

    public function getOnlineImpProducts(Request $request)
    {
        $application_id = $request->input('application_id');
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_clinical_trial_products as t1')
                ->where('t1.application_id', $application_id);
            $results = $qry->get();
            foreach ($results as $key => $result) {
                $results[$key]->product_strength_txt = $result->product_strength . getSingleRecordColValue('si_units', array('id' => $result->si_unit_id), 'name');
                $results[$key]->category_name = getSingleRecordColValue('clinical_product_categories', array('id' => $result->product_category_id), 'category_name');
                $results[$key]->common_name = getSingleRecordColValue('par_common_names', array('id' => $result->common_name_id), 'name');
                $results[$key]->dosage_form = getSingleRecordColValue('pharmaceutical_dosage_forms', array('id' => $result->dosage_form_id), 'name');
                $results[$key]->admin_route = getSingleRecordColValue('routes_of_administration', array('id' => $result->routes_of_admin_id), 'name');
                $results[$key]->market_location = getSingleRecordColValue('imp_sources', array('id' => $result->market_location_id), 'name');
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

    public function getImpProductIngredients(Request $request)
    {
        $product_id = $request->input('product_id');
        try {
            $qry = DB::table('impproduct_ingredients as t1')
                ->join('master_ingredients as t2', 't1.ingredient_id', '=', 't2.id')
                ->join('product_specifications as t3', 't1.specification_id', '=', 't3.id')
                ->join('si_units as t4', 't1.si_unit_id', '=', 't4.id')
                ->join('inclusion_reason as t5', 't1.inclusion_reason_id', '=', 't5.id')
                ->select(DB::raw("t1.*,t2.name as ingredient,t3.name as specification,t5.name as inclusion_reason,
                CONCAT(t1.strength,t4.name) as strength_txt"))
                ->where('t1.product_id', $product_id);
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

    public function getOnlineImpProductIngredients(Request $request)
    {
        $product_id = $request->input('product_id');
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_impproduct_ingredients as t1')
                ->where('t1.product_id', $product_id);
            $results = $qry->get();
            foreach ($results as $key => $result) {
                $results[$key]->ingredient = getSingleRecordColValue('master_ingredients', array('id' => $result->ingredient_id), 'name');
                $results[$key]->specification = getSingleRecordColValue('product_specifications', array('id' => $result->specification_id), 'name');;
                $results[$key]->inclusion_reason = getSingleRecordColValue('inclusion_reason', array('id' => $result->inclusion_reason_id), 'name');;
                $results[$key]->strength_txt = $result->strength . getSingleRecordColValue('si_units', array('id' => $result->si_unit_id), 'name');;
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

    public function getClinicalTrialManagerApplicationsGeneric(Request $request)
    {
        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        $meeting_id = $request->input('meeting_id');
        $strict_mode = $request->input('strict_mode');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', function ($join) {
                    $join->on('t1.application_status_id', '=', 't4.id');
                })
                ->leftJoin('tra_approval_recommendations as t5', function ($join) {
                    $join->on('t1.id', '=', 't5.application_id')
                        ->on('t1.application_code', '=', 't5.application_code');
                })
                ->leftJoin('par_approval_decisions as t6', 't5.decision_id', '=', 't6.id')
                ->join('clinical_trial_personnel as t7', 't1.sponsor_id', '=', 't7.id')
                ->join('clinical_trial_personnel as t8', 't1.investigator_id', '=', 't8.id');
            if (isset($strict_mode) && $strict_mode == 1) {
                $qry->join('tc_meeting_applications as t9', function ($join) use ($meeting_id) {
                    $join->on('t1.application_code', '=', 't9.application_code')
                        ->where('t9.meeting_id', $meeting_id);
                });
            } else {
                $qry->leftJoin('tc_meeting_applications as t9', function ($join) use ($meeting_id) {
                    $join->on('t1.application_code', '=', 't9.application_code')
                        ->where('t9.meeting_id', $meeting_id);
                });
            }
            $qry->select('t1.*', 't3.name as applicant_name', 't4.name as application_status',
                't6.name as approval_status', 't5.decision_id', 't1.id as active_application_id',
                't7.name as sponsor', 't8.name as investigator')
                ->where('t1.workflow_stage_id', $workflow_stage);
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

    public function getClinicalTrialManagerMeetingApplications(Request $request)
    {
        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        $meeting_id = $request->input('meeting_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', 't1.application_status_id', '=', 't4.id')
                ->join('clinical_trial_personnel as t7', 't1.sponsor_id', '=', 't7.id')
                ->join('clinical_trial_personnel as t8', 't1.investigator_id', '=', 't8.id')
                ->leftJoin('tc_meeting_applications as t9', function ($join) use ($meeting_id) {
                    $join->on('t1.application_code', '=', 't9.application_code')
                        ->where('t9.meeting_id', $meeting_id);
                })
                ->join('tra_assessment_recommendations as t10', 't1.application_code', '=', 't10.application_code')
                ->join('tra_auditing_recommendations as t11', 't1.application_code', '=', 't11.application_code')
                ->join('wf_workflow_actions as t12', 't10.recommendation_id', '=', 't12.id')
                ->join('wf_workflow_actions as t13', 't11.recommendation_id', '=', 't13.id')
                ->select('t1.*', 't3.name as applicant_name', 't4.name as application_status',
                    't9.meeting_id', 't1.id as active_application_id', 't7.name as sponsor', 't8.name as investigator',
                    't12.name as assessment_recomm', 't13.name as audit_recomm')
                ->where('t1.workflow_stage_id', $workflow_stage);
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

    public function getClinicalTrialRecommReviewApplications(Request $request)
    {
        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        $meeting_id = $request->input('meeting_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', 't1.application_status_id', '=', 't4.id')
                ->join('tc_meeting_applications as t9', function ($join) use ($meeting_id) {
                    $join->on('t1.application_code', '=', 't9.application_code')
                        ->where('t9.meeting_id', $meeting_id);
                })
                ->join('tra_assessment_recommendations as t10', 't1.application_code', '=', 't10.application_code')
                ->join('tra_auditing_recommendations as t11', 't1.application_code', '=', 't11.application_code')
                ->join('wf_workflow_actions as t12', 't10.recommendation_id', '=', 't12.id')
                ->join('wf_workflow_actions as t13', 't11.recommendation_id', '=', 't13.id')
                ->leftJoin('tc_recommendations as t14', 't1.application_code', '=', 't14.application_code')
                ->leftJoin('par_tcmeeting_decisions as t15', 't14.decision_id', '=', 't15.id')
                ->select('t1.*', 't3.name as applicant_name', 't4.name as application_status',
                    't9.meeting_id', 't1.id as active_application_id', 't12.name as assessment_recomm', 't13.name as audit_recomm',
                    't15.name as tc_recomm', 't14.decision_id', 't14.id as recomm_id', 't14.comments')
                ->where('t1.workflow_stage_id', $workflow_stage);
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

    public function getClinicalTrialApplicationsAtApproval(Request $request)
    {
        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        $meeting_id = $request->input('meeting_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', 't1.application_status_id', '=', 't4.id')
                ->leftJoin('tra_approval_recommendations as t5', function ($join) {
                    $join->on('t1.id', '=', 't5.application_id')
                        ->on('t1.application_code', '=', 't5.application_code');
                })
                ->leftJoin('par_approval_decisions as t6', 't5.decision_id', '=', 't6.id')
                ->join('wf_tfdaprocesses as t7', 't1.process_id', '=', 't7.id')
                ->join('wf_workflow_stages as t8', 't1.workflow_stage_id', '=', 't8.id')
                ->join('tc_recommendations as t14', 't1.application_code', '=', 't14.application_code')
                ->join('par_tcmeeting_decisions as t15', 't14.decision_id', '=', 't15.id')
                ->join('tc_meeting_applications as t9', function ($join) use ($meeting_id) {
                    $join->on('t1.application_code', '=', 't9.application_code')
                        ->where('t9.meeting_id', $meeting_id);
                })
                ->select('t1.*', 't1.id as active_application_id', 't3.name as applicant_name', 't4.name as application_status', 't6.name as approval_status',
                    't7.name as process_name', 't8.name as workflow_stage', 't8.is_general', 't5.id as recommendation_id', 't6.name as recommendation',
                    't15.name as tc_recomm', 't14.decision_id', 't14.id as recomm_id', 't14.comments')
                ->where('t1.workflow_stage_id', $workflow_stage);
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

    public function saveTCMeetingDetails(Request $request)
    {
        $id = $request->input('id');
        $application_code = $request->input('application_code');
        $meeting_name = $request->input('meeting_name');
        $meeting_desc = $request->input('meeting_desc');
        $date_requested = $request->input('date_requested');
        $selected = $request->input('selected');
        $selected_codes = json_decode($selected);
        $user_id = $this->user_id;
        try {
            $params = array(
                'meeting_name' => $meeting_name,
                'meeting_desc' => $meeting_desc,
                'date_requested' => $date_requested
            );
            if (isset($id) && $id != '') {
                $params['altered_by'] = $user_id;
                DB::table('tc_meeting_details')
                    ->where('id', $id)
                    ->update($params);
            } else {
                $params['created_by'] = $user_id;
                $insert_res = insertRecord('tc_meeting_details', $params, $user_id);
                $id = $insert_res['record_id'];
                $app_meeting = array(
                    'application_code' => $application_code,
                    'meeting_id' => $id,
                    'created_by' => $user_id
                );
                insertRecord('tc_meeting_applications', $app_meeting, $user_id);
            }
            $params2 = array();
            foreach ($selected_codes as $selected_code) {
                $params2[] = array(
                    'meeting_id' => $id,
                    'application_code' => $selected_code,
                    'created_by' => $this->user_id
                );
            }
            DB::table('tc_meeting_applications')
                ->where('meeting_id', $id)
                ->delete();
            DB::table('tc_meeting_applications')
                ->insert($params2);
            $res = array(
                'success' => true,
                'record_id' => $id,
                'message' => 'Details saved successfully!!'
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

    public function syncTcMeetingParticipants(Request $request)
    {
        $selected = $request->input('selected');
        $meeting_id = $request->input('meeting_id');
        $selected_users = json_decode($selected);
        $where = array(
            'meeting_id' => $meeting_id
        );
        try {
            DB::transaction(function () use ($selected_users, $meeting_id, $where) {
                $params = array();
                foreach ($selected_users as $selected_user) {
                    $check = array(
                        'user_id' => $selected_user->user_id,
                        'meeting_id' => $meeting_id
                    );
                    if (DB::table('tc_meeting_participants')
                            ->where($check)->count() == 0) {
                        $params[] = array(
                            'meeting_id' => $meeting_id,
                            'user_id' => $selected_user->user_id,
                            'participant_name' => $selected_user->participant_name,
                            'phone' => $selected_user->phone,
                            'email' => $selected_user->email,
                            'created_by' => $this->user_id
                        );
                    }
                }
                DB::table('tc_meeting_participants')
                    ->insert($params);
            }, 5);
            $res = array(
                'success' => true,
                'message' => 'Participants saved successfully!!'
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

    public function getTcMeetingParticipants(Request $request)
    {
        $meeting_id = $request->input('meeting_id');
        try {
            $qry = DB::table('tc_meeting_participants as t1')
                ->select('t1.*')
                ->where('t1.meeting_id', $meeting_id);
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

    public function getExternalAssessorDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        try {
            $qry = DB::table('clinical_external_assessors')
                ->where('application_id', $application_id);
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

    public function getTcMeetingDetails(Request $request)
    {
        $application_code = $request->input('application_code');
        try {
            $qry = DB::table('tc_meeting_applications as t1')
                ->join('tc_meeting_details as t2', 't1.meeting_id', '=', 't2.id')
                ->select('t2.*')
                ->where('t1.application_code', $application_code);
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

    public function getClinicalTrialApplicationMoreDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $sharedQry = DB::table('tra_clinical_trial_applications as t1')
                ->where('t1.id', $application_id);

            $applicantQry = clone $sharedQry;
            $applicantQry->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->select('t3.name as applicant_name', 't3.contact_person', 't1.applicant_id',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website');
            $applicantDetails = $applicantQry->first();

            $appQry = clone $sharedQry;
            $appDetails = $appQry->first();

            $sponsorQry = clone $sharedQry;
            $sponsorQry->join('clinical_trial_personnel as t2', 't1.sponsor_id', '=', 't2.id')
                ->select('t2.*');
            $sponsorDetails = $sponsorQry->first();

            $investigatorQry = clone $sharedQry;
            $investigatorQry->join('clinical_trial_personnel as t2', 't1.investigator_id', '=', 't2.id')
                ->select('t2.*');
            $investigatorDetails = $investigatorQry->first();

            $res = array(
                'success' => true,
                'app_details' => $appDetails,
                'applicant_details' => $applicantDetails,
                'sponsor_details' => $sponsorDetails,
                'investigator_details' => $investigatorDetails,
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

    public function getOnlineApplications(Request $request)
    {
        $section_id = $request->input('section_id');
        $sub_module_id = $request->input('sub_module_id');
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_clinical_trial_applications as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('wb_statuses as t4', 't1.application_status_id', '=', 't4.id')
                ->select('t1.*', 't1.id as active_application_id', 't1.application_code',
                    't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't4.name as application_status', 't4.is_manager_query')
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

}
