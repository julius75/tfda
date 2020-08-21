<?php

namespace App\Modules\PremiseRegistration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;

Builder::macro('firstOrFail', function () {
    if ($record = $this->first()) {
        return $record;
    }
    return '';
});

class PremiseRegistrationController extends Controller
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
        return view('premiseregistration::index');
    }

    public function savePremiseRegCommonData(Request $req)
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

    public function savePremiseOtherDetails(Request $req)
    {
        try {
            $user_id = \Auth::user()->id;
            $post_data = $req->all();
            $table_name = $post_data['table_name'];
            $id = $post_data['id'];
            $is_temporal = $post_data['is_temporal'];
            $premise_id = $post_data['premise_id'];
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
                'premise_id' => $premise_id,
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
            $portal_premise_id = getSingleRecordColValue('tra_premises', array('id' => $premise_id), 'portal_id');
            $portal_db = DB::connection('portal_db');
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
                    //update portal
                    if ($is_temporal < 1) {
                        unset($table_data['premise_id']);
                        $portal_id = $previous_data[0]['portal_id'];
                        $portal_db->table('wb_premises_otherdetails')
                            ->where('id', $portal_id)
                            ->update($table_data);
                    }
                }
            } else {
                //insert portal
                $premise_id = $table_data['premise_id'];
                unset($table_data['premise_id']);
                if ($is_temporal < 1) {
                    $table_data['premise_id'] = $portal_premise_id;
                    $portal_id = $portal_db->table('wb_premises_otherdetails')
                        ->insertGetId($table_data);
                    $table_data['portal_id'] = $portal_id;
                }
                $table_data['premise_id'] = $premise_id;
                //$table_data['portal_id'] = $portal_id;
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

    public function savePremisePersonnelQualifications(Request $req)
    {
        try {
            $user_id = \Auth::user()->id;
            $post_data = $req->all();
            $table_name = $post_data['table_name'];
            $id = $post_data['id'];
            $personnel_id = $post_data['personnel_id'];
            $study_field_id = $post_data['study_field_id'];
            $qualification_id = $post_data['qualification_id'];
            //unset unnecessary values
            unset($post_data['_token']);
            unset($post_data['table_name']);
            unset($post_data['model']);
            unset($post_data['id']);
            $table_data = $post_data;
            $table_data_p = $post_data;
            //add extra params
            $table_data['created_on'] = Carbon::now();
            $table_data['created_by'] = $user_id;
            $where = array(
                'id' => $id
            );
            $where2 = array(
                'personnel_id' => $personnel_id,
                'study_field_id' => $study_field_id,
                'qualification_id' => $qualification_id
            );
            /*$portal_personnel_id = getSingleRecordColValue('tra_personnel_information', array('id' => $personnel_id), 'portal_id');
            $portal_db = DB::connection('portal_db');*/
            if (isset($id) && $id != "") {
                if (recordExists($table_name, $where)) {
                    unset($table_data['created_on']);
                    unset($table_data['created_by']);
                    $table_data['dola'] = Carbon::now();
                    $table_data['altered_by'] = $user_id;
                    $prev_data = getPreviousRecords($table_name, $where);
                    if ($prev_data['success'] == false) {
                        return $prev_data;
                    }
                    $previous_data = $prev_data['results'];
                    //portal
                    /*unset($table_data_p['personnel_id']);
                    $portal_id = $previous_data[0]['portal_id'];
                    if (isset($portal_id) && $portal_id != '') {//update
                        $table_data_p['mis_altered_by'] = $user_id;
                        $table_data_p['mis_dola'] = Carbon::now();
                        $portal_db->table('wb_personnel_qualifications')
                            ->where('id', $portal_id)
                            ->update($table_data_p);
                    } else {//insert
                        $table_data_p['mis_created_by'] = $user_id;
                        $table_data_p['mis_created_on'] = Carbon::now();
                        $table_data['personnel_id'] = $portal_personnel_id;
                        $portal_id = $portal_db->table('wb_personnel_qualifications')
                            ->insertGetId($table_data_p);
                    }
                    $table_data['portal_id'] = $portal_id;*/
                    $res = updateRecord($table_name, $previous_data, $where, $table_data, $user_id);
                }
            } else {
                //check existence of this combination
                if (DB::table($table_name)
                        ->where($where2)
                        ->count() > 0) {
                    $res = array(
                        'success' => false,
                        'message' => 'This combination already exists!!'
                    );
                    return \response()->json($res);
                };
                //insert portal
                /*unset($table_data_p['personnel_id']);
                $table_data_p['personnel_id'] = $portal_personnel_id;
                $table_data_p['mis_created_by'] = $user_id;
                $table_data_p['mis_created_on'] = Carbon::now();
                $portal_id = $portal_db->table('wb_personnel_qualifications')
                    ->insertGetId($table_data_p);*/
                //insert mis
                //$table_data['portal_id'] = $portal_id;
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

    public function deletePremiseRegRecord(Request $req)
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

    public function softDeletePremiseRegRecord(Request $req)
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

    public function undoPremiseRegSoftDeletes(Request $req)
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

    public function getApplicantsList(Request $request)
    {
        $applicant_id = $request->input('applicant_id');
        $applicantType = $request->input('applicantType');
        try {
            $qry = DB::table('wb_trader_account as t1')
                ->join('par_countries as t2', 't1.country_id', '=', 't2.id')
                ->join('par_regions as t3', 't1.region_id', '=', 't3.id')
                ->join('par_districts as t4', 't1.district_id', '=', 't4.id')
                ->select('t1.id as applicant_id', 't1.id as ltr_id', 't1.name as applicant_name', 't1.contact_person', 't1.tin_no',
                    't1.country_id as app_country_id', 't1.region_id as app_region_id', 't1.district_id as app_district_id',
                    't1.physical_address as app_physical_address', 't1.postal_address as app_postal_address', 't1.telephone_no as app_telephone',
                    't1.fax as app_fax', 't1.email as app_email', 't1.website as app_website', 't2.name as country_name', 't3.name as region_name', 't4.name as district_name');

            if (isset($applicant_id) && $applicant_id != '') {
                $qry->where('t1.id', $applicant_id);
            }
            if (isset($applicantType) && $applicantType != 'local') {
                $qry->where('t1.country_id', 36);
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

    public function getPremisesList(Request $request)
    {
        $premise_id = $request->input('premise_id');
        $section_id = $request->input('section_id');
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
            $qry = DB::table('registered_premises as t0')
                ->join('tra_premises as t1', 't0.tra_premise_id', '=', 't1.id')
                ->join('tra_approval_recommendations as t2', 't1.permit_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->select('t0.id as main_registered_id', 't1.id as premise_id', 't1.id as manufacturing_site_id', 't1.*', 't2.permit_no', 't3.name as applicant_name',
                    't3.id as applicant_id', 't3.name as applicant_name', 't3.contact_person', 't3.tin_no',
                    't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id',
                    't3.physical_address as app_physical_address', 't3.postal_address as app_postal_address',
                    't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website')
                ->whereIn('t0.status_id', array(2, 4));
            if (isset($section_id) && $section_id != '') {
                $qry->where('t1.section_id', $section_id);
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

    public function getPremiseApplications(Request $request)
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
            $qry = DB::table('tra_premises_applications as t1')
                ->join('tra_premises as t2', 't1.premise_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('wf_tfdaprocesses as t4', 't1.process_id', '=', 't4.id')
                ->join('wf_workflow_stages as t5', 't1.workflow_stage_id', '=', 't5.id')
                ->join('par_system_statuses as t6', 't1.application_status_id', '=', 't6.id')
                ->select(DB::raw("t1.id as active_application_id, t1.application_code, t4.module_id, t4.sub_module_id, t4.section_id, t2.name as premise_name,
                    t6.name as application_status, t3.name as applicant_name, t4.name as process_name, t5.name as workflow_stage, t5.is_general, t3.contact_person,
                    t3.tin_no, t3.country_id as app_country_id, t3.region_id as app_region_id, t3.district_id as app_district_id, t3.physical_address as app_physical_address,
                    t3.postal_address as app_postal_address, t3.telephone_no as app_telephone, t3.fax as app_fax, t3.email as app_email, t3.website as app_website,
                    t2.*, t1.*"));

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

    public function getPremiseOtherDetails(Request $request)
    {
        $premise_id = $request->input('premise_id');
        $is_temporal = $request->input('is_temporal');
        try {
            $qry = DB::table('tra_premises_otherdetails as t1')
                ->join('par_business_types as t2', 't1.business_type_id', '=', 't2.id')
                ->join('par_business_type_details as t3', 't1.business_type_detail_id', '=', 't3.id')
                ->select('t1.*', 't2.name as business_type', 't3.name as business_type_detail')
                ->where('t1.premise_id', $premise_id);
            if (isset($is_temporal) && $is_temporal == 1) {

            } else {
                $qry->where(function ($query) {
                    $query->where('t1.is_temporal', 0)
                        ->orWhereNull('t1.is_temporal');
                });
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

    public function getPremisePersonnelDetails(Request $request)
    {
        $premise_id = $request->input('premise_id');
        $is_temporal = $request->input('is_temporal');
        try {
            $qry = DB::table('tra_premises_personnel as t1')
                ->join('tra_personnel_information as t2', 't1.personnel_id', '=', 't2.id')
                ->join('tra_personnel_qualifications as t3', 't1.personnel_qualification_id', '=', 't3.id')
                ->join('par_personnel_studyfield as t4', 't3.study_field_id', '=', 't4.id')
                ->join('par_personnel_qualifications as t5', 't3.qualification_id', '=', 't5.id')
                ->select(DB::raw("t1.premise_id, t1.start_date, t1.end_date, t2.*,t1.position_id,t1.personnel_qualification_id,
                CONCAT(CONCAT_WS(' - ',t4.name,t5.name),' (',t3.institution,')') as qualification_combined,t1.is_temporal"))
                ->where('t1.premise_id', $premise_id);
            if (isset($is_temporal) && $is_temporal == 1) {

            } else {
                $qry->where(function ($query) {
                    $query->where('t1.is_temporal', 0)
                        ->orWhereNull('t1.is_temporal');
                });
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

    public function getPremisePersonnelQualifications(Request $request)
    {
        $personnel_id = $request->input('personnel_id');
        try {
            $qry = DB::table('tra_personnel_qualifications as t1')
                ->join('par_personnel_qualifications as t2', 't1.qualification_id', '=', 't2.id')
                ->join('par_personnel_studyfield as t3', 't1.study_field_id', '=', 't3.id')
                ->select(DB::raw("t1.*, t3.name as study_field,t2.name as qualification,
                CONCAT(CONCAT_WS(' - ',t3.name,t2.name),' (',t1.institution,')') as qualification_combined"))
                ->where('t1.personnel_id', $personnel_id);
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

    public function getQueryPrevResponses(Request $request)
    {
        $query_id = $request->input('query_id');
        try {
            $qry = DB::table('checklistitems_queryresponses as t1')
                ->where('query_id', $query_id)
                ->where('t1.id', '<>', DB::raw("(SELECT MAX(id) FROM checklistitems_queryresponses WHERE query_id=$query_id)"))
                ->orderBy('id', 'ASC');
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

    public function closeApplicationQuery(Request $request)
    {
        $query_id = $request->input('query_id');
        $item_resp_id = $request->input('item_resp_id');
        $user_id = $this->user_id;
        $table_name = 'checklistitems_queries';
        $where = array(
            'id' => $query_id
        );
        $table_data = array(
            'status' => 4
        );
        try {
            $prev_data = getPreviousRecords($table_name, $where);
            if ($prev_data['success'] == true) {
                $previous_data = $prev_data['results'];
                $res = updateRecord($table_name, $previous_data, $where, $table_data, $user_id);
                if (DB::table('checklistitems_queries')
                        ->where('item_resp_id', $item_resp_id)
                        ->where('status', '<>', 4)
                        ->count() == 0) {
                    DB::table('checklistitems_responses')
                        ->where('id', $item_resp_id)
                        ->update(array('pass_status' => 1));
                }
            } else {
                $res = $prev_data;
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

    public function saveApplicationReQueryDetails(Request $request)
    {
        $query_id = $request->input('id');
        $comment = $request->input('comment');
        $item_resp_id = $request->input('item_resp_id');
        $user_id = $this->user_id;
        $table_name = 'checklistitems_queries';
        $where = array(
            'id' => $query_id
        );
        $table_data = array(
            'status' => 3,
            'comment' => $comment
        );
        try {
            $prev_data = getPreviousRecords($table_name, $where);
            if ($prev_data['success'] == true) {
                $previous_data = $prev_data['results'];
                $res = updateRecord($table_name, $previous_data, $where, $table_data, $user_id);
                DB::table('checklistitems_responses')
                    ->where('id', $item_resp_id)
                    ->update(array('pass_status' => 2));
            } else {
                $res = $prev_data;
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

    public function removeApplicationPaymentDetails(Request $request)
    {
        $item_ids = $request->input();
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $invoice_id = $request->input('invoice_id');
        $user_id = $this->user_id;
        unset($item_ids['application_id']);
        unset($item_ids['application_code']);
        unset($item_ids['invoice_id']);
        try {
            $res = array();
            DB::transaction(function () use ($item_ids, $user_id, &$res, $application_id, $application_code, $invoice_id) {
                $qry = DB::table('tra_payments as t1')
                    ->whereIn('id', $item_ids);
                //log records
                $records = $qry->select(DB::raw("t1.*,$user_id as deleted_by,NOW() as deleted_at"))
                    ->get();
                $records = convertStdClassObjToArray($records);
                DB::table('tra_payments_deletion_log')
                    ->insert($records);
                DB::table('tra_payments')
                    ->whereIn('id', $item_ids)
                    ->delete();
                $payment_details = getApplicationPaymentsRunningBalance($application_id, $application_code, $invoice_id);
                $balance = $payment_details['running_balance'];
                $invoice_amount = $payment_details['invoice_amount'];
                $res = array(
                    'success' => true,
                    'balance' => $balance,
                    'invoice_amount' => $invoice_amount,
                    'message' => 'Selected payment details removed successfully!!'
                );
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

    public function getManagerApplicationsGeneric(Request $request)
    {
        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('tra_premises as t2', 't1.premise_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', function ($join) {
                    $join->on('t1.application_status_id', '=', 't4.id');
                })
                ->leftJoin('tra_approval_recommendations as t5', function ($join) {
                    $join->on('t1.id', '=', 't5.application_id')
                        ->on('t1.application_code', '=', 't5.application_code');
                })
                ->leftJoin('par_approval_decisions as t6', 't5.decision_id', '=', 't6.id')
                ->select('t1.*', 't2.name as premise_name', 't3.name as applicant_name', 't4.name as application_status',
                    't6.name as approval_status', 't5.decision_id', 't1.id as active_application_id')
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

    public function getManagerApplicationsRenewalGeneric(Request $request)
    {
        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('tra_premises as t2', 't1.premise_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', function ($join) {
                    $join->on('t1.application_status_id', '=', 't4.id');
                    //->on('t1.process_id', '=', 't4.process_id');
                })
                ->leftJoin('tra_approval_recommendations as t5', function ($join) {
                    $join->on('t1.id', '=', 't5.application_id')
                        ->on('t1.application_code', '=', 't5.application_code');
                })
                ->leftJoin('par_approval_decisions as t6', 't5.decision_id', '=', 't6.id')
                ->select('t1.*', 't2.target_id as premise_id', 't2.name as premise_name', 't3.name as applicant_name', 't4.name as application_status', 't6.name as approval_status')
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

    public function getPremiseApplicationsAtApproval(Request $request)
    {
        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('tra_premises as t2', 't1.premise_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->leftjoin('par_system_statuses as t4', function ($join) {//should be inner
                    $join->on('t1.application_status_id', '=', 't4.id');
                    //->on('t1.process_id', '=', 't4.process_id');
                })
                ->leftJoin('tra_approval_recommendations as t5', function ($join) {
                    $join->on('t1.id', '=', 't5.application_id')
                        ->on('t1.application_code', '=', 't5.application_code');
                })
                ->leftJoin('par_approval_decisions as t6', 't5.decision_id', '=', 't6.id')
                ->join('wf_tfdaprocesses as t7', 't1.process_id', '=', 't7.id')
                ->join('wf_workflow_stages as t8', 't1.workflow_stage_id', '=', 't8.id')
                ->select('t1.*', 't1.id as active_application_id', 't2.name as premise_name', 't3.name as applicant_name', 't4.name as application_status', 't6.name as approval_status',
                    't2.init_premise_id', 't7.name as process_name', 't8.name as workflow_stage', 't8.is_general', 't5.id as recommendation_id', 't6.name as recommendation')
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

    public function getPremApplicationMoreDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $applicant_id = $request->input('applicant_id');
        $premise_id = $request->input('premise_id');
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

            $qryPremise = DB::table('tra_premises as t1')
                ->join('par_countries as t2', 't1.country_id', '=', 't2.id')
                ->join('par_regions as t3', 't1.region_id', '=', 't3.id')
                ->leftJoin('par_districts as t4', 't1.district_id', '=', 't4.id')
                ->select('t1.name as premise_name', 't1.id as premise_id', 't1.*')
                ->where('t1.id', $premise_id);
            $premiseDetails = $qryPremise->first();

            $res = array(
                'success' => true,
                'applicant_details' => $applicantDetails,
                'premise_details' => $premiseDetails,
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

    public function getApplicationEvaluationTemplate(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $where = array(
            'application_id' => $application_id,
            'application_code' => $application_code
        );
        try {
            $results = getTableData('evaluation_templates', $where);
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

    public function getOnlineApplications(Request $request)
    {
        $section_id = $request->input('section_id');
        $sub_module_id = $request->input('sub_module_id');
        try {
            $portal_db = DB::connection('portal_db');
            //get process details
            $qry = $portal_db->table('wb_premises_applications as t1')
                ->join('wb_premises as t2', 't1.premise_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.trader_id', '=', 't3.id')
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

    public function getOnlineAppPremiseOtherDetails(Request $request)
    {
        $premise_id = $request->input('premise_id');
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_premises_otherdetails as t1')
                ->where('premise_id', $premise_id);
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

    public function getOnlineAppPremisePersonnelDetails(Request $request)
    {
        $premise_id = $request->input('premise_id');
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_premises_personnel as t1')
                ->where('premise_id', $premise_id);
            $results = $qry->get();
            foreach ($results as $key => $result) {
                $personnel_details = DB::table('tra_personnel_information')
                    ->where('id', $result->personnel_id)
                    ->firstOrFail();
                $qualification_details = DB::table('tra_personnel_qualifications as t1')
                    ->join('par_personnel_qualifications as t2', 't1.qualification_id', '=', 't2.id')
                    ->join('par_personnel_studyfield as t3', 't1.study_field_id', '=', 't3.id')
                    ->where('t1.id', $result->personnel_qualification_id)
                    ->select(DB::raw("CONCAT(CONCAT_WS(' - ',t3.name,t3.name),' (',t1.institution,')') as qualification_combined"))
                    ->firstOrFail();
                $results[$key]->id = $personnel_details->id;
                $results[$key]->name = $personnel_details->name;
                $results[$key]->telephone_no = $personnel_details->telephone_no;
                $results[$key]->email_address = $personnel_details->email_address;
                $results[$key]->postal_address = $personnel_details->postal_address;
                $results[$key]->qualification_combined = $qualification_details->qualification_combined;
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

    public function updateOnlineApplicationQueryResponse(Request $request)
    {
        $portal_application_id = $request->input('application_id');
        $comment = $request->input('comment');
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            //Prev application details
            $prev_app_details = DB::table('tra_premises_applications')
                ->where('portal_id', $portal_application_id)
                ->first();
            if (is_null($prev_app_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching previous MIS application details, consult System Admin!!'
                );
                return \response()->json($res);
            }
            $application_id = $prev_app_details->id;
            $process_id = $prev_app_details->process_id;
            $premise_id = $prev_app_details->premise_id;
            $applicant_id = $prev_app_details->applicant_id;
            $application_code = $prev_app_details->application_code;
            $module_id = $prev_app_details->module_id;
            $sub_module_id = $prev_app_details->sub_module_id;
            $section_id = $prev_app_details->section_id;
            $ref_no = $prev_app_details->reference_no;
            $app_status_id = 4;
            //process details
            $where = array(
                'id' => $process_id
            );
            $process_details = getTableData('wf_tfdaprocesses', $where);
            if (is_null($process_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting process details, consult System Admin!!'
                );
                return \response()->json($res);
            }
            //workflow details
            $where2 = array(
                'id' => $process_details->workflow_id,
                'stage_status' => 1
            );
            $workflow_details = getTableData('wf_workflow_stages', $where2);
            if (is_null($workflow_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting workflow details, consult System Admin!!'
                );
                return \response()->json($res);
            }

            $portal_db = DB::connection('portal_db');
            $portal_details = $portal_db->table('wb_premises_applications as t1')
                ->where('id', $portal_application_id)
                ->first();
            if (is_null($portal_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting portal application details, consult System Admin!!'
                );
                return \response()->json($res);
            }
            //applicant details
            $applicant_details = $portal_db->table('wb_trader_account')
                ->where('id', $portal_details->trader_id)
                ->first();
            if (is_null($applicant_details)) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting applicant details, consult System Admin!!'
                );
                return \response()->json($res);
            }
            //premise details
            $premise_details = $portal_db->table('wb_premises as t1')
                ->select(DB::raw("t1.*,t1.id as portal_id,$user_id as altered_by,NOW() as dola"))
                ->where('id', $portal_details->premise_id)
                ->first();
            if (is_null($premise_details)) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting premise details, consult System Admin!!'
                );
                return \response()->json($res);
            }
            //update premise main details
            $where_premise = array(
                'id' => $premise_id
            );
            $premise_data = convertStdClassObjToArray($premise_details);
            unset($premise_data['id']);
            unset($premise_data['mis_dola']);
            unset($premise_data['mis_altered_by']);
            $prev_data = getPreviousRecords('tra_premises', $where_premise);
            if ($prev_data['success'] == false) {
                DB::rollBack();
                return \response()->json($prev_data);
            }
            $premise_update = updateRecord('tra_premises', $prev_data['results'], $where_premise, $premise_data, $user_id);
            //update premise other details
            $premise_otherdetails = $portal_db->table('wb_premises_otherdetails')
                ->where('premise_id', $portal_details->premise_id)
                ->select(DB::raw("id as portal_id,$premise_id as premise_id,business_type_id,business_type_detail_id,$user_id as created_by"))
                ->get();
            $premise_otherdetails = convertStdClassObjToArray($premise_otherdetails);
            unset($premise_otherdetails['id']);
            $where_premise2 = array(
                'premise_id' => $premise_id
            );
            DB::table('tra_premises_otherdetails')
                ->where($where_premise2)
                ->delete();
            DB::table('tra_premises_otherdetails')
                ->insert($premise_otherdetails);
            //application update
            $app_update = array(
                'premise_id' => $premise_id,
                'zone_id' => $portal_details->zone_id,
                'workflow_stage_id' => $workflow_details->id,
                'application_status_id' => $app_status_id
            );
            $application_status = getSingleRecordColValue('par_system_statuses', array('id' => $app_status_id), 'name');
            $where_app = array(
                'id' => $application_id
            );
            $prev_data = getPreviousRecords('tra_premises_applications', $where_app);
            if ($prev_data['success'] == false) {
                DB::rollBack();
                return \response()->json($prev_data);
            }
            $application_update = updateRecord('tra_premises_applications', $prev_data['results'], $where_app, $app_update, $user_id);
            if ($application_update['success'] == false) {
                DB::rollBack();
                return \response()->json($application_update);
            }
            //print_r($application_update);
            $portal_db->table('wb_premises_applications')
                ->where('id', $portal_application_id)
                ->update(array('application_status_id' => 3));
            $details = array(
                'application_id' => $application_id,
                'application_code' => $application_code,
                'reference_no' => $ref_no,
                'application_status' => $application_status,
                'process_id' => $process_details->id,
                'process_name' => $process_details->name,
                'workflow_stage_id' => $workflow_details->id,
                'workflow_stage' => $workflow_details->name,
                'module_id' => $module_id,
                'sub_module_id' => $sub_module_id,
                'section_id' => $section_id,
                'premise_id' => $premise_id,
                'applicant_id' => $applicant_id
            );
            //submissions
            $submission_params = array(
                'application_id' => $application_id,
                'process_id' => $process_details->id,
                'application_code' => $application_code,
                'reference_no' => $ref_no,
                'usr_from' => $user_id,
                'usr_to' => $user_id,
                'previous_stage' => $workflow_details->id,
                'current_stage' => $workflow_details->id,
                'module_id' => $module_id,
                'sub_module_id' => $sub_module_id,
                'section_id' => $section_id,
                'application_status_id' => $app_status_id,
                'urgency' => 1,
                'applicant_id' => $applicant_id,
                'remarks' => $comment,
                'date_received' => Carbon::now(),
                'created_on' => Carbon::now(),
                'created_by' => $user_id
            );
            DB::table('tra_submissions')
                ->insert($submission_params);
            DB::commit();
            $res = array(
                'success' => true,
                'details' => $details,
                'message' => 'Application saved successfully in the MIS!!'
            );
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

    public function rejectOnlineApplicationDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        try {
            $portal_db = DB::connection('portal_db');
            $portal_db->table('wb_premises_applications')
                ->where('id', $application_id)
                ->update(array('application_status_id' => 4));
            $res = array(
                'success' => true,
                'message' => 'Application reversed successfully to the Portal!!'
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

    public function deleteApplicationInvoice(Request $request)
    {
        $invoice_id = $request->input('invoice_id');
        $user_id = $this->user_id;
        $res = array();
        try {
            DB::transaction(function () use ($invoice_id, $user_id, &$res) {
                $table_name = 'tra_application_invoices';
                $where = array(
                    'id' => $invoice_id
                );
                $res = getPreviousRecords($table_name, $where);
                if ($res['success'] == true) {
                    $data = $res['results'];
                    DB::table('tra_invoice_details')
                        ->where('invoice_id', $invoice_id)
                        ->delete();
                    $res = deleteRecord($table_name, $data, $where, $user_id);
                }
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

    public function getAllApplicationChecklistQueries(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $process_id = $request->input('process_id');
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $section_id = $request->input('section_id');
        $checklist_category = $request->input('checklist_category');
        $where = array(
            't1.application_id' => $application_id,
            't6.module_id' => $module_id,
            't6.sub_module_id' => $sub_module_id,
            't6.section_id' => $section_id,
            't6.checklist_category_id' => $checklist_category
        );
        try {
            $qry = DB::table('checklistitems_responses as t1')
                ->join('checklistitems_queries as t2', 't1.id', '=', 't2.item_resp_id')
                ->leftJoin('checklistitems_queryresponses as t4', function ($query) {
                    $query->on('t2.id', '=', 't4.query_id')
                        ->whereRaw('t4.id IN (select MAX(a2.id) from checklistitems_queryresponses as a2 join checklistitems_queries as u2 on u2.id = a2.query_id group by u2.id)');
                })
                ->join('par_checklist_items as t5', 't1.checklist_item_id', '=', 't5.id')
                ->join('par_checklist_types as t6', 't5.checklist_type_id', '=', 't6.id')
                ->select(DB::raw("t2.*,t2.query,t5.name as checklist_item_name,t4.response as last_response"))
                ->where($where);
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

    public function savePremisePersonnelDetails(Request $request)
    {
        $personnel_id = $request->input('id');
        $trader_id = $request->input('trader_id');
        $user_id = $this->user_id;
        $personnel_data = array(
            'name' => $request->input('name'),
            'trader_id' => $trader_id,
            'postal_address' => $request->input('postal_address'),
            'telephone_no' => $request->input('telephone_no'),
            'email_address' => $request->input('email_address')
        );
        /*$portal_personnel_data = array(
            'name' => $request->input('name'),
            'registration_no' => $request->input('registration_no'),
            'postal_address' => $request->input('postal_address'),
            'telephone_no' => $request->input('telephone_no'),
            'email_address' => $request->input('email_address')
        );*/
        try {
            /*$portal_db = DB::connection('portal_db');
            $portal_trader_id = getSingleRecordColValue('wb_trader_account', array('id' => $trader_id), 'portal_id');*/
            if (isset($personnel_id) && $personnel_id != '') {
                $personnel_data['dola'] = Carbon::now();
                $personnel_data['altered_by'] = $user_id;
                $prev_data = getPreviousRecords('tra_personnel_information', array('id' => $personnel_id));
                if ($prev_data['success'] == false) {
                    return \response()->json($prev_data);
                }
                //portal
                /*$portal_id = $prev_data['results'][0]['portal_id'];
                if (isset($portal_id) && $portal_id != '') {//update portal
                    $portal_personnel_data['mis_dola'] = Carbon::now();
                    $portal_personnel_data['mis_altered_by'] = $user_id;
                    $portal_db->table('wb_personnel_information')
                        ->where('id', $portal_id)
                        ->update($portal_personnel_data);
                } else {//insert portal
                    $portal_personnel_data['trader_id'] = $portal_trader_id;
                    $portal_personnel_data['mis_created_on'] = Carbon::now();
                    $portal_personnel_data['mis_created_by'] = $user_id;
                    $portal_id = $portal_db->table('wb_personnel_information')
                        ->insertGetId($portal_personnel_data);
                }*/
                //mis
                //$personnel_data['portal_id'] = $portal_id;
                $res = updateRecord('tra_personnel_information', $prev_data['results'], array('id' => $personnel_id), $personnel_data, $user_id);
                if ($res['success'] == false) {
                    return \response()->json($res);
                }
            } else {
                $personnel_data['created_on'] = Carbon::now();
                $personnel_data['created_by'] = $user_id;
                //portal
                /*$portal_personnel_data['trader_id'] = $portal_trader_id;
                $portal_personnel_data['mis_created_on'] = Carbon::now();
                $portal_personnel_data['mis_created_by'] = $user_id;
                $portal_id = $portal_db->table('wb_personnel_information')
                    ->insertGetId($portal_personnel_data);*/
                //mis
                //$personnel_data['portal_id'] = $portal_id;
                $res = insertRecord('tra_personnel_information', $personnel_data, $user_id);
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

    public function savePremisePersonnelLinkageDetails(Request $request)//kip here
    {
        $premise_id = $request->input('premise_id');
        $personnel_id = $request->input('personnel_id');
        $user_id = $this->user_id;
        try {
            $portal_db = DB::connection('portal_db');
            //$portal_personnel_id = getSingleRecordColValue('tra_personnel_information', array('id' => $personnel_id), 'portal_id');
            $portal_premise_id = getSingleRecordColValue('tra_premises', array('id' => $premise_id), 'portal_id');

            //PORTAL
            $where_p = array(
                'personnel_id' => $personnel_id,
                'premise_id' => $portal_premise_id
            );
            $link_data_p = array(
                'personnel_qualification_id' => $request->input('personnel_qualification_id'),
                'position_id' => $request->input('position_id'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            );
            $qry_p = $portal_db->table('wb_premises_personnel')
                ->where($where_p);
            $portal_info = $qry_p->first();
            $portal_id = 0;
            if (is_numeric($portal_premise_id) && $portal_premise_id > 0) {//take care of null/zero portal premise ids
                if (is_null($portal_info)) {
                    $link_data_p['premise_id'] = $portal_premise_id;
                    $link_data_p['personnel_id'] = $personnel_id;
                    $link_data_p['mis_created_on'] = Carbon::now();
                    $link_data_p['mis_created_by'] = $user_id;
                    $portal_id = $portal_db->table('wb_premises_personnel')
                        ->insertGetId($link_data_p);
                } else {
                    $portal_id = $portal_info->id;
                    $link_data_p['mis_dola'] = Carbon::now();
                    $link_data_p['mis_altered_by'] = $user_id;
                    $qry_p->update($link_data_p);
                }
            }
            //MIS
            $where = array(
                'personnel_id' => $personnel_id,
                'premise_id' => $premise_id
            );
            $link_data = array(
                'personnel_qualification_id' => $request->input('personnel_qualification_id'),
                'position_id' => $request->input('position_id'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'is_temporal' => $request->input('is_temporal')
            );
            $qry = DB::table('tra_premises_personnel')
                ->where($where);
            $count = $qry->count();
            if ($count > 0) {
                $link_data['dola'] = Carbon::now();
                $link_data['altered_by'] = $user_id;
                $link_data['portal_id'] = $portal_id;
                $prev_data = getPreviousRecords('tra_premises_personnel', $where);
                if ($prev_data['success'] == false) {
                    return \response()->json($prev_data);
                }
                $res = updateRecord('tra_premises_personnel', $prev_data['results'], $where, $link_data, $user_id);
            } else {
                $link_data['premise_id'] = $premise_id;
                $link_data['personnel_id'] = $personnel_id;
                $link_data['portal_id'] = $portal_id;
                $link_data['created_on'] = Carbon::now();
                $link_data['created_by'] = $user_id;
                $res = insertRecord('tra_premises_personnel', $link_data, $user_id);
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

    public function deletePersonnelQualification(Request $request)
    {
        $id = $request->input('id');
        $user_id = $this->user_id;
        $where = array(
            'id' => $id
        );
        try {
            $prev_data = getPreviousRecords('tra_personnel_qualifications', $where);
            if ($prev_data['success'] == false) {
                return \response()->json($prev_data);
            }
            $portal_id = $prev_data['results'][0]['portal_id'];
            $res = deleteRecord('tra_personnel_qualifications', $prev_data['results'], $where, $user_id);
            if (isset($portal_id) && $portal_id != '') {
                $portal_db = Db::connection('portal_db');
                $portal_db->table('wb_personnel_qualifications')
                    ->where('id', $portal_id)
                    ->delete();
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

    public function uploadPersonnelDocument(Request $request)
    {
        $params = array(
            'personnel_id' => $request->input('personnel_id'),
            'name' => $request->input('name'),
            'description' => $request->input('description')
        );
        $table_name = 'tra_personnel_docs';
        $folder = '\resources\uploads';
        $user_id = $this->user_id;
        $res = uploadFile($request, $params, $table_name, $folder, $user_id);
        return \response()->json($res);
    }

    public function getPersonnelDocuments(Request $request)
    {
        $personnel_id = $request->input('personnel_id');
        try {
            $where = array(
                'personnel_id' => $personnel_id
            );
            $results = DB::table('tra_personnel_docs')
                ->where($where)
                ->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well!!'
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

    public function getTraderPersonnel(Request $request)
    {
        $trader_id = $request->input('trader_id');
        try {
            $where = array(
                'trader_id' => $trader_id
            );
            $results = DB::table('tra_personnel_information')
                ->where($where)
                ->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well!!'
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

    public function getInspectionDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        try {
            $qry = DB::table('inspection_details')
                ->where(array('application_id' => $application_id, 'application_code' => $application_code))
                ->where('status', 1);
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

    public function getInspectionInspectors(Request $request)
    {
        $inspection_id = $request->input('inspection_id');
        try {
            $qry = DB::table('inspection_inspectors as t1')
                ->join('users as t2', 't1.inspector_id', '=', 't2.id')
                ->where('t1.inspection_id', $inspection_id)
                ->select(DB::raw("t1.*,CONCAT_WS(' ',decrypt(t2.first_name),decrypt(t2.last_name)) as inspector_name"));
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

    public function getInspectorsList(Request $request)
    {
        $inspection_id = $request->input('inspection_id');
        try {
            $qry = DB::table('users as t1')
                ->whereNotIn('t1.id', function ($query) use ($inspection_id) {
                    $query->select(DB::raw('t2.inspector_id'))
                        ->from('inspection_inspectors as t2')
                        ->where('t2.inspection_id', $inspection_id);
                })
                ->select(DB::raw("t1.id,decrypt(t1.email) as email_address,
                CONCAT_WS(' ',decrypt(t1.first_name),decrypt(t1.last_name)) as inspector_name"));
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

    public function saveInspectionInspectors(Request $request)
    {
        $selected = $request->input('selected');
        $selected = json_decode($selected);
        try {
            $insert_params = convertStdClassObjToArray($selected);
            DB::table('inspection_inspectors')
                ->insert($insert_params);
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

    public function removeInspectionInspectors(Request $request)
    {
        $selected = $request->input('selected');
        $selected = json_decode($selected);
        try {
            DB::table('inspection_inspectors')
                ->whereIn('id', $selected)
                ->delete();
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

    public function saveNewReceivingBaseDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $premise_id = $request->input('premise_id');
        $applicant_id = $request->input('applicant_id');
        $process_id = $request->input('process_id');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $zone_id = $request->input('zone_id');
        $section_id = $request->input('section_id');
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $user_id = $this->user_id;
        $premise_params = array(
            'name' => $request->input('name'),
            'applicant_id' => $applicant_id,
            'section_id' => $section_id,
            //'zone_id' => $zone_id,
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
            $premise_table = 'tra_premises';
            $applications_table = 'tra_premises_applications';

            $where_premise = array(
                'id' => $premise_id
            );
            $where_app = array(
                'id' => $application_id
            );
            $portal_applicant_id = getSingleRecordColValue('wb_trader_account', array('id' => $applicant_id), 'portal_id');
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
                    $app_res = updateRecord($applications_table, $app_details, $where_app, $application_params, $user_id);
                    if ($app_res['success'] == false) {
                        return $app_res;
                    }
                }
                $application_code = $app_details[0]['application_code'];//$app_details->application_code;
                $ref_number = $app_details[0]['reference_no'];//$app_details->reference_no;
                //Premise_edit
                if (recordExists($premise_table, $where_premise)) {
                    $premise_params['dola'] = Carbon::now();
                    $premise_params['altered_by'] = $user_id;
                    $previous_data = getPreviousRecords($premise_table, $where_premise);
                    if ($previous_data['success'] == false) {
                        return $previous_data;
                    }
                    $previous_data = $previous_data['results'];
                    $res = updateRecord($premise_table, $previous_data, $where_premise, $premise_params, $user_id);
                    //update portal also
                    unset($premise_params['created_by']);
                    unset($premise_params['created_on']);
                    unset($premise_params['dola']);
                    unset($premise_params['altered_by']);
                    $premise_params['mis_dola'] = Carbon::now();
                    $premise_params['mis_altered_by'] = $user_id;
                    $premise_params['applicant_id'] = $portal_applicant_id;
                    $portal_premise_id = getSingleRecordColValue('tra_premises', $where_premise, 'portal_id');
                    $portal_db = DB::connection('portal_db');
                    $portal_db->table('wb_premises')
                        ->where('id', $portal_premise_id)
                        ->update($premise_params);
                }
            } else {//Create
                //Premise_create
                $prem_res = insertRecord($premise_table, $premise_params, $user_id);
                if ($prem_res['success'] == false) {
                    return \response()->json($prem_res);
                }
                $premise_id = $prem_res['record_id'];
                //Application_create
                $zone_code = getSingleRecordColValue('par_zones', array('id' => $zone_id), 'zone_code');
                $section_code = getSingleRecordColValue('par_sections', array('id' => $section_id), 'code');
                $codes_array = array(
                    'section_code' => $section_code,
                    'zone_code' => $zone_code
                );
                $view_id = generateApplicationViewID();
                $ref_number = generatePremiseRefNumber(1, $codes_array, date('Y'), $process_id, $zone_id, $user_id);
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
                    'premise_id' => $premise_id,
                    'process_id' => $process_id,
                    'workflow_stage_id' => $workflow_stage_id,
                    'reference_no' => $ref_number,
                    'application_status_id' => $application_status->status_id
                );
                $res = insertRecord($applications_table, $application_params, $user_id);
                $application_id = $res['record_id'];

                //insert registration table
                $reg_params = array(
                    'tra_premise_id' => $premise_id,
                    'status_id' => 1,
                    'created_by' => $user_id
                );
                createInitialRegistrationRecord('registered_premises', $applications_table, $reg_params, $application_id, 'reg_premise_id');
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
            $res['premise_id'] = $premise_id;
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

    public function saveRenewalReceivingBaseDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $premise_id = $request->input('premise_id');
        $applicant_id = $request->input('applicant_id');
        $process_id = $request->input('process_id');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $zone_id = $request->input('zone_id');
        $section_id = $request->input('section_id');
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $user_id = $this->user_id;
        $premise_params = array(
            'name' => $request->input('name'),
            'applicant_id' => $applicant_id,
            'section_id' => $section_id,
            'zone_id' => $zone_id,
            //'premise_reg_no' => $request->input('premise_reg_no'),
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
            $premise_table = 'tra_premises';
            $applications_table = 'tra_premises_applications';

            $where_premise = array(
                'id' => $premise_id
            );
            $where_app = array(
                'id' => $application_id
            );
            $target_premise_params = getTableData($premise_table, $where_premise);
            if (is_null($target_premise_params)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching target premise details!!'
                );
                return \response()->json($res);
            }
            $permit_id = $target_premise_params->permit_id;
            $premise_params['is_temporal'] = 1;
            $premise_params['target_id'] = $premise_id;
            $premise_params['permit_id'] = $permit_id;
            $premise_params['premise_reg_no'] = $target_premise_params->premise_reg_no;
            $premise_params['certificate_issue_date'] = $target_premise_params->certificate_issue_date;
            $premise_params['portal_id'] = $target_premise_params->portal_id;

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
                    updateRecord($applications_table, $app_details, $where_app, $application_params, $user_id);
                }
                $application_code = $app_details[0]['application_code'];//$app_details->application_code;
                $ref_number = $app_details[0]['reference_no'];//$app_details->reference_no;
                $temporal_premise_id = $app_details[0]['premise_id'];
                $where_temp_premise = array(
                    'id' => $temporal_premise_id
                );
                //Premise_edit
                $premise_params['dola'] = Carbon::now();
                $premise_params['altered_by'] = $user_id;
                $previous_data = getPreviousRecords($premise_table, $where_temp_premise);
                if ($previous_data['success'] == false) {
                    return $previous_data;
                }
                $previous_data = $previous_data['results'];
                $res = updateRecord($premise_table, $previous_data, $where_temp_premise, $premise_params, $user_id);
            } else {//Create
                //Premise_create
                $prem_res = insertRecord($premise_table, $premise_params, $user_id);
                if ($prem_res['success'] == false) {
                    return \response()->json($prem_res);
                }
                $temporal_premise_id = $prem_res['record_id'];
                //Application_create
                $zone_code = getSingleRecordColValue('par_zones', array('id' => $zone_id), 'zone_code');
                $section_code = getSingleRecordColValue('par_sections', array('id' => $section_id), 'code');
                $codes_array = array(
                    'section_code' => $section_code,
                    'zone_code' => $zone_code
                );
                $view_id = generateApplicationViewID();
                $ref_number = generatePremiseRefNumber(7, $codes_array, date('Y'), $process_id, $zone_id, $user_id);
                $application_code = generateApplicationCode($sub_module_id, $applications_table);
                $application_status = getApplicationInitialStatus($module_id, $sub_module_id);
                $application_params = array(
                    'applicant_id' => $applicant_id,
                    'view_id' => $view_id,
                    'module_id' => $module_id,
                    'sub_module_id' => $sub_module_id,
                    'zone_id' => $zone_id,
                    'section_id' => $section_id,
                    'application_code' => $application_code,
                    'premise_id' => $temporal_premise_id,
                    'process_id' => $process_id,
                    'workflow_stage_id' => $workflow_stage_id,
                    'reference_no' => $ref_number,
                    'application_status_id' => $application_status->status_id
                );
                $res = insertRecord($applications_table, $application_params, $user_id);
                $application_id = $res['record_id'];
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
            $res['premise_id'] = $premise_id;
            $res['temporal_premise_id'] = $temporal_premise_id;
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

    public function saveRenewalAlterationReceivingBaseDetails(Request $request)
    {
        try {
            $res = array();
            DB::transaction(function () use ($request, &$res) {
                $application_id = $request->input('application_id');
                $init_premise_id = $request->input('premise_id');
                $registered_id = $request->input('main_registered_id');
                $applicant_id = $request->input('applicant_id');
                $process_id = $request->input('process_id');
                $workflow_stage_id = $request->input('workflow_stage_id');
                $zone_id = $request->input('zone_id');
                $section_id = $request->input('section_id');
                $module_id = $request->input('module_id');
                $sub_module_id = $request->input('sub_module_id');
                $user_id = $this->user_id;
                if (!is_numeric($registered_id)) {
                    $res = array(
                        'success' => false,
                        'message' => 'Problem encountered validating your POST data, try again!!'
                    );
                    return \response()->json($res);
                }
                $premise_params = array(
                    'name' => $request->input('name'),
                    'applicant_id' => $applicant_id,
                    'registered_id' => $registered_id,
                    'section_id' => $section_id,
                    //'zone_id' => $zone_id,
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

                $premise_table = 'tra_premises';
                $applications_table = 'tra_premises_applications';

                $where_premise = array(
                    'id' => $init_premise_id
                );
                $where_app = array(
                    'id' => $application_id
                );
                $portal_applicant_id = getSingleRecordColValue('wb_trader_account', array('id' => $applicant_id), 'portal_id');
                if (isset($application_id) && $application_id != "") {//Edit
                    $premise_id = $init_premise_id;
                    //Application_edit
                    $application_params = array(
                        'applicant_id' => $applicant_id,
                        'zone_id' => $zone_id,
                        'reg_premise_id' => $registered_id
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
                    if (recordExists($premise_table, $where_premise)) {
                        $premise_params['dola'] = Carbon::now();
                        $premise_params['altered_by'] = $user_id;
                        $previous_data = getPreviousRecords($premise_table, $where_premise);
                        if ($previous_data['success'] == false) {
                            return $previous_data;
                        }
                        $previous_data = $previous_data['results'];
                        $res = updateRecord($premise_table, $previous_data, $where_premise, $premise_params, $user_id);
                        //update portal also
                        unset($premise_params['created_by']);
                        unset($premise_params['created_on']);
                        unset($premise_params['dola']);
                        unset($premise_params['altered_by']);
                        $premise_params['mis_dola'] = Carbon::now();
                        $premise_params['mis_altered_by'] = $user_id;
                        $premise_params['applicant_id'] = $portal_applicant_id;
                        $portal_premise_id = getSingleRecordColValue('tra_premises', $where_premise, 'portal_id');
                        $portal_db = DB::connection('portal_db');
                        $portal_db->table('wb_premises')
                            ->where('id', $portal_premise_id)
                            ->update($premise_params);
                    }
                } else {//Create
                    $anyOngoingApps = checkForOngoingApplications($registered_id, $applications_table, 'reg_premise_id', $process_id);
                    if ($anyOngoingApps['exists'] == true) {
                        $res = array(
                            'success' => false,
                            'message' => 'There is an ongoing application of the same nature on the selected Premise with reference number ' . $anyOngoingApps['ref_no']
                        );
                        return \response()->json($res);
                    }
                    $init_premise_params = getTableData($premise_table, $where_premise);
                    if (is_null($init_premise_params)) {
                        $res = array(
                            'success' => false,
                            'message' => 'Problem encountered while fetching target premise details!!'
                        );
                        return \response()->json($res);
                    }
                    $premise_params['permit_id'] = $init_premise_params->permit_id;
                    $premise_params['premise_reg_no'] = $init_premise_params->premise_reg_no;
                    $premise_params['certificate_issue_date'] = $init_premise_params->certificate_issue_date;
                    $premise_params['portal_id'] = $init_premise_params->portal_id;
                    //Premise_create
                    $premise_params['init_premise_id'] = $init_premise_id;
                    $prem_res = insertRecord($premise_table, $premise_params, $user_id);
                    if ($prem_res['success'] == false) {
                        return \response()->json($prem_res);
                    }
                    $premise_id = $prem_res['record_id'];
                    //copy premise personnel details and business details
                    $init_personnelDetails = DB::table('tra_premises_personnel as t1')
                        ->select(DB::raw("t1.personnel_id,t1.temp_premise_id,t1.position_id,t1.personnel_qualification_id,t1.start_date,t1.end_date,t1.status_id,t1.portal_id,t1.is_temporal,
                    $user_id as created_by,t1.premise_id as init_premise_id,$premise_id as premise_id"))
                        ->where('premise_id', $init_premise_id)
                        ->get();
                    $init_personnelDetails = convertStdClassObjToArray($init_personnelDetails);
                    $init_businessDetails = DB::table('tra_premises_otherdetails as t2')
                        ->select(DB::raw("t2.temp_premise_id,t2.business_type_id,t2.business_type_detail_id,t2.portal_id,t2.is_temporal,
                    $user_id as created_by,t2.premise_id as init_premise_id,$premise_id as premise_id"))
                        ->where('premise_id', $init_premise_id)
                        ->get();
                    $init_businessDetails = convertStdClassObjToArray($init_businessDetails);
                    DB::table('tra_premises_personnel')
                        ->insert($init_personnelDetails);
                    DB::table('tra_premises_otherdetails')
                        ->insert($init_businessDetails);
                    //Application_create
                    $zone_code = getSingleRecordColValue('par_zones', array('id' => $zone_id), 'zone_code');
                    $section_code = getSingleRecordColValue('par_sections', array('id' => $section_id), 'code');
                    $codes_array = array(
                        'section_code' => $section_code,
                        'zone_code' => $zone_code
                    );
                    if ($sub_module_id == 2) {
                        $ref_id = 7;//renewal
                    } else {
                        $ref_id = 8;//alteration
                    }
                    $view_id = generateApplicationViewID();
                    $ref_number = generatePremiseRefNumber($ref_id, $codes_array, date('Y'), $process_id, $zone_id, $user_id);
                    $application_code = generateApplicationCode($sub_module_id, $applications_table);
                    $application_status = getApplicationInitialStatus($module_id, $sub_module_id);
                    $application_params = array(
                        'applicant_id' => $applicant_id,
                        'view_id' => $view_id,
                        'module_id' => $module_id,
                        'reg_premise_id' => $registered_id,
                        'sub_module_id' => $sub_module_id,
                        'section_id' => $section_id,
                        'application_code' => $application_code,
                        'zone_id' => $zone_id,
                        'premise_id' => $premise_id,
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
                $res['premise_id'] = $premise_id;
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

    public function prepareNewPremiseReceivingStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table('tra_premises_applications as t1')
                ->join('tra_premises as t2', 't1.premise_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->leftJoin('tra_application_invoices as t4', function ($join) use ($application_code) {
                    $join->on('t1.id', '=', 't4.application_id')
                        ->on('t4.application_code', '=', 't4.application_code');
                })
                ->leftJoin('tra_approval_recommendations as t5', 't2.permit_id', '=', 't5.id')
                ->select('t1.*', 't1.id as active_application_id', 't2.name as premise_name',
                    't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't2.*', 't4.id as invoice_id', 't4.invoice_no', 't5.permit_no')
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

    public function prepareRenewalPremiseReceivingStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table('tra_premises_applications as t1')
                ->join('tra_premises as t2', 't1.premise_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->leftJoin('tra_application_invoices as t4', function ($join) use ($application_code) {
                    $join->on('t1.id', '=', 't4.application_id')
                        ->on('t4.application_code', '=', 't4.application_code');
                })
                ->join('tra_approval_recommendations as t5', 't2.permit_id', '=', 't5.id')
                ->select('t1.*', 't1.id as active_application_id', 't2.name as premise_name',
                    't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't2.*', 't4.id as invoice_id', 't4.invoice_no', 't2.id as temporal_premise_id', 't2.target_id as premise_id', 't5.permit_no')
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

    public function prepareNewPremiseInvoicingStage(Request $request)
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
                ->join('tra_premises as t4', 't1.premise_id', '=', 't4.id')
                ->select(DB::raw("t1.applicant_id,t1.premise_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details, t3.id as invoice_id, t3.invoice_no,
                    t3.isLocked,t3.paying_currency_id,t1.section_id,t1.module_id,CONCAT_WS(',',t4.name,t4.postal_address) as premise_details,t1.is_fast_track"))
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

    public function prepareRenewalPremiseInvoicingStage(Request $request)
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
                ->join('tra_premises as t4', 't1.premise_id', '=', 't4.id')
                ->select(DB::raw("t1.applicant_id,t4.target_id as premise_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details, t3.id as invoice_id, t3.invoice_no,
                t1.section_id,t1.module_id,CONCAT_WS(',',t4.name,t4.postal_address) as premise_details"))
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

    public function prepareNewPremisePaymentStage(Request $request)
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
                ->join('tra_premises as t4', 't1.premise_id', '=', 't4.id')
                ->select(DB::raw("t1.applicant_id,t1.premise_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details, t3.id as invoice_id, t3.invoice_no,
                t1.section_id,t1.module_id,CONCAT_WS(',',t4.name,t4.postal_address) as premise_details"))
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

    public function prepareRenewalPremisePaymentStage(Request $request)
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
                ->join('tra_premises as t4', 't1.premise_id', '=', 't4.id')
                ->select(DB::raw("t1.applicant_id,t4.target_id as premise_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details, t3.id as invoice_id, t3.invoice_no,
                t1.section_id,t1.module_id,CONCAT_WS(',',t4.name,t4.postal_address) as premise_details"))
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

    public function prepareNewPremiseEvaluationStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t2', 't1.applicant_id', '=', 't2.id')
                ->join('tra_premises as t3', 't1.premise_id', '=', 't3.id')
                ->select(DB::raw("t1.applicant_id,t1.premise_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details,
                t1.section_id,t1.module_id,CONCAT_WS(',',t3.name,t3.postal_address) as premise_details"))
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

    public function prepareRenewalPremiseEvaluationStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t2', 't1.applicant_id', '=', 't2.id')
                ->join('tra_premises as t3', 't1.premise_id', '=', 't3.id')
                ->select(DB::raw("t1.applicant_id,t3.target_id as premise_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details,
                t1.section_id,t1.module_id,CONCAT_WS(',',t3.name,t3.postal_address) as premise_details"))
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

    public function getOnlineApplicationQueries(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $where = array(
            'application_id' => $application_id,
            'application_code' => $application_code
        );
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('tra_online_queries')
                ->where($where);
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

    public function saveOnlineQueries(Request $request)
    {
        $post_data = $request->input();
        $id = $post_data['id'];
        try {
            $portal_db = DB::connection('portal_db');
            if (isset($id) && $id != '') {
                unset($post_data['id']);
                $post_data['mis_dola'] = Carbon::now();
                $post_data['mis_altered_by'] = $this->user_id;
                $portal_db->table('tra_online_queries')
                    ->where('id', $id)
                    ->update($post_data);
            } else {
                $post_data['mis_created_on'] = Carbon::now();
                $post_data['mis_created_by'] = $this->user_id;
                $portal_db->table('tra_online_queries')
                    ->insert($post_data);
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

    public function uploadApplicationFile(Request $req)
    {
        $application_id = $req->input('application_id');
        $application_code = $req->input('application_code');
        $workflow_stage_id = $req->input('workflow_stage_id');
        $description = $req->input('description');
        $user_id = $this->user_id;
        $res = array();
        try {
            if ($req->hasFile('uploaded_doc')) {
                $file = $req->file('uploaded_doc');
                $origFileName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileSize = $file->getClientSize();
                $folder = '\resources\uploads';
                $destination = getcwd() . $folder;
                $savedName = str_random(5) . time() . '.' . $extension;
                $file->move($destination, $savedName);
                $params = array(
                    'application_id' => $application_id,
                    'application_code' => $application_code,
                    'workflow_stage_id' => $workflow_stage_id,
                    'initial_filename' => $origFileName,
                    'savedname' => $savedName,
                    'filesize' => formatBytes($fileSize),
                    'filetype' => $extension,
                    'server_filepath' => $destination,
                    'server_folder' => $folder,
                    'description' => $description,
                    'created_on' => Carbon::now(),
                    'created_by' => \Auth::user()->id
                );
                $res = insertRecord('tra_premiseapplications_uploads', $params, $user_id);
                if ($res['success'] == true) {
                    $res = array(
                        'success' => true,
                        'message' => 'File uploaded successfully!!'
                    );
                }
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

    public function syncAlterationAmendmentFormParts(Request $request)
    {
        $selected = $request->input('selected');
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $form_id = $request->input('form_id');
        $selected_ids = json_decode($selected);
        $where = array(
            'application_id' => $application_id,
            'application_code' => $application_code
        );
        try {
            DB::transaction(function () use ($selected_ids, $application_id, $application_code, $form_id, $where) {
                $params = array();
                foreach ($selected_ids as $selected_id) {
                    $params[] = array(
                        'application_id' => $application_id,
                        'application_code' => $application_code,
                        'form_id' => $form_id,
                        'field_id' => $selected_id,
                        'created_by' => $this->user_id
                    );
                }
                DB::table('tra_alt_formparts_amendments')
                    ->where($where)
                    ->delete();
                DB::table('tra_alt_formparts_amendments')
                    ->insert($params);
            }, 5);
            $res = array(
                'success' => true,
                'message' => 'Request synced successfully!!'
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

    public function syncAlterationAmendmentOtherParts(Request $request)
    {
        $selected = $request->input('selected');
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $selected_ids = json_decode($selected);
        $where = array(
            'application_id' => $application_id,
            'application_code' => $application_code
        );
        try {
            DB::transaction(function () use ($selected_ids, $application_id, $application_code, $where) {
                $params = array();
                foreach ($selected_ids as $selected_id) {
                    $params[] = array(
                        'application_id' => $application_id,
                        'application_code' => $application_code,
                        'part_id' => $selected_id,
                        'created_by' => $this->user_id
                    );
                }
                DB::table('tra_alt_otherparts_amendments')
                    ->where($where)
                    ->delete();
                DB::table('tra_alt_otherparts_amendments')
                    ->insert($params);
            }, 5);
            $res = array(
                'success' => true,
                'message' => 'Request synced successfully!!'
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

    public function getPremiseComparisonDetails(Request $request)
    {
        $premise_id = $request->input('premise_id');
        $init_premise_id = $request->input('init_premise_id');
        try {
            $qry1 = DB::table('tra_premises as t1')
                ->select('t1.*', 't1.id as premise_id')
                ->where('id', $premise_id);
            $qry2 = DB::table('tra_premises as t2')
                ->select('t2.*', 't2.id as premise_id')
                ->where('id', $init_premise_id);
            $amendedDetails = $qry1->first();
            $initialDetails = $qry2->first();
            $res = array(
                'success' => true,
                'amendedDetails' => $amendedDetails,
                'initialDetails' => $initialDetails,
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

    public function getApplicationUploadedDocs(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $workflow_stage_id = $request->input('workflow_stage_id');
        try {
            $qry = DB::table('tra_premiseapplications_uploads as t1')
                ->leftJoin('wf_workflow_stages as t2', 't1.workflow_stage_id', '=', 't2.id')
                ->select('t1.*', 't2.name as stage_name')
                ->where('t1.application_id', $application_id)
                ->where('t1.application_code', $application_code);
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
        return response()->json($res);
    }

    public function getApplicationChecklistQueries(Request $request)
    {
        $item_resp_id = $request->input('item_resp_id');
        try {
            $qry = DB::table('checklistitems_queries as t1')
                ->join('par_query_statuses as t2', 't1.status', '=', 't2.id')
                ->leftJoin('checklistitems_queryresponses as t3', function ($query) {
                    $query->on('t1.id', '=', 't3.query_id')
                        ->whereRaw('t3.id = (select MAX(a2.id) from checklistitems_queryresponses as a2 join checklistitems_queries as u2 on u2.id = a2.query_id)');
                })
                ->select('t1.*', 't2.name as query_status', 't3.response as last_response')
                ->where('item_resp_id', $item_resp_id);
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

    public function saveNewAuditingChecklistDetails(Request $request)
    {
        $screening_details = $request->input('screening_details');
        $screening_details = json_decode($screening_details);
        $table_name = 'checklistitems_responses';
        $user_id = $this->user_id;
        try {
            foreach ($screening_details as $screening_detail) {
                $item_resp_id = $screening_detail->item_resp_id;
                if (isset($item_resp_id) && $item_resp_id != '') {
                    $where = array(
                        'id' => $item_resp_id
                    );
                    $update_params = array(
                        'auditor_comment' => $screening_detail->auditor_comment,
                        'audit_created_on' => Carbon::now(),
                        'audit_created_by' => $user_id
                    );
                    $prev_data = getPreviousRecords($table_name, $where);
                    updateRecord($table_name, $prev_data['results'], $where, $update_params, $user_id);
                }
            }
            $res = array(
                'success' => true,
                'message' => 'Auditing details saved successfully!!'
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

    public function prepareNewFoodOnlineReceivingStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('aon_pplicaticode');
        $table_name = $request->input('table_name');
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_premises_applications as t1')
                ->join('wb_premises as t2', 't1.premise_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.trader_id', '=', 't3.id')
                ->select('t1.*', 't1.id as active_application_id', 't2.name as premise_name',
                    't3.id as applicant_id', 't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't2.*', 't2.zone_id')
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

}
