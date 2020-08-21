<?php

namespace App\Modules\Surveillance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SurveillanceController extends Controller
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
        return view('surveillance::index');
    }

    public function saveSurveillanceCommonData(Request $req)
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

    public function deleteSurveillanceRecord(Request $req)
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

    public function savePmsProgramRegions(Request $request)
    {
        $program_id = $request->input('program_id');
        $region_id = $request->input('region_id');
        $user_id = $this->user_id;
        $params = array(
            'program_id' => $program_id,
            'region_id' => $region_id
        );
        try {
            if (DB::table('pms_program_regions')->where($params)->count() > 0) {
                $res = array(
                    'success' => false,
                    'message' => 'Region added already!!'
                );
            } else {
                $params['created_on'] = Carbon::now();
                $params['created_by'] = $user_id;
                $res = insertRecord('pms_program_regions', $params, $user_id);
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

    public function savePmsProgramProducts(Request $request)
    {
        $program_id = $request->input('program_id');
        $product_id = $request->input('product_id');
        $user_id = $this->user_id;
        $params = array(
            'program_id' => $program_id,
            'product_id' => $product_id
        );
        try {
            if (DB::table('pms_program_products')->where($params)->count() > 0) {
                $res = array(
                    'success' => false,
                    'message' => 'Product added already!!'
                );
            } else {
                $params['created_on'] = Carbon::now();
                $params['created_by'] = $user_id;
                $res = insertRecord('pms_program_products', $params, $user_id);
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

    public function getPmsProgramRegions(Request $request)
    {
        $program_id = $request->input('program_id');
        try {
            $qry = DB::table('pms_program_regions as t1')
                ->join('par_regions as t2', 't1.region_id', '=', 't2.id')
                ->select('t1.*', 't2.name as region_name')
                ->where('t1.program_id', $program_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'Records fetched successfully'
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

    public function getPmsProgramProducts(Request $request)
    {
        $program_id = $request->input('program_id');
        try {
            $qry = DB::table('pms_program_products as t1')
                ->join('par_common_names as t2', 't1.product_id', '=', 't2.id')
                ->select('t1.*', 't2.name as product_name')
                ->where('t1.program_id', $program_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'Records fetched successfully'
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

    public function getPmsPrograms(Request $request)
    {
        $section_id = $request->input('section_id');
        try {
            $qry = DB::table('pms_program_details as t1')
                ->join('par_sections as t2', 't1.section_id', '=', 't2.id')
                ->select('t1.*', 't2.name as section_name', 't1.id as pms_program_id');
            if (isset($section_id) && is_numeric($section_id)) {
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
        return response()->json($res);
    }

    public function getPmsProgramPlans(Request $request)
    {
        $program_id = $request->input('program_id');
        try {
            $qry = DB::table('pms_program_plans as t1')
                ->join('par_business_types as t2', 't1.sampling_site_id', '=', 't2.id')
                ->join('par_common_names as t3', 't1.product_id', '=', 't3.id')
                ->join('pms_product_categories as t4', 't1.product_category_id', '=', 't4.id')
                ->join('pharmaceutical_dosage_forms as t5', 't1.dosage_form_id', '=', 't5.id')
                ->join('si_units as t6', 't1.si_unit_id', '=', 't6.id')
                ->join('par_containers as t7', 't1.container_id', '=', 't7.id')
                ->join('par_packaging_units as t8', 't1.packaging_unit_id', '=', 't8.id')
                ->select(DB::raw("t1.*,t2.name as sampling_site,t3.name as product,t4.category_name,t5.name as dosage_form,
                CONCAT_WS(' of ',t7.name,CONCAT(t1.unit_pack,t8.name)) as pack,CONCAT(t1.strength,t6.name) as strength_txt,
                (t1.number_of_brand*t1.number_of_batch*t1.number_of_unitpack) as total_samples,t1.id as pms_plan_id"))
                ->where('t1.id', $program_id);
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

    public function getSurveillanceApplications(Request $request)
    {
        $module_id = $request->input('module_id');
        $section_id = $request->input('section_id');
        $sub_module_id = $request->input('sub_module_id');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $user_id = $this->user_id;
        $assigned_groups = getUserGroups($user_id);
        $is_super = belongsToSuperGroup($assigned_groups);
        try {
            $assigned_stages = getAssignedProcessStages($user_id, $module_id);
            $qry = DB::table('tra_surveillance_applications as t1')
                ->join('tra_samplecollection_sites as t2', 't1.sample_site_id', '=', 't2.id')
                ->join('par_zones as t3', 't1.zone_id', '=', 't3.id')
                ->join('wf_tfdaprocesses as t4', 't1.process_id', '=', 't4.id')
                ->join('wf_workflow_stages as t5', 't1.workflow_stage_id', '=', 't5.id')
                ->join('par_system_statuses as t6', 't1.application_status_id', '=', 't6.id')
                ->join('par_directorates as t7', 't1.directorate_id', '=', 't7.id')
                ->select(DB::raw("t1.id as active_application_id, t1.application_code, t4.module_id, t4.sub_module_id, t4.section_id, t2.name as premise_name,
                    t6.name as application_status, t3.name as zone, t7.name as directorate, t4.name as process_name, t5.name as workflow_stage, t5.is_general,
                    t2.*, t1.*,t2.name as sample_site"));

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
        try {
            $qry = DB::table('tra_surveillance_applications as t1')
                ->join('tra_samplecollection_sites as t2', 't1.sample_site_id', '=', 't2.id')
                ->join('par_zones as t3', 't1.zone_id', '=', 't3.id')
                ->join('wf_tfdaprocesses as t4', 't1.process_id', '=', 't4.id')
                ->join('wf_workflow_stages as t5', 't1.workflow_stage_id', '=', 't5.id')
                ->join('par_system_statuses as t6', 't1.application_status_id', '=', 't6.id')
                ->join('par_directorates as t7', 't1.directorate_id', '=', 't7.id')
                ->leftJoin('tra_approval_recommendations as t8', function ($join) {
                    $join->on('t1.id', '=', 't8.application_id')
                        ->on('t1.application_code', '=', 't8.application_code');
                })
                ->leftJoin('par_approval_decisions as t9', 't8.decision_id', '=', 't9.id')
                ->select(DB::raw("t1.id as active_application_id, t1.application_code, t4.module_id, t4.sub_module_id, t4.section_id, t2.name as premise_name,
                    t6.name as application_status, t3.name as zone, t7.name as directorate, t4.name as process_name, t5.name as workflow_stage, t5.is_general,
                    t2.*, t1.*,t2.name as sample_site,t9.name as approval_status, t8.decision_id"))
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

    public function saveNewReceivingBaseDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $sample_site_id = $request->input('sample_site_id');
        $process_id = $request->input('process_id');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $zone_id = $request->input('zone_id');
        $directorate_id = $request->input('directorate_id');
        $section_id = $request->input('section_id');
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $pms_program_id = $request->input('pms_program_id');
        $pms_plan_id = $request->input('pms_plan_id');
        $user_id = $this->user_id;
        $samplesite_params = array(
            'name' => $request->input('name'),
            'section_id' => $section_id,
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
        DB::beginTransaction();
        try {
            $samplesite_table = 'tra_samplecollection_sites';
            $applications_table = 'tra_surveillance_applications';

            $where_samplesite = array(
                'id' => $sample_site_id
            );
            $where_app = array(
                'id' => $application_id
            );
            if (isset($application_id) && $application_id != "") {//Edit
                //Application_edit
                $application_params = array(
                    'zone_id' => $zone_id,
                    'directorate_id' => $directorate_id,
                    'sample_site_id' => $sample_site_id
                );
                $app_details = array();
                if (recordExists($applications_table, $where_app)) {
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
                //Sample site_edit
                if (recordExists($samplesite_table, $where_samplesite)) {
                    $samplesite_params['dola'] = Carbon::now();
                    $samplesite_params['altered_by'] = $user_id;
                    $previous_data = getPreviousRecords($samplesite_table, $where_samplesite);
                    if ($previous_data['success'] == false) {
                        return $previous_data;
                    }
                    $previous_data = $previous_data['results'];
                    $res = updateRecord($samplesite_table, $previous_data, $where_samplesite, $samplesite_params, $user_id);
                }
            } else {//Create
                //Sample site_create
                $samplesite_res = insertRecord($samplesite_table, $samplesite_params, $user_id);
                if ($samplesite_res['success'] == false) {
                    return \response()->json($samplesite_res);
                }
                $sample_site_id = $samplesite_res['record_id'];
                //Application_create
                $zone_code = getSingleRecordColValue('par_zones', array('id' => $zone_id), 'zone_code');
                $section_code = getSingleRecordColValue('par_sections', array('id' => $section_id), 'code');
                $sub_module_code = getSingleRecordColValue('sub_modules', array('id' => $sub_module_id), 'code');
                $codes_array = array(
                    'section_code' => $section_code,
                    'zone_code' => $zone_code,
                    'sub_module_code' => $sub_module_code
                );
                $view_id = generateApplicationViewID();
                $ref_number = generatePremiseRefNumber(15, $codes_array, date('Y'), $process_id, $zone_id, $user_id);
                $application_code = generateApplicationCode($sub_module_id, $applications_table);
                $application_status = getApplicationInitialStatus($module_id, $sub_module_id);
                $application_params = array(
                    'module_id' => $module_id,
                    'view_id'=>$view_id,
                    'sub_module_id' => $sub_module_id,
                    'section_id' => $section_id,
                    'application_code' => $application_code,
                    'zone_id' => $zone_id,
                    'sample_site_id' => $sample_site_id,
                    'directorate_id' => $directorate_id,
                    'pms_program_id' => $pms_program_id,
                    'pms_plan_id' => $pms_plan_id,
                    'process_id' => $process_id,
                    'workflow_stage_id' => $workflow_stage_id,
                    'reference_no' => $ref_number,
                    'application_status_id' => $application_status->status_id
                );
                $res = insertRecord($applications_table, $application_params, $user_id);
                $application_id = $res['record_id'];
                //insert registration table
                $reg_params = array(
                    'tra_surveillance_id' => $application_id,
                    'status_id' => 1,
                    'created_by' => $user_id
                );
                createInitialRegistrationRecord('registered_surveillance', $applications_table, $reg_params, $application_id, 'reg_surveillance_id');
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
                    'applicant_id' => 0,
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
            $res['sample_site_id'] = $sample_site_id;
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

    //prepare functions
    public function prepareStructuredPmsReceivingStage(Request $request)
    {
        $application_id = $request->input('application_id');
        try {
            $mainQry = DB::table('tra_surveillance_applications as t1')
                ->where('t1.id', $application_id);
            $pmsQry = clone $mainQry;
            $pmsQry->join('pms_program_details as t2', 't1.pms_program_id', '=', 't2.id')
                ->join('pms_program_plans as t3', 't1.pms_plan_id', '=', 't3.id')
                ->select('t2.*', 't3.*', 't2.id as pms_program_id', 't3.id as pms_plan_id');
            $sampleSiteQry = clone $mainQry;
            $sampleSiteQry->join('tra_samplecollection_sites as t4', 't1.sample_site_id', '=', 't4.id');

            $mainResults = $mainQry->first();
            $pmsResults = $pmsQry->first();
            $sampleSiteResults = $sampleSiteQry->first();
            $res = array(
                'success' => true,
                'mainResults' => $mainResults,
                'pmsResults' => $pmsResults,
                'sampleSiteResults' => $sampleSiteResults,
                'message' => 'Records fetched successfully'
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

    public function saveSurveillanceSampleDetails(Request $request)
    {
        $sample_id = $request->input('sample_id');
        $application_id = $request->input('application_id');
        $post_data = $request->input();
        $user_id = $this->user_id;
        unset($post_data['section_id']);
        unset($post_data['sample_id']);
        unset($post_data['isReadOnly']);
        unset($post_data['sample_appcode']);
        $table_name = 'tra_surveillance_sample_details';
        try {
            if (isset($sample_id) && is_numeric($sample_id)) {
                $where = array('id' => $sample_id);
                $previous_data = getPreviousRecords($table_name, $where);
                if ($previous_data['success'] == false) {
                    return $previous_data;
                }
                $res = updateRecord($table_name, $previous_data['results'], $where, $post_data, $user_id);
            } else {
                $application_details = getTableData('tra_surveillance_applications', array('id' => $application_id));
                if (is_null($application_details)) {
                    $res = array(
                        'success' => false,
                        'message' => 'Problem encountered getting application details, try again'
                    );
                    return \response()->json($res);
                }
                $group_ref_no = $application_details->reference_no;//getSingleRecordColValue('tra_surveillance_applications', array('id' => $application_id), 'reference_no');
                $serial_no = DB::table('tra_surveillance_sample_details')
                    ->where('application_id', $application_id)
                    ->count();
                $serial_no = $serial_no + 1;
                $codes_array = array(
                    'group_ref_no' => $group_ref_no,
                    'serial_no' => $serial_no,
                );
                $sample_appcode = $application_details->application_code . $serial_no;
                $sample_refno = generateRefNumber($codes_array, 16);
                $post_data['sample_refno'] = $sample_refno;
                $post_data['sample_appcode'] = $sample_appcode;
                $res = insertRecord($table_name, $post_data, $user_id);
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
        return \response()->json($res);
    }

    public function surveillanceApplicationSamplesQry()
    {
        $qry = DB::table('tra_surveillance_sample_details as t1')
            ->join('pharmaceutical_dosage_forms as t2', 't1.dosage_form_id', '=', 't2.id')
            ->join('par_classifications as t3', 't1.classification_id', '=', 't3.id')
            ->join('par_packaging_units as t4', 't1.packaging_units_id', '=', 't4.id')
            ->join('tra_manufacturers_information as t5', 't1.manufacturer_id', '=', 't5.id')
            ->join('par_storage_conditions as t6', 't1.storage_condition_id', '=', 't6.id')
            ->join('par_seal_types as t7', 't1.seal_condition_id', '=', 't7.id')
            ->join('par_samplingreasons as t8', 't1.sampling_reason_id', '=', 't8.id')
            ->join('users as t9', 't1.sample_collector_id', '=', 't9.id')
            ->join('par_sample_application_types as t10', 't1.sample_application_id', '=', 't10.id')
            ->select(DB::raw("t1.*,t1.id as sample_id,t2.name as dosage_form,t3.name as class,t4.name as packaging_unit,t5.name as manufacturer,t6.name as storage, 
                    t7.name as seal_condition,t8.name as sampling_reason,CONCAT_WS(' ',decrypt(t9.first_name),decrypt(t9.last_name)) as collector,t10.name as sample_type"));
        return $qry;
    }

    public function getPmsApplicationSamplesReceiving(Request $request)
    {
        $application_id = $request->input('application_id');
        try {
            $qry = $this->surveillanceApplicationSamplesQry()
                ->where('t1.application_id', $application_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'Records fetched successfully'
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

    public function getPmsApplicationSamplesLabStages(Request $request)
    {
        $application_id = $request->input('application_id');
        $analysis_type_id = $request->input('analysis_type_id');
        try {
            $qry = $this->surveillanceApplicationSamplesQry()
                ->leftJoin('tra_pmslabresult_recommendations as t11', function ($join) use ($analysis_type_id) {
                    $join->on('t1.id', '=', 't11.sample_id')
                        ->where('t11.analysis_type_id', $analysis_type_id);
                });
            if ($analysis_type_id == 1) {
                $qry->leftJoin('par_pmsevaluation_decisions as t12', 't11.decision_id', '=', 't12.id');
            } else if ($analysis_type_id == 2) {
                $qry->leftJoin('par_pmsscreening_decisions as t12', 't11.decision_id', '=', 't12.id');
            } else if ($analysis_type_id == 3) {
                $qry->leftJoin('par_pmsanalysis_decisions as t12', 't11.decision_id', '=', 't12.id');
            }
            $qry->addSelect('t11.decision_id', 't11.comments as results_comments', 't12.name as recommendation')
                ->where('t1.application_id', $application_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'Records fetched successfully'
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

    public function getSurveillanceApplicationSamples(Request $request)
    {
        $application_id = $request->input('application_id');
        $analysis_type_id = $request->input('analysis_type_id');
        try {
            /*$qry = DB::table('tra_surveillance_sample_details as t1')
                ->join('pharmaceutical_dosage_forms as t2', 't1.dosage_form_id', '=', 't2.id')
                ->join('par_classifications as t3', 't1.classification_id', '=', 't3.id')
                ->join('par_packaging_units as t4', 't1.packaging_units_id', '=', 't4.id')
                ->join('tra_manufacturers_information as t5', 't1.manufacturer_id', '=', 't5.id')
                ->join('par_storage_conditions as t6', 't1.storage_condition_id', '=', 't6.id')
                ->join('par_seal_types as t7', 't1.seal_condition_id', '=', 't7.id')
                ->join('par_samplingreasons as t8', 't1.sampling_reason_id', '=', 't8.id')
                ->join('users as t9', 't1.sample_collector_id', '=', 't9.id')
                ->join('par_sample_application_types as t10', 't1.sample_application_id', '=', 't10.id')
                ->leftJoin('tra_pmslabresult_recommendations as t11', function ($join) use ($analysis_type_id) {
                    $join->on('t1.id', '=', 't11.sample_id')
                        ->where('t11.analysis_type_id', $analysis_type_id);
                })
                ->where('t1.application_id', $application_id)
                ->select(DB::raw("t1.*,t1.id as sample_id,t2.name as dosage_form,t3.name as class,t4.name as packaging_unit,t5.name as manufacturer,t6.name as storage, 
                    t7.name as seal_condition,t8.name as sampling_reason,CONCAT_WS(' ',decrypt(t9.first_name),decrypt(t9.last_name)) as collector,t10.name as sample_type,
                    t11.decision_id,t11.comments as results_comments"));*/
            $qry = $this->surveillanceApplicationSamplesQry()
                ->where('t1.application_id', $application_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'Records fetched successfully'
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

    public function getPmsApplicationMoreDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        try {
            $mainQry = DB::table('tra_surveillance_applications as t1')
                ->where('t1.id', $application_id);
            $pmsQry = clone $mainQry;
            $pmsQry->join('pms_program_details as t2', 't1.pms_program_id', '=', 't2.id')
                ->join('pms_program_plans as t3', 't1.pms_plan_id', '=', 't3.id')
                ->select('t2.*', 't3.*', 't2.id as pms_program_id', 't3.id as pms_plan_id');
            $sampleSiteQry = clone $mainQry;
            $sampleSiteQry->join('tra_samplecollection_sites as t4', 't1.sample_site_id', '=', 't4.id');

            $mainResults = $mainQry->first();
            $pmsResults = $pmsQry->first();
            $sampleSiteResults = $sampleSiteQry->first();
            $res = array(
                'success' => true,
                'mainResults' => $mainResults,
                'pmsResults' => $pmsResults,
                'sampleSiteResults' => $sampleSiteResults,
                'message' => 'Records fetched successfully'
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

    public function savePmsPIRRecommendation(Request $request)
    {
        $sample_id = $request->input('sample_id');
        $decision_id = $request->input('decision_id');
        $analysis_type_id = $request->input('analysis_type_id');
        $comments = $request->input('comments');
        $table_name = 'tra_pmslabresult_recommendations';
        $user_id = $this->user_id;
        $params = array(
            'decision_id' => $decision_id,
            'comments' => $comments
        );
        try {
            $where = array(
                'sample_id' => $sample_id,
                'analysis_type_id' => $analysis_type_id
            );
            if (recordExists($table_name, $where)) {
                $params['altered_by'] = $user_id;
                $params['dola'] = Carbon::now();
                $previous_data = getPreviousRecords($table_name, $where);
                if ($previous_data['success'] == false) {
                    return $previous_data;
                }
                $previous_data = $previous_data['results'];
                $res = updateRecord($table_name, $previous_data, $where, $params, $user_id);
            } else {
                $params['sample_id'] = $sample_id;
                $params['created_by'] = $user_id;
                $params['analysis_type_id'] = $analysis_type_id;
                $params['created_on'] = Carbon::now();
                $res = insertRecord($table_name, $params, $user_id);
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
        return \response()->json($res);
    }

    public function getPmsSampleIngredients(Request $request)
    {
        $sample_id = $request->input('sample_id');
        try {
            $qry = DB::table('tra_pmssample_ingredients as t1')
                ->join('master_ingredients as t2', 't1.ingredient_id', '=', 't2.id')
                ->join('product_specifications as t3', 't1.specification_id', '=', 't3.id')
                ->join('si_units as t4', 't1.si_unit_id', '=', 't4.id')
                ->join('inclusion_reason as t5', 't1.inclusion_reason_id', '=', 't5.id')
                ->select(DB::raw("t1.*,t2.name as ingredient,t3.name as specification,t5.name as inclusion_reason,
                CONCAT(t1.strength,t4.name) as strength_txt"))
                ->where('t1.sample_id', $sample_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => returnMessage($results)
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

    public function getSampleLabAnalysisResults(Request $request)
    {
        $sample_id = $request->input('sample_id');
        $analysis_type_id = $request->input('analysis_type_id');
        try {
            $qry = DB::table('tra_survsample_analysis_results as t1')
                ->join('cost_elements as t2', 't1.test_parameter_id', '=', 't2.id')
                ->select('t1.*', 't2.name as test_parameter')
                ->where('t1.sample_id', $sample_id);
            if (isset($analysis_type_id) && is_numeric($analysis_type_id)) {
                $qry->where('t1.analysis_type_id', $analysis_type_id);
            }
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => returnMessage($results)
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
