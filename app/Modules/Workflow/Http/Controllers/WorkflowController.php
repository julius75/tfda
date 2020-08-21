<?php

namespace App\Modules\Workflow\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Modules\PremiseRegistration\Traits\PremiseRegistrationTrait;
use App\Modules\GmpApplications\Traits\GmpApplicationsTrait;
use App\Modules\ProductRegistration\Traits\ProductsRegistrationTrait;
use App\Modules\ClinicalTrial\Traits\ClinicalTrialTrait;
use App\Modules\Surveillance\Traits\SurveillanceTrait;

class WorkflowController extends Controller
{
    protected $user_id;
    use PremiseRegistrationTrait;
    use GmpApplicationsTrait;
    use ProductsRegistrationTrait;
    use ClinicalTrialTrait;
    use SurveillanceTrait;

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
        return view('workflow::index');
    }

    public function saveWorkflowCommonData(Request $req)
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
            $table_data = $post_data;
            //add extra params
            $table_data['created_on'] = Carbon::now();
            $table_data['created_by'] = $user_id;
            $where = array(
                'id' => $id
            );
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
                    $res['record_id'] = $id;
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

    public function getWorkflowParamFromModel(Request $request)
    {
        $model_name = $request->input('model_name');
        $strict_mode = $request->input('strict_mode');
        try {
            $model = 'App\\Modules\\Workflow\\Entities\\' . $model_name;
            if (isset($strict_mode) && $strict_mode == 1) {
                $results = $model::where('is_enabled', 1)
                    ->get()
                    ->toArray();
            } else {
                $results = $model::all()
                    ->toArray();
            }
            //$results = decryptArray($results);
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

    public function getSystemSubModules(Request $request)
    {
        $module_id = $request->input('module_id');
        try {
            $qry = Db::table('sub_modules as t1');
            if (isset($module_id) && $module_id != '') {
                $qry->where('module_id', $module_id);
            }
            $results = $qry->get();
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

    public function getTfdaSystemProcesses()
    {
        try {
            $qry = DB::table('wf_tfdaprocesses as t1')
                ->join('modules as t2', 't1.module_id', '=', 't2.id')
                ->join('sub_modules as t3', 't1.sub_module_id', '=', 't3.id')
                ->join('par_sections as t4', 't1.section_id', '=', 't4.id')
                ->leftJoin('wf_workflows as t5', 't1.workflow_id', '=', 't5.id')
                ->select('t1.*', 't2.name as module', 't3.name as submodule', 't4.name as section', 't5.name as workflow');
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

    public function softDeleteWorkflowRecord(Request $req)
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

    public function undoWorkflowSoftDeletes(Request $req)
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

    public function deleteWorkflowRecord(Request $req)
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

    public function getWorkflowStages(Request $request)
    {
        $workflow_id = $request->input('workflow_id');
        try {
            $qry = DB::table('wf_workflow_stages as t1')
                //->join('wf_workflow_statuses as t2', 't1.application_status', '=', 't2.id')
                ->join('wf_workflowstages_statuses as t3', 't1.stage_status', '=', 't3.id')
                ->leftJoin('wf_workflow_interfaces as t4', 't1.interface_id', '=', 't4.id')
                ->select('t1.*', 't3.name as stage_status_name', 't4.name as interface_name')
                ->where('workflow_id', $workflow_id)
                ->orderBy('t1.order_no');
            $results = $qry->get();
            foreach ($results as $key => $result) {
                $results[$key]->groups_string = $this->getStageGroupsString($result->id);
                //$results[$key]->groups = $this->getStageGroupsArray($result->id);
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
        return response()->json($res);
    }

    public function getStageGroupsString($stage_id)
    {
        $str = '<ul>';
        $qry = DB::table('wf_stages_groups as t1')
            ->join('par_groups as t2', 't1.group_id', '=', 't2.id')
            ->select('t1.id', 't2.name')
            //->select(DB::raw("GROUP_CONCAT(t2.name) as groups_string"))
            ->where('stage_id', $stage_id);
        $results = $qry->get();
        foreach ($results as $result) {
            $str .= '<li>' . $result->name . '</li>';
        }
        $str .= '</ul>';
        return $str;
        //return $results[0]->groups_string;
    }

    public function getStageGroupsArray($stage_id)
    {
        $qry = DB::table('wf_stages_groups as t1')
            ->select('t1.group_id')
            ->where('stage_id', $stage_id);
        $results = $qry->get();
        $results = convertStdClassObjToArray($results);
        $results = convertAssArrayToSimpleArray($results, 'group_id');
        return $results;
    }

    public function getWorkflowAssociatedMenus(Request $request)
    {
        $workflow_id = $request->input('workflow_id');
        try {
            $qry = DB::table('wf_menu_workflows as t1')
                ->join('par_menus as t2', 't1.menu_id', '=', 't2.id')
                ->select('t1.*', 't2.name', 't2.viewType')
                ->where('t1.workflow_id', $workflow_id);
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

    public function getSubmissionWorkflowStages(Request $request)
    {
        $process_id = $request->input('process_id');
        try {
            $qry = DB::table('wf_tfdaprocesses as t1')
                ->join('wf_workflows as t2', 't1.workflow_id', '=', 't2.id')
                ->join('wf_workflow_stages as t3', 't2.id', '=', 't3.workflow_id')
                ->select('t3.*')
                ->where('t1.id', $process_id);
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

    public function getWorkflowActions(Request $request)
    {
        $stage_id = $request->input('stage_id');
        $application_status_id = $request->input('application_status_id');
        $is_status_tied = $request->input('is_status_tied');;
        try {
            $qry = DB::table('wf_workflow_actions as t1')
                ->join('wf_workflow_stages as t2', 't1.stage_id', '=', 't2.id')
                ->join('wf_workflowaction_types as t3', 't1.action_type_id', '=', 't3.id')
                ->select('t1.*', 't2.name as stage_name', 't3.name as action_type')
                ->where('stage_id', $stage_id);
            if (isset($is_status_tied) && $is_status_tied == 1) {
                $qry->where('application_status_id', $application_status_id)
                    ->where('is_status_tied', '=', 1);
            } else {
                $qry->where(function ($query) {
                    $query->whereNull('is_status_tied')
                        ->orWhere('is_status_tied', '=', 2);
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
        return response()->json($res);
    }

    public function getWorkflowTransitions(Request $request)
    {
        $workflow_id = $request->input('workflow_id');
        try {
            $qry = DB::table('wf_workflow_transitions as t1')
                ->join('wf_workflow_stages as t2', 't1.stage_id', '=', 't2.id')
                ->join('wf_workflow_stages as t3', 't1.nextstage_id', '=', 't3.id')
                ->join('wf_workflow_actions as t4', 't1.action_id', '=', 't4.id')
                ->leftJoin('par_system_statuses as t5', 't1.application_status_id', '=', 't5.id')
                ->select('t1.*', 't2.name as stage_name', 't3.name as nextstage_name', 't4.name as action_name', 't5.name as application_status')
                ->where('t1.workflow_id', $workflow_id)
                ->orderBy('t2.order_no');
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

    public function getProcessWorkflowStages(Request $request)
    {
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $section_id = $request->input('section_id');
        try {
            //get workflow id
            $where = array(
                'module_id' => $module_id,
                'sub_module_id' => $sub_module_id,
                'section_id' => $section_id
            );
            $workflow_id = DB::table('wf_tfdaprocesses')
                ->where($where)
                ->value('workflow_id');
            $qry = DB::table('wf_workflow_stages as t1')
                //->join('wf_workflow_statuses as t2', 't1.application_status', '=', 't2.id')
                ->join('wf_workflowstages_statuses as t3', 't1.stage_status', '=', 't3.id')
                ->select('t1.*', 't3.name as stage_status_name')
                ->where('workflow_id', $workflow_id);
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

    public function getBasicWorkflowDetails(Request $request)
    {
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $section_id = $request->input('section_id');
        try {
            //get workflow id
            $where = array(
                't1.module_id' => $module_id,
                't1.sub_module_id' => $sub_module_id,
                't1.section_id' => $section_id
            );
            $qry = DB::table('wf_tfdaprocesses as t1')
                ->join('wf_workflows as t2', 't1.workflow_id', '=', 't2.id')
                ->select('t1.workflow_id', 't2.name')
                ->where($where);
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
        return response()->json($res);
    }

    public function getInitialWorkflowDetails(Request $request)
    {
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $section_id = $request->input('section_id');
        try {
            //get workflow id
            $where = array(
                't1.module_id' => $module_id,
                't1.sub_module_id' => $sub_module_id,
                't1.section_id' => $section_id
            );
            $qry = DB::table('wf_tfdaprocesses as t1')
                ->join('wf_workflows as t2', 't1.workflow_id', '=', 't2.id')
                ->join('wf_workflow_stages as t3', function ($join) {
                    $join->on('t2.id', '=', 't3.workflow_id')
                        ->on('t3.stage_status', '=', DB::raw(1));
                })
                ->join('wf_workflow_interfaces as t4', 't3.interface_id', '=', 't4.id')
                ->select('t4.viewtype', 't1.id as processId', 't1.name as processName', 't3.name as initialStageName', 't3.id as initialStageId');
            $qry->where($where);
            $results = $qry->first();
            //initial status details
            $statusDetails = getApplicationInitialStatus($module_id, $sub_module_id);
            $results->initialAppStatus = $statusDetails->name;
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

    public function getAllWorkflowDetails(Request $request)
    {
        $process_id = $request->input('process_id');
        $stage_id = $request->input('workflow_stage');
        try {
            //get workflow id
            $where = array(
                't1.id' => $process_id,
                't3.id' => $stage_id
            );
            $qry = DB::table('wf_tfdaprocesses as t1')
                ->join('wf_workflows as t2', 't1.workflow_id', '=', 't2.id')
                ->join('wf_workflow_stages as t3', 't3.workflow_id', '=', 't2.id')
                ->join('wf_workflow_interfaces as t4', 't3.interface_id', '=', 't4.id')
                ->select('t1.workflow_id', 't2.name', 't4.viewtype', 't1.id as processId', 't1.name as processName', 't3.name as initialStageName', 't3.id as initialStageId');
            $qry->where($where);
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
        return response()->json($res);
    }

    public function getApplicationSubmissionDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wf_tfdaprocesses as t2', 't1.process_id', '=', 't2.id')
                ->join('wf_workflow_stages as t3', 't1.workflow_stage_id', 't3.id')
                ->join('par_system_statuses as t4', 't1.application_status_id', 't4.id')
                ->select('t1.id', 't1.reference_no', 't1.process_id as processId', 't1.workflow_stage_id as currentStageId', 't2.name as processName', 't3.name as currentStageName',
                    't4.name as applicationStatus', 't4.id as applicationStatusId', 't2.module_id', 't2.sub_module_id', 't2.section_id')
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
        return response()->json($res);
    }

    public function getOnlineApplicationSubmissionDetails(Request $request)
    {
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $section_id = $request->input('section_id');
        $is_manager_query = $request->input('is_manager_query');
        $where = array(
            't1.module_id' => $module_id,
            't1.sub_module_id' => $sub_module_id,
            't1.section_id' => $section_id
        );
        try {
            $qry = DB::table('wf_tfdaprocesses as t1');
            if (isset($is_manager_query) && $is_manager_query == 1) {
                $qry->join('wf_workflow_stages as t2', function ($join) {
                    $join->on('t2.workflow_id', '=', 't1.workflow_id')
                        ->on('t2.is_manager_query', '=', DB::raw(1));
                });
            } else {
                $qry->join('wf_workflow_stages as t2', function ($join) {
                    $join->on('t2.workflow_id', '=', 't1.workflow_id')
                        ->on('t2.stage_status', '=', DB::raw(1));
                });
            }
            $qry->select('t1.id as processId', 't2.id as currentStageId', 't1.name as processName', 't2.name as currentStageName',
                't1.module_id', 't1.sub_module_id', 't1.section_id')
                ->where($where);

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
        return response()->json($res);
    }

    public function saveWorkflowStage(Request $request)
    {
        try {
            $user_id = \Auth::user()->id;
            $post_data = $request->all();
            $table_name = $post_data['table_name'];
            $id = $post_data['id'];
            //unset unnecessary values
            unset($post_data['_token']);
            unset($post_data['table_name']);
            unset($post_data['model']);
            unset($post_data['id']);
            unset($post_data['groups']);
            $table_data = $post_data;
            //add extra params
            $table_data['created_on'] = Carbon::now();
            $table_data['created_by'] = $user_id;
            $where = array(
                'id' => $id
            );
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
                    $res['record_id'] = $id;
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

    public function saveWorkflowTransition(Request $request)
    {
        try {
            $user_id = \Auth::user()->id;
            $post_data = $request->all();
            $table_name = $post_data['table_name'];
            $id = $post_data['id'];
            $workflow_id = $post_data['workflow_id'];
            $stage = $post_data['stage_id'];
            $action = $post_data['action_id'];
            $nextstage = $post_data['nextstage_id'];
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
                    $res['record_id'] = $id;
                }
            } else {
                $dup_check = array(
                    'workflow_id' => $workflow_id,
                    'stage_id' => $stage,
                    'action_id' => $action
                    //'nextstage_id' => $nextstage
                );
                $count = DB::table('wf_workflow_transitions')
                    ->where($dup_check)
                    ->count();
                if ($count > 0) {
                    $res = array(
                        'success' => false,
                        'message' => 'This transition has been added already, please edit!!'
                    );
                    return response()->json($res);
                }
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

    public function getProcessApplicableChecklistCategories(Request $request)
    {
        $process_id = $request->input('process_id');
        $workflow_stage = $request->input('workflow_stage');
        try {
            $qry = DB::table('par_checklist_categories as t1')
                ->leftJoin('tra_proc_applicable_checklists as t2', function ($join) use ($process_id, $workflow_stage) {
                    $join->on('t2.checklist_category_id', '=', 't1.id')
                        ->on('t2.process_id', '=', DB::raw($process_id))
                        ->on('t2.stage_id', '=', DB::raw($workflow_stage));
                })
                ->select('t1.*', 't2.id as applicable_checklist');
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

    public function getProcessApplicableDocumentTypes(Request $request)
    {
        $process_id = $request->input('process_id');
        $workflow_stage = $request->input('workflow_stage');
        try {
            $qry = DB::table('par_document_types as t1')
                ->leftJoin('tra_proc_applicable_doctypes as t2', function ($join) use ($process_id, $workflow_stage) {
                    $join->on('t2.doctype_id', '=', 't1.id')
                        ->on('t2.process_id', '=', DB::raw($process_id))
                        ->on('t2.stage_id', '=', DB::raw($workflow_stage));
                })
                ->select('t1.*', 't2.id as applicable_doctype');
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

    public function getProcessEditableFormFields(Request $request)
    {
        $process_id = $request->input('process_id');
        $form_id = $request->input('form_id');
        try {
            $qry = DB::table('par_key_form_fields as t1')
                ->join('par_form_field_types as t2', 't1.field_type_id', '=', 't2.id')
                ->leftJoin('tra_process_form_auth as t3', function ($join) use ($process_id) {
                    $join->on('t3.field_id', '=', 't1.id')
                        ->on('t3.process_id', '=', DB::raw($process_id));
                })
                ->select('t1.*', 't2.name as field_type', 't3.id as isEditable')
                ->where('t1.form_id', $form_id);
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

    public function getProcessEditableOtherParts(Request $request)
    {
        $process_id = $request->input('process_id');
        $module_id = $request->input('module_id');
        try {
            $qry = DB::table('par_alteration_setup as t1')
                ->leftJoin('tra_process_otherparts_auth as t2', function ($join) use ($process_id) {
                    $join->on('t1.id', '=', 't2.part_id')
                        ->where('t2.process_id', '=', $process_id);
                })
                ->select('t1.*', 't2.id as isEditable')
                ->where('t1.is_form_tied', '=', 2)
                ->where('t1.module_id', $module_id);
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

    public function getProcessApplicableChecklistTypes(Request $request)
    {
        $process_id = $request->input('process_id');
        $workflow_stage = $request->input('workflow_stage');
        $is_approval = $request->input('is_approval');
        $target_stage = $request->input('target_stage');
        if (isset($is_approval) && $is_approval == 1) {
            $workflow_stage = $target_stage;
        }
        $where = array(
            'process_id' => $process_id,
            'stage_id' => $workflow_stage
        );
        try {
            //module_id, sub_module_id and section_id
            $where2 = DB::table('wf_tfdaprocesses')
                ->select('module_id', 'sub_module_id', 'section_id')
                ->where('id', $process_id)
                ->get();
            $where2 = convertStdClassObjToArray($where2);
            //get applicable checklist categories
            $qry1 = DB::table('tra_proc_applicable_checklists')
                ->select('checklist_category_id')
                ->where($where);
            $checklist_categories = $qry1->get();
            $checklist_categories = convertStdClassObjToArray($checklist_categories);
            $checklist_categories = convertAssArrayToSimpleArray($checklist_categories, 'checklist_category_id');
            //get applicable checklist types

            $qry2 = DB::table('par_checklist_types as t1')
                ->where($where2[0])
                ->whereIn('checklist_category_id', $checklist_categories);
            $results = $qry2->get();

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

    public function getProcessApplicableChecklistItems(Request $request)
    {
        $checklist_type = $request->input('checklist_type');
        $checklist_category_id = $request->input('checklist_category_id');

        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $is_previous = $request->input('is_previous');
        $process_id = $request->input('process_id');
        $workflow_stage = $request->input('workflow_stage');
        $where = array(
            'process_id' => $process_id,
            'stage_id' => $workflow_stage
        );
        try {
            //module_id, sub_module_id and section_id
            $where2 = DB::table('wf_tfdaprocesses')
                ->select('module_id', 'sub_module_id', 'section_id')
                ->where('id', $process_id)
                ->get();
            $where2 = convertStdClassObjToArray($where2);
            //get applicable checklist categories
            $qry1 = DB::table('tra_proc_applicable_checklists')
                ->select('checklist_category_id')
                ->where($where);
            $checklist_categories = $qry1->get();
            $checklist_categories = convertStdClassObjToArray($checklist_categories);
            $checklist_categories = convertAssArrayToSimpleArray($checklist_categories, 'checklist_category_id');
            //get applicable checklist types
            $qry2 = DB::table('par_checklist_types as t1')
                ->select('t1.id')
                ->where($where2[0])
                ->whereIn('checklist_category_id', $checklist_categories);
            $checklist_types = $qry2->get();
            $checklist_types = convertStdClassObjToArray($checklist_types);
            $checklist_types = convertAssArrayToSimpleArray($checklist_types, 'id');

            $qry = DB::table('par_checklist_items as t1')
                ->leftJoin('checklistitems_responses as t2', function ($join) use ($application_code, $is_previous) {
                    $join->on('t2.checklist_item_id', '=', 't1.id')
                        ->where('t2.application_code', $application_code);
                    //->on('t2.application_code', '=', DB::raw($application_code));
                    if (isset($is_previous) && $is_previous != '') {
                        $join->where('t2.status', 0);
                    } else {
                        $join->where('t2.status', 1);
                    }
                })
                ->join('par_checklist_types as t3', 't1.checklist_type_id', '=', 't3.id')
                ->select('t1.*', 't2.id as item_resp_id', 't2.pass_status', 't2.comment', 't2.auditor_comment', 't3.name as checklist_type');
            if (isset($checklist_type) && $checklist_type != '') {
                $qry->where('t1.checklist_type_id', $checklist_type);
            } else {
                $qry->whereIn('t1.checklist_type_id', $checklist_types);
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

    public function syncProcessApplicableChecklistCategories(Request $request)
    {
        $selected = $request->input('selected');
        $process_id = $request->input('process_id');
        $stage_id = $request->input('stage_id');
        $selected_ids = json_decode($selected);
        $where = array(
            'process_id' => $process_id,
            'stage_id' => $stage_id
        );
        try {
            DB::transaction(function () use ($selected_ids, $process_id, $stage_id, $where) {
                $params = array();
                foreach ($selected_ids as $selected_id) {
                    $params[] = array(
                        'process_id' => $process_id,
                        'stage_id' => $stage_id,
                        'checklist_category_id' => $selected_id,
                        'created_by' => $this->user_id
                    );
                }
                DB::table('tra_proc_applicable_checklists')
                    ->where($where)
                    ->delete();
                DB::table('tra_proc_applicable_checklists')
                    ->insert($params);
            }, 5);
            $res = array(
                'success' => true,
                'message' => 'Changes synced successfully!!'
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

    public function syncProcessApplicableDocumentTypes(Request $request)
    {
        $selected = $request->input('selected');
        $process_id = $request->input('process_id');
        $stage_id = $request->input('stage_id');
        $selected_ids = json_decode($selected);
        $where = array(
            'process_id' => $process_id,
            'stage_id' => $stage_id
        );
        try {
            DB::transaction(function () use ($selected_ids, $process_id, $stage_id, $where) {
                $params = array();
                foreach ($selected_ids as $selected_id) {
                    $params[] = array(
                        'process_id' => $process_id,
                        'stage_id' => $stage_id,
                        'doctype_id' => $selected_id,
                        'created_by' => $this->user_id
                    );
                }
                DB::table('tra_proc_applicable_doctypes')
                    ->where($where)
                    ->delete();
                DB::table('tra_proc_applicable_doctypes')
                    ->insert($params);
            }, 5);
            $res = array(
                'success' => true,
                'message' => 'Changes synced successfully!!'
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

    public function syncProcessAmendableParts(Request $request)
    {
        $selected = $request->input('selected');
        $selected_parts = $request->input('selected_parts');
        $process_id = $request->input('process_id');
        $form_id = $request->input('form_id');
        $selected_ids = json_decode($selected);
        $selected_part_ids = json_decode($selected_parts);
        $where = array(
            'process_id' => $process_id,
            'form_id' => $form_id
        );
        try {
            DB::transaction(function () use ($selected_ids, $selected_part_ids, $process_id, $form_id, $where) {
                $params = array();
                $params2 = array();
                if (count($selected_ids) > 0) {
                    foreach ($selected_ids as $selected_id) {
                        $params[] = array(
                            'process_id' => $process_id,
                            'form_id' => $form_id,
                            'field_id' => $selected_id,
                            'created_by' => $this->user_id
                        );
                    }
                }
                if (count($selected_part_ids) > 0) {
                    foreach ($selected_part_ids as $selected_part_id) {
                        $params2[] = array(
                            'process_id' => $process_id,
                            'part_id' => $selected_part_id,
                            'created_by' => $this->user_id
                        );
                    }
                }
                //todo form parts
                DB::table('tra_process_form_auth')
                    ->where($where)
                    ->delete();
                DB::table('tra_process_form_auth')
                    ->insert($params);
                //todo other parts
                DB::table('tra_process_otherparts_auth')
                    ->where('process_id', $process_id)
                    ->delete();
                DB::table('tra_process_otherparts_auth')
                    ->insert($params2);
            }, 5);
            $res = array(
                'success' => true,
                'message' => 'Changes synced successfully!!'
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

    public function syncWorkflowStageResponsibleGroups(Request $request)
    {
        $selected = $request->input('selected');
        $stage_id = $request->input('stage_id');
        $selected_ids = json_decode($selected);
        $where = array(
            'stage_id' => $stage_id
        );
        try {
            DB::transaction(function () use ($selected_ids, $stage_id, $where) {
                $params = array();
                foreach ($selected_ids as $selected_id) {
                    $params[] = array(
                        'stage_id' => $stage_id,
                        'group_id' => $selected_id,
                        'created_by' => $this->user_id
                    );
                }
                DB::table('wf_stages_groups')
                    ->where($where)
                    ->delete();
                DB::table('wf_stages_groups')
                    ->insert($params);
            }, 5);
            $res = array(
                'success' => true,
                'message' => 'Changes synced successfully!!'
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

    public function getMenuWorkflowLinkages(Request $request)
    {
        $menu_id = $request->input('menu_id');
        $workflow_id = $request->input('workflow_id');
        try {
            $qry = DB::table('wf_menus_stages')
                ->where('menu_id', $menu_id)
                ->select('stage_id');
            $results = $qry->get();
            $results = convertStdClassObjToArray($results);
            $results = convertAssArrayToSimpleArray($results, 'stage_id');
            $data = array(
                'menu_id' => $menu_id,
                'workflow_id' => $workflow_id,
                'workflow_stages' => $results
            );
            $res = array(
                'success' => true,
                'results' => $data,
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

    public function getMenuWorkFlowsLinkages(Request $request)
    {
        $menu_id = $request->input('menu_id');
        try {
            $qry = DB::table('wf_menu_workflows')
                ->where('menu_id', $menu_id)
                ->select('workflow_id');
            $results = $qry->get();
            $results = convertStdClassObjToArray($results);
            $results = convertAssArrayToSimpleArray($results, 'workflow_id');
            $data = array(
                'menu_id' => $menu_id,
                'workflow_ids' => $results
            );
            $res = array(
                'success' => true,
                'results' => $data,
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

    public function saveMenuWorkflowLinkage(Request $request)
    {
        $menu_id = $request->input('menu_id');
        $workflow_id = $request->input('workflow_id');
        $workflow_stages = $request->input('workflow_stages');
        $res = array();
        try {
            DB::transaction(function () use ($menu_id, $workflow_id, $workflow_stages, &$res) {
                $workflow_stages = json_decode($workflow_stages);
                $params = array();
                DB::table('wf_menus_stages')
                    ->where('menu_id', $menu_id)
                    ->delete();
                if (isset($workflow_stages) > 0) {
                    foreach ($workflow_stages as $workflow_stage) {
                        $params[] = array(
                            'menu_id' => $menu_id,
                            'stage_id' => $workflow_stage,
                            'created_on' => Carbon::now(),
                            'created_by' => \Auth::user()->id
                        );
                    }
                    DB::table('wf_menus_stages')
                        ->insert($params);
                }
                DB::table('par_menus')
                    ->where('id', $menu_id)
                    ->update(array('workflow_id' => $workflow_id));
            }, 5);
            $res = array(
                'success' => true,
                'message' => 'Data saved successfully!!'
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

    public function saveMenuWorkFlowsLinkage(Request $request)
    {
        $menu_id = $request->input('menu_id');
        $workflow_ids = $request->input('workflow_ids');
        $res = array();
        try {
            DB::transaction(function () use ($menu_id, $workflow_ids, &$res) {
                $workflow_ids = json_decode($workflow_ids);
                $params = array();
                DB::table('wf_menu_workflows')
                    ->where('menu_id', $menu_id)
                    ->delete();
                if (count($workflow_ids) > 0) {
                    foreach ($workflow_ids as $workflow_id) {
                        $params[] = array(
                            'menu_id' => $menu_id,
                            'workflow_id' => $workflow_id,
                            'created_on' => Carbon::now(),
                            'created_by' => \Auth::user()->id
                        );
                    }
                    DB::table('wf_menu_workflows')
                        ->insert($params);
                }
            }, 5);
            $res = array(
                'success' => true,
                'message' => 'Data saved successfully!!'
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

    public function deleteMenuWorkflowLinkage(Request $request)
    {
        $menu_id = $request->input('menu_id');
        try {
            DB::table('wf_menus_stages')
                ->where('menu_id', $menu_id)
                ->delete();
            DB::table('par_menus')
                ->where('id', $menu_id)
                ->update(array('workflow_id' => null));
            $res = array(
                'success' => true,
                'message' => 'Workflow data deleted successfully!!'
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

    public function deleteMenuWorkFlowsLinkage(Request $request)
    {
        $menu_id = $request->input('menu_id');
        try {
            DB::table('wf_menu_workflows')
                ->where('menu_id', $menu_id)
                ->delete();
            $res = array(
                'success' => true,
                'message' => 'Workflow setup data deleted successfully!!'
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

    public function getWorkflowStageResponsibleGroups(Request $request)//deprecated
    {
        $workflow_id = $request->input('workflow_id');
        try {
            //get associated menus
            $qry1 = DB::table('wf_menu_workflows')
                ->select('menu_id')
                ->where('workflow_id', $workflow_id);
            $menus = $qry1->get();
            $menus = convertStdClassObjToArray($menus);
            $menus = convertAssArrayToSimpleArray($menus, 'menu_id');
            //get groups assigned to these menus
            $qry2 = DB::table('par_groups as t1')
                ->select('t1.*')
                ->whereIn('t1.id', function ($query) use ($menus) {
                    $query->select(DB::raw('t2.group_id'))
                        ->from('tra_permissions as t2')
                        ->whereIn('t2.menu_id', $menus);
                });
            $results = $qry2->get();
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

    public function getWorkflowStagePossibleResponsibleGroups(Request $request)
    {
        $stage_id = $request->input('stage_id');
        $directorate_id = $request->input('directorate_id');
        $department_id = $request->input('department_id');
        $zone_id = $request->input('zone_id');
        $workflow_id = $request->input('workflow_id');
        try {
            //get associated menus
            $qry1 = DB::table('wf_menu_workflows')
                ->select('menu_id')
                ->where('workflow_id', $workflow_id);
            $menus = $qry1->get();
            $menus = convertStdClassObjToArray($menus);
            $menus = convertAssArrayToSimpleArray($menus, 'menu_id');

            $qry = DB::table('par_groups as t1')
                ->join('par_directorates as t2', 't1.directorate_id', '=', 't2.id')
                ->join('par_departments as t3', 't1.department_id', '=', 't3.id')
                ->join('par_zones as t4', 't1.zone_id', '=', 't4.id')
                ->leftJoin('wf_stages_groups as t5', function ($join) use ($stage_id) {
                    $join->on('t1.id', '=', 't5.group_id')
                        ->on('t5.stage_id', '=', DB::raw($stage_id));
                })
                ->select('t1.*', 't2.name as directorate', 't3.name as department', 't4.name as zone', 't5.id as stage_group_id')
                ->whereIn('t1.id', function ($query) use ($menus) {
                    $query->select(DB::raw('t2.group_id'))
                        ->from('tra_permissions as t2')
                        ->where('t2.accesslevel_id', '<>', 1)
                        ->whereIn('t2.menu_id', $menus);
                });
            if (isset($directorate_id) && $directorate_id != '') {
                $qry->where('t1.directorate_id', $directorate_id);
            }
            if (isset($department_id) && $department_id != '') {
                $qry->where('t1.department_id', $department_id);
            }
            if (isset($zone_id) && $zone_id != '') {
                $qry->where('t1.zone_id', $zone_id);
            }
            $results = $qry->get();
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

    public function getSubmissionNextStageDetails(Request $request)
    {
        $current_stage = $request->input('current_stage');
        $action = $request->input('action');
        $where = array(
            'stage_id' => $current_stage,
            'action_id' => $action
        );
        try {
            $qry = DB::table('wf_workflow_transitions as t1')
                ->select('t1.*')
                ->where($where);
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

    public function getSubmissionResponsibleUsers(Request $request)
    {
        $next_stage = $request->input('next_stage');
        try {
            //query 1
            $qry1 = DB::table('wf_stages_groups as t1')
                ->select('t1.group_id')
                ->where('stage_id', $next_stage);
            $stage_groups = $qry1->get();
            $stage_groups = convertStdClassObjToArray($stage_groups);
            $stage_groups = convertAssArrayToSimpleArray($stage_groups, 'group_id');
            //query 2
            $qry2 = DB::table('users as t2')
                ->select(DB::raw("t2.id,CONCAT_WS(' ',decrypt(t2.first_name),decrypt(t2.last_name)) as name"))
                ->whereIn('t2.id', function ($query) use ($stage_groups) {
                    $query->select(DB::raw('t3.user_id'))
                        ->from('tra_user_group as t3')
                        ->whereIn('t3.group_id', $stage_groups);
                });
            $results = $qry2->get();
            //return
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

    public function showWorkflowDiagram(Request $request)
    {
        try {
            $data = array();
            $workflow_id = $request->input('workflow_id');
            $states = DB::table('wf_workflow_stages')
                ->select(DB::raw("id,name as text,stage_status as status"))
                ->where('workflow_id', $workflow_id)
                ->get();
            $transitions = DB::table('wf_workflow_transitions as t1')
                ->join('wf_workflow_actions as t2', 't1.action_id', '=', 't2.id')
                ->select(DB::raw("t1.stage_id as 'from',t1.nextstage_id as 'to',t2.name as text"))
                ->where('t1.workflow_id', $workflow_id)
                ->get();
            $diagramDataArray = array(
                "nodeKeyProperty" => "id",
                'nodeDataArray' => $states,
                'linkDataArray' => $transitions
            );
            $data['workflowData'] = $diagramDataArray;
        } catch (\Exception $exception) {

        } catch (\Throwable $throwable) {

        }
        return view('workflow::workflow', $data);
    }

    public function handleApplicationSubmission(Request $request)
    {
        $module_id = $request->input('module_id');
        if ($module_id == 1) {//PRODUCT REGISTRATION
            $this->processProductsApplicationSubmission($request);
        } else if ($module_id == 2) {//PREMISE REGISTRATION
            $this->processPremiseApplicationSubmission($request);
        } else if ($module_id == 3) {//GMP APPLICATIONS
            $this->processGmpApplicationsSubmission($request);
        } else if ($module_id == 7) {//CLINICAL TRIAL
            $this->processClinicalTrialApplicationsSubmission($request);
        } else if ($module_id == 5) {//SURVEILLANCE
            $this->processSurveillanceApplicationsSubmission($request);
        } else {
            //unknown module
        }
    }

    public function handleManagersApplicationSubmissions(Request $request)
    {
        $module_id = $request->input('module_id');
        if ($module_id == 1) {//PRODUCT REGISTRATION
            $this->processProductManagersApplicationSubmission($request);
        } else if ($module_id == 2) {//PREMISE REGISTRATION
            $this->processPremiseManagersApplicationSubmission($request);
        } else if ($module_id == 3) { //GMP APPLICATIONS
            $this->processGmpManagersApplicationSubmission($request);
        } else if ($module_id == 7) {//CLINICAL TRIAL
            $this->processClinicalTrialManagersApplicationSubmission($request);
        } else if ($module_id == 5) {//SURVEILLANCE
            $this->processSurveillanceManagersApplicationSubmission($request);
        } else {
            //unknown module
        }
    }

    public function receiveOnlineApplicationDetails(Request $request)
    {
        $module_id = $request->input('module_id');
        $res = array();
        if ($module_id == 1) {//PRODUCT REGISTRATION
            $res = $this->saveProductOnlineApplicationDetails($request);
        } else if ($module_id == 2) {//PREMISE REGISTRATION
            $res = $this->savePremiseOnlineApplicationDetails($request);
        } else if ($module_id == 3) { //GMP APPLICATIONS
            $res = $this->saveGmpOnlineApplicationDetails($request);
        } else if ($module_id == 7) { //CLINICAL TRIAL
            $res = $this->saveClinicalTrialOnlineApplicationDetails($request);
        } else {
            //unknown module
        }
        return \response()->json($res);
    }

    public function processNormalApplicationSubmission($request, $keep_status = false)
    {
        $application_id = $request->input('application_id');
        $table_name = $request->input('table_name');
        $prev_stage = $request->input('curr_stage_id');
        $action = $request->input('action');
        $to_stage = $request->input('next_stage');
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            //get application_details

            $application_details = DB::table($table_name)
                ->where('id', $application_id)
                ->first();
            if (is_null($application_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching application details!!'
                );
                echo json_encode($res);
                exit();
            }
            $application_status_id = getApplicationTransitionStatus($prev_stage, $action, $to_stage);
            if ($keep_status == true) {//for approvals
                $application_status_id = $application_details->application_status_id;
            }
            $where = array(
                'id' => $application_id
            );
            $app_update = array(
                'workflow_stage_id' => $to_stage,
                'application_status_id' => $application_status_id
            );
            $prev_data = getPreviousRecords($table_name, $where);
            if ($prev_data['success'] == false) {
                echo json_encode($prev_data);
                exit();
            }
            $update_res = updateRecord($table_name, $prev_data['results'], $where, $app_update, $user_id);

            if ($update_res['success'] == false) {
                echo json_encode($update_res);
                exit();
            }
            $this->updateApplicationSubmission($request, $application_details, $application_status_id);

        } catch (\Exception $exception) {
            DB::rollBack();
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
            echo json_encode($res);
            exit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
            echo json_encode($res);
            exit();
        }
    }

    public function processNormalManagersApplicationSubmission($request, $keep_status = false)
    {
        $process_id = $request->input('process_id');
        $table_name = $request->input('table_name');
        $selected = $request->input('selected');
        $selected_ids = json_decode($selected);
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            //get application_details
            $application_details = DB::table($table_name)
                ->whereIn('id', $selected_ids)
                ->get();
            if (is_null($application_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching application details!!'
                );
                echo json_encode($res);
                exit();
            }
            //get process other details
            $process_details = DB::table('wf_tfdaprocesses')
                ->where('id', $process_id)
                ->first();
            if (is_null($process_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching process details!!'
                );
                echo json_encode($res);
                exit();
            }
            // DB::transaction(function () use ($request, $selected_ids, &$res, $table_name, $user_id, $application_id, $process_id, $application_details, $process_details) {
            $application_codes = array();
            $from_stage = $request->input('curr_stage_id');
            $action = $request->input('action');
            $to_stage = $request->input('next_stage');
            $responsible_user = $request->input('responsible_user');
            $remarks = $request->input('remarks');
            $directive_id = $request->input('directive_id');
            $urgency = $request->input('urgency');
            $transition_params = array();
            $submission_params = array();
            //process other details
            $module_id = $process_details->module_id;
            $sub_module_id = $process_details->sub_module_id;
            $section_id = $process_details->section_id;
            $application_status_id = getApplicationTransitionStatus($from_stage, $action, $to_stage);
            //application details
            foreach ($application_details as $key => $application_detail) {
                /*if ($from_stage == 14 && $to_stage == 15) {
                    $application_status_id = $application_detail->application_status_id;
                }*/
                if ($keep_status == true) {
                    $application_status_id = $application_detail->application_status_id;
                }
                //transitions
                $transition_params[] = array(
                    'application_id' => $application_detail->id,
                    'application_code' => $application_detail->application_code,
                    'application_status_id' => $application_status_id,
                    'process_id' => $process_id,
                    'from_stage' => $from_stage,
                    'to_stage' => $to_stage,
                    'author' => $user_id,
                    'directive_id' => $directive_id,
                    'remarks' => $remarks,
                    'created_on' => Carbon::now(),
                    'created_by' => $user_id
                );
                //submissions
                $submission_params[] = array(
                    'application_id' => $application_detail->id,
                    'view_id'=>$application_detail->view_id,
                    'process_id' => $process_id,
                    'application_code' => $application_detail->application_code,
                    'reference_no' => $application_detail->reference_no,
                    'tracking_no' => $application_detail->tracking_no,
                    'usr_from' => $user_id,
                    'usr_to' => $responsible_user,
                    'previous_stage' => $from_stage,
                    'current_stage' => $to_stage,
                    'module_id' => $module_id,
                    'sub_module_id' => $sub_module_id,
                    'section_id' => $section_id,
                    'application_status_id' => $application_status_id,
                    'urgency' => $urgency,
                    'applicant_id' => $application_detail->applicant_id,
                    'remarks' => $remarks,
                    'directive_id' => $directive_id,
                    'date_received' => Carbon::now(),
                    'created_on' => Carbon::now(),
                    'created_by' => $user_id
                );
                $application_codes[] = array($application_detail->application_code);
            }
            //application update
            $update_params = array(
                'workflow_stage_id' => $to_stage,
                'application_status_id' => $application_status_id
            );
            $app_update = DB::table($table_name . ' as t1')
                ->whereIn('id', $selected_ids)
                ->update($update_params);
            if ($app_update < 1) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while updating application details!!'
                );
                echo json_encode($res);
                exit();
            }
            //transitions update
            DB::table('tra_applications_transitions')
                ->insert($transition_params);
            //submissions update
            DB::table('tra_submissions')
                ->insert($submission_params);
            updateInTraySubmissionsBatch($selected_ids, $application_codes, $from_stage, $user_id);
            DB::commit();
            $res = array(
                'success' => true,
                'message' => 'Application Submitted Successfully!!'
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
        echo json_encode($res);
        exit();
    }

    public function processNewApprovalApplicationSubmission(Request $request, $keep_status = false)
    {
        $process_id = $request->input('process_id');
        $table_name = $request->input('table_name');
        $selected = $request->input('selected');
        $selected_ids = json_decode($selected);
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            //get application_details
            $application_details = DB::table($table_name)
                ->whereIn('id', $selected_ids)
                ->get();
            if (is_null($application_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching application details!!'
                );
                echo json_encode($res);
                exit();
            }
            //get process other details
            $process_details = DB::table('wf_tfdaprocesses')
                ->where('id', $process_id)
                ->first();
            if (is_null($process_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching process details!!'
                );
                echo json_encode($res);
                exit();
            }
            $application_codes = array();
            $from_stage = $request->input('curr_stage_id');
            $action = $request->input('action');
            $to_stage = $request->input('next_stage');
            $responsible_user = $request->input('responsible_user');
            $remarks = $request->input('remarks');
            $directive_id = $request->input('directive_id');
            $urgency = $request->input('urgency');
            $transition_params = array();
            $submission_params = array();
            //process other details
            $module_id = $process_details->module_id;
            $sub_module_id = $process_details->sub_module_id;
            $section_id = $process_details->section_id;
            $application_status_id = getApplicationTransitionStatus($from_stage, $action, $to_stage);
            $portal_table_name = getPortalApplicationsTable($module_id);
            //application details
            foreach ($application_details as $key => $application_detail) {
                if ($keep_status == true) {
                    $application_status_id = $application_detail->application_status_id;
                }
                //update registration table
                if ($application_detail->application_status_id == 6) {//approved
                    $reg_status_id = 2;
                    $portal_status_id = 10;
                    //$this->insertIntoRegistrationTable($application_detail, $module_id, $table_name);
                } else {
                    $reg_status_id = 3;
                    $portal_status_id = 11;
                }
                $this->updateRegTableRecordStatusOnApproval($application_detail, $module_id, $reg_status_id);
                updatePortalApplicationStatus($application_detail->id, $portal_status_id, $table_name, $portal_table_name);
                //transitions
                $transition_params[] = array(
                    'application_id' => $application_detail->id,
                    'application_code' => $application_detail->application_code,
                    'application_status_id' => $application_status_id,
                    'process_id' => $process_id,
                    'from_stage' => $from_stage,
                    'to_stage' => $to_stage,
                    'author' => $user_id,
                    'directive_id' => $directive_id,
                    'remarks' => $remarks,
                    'created_on' => Carbon::now(),
                    'created_by' => $user_id
                );
                //submissions
                $submission_params[] = array(
                    'application_id' => $application_detail->id,
                    'view_id'=>$application_detail->view_id,
                    'process_id' => $process_id,
                    'application_code' => $application_detail->application_code,
                    'reference_no' => $application_detail->reference_no,
                    'tracking_no' => $application_detail->tracking_no,
                    'usr_from' => $user_id,
                    'usr_to' => $responsible_user,
                    'previous_stage' => $from_stage,
                    'current_stage' => $to_stage,
                    'module_id' => $module_id,
                    'sub_module_id' => $sub_module_id,
                    'section_id' => $section_id,
                    'application_status_id' => $application_status_id,
                    'urgency' => $urgency,
                    'applicant_id' => $application_detail->applicant_id,
                    'remarks' => $remarks,
                    'directive_id' => $directive_id,
                    'date_received' => Carbon::now(),
                    'created_on' => Carbon::now(),
                    'created_by' => $user_id
                );
                $application_codes[] = array($application_detail->application_code);
            }
            //application update
            $update_params = array(
                'workflow_stage_id' => $to_stage,
                'application_status_id' => $application_status_id
            );
            $app_update = DB::table($table_name . ' as t1')
                ->whereIn('id', $selected_ids)
                ->update($update_params);
            if ($app_update < 1) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while updating application details!!'
                );
                echo json_encode($res);
                exit();
            }
            //transitions update
            DB::table('tra_applications_transitions')
                ->insert($transition_params);
            //submissions update
            DB::table('tra_submissions')
                ->insert($submission_params);
            updateInTraySubmissionsBatch($selected_ids, $application_codes, $from_stage, $user_id);
            DB::commit();
            $res = array(
                'success' => true,
                'message' => 'Application Submitted Successfully!!'
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
        //return response()->json($res);
        echo json_encode($res);
        exit();
    }

    public function processSubsequentApprovalApplicationSubmission(Request $request)
    {//after New...(Renewals,Alterations/Amendments, etc)
        $process_id = $request->input('process_id');
        $table_name = $request->input('table_name');
        $selected = $request->input('selected');
        $selected_ids = json_decode($selected);
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            //get application_details
            $application_details = DB::table($table_name . ' as t1')
                ->join('tra_approval_recommendations as t2', 't1.application_code', '=', 't2.application_code')
                ->select('t1.*', 't2.decision_id')
                ->whereIn('t1.id', $selected_ids)
                ->get();
            if (is_null($application_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching application details!!'
                );
                echo json_encode($res);
                exit();
            }
            //get process other details
            $process_details = DB::table('wf_tfdaprocesses')
                ->where('id', $process_id)
                ->first();
            if (is_null($process_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching process details!!'
                );
                echo json_encode($res);
                exit();
            }
            $application_codes = array();
            $from_stage = $request->input('curr_stage_id');
            $to_stage = $request->input('next_stage');
            $responsible_user = $request->input('responsible_user');
            $remarks = $request->input('remarks');
            $directive_id = $request->input('directive_id');
            $urgency = $request->input('urgency');
            $transition_params = array();
            $submission_params = array();
            //process other details
            $module_id = $process_details->module_id;
            $sub_module_id = $process_details->sub_module_id;
            $section_id = $process_details->section_id;

            $portal_table_name = getPortalApplicationsTable($module_id);
            //application details
            foreach ($application_details as $key => $application_detail) {
                $application_status_id = $application_detail->application_status_id;
                if ($application_detail->decision_id == 1) {
                    $portal_status_id = 10;
                    $this->updateRegTableRecordTraIDOnApproval($application_detail, $module_id);
                    $this->updateRegTableRecordStatusOnApproval($application_detail, $module_id, 2);
                } else {
                    $portal_status_id = 11;
                }
                updatePortalApplicationStatus($application_detail->id, $portal_status_id, $table_name, $portal_table_name);
                //transitions
                $transition_params[] = array(
                    'application_id' => $application_detail->id,
                    'application_code' => $application_detail->application_code,
                    'application_status_id' => $application_status_id,
                    'process_id' => $process_id,
                    'from_stage' => $from_stage,
                    'to_stage' => $to_stage,
                    'author' => $user_id,
                    'directive_id' => $directive_id,
                    'remarks' => $remarks,
                    'created_on' => Carbon::now(),
                    'created_by' => $user_id
                );
                //submissions
                $submission_params[] = array(
                    'application_id' => $application_detail->id,
                    'view_id'=>$application_detail->view_id,
                    'process_id' => $process_id,
                    'application_code' => $application_detail->application_code,
                    'reference_no' => $application_detail->reference_no,
                    'tracking_no' => $application_detail->tracking_no,
                    'usr_from' => $user_id,
                    'usr_to' => $responsible_user,
                    'previous_stage' => $from_stage,
                    'current_stage' => $to_stage,
                    'module_id' => $module_id,
                    'sub_module_id' => $sub_module_id,
                    'section_id' => $section_id,
                    'application_status_id' => $application_status_id,
                    'urgency' => $urgency,
                    'applicant_id' => $application_detail->applicant_id,
                    'remarks' => $remarks,
                    'directive_id' => $directive_id,
                    'date_received' => Carbon::now(),
                    'created_on' => Carbon::now(),
                    'created_by' => $user_id
                );
                $application_codes[] = array($application_detail->application_code);
            }
            //application update
            $update_params = array(
                'workflow_stage_id' => $to_stage
            );
            $app_update = DB::table($table_name . ' as t1')
                ->whereIn('id', $selected_ids)
                ->update($update_params);
            if ($app_update < 1) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while updating application details!!'
                );
                echo json_encode($res);
                exit();
            }
            //transitions update
            DB::table('tra_applications_transitions')
                ->insert($transition_params);
            //submissions update
            DB::table('tra_submissions')
                ->insert($submission_params);
            updateInTraySubmissionsBatch($selected_ids, $application_codes, $from_stage, $user_id);
            DB::commit();
            $res = array(
                'success' => true,
                'message' => 'Application Submitted Successfully!!'
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
        echo json_encode($res);
        return true;
    }

    public function insertIntoRegistrationTable($application_detail, $module_id, $application_table)
    {
        $user_id = $this->user_id;
        $application_id = $application_detail->id;
        $reg_table = '';
        $reg_column = '';
        $reg_params = array(
            'registration_date' => Carbon::now(),
            'created_by' => $user_id,
            'status_id' => 2
        );
        if ($module_id == 1) {//Products
            $reg_table = 'registered_products';
            $reg_column = 'reg_product_id';
        } else if ($module_id == 2) {//Premises
            $reg_table = 'registered_premises';
            $reg_column = 'reg_premise_id';
            $reg_params['tra_premise_id'] = $application_detail->premise_id;
        } else if ($module_id == 3) {//GMP
            $reg_table = 'registered_manufacturing_sites';
            $reg_column = 'reg_site_id';
            $reg_params['tra_site_id'] = $application_detail->manufacturing_site_id;
        } else {
            //unknown module
        }
        $reg_id = DB::table($reg_table)
            ->insertGetId($reg_params);
        DB::table($application_table)
            ->where('id', $application_id)
            ->update(array($reg_column => $reg_id));
    }

    public function updateRegistrationTable($registered_id, $current_id, $module_id)
    {
        $table_name = '';
        $table_column = '';
        if ($module_id == 1) {//Products
            $table_name = '';
            $table_column = '';
        } else if ($module_id == 2) {//Premises
            $table_name = 'registered_premises';
            $table_column = 'tra_premise_id';
        } else if ($module_id == 3) {//GMP
            $table_name = 'registered_manufacturing_sites';
            $table_column = 'tra_site_id';
        }
        $params = array(
            $table_column => $current_id,
            'altered_by' => $this->user_id
        );
        DB::table($table_name)
            ->where('id', $registered_id)
            ->update($params);
    }

    public function updateRegTableRecordStatusOnApproval($application_detail, $module_id, $status_id)
    {//New Applications
        $reg_table = '';
        $app_reg_column = '';
        $reg_params = array(
            'approval_date' => Carbon::now(),
            'status_id' => $status_id
        );
        if ($module_id == 1) {//Products
            $reg_table = 'registered_products';
            $app_reg_column = 'reg_product_id';
        } else if ($module_id == 2) {//Premises
            $reg_table = 'registered_premises';
            $app_reg_column = 'reg_premise_id';
        } else if ($module_id == 3) {//GMP
            $reg_table = 'registered_manufacturing_sites';
            $app_reg_column = 'reg_site_id';
        } else if ($module_id == 7) {//Clinical Trial
            $reg_table = 'registered_clinical_trials';
            $app_reg_column = 'reg_clinical_trial_id';
        } else {
            //unknown module
        }
        $reg_id = $application_detail->$app_reg_column;
        DB::table($reg_table)
            ->where('id', $reg_id)
            ->update($reg_params);
    }

    public function updateRegTableRecordTraIDOnApproval($application_detail, $module_id)
    {//Subsequent Applications (Renewal,Alterations/Amendments, etc)
        $table_name = '';
        $tra_table_column = '';
        $reg_id_column = '';
        $current_id_column = '';
        if ($module_id == 1) {//Products
            $table_name = '';
            $tra_table_column = '';
            $reg_id_column = '';
            $current_id_column = '';
        } else if ($module_id == 2) {//Premises
            $table_name = 'registered_premises';
            $tra_table_column = 'tra_premise_id';
            $reg_id_column = 'reg_premise_id';
            $current_id_column = 'premise_id';
        } else if ($module_id == 3) {//GMP
            $table_name = 'registered_manufacturing_sites';
            $tra_table_column = 'tra_site_id';
            $reg_id_column = 'reg_site_id';
            $current_id_column = 'manufacturing_site_id';
        } else if ($module_id == 7) {//Clinical Trial
            $table_name = 'registered_clinical_trials';
            $tra_table_column = 'tra_clinical_trial_id';
            $reg_id_column = 'reg_clinical_trial_id';
            $current_id_column = 'id';
        }
        $registered_id = $application_detail->$reg_id_column;
        $current_id = $application_detail->$current_id_column;
        $params = array(
            $tra_table_column => $current_id,
            'altered_by' => $this->user_id
        );
        DB::table($table_name)
            ->where('id', $registered_id)
            ->update($params);
    }

    public function processReceivingQueriedApplicationSubmission(Request $request)
    {
        $application_id = $request->input('application_id');
        $module_id = $request->input('module_id');
        $table_name = $request->input('table_name');
        $to_stage = $request->input('next_stage');
        $action = $request->input('action');
        $prev_stage = $request->input('curr_stage_id');
        $remarks = $request->input('remarks');
        $urgency = $request->input('urgency');
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            //get application_details
            $application_details = DB::table($table_name)
                ->where('id', $application_id)
                ->first();
            if (is_null($application_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching application details!!'
                );
                echo json_encode($res);
                exit();
            }
            $application_code = $application_details->application_code;
            //must have unclosed queries since it has been queried
            $continue = $this->validateReceivingQueriedApplication($application_code);
            if ($continue == false) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'The action you selected is not applicable for an application without unclosed queries!!'
                );
                echo json_encode($res);
                exit();
            }
            $application_status_id = getApplicationTransitionStatus($prev_stage, $action, $to_stage);
            $where = array(
                'id' => $application_id
            );
            $app_update = array(
                'workflow_stage_id' => $to_stage,
                'application_status_id' => $application_status_id
            );
            $prev_data = getPreviousRecords($table_name, $where);
            if ($prev_data['success'] == false) {
                DB::rollBack();
                echo json_encode($prev_data);
                exit();
            }
            $update_res = updateRecord($table_name, $prev_data['results'], $where, $app_update, $user_id);
            if ($update_res['success'] == false) {
                DB::rollBack();
                echo json_encode($update_res);
                exit();
            }
            $portal_update = false;
            if ($module_id == 1) {

            } else if ($module_id == 2) {
                $portal_update = $this->updateQueriedPremiseApplicationPortal($request, $application_details);
            } else if ($module_id == 3) {
                $portal_update = true;
            }
            if ($portal_update == true) {
                //updateInTraySubmissions($application_id, $application_details->application_code, $prev_stage, $user_id);
                $this->updateApplicationSubmission($request, $application_details, $application_status_id);
                DB::commit();
                $res = array(
                    'success' => true,
                    'message' => 'Application submitted successfully!!'
                );
                echo json_encode($res);
                exit();
            } else {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while trying to update portal data, consult System Admin!!'
                );
                echo json_encode($res);
                exit();
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
            echo json_encode($res);
            exit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
            echo json_encode($res);
            exit();
        }
    }

    public function validateReceivingQueriedApplication($application_code)
    {
        $return_val = true;
        //for queried there should be unclosed queries
        $unclosed_queries = DB::table('checklistitems_responses as t1')
            ->join('checklistitems_queries as t2', 't1.id', '=', 't2.item_resp_id')
            ->where('t1.application_code', $application_code)
            ->where('t2.status', '<>', 4)
            ->count();
        if ($unclosed_queries < 1) {
            $return_val = false;
        }
        return $return_val;
    }

    public function processReceivingRejectedApplicationSubmission(Request $request)
    {
        $application_id = $request->input('application_id');
        $module_id = $request->input('module_id');
        $table_name = $request->input('table_name');
        $to_stage = $request->input('next_stage');
        $action = $request->input('action');
        $prev_stage = $request->input('curr_stage_id');
        $remarks = $request->input('remarks');
        $urgency = $request->input('urgency');
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            //get application_details
            $application_details = DB::table($table_name)
                ->where('id', $application_id)
                ->first();
            if (is_null($application_details)) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching application details!!'
                );
                echo json_encode($res);
                exit();
            }
            $application_status_id = getApplicationTransitionStatus($prev_stage, $action, $to_stage);
            $where = array(
                'id' => $application_id
            );
            $app_update = array(
                'workflow_stage_id' => $to_stage,
                'application_status_id' => $application_status_id
            );
            $prev_data = getPreviousRecords($table_name, $where);
            if ($prev_data['success'] == false) {
                DB::rollBack();
                echo json_encode($prev_data);
                exit();
            }
            $update_res = updateRecord($table_name, $prev_data['results'], $where, $app_update, $user_id);
            if ($update_res['success'] == false) {
                DB::rollBack();
                echo json_encode($update_res);
                exit();
            }
            $portal_update = false;
            if ($module_id == 1) {

            } else if ($module_id == 2) {
                $portal_update = $this->updateRejectedPremiseApplicationPortal($request, $application_details);
            } else if ($module_id == 3) {
                $portal_update = true;
            }
            if ($portal_update == false) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while updating portal data, consult System Admin!!'
                );
                echo json_encode($res);
                exit();
            }
            //select insert
            $data = DB::table($table_name . ' as t1')
                ->select(DB::raw("t1.*,$user_id as rejected_by,NOW() as rejected_on"))
                ->where('id', $application_id)
                ->first();
            $data->rejection_reason = $remarks;
            $data = convertStdClassObjToArray($data);
            $mis_insert_res = insertRecord('tra_rejected_premises_applications', $data, $user_id);
            if ($mis_insert_res['success'] == false) {
                DB::rollBack();
                echo json_encode($mis_insert_res);
                exit();
            }
            $prev_data = getPreviousRecords($table_name, $where);
            if ($prev_data['success'] == false) {
                DB::rollBack();
                echo json_encode($prev_data);
                exit();
            }
            $delete_res = deleteRecord($table_name, $prev_data['results'], $where, $user_id);
            if ($delete_res['success'] == false) {
                DB::rollBack();
                echo json_encode($delete_res);
                exit();
            }
            $this->updateApplicationSubmission($request, $application_details, $application_status_id);
            DB::commit();
            $res = array(
                'success' => true,
                'message' => 'Application submitted successfully!!'
            );
            echo json_encode($res);
            exit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
            echo json_encode($res);
            exit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
            echo json_encode($res);
            exit();
        }
    }

    public function submitApplicationFromManagerQueryToCustomer(Request $request)
    {
        $process_id = $request->input('process_id');
        $table_name = $request->input('table_name');
        $directive_id = $request->input('directive_id');
        $selected = $request->input('selected');
        $selected_ids = json_decode($selected);
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            //get application_details
            $application_details = DB::table($table_name)
                ->whereIn('id', $selected_ids)
                ->get();
            if (is_null($application_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching application details!!'
                );
                echo json_encode($res);
                exit();
            }
            //get process other details
            $process_details = DB::table('wf_tfdaprocesses')
                ->where('id', $process_id)
                ->first();
            if (is_null($process_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching process details!!'
                );
                echo json_encode($res);
                exit();
            }
            $application_codes = array();
            $from_stage = $request->input('curr_stage_id');
            $action = $request->input('action');
            $to_stage = $request->input('next_stage');
            $responsible_user = $request->input('responsible_user');
            $remarks = $request->input('remarks');
            $urgency = $request->input('urgency');
            $insert_remarks = array();
            $portal_ids = array();
            //process other details
            $module_id = $process_details->module_id;
            $sub_module_id = $process_details->sub_module_id;
            $section_id = $process_details->section_id;

            $application_status_id = getApplicationTransitionStatus($from_stage, $action, $to_stage);
            $portal_db = DB::connection('portal_db');
            //application details
            foreach ($application_details as $key => $application_detail) {
                $insert_remarks[] = array(
                    'application_id' => $application_detail->portal_id,
                    'remark' => $remarks,
                    'urgency' => $urgency,
                    'mis_created_by' => $user_id
                );
                $portal_ids[] = array($application_detail->portal_id);
                $application_codes[] = array($application_detail->application_code);
                //transitions
                $transition_params[] = array(
                    'application_id' => $application_detail->id,
                    'application_code' => $application_detail->application_code,
                    'application_status_id' => $application_status_id,
                    'process_id' => $process_id,
                    'from_stage' => $from_stage,
                    'to_stage' => $to_stage,
                    'directive_id' => $directive_id,
                    'author' => $user_id,
                    'remarks' => $remarks,
                    'created_on' => Carbon::now(),
                    'created_by' => $user_id
                );
                //submissions
                $submission_params[] = array(
                    'application_id' => $application_detail->id,
                    'view_id'=>$application_detail->view_id,
                    'process_id' => $process_id,
                    'application_code' => $application_detail->application_code,
                    'reference_no' => $application_detail->reference_no,
                    'tracking_no' => $application_detail->tracking_no,
                    'usr_from' => $user_id,
                    'usr_to' => $responsible_user,
                    'previous_stage' => $from_stage,
                    'current_stage' => $to_stage,
                    'module_id' => $module_id,
                    'sub_module_id' => $sub_module_id,
                    'section_id' => $section_id,
                    'application_status_id' => $application_status_id,
                    'urgency' => $urgency,
                    'directive_id' => $directive_id,
                    'applicant_id' => $application_detail->applicant_id,
                    'remarks' => $remarks,
                    'date_received' => Carbon::now(),
                    'created_on' => Carbon::now(),
                    'created_by' => $user_id
                );
                updateApplicationQueryRef($application_detail->id, $application_detail->application_code, $application_detail->reference_no, $table_name, $user_id);
            }
            //application update
            $update_params = array(
                'workflow_stage_id' => $to_stage,
                'application_status_id' => $application_status_id
            );
            $app_update = DB::table($table_name . ' as t1')
                ->whereIn('id', $selected_ids)
                ->update($update_params);
            if ($app_update < 1) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while updating application details!!'
                );
                echo json_encode($res);
                exit();
            }
            $portal_update = false;
            if ($module_id == 1) {

            } else if ($module_id == 2) {
                $portal_update = $this->updatePremiseManagerQueryToCustomerPortal($portal_ids);
            } else if ($module_id == 3) {
                $portal_update = true;
            }
            if ($portal_update == false) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while updating portal details!!'
                );
                echo json_encode($res);
                exit();
            }
            $portal_db->table('wb_manager_query_remarks')
                ->insert($insert_remarks);
            //transitions update
            DB::table('tra_applications_transitions')
                ->insert($transition_params);
            //submissions update
            DB::table('tra_submissions')
                ->insert($submission_params);
            updateInTraySubmissionsBatch($selected_ids, $application_codes, $from_stage, $user_id);
            DB::commit();
            $res = array(
                'success' => true,
                'message' => 'Application Submitted Successfully!!'
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
        echo json_encode($res);
        return true;
    }

    public function getActionChecklistCategory($action_id)
    {
        $qry = DB::table('wf_workflow_actions')
            ->where('id', $action_id);
        $checklist_category = $qry->value('checklist_category_id');
        if (is_numeric($checklist_category) && $checklist_category > 0) {
            return $checklist_category;
        } else {
            return false;
        }
    }

    public function processManagerQueryReturnApplicationSubmission(Request $request)
    {
        $process_id = $request->input('process_id');
        $action = $request->input('action');
        $table_name = $request->input('table_name');
        $directive_id = $request->input('directive_id');
        $selected = $request->input('selected');
        $selected_ids = json_decode($selected);
        $user_id = $this->user_id;
        $invalidate_checklist = false;

        $checklist_category = $this->getActionChecklistCategory($action);
        if ($checklist_category == false) {
            $res = array(
                'success' => false,
                'message' => 'Problem encountered while fetching action checklist category!!'
            );
            echo json_encode($res);
            exit();
        }
        if ($directive_id == 2 || $directive_id == 4) {//redo inspection(2),evaluation(4)
            $invalidate_checklist = true;
        }
        DB::beginTransaction();
        try {
            //get application_details
            $application_details = DB::table($table_name)
                ->whereIn('id', $selected_ids)
                ->get();
            if (is_null($application_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching application details!!'
                );
                echo json_encode($res);
                exit();
            }
            //get process other details
            $process_details = DB::table('wf_tfdaprocesses')
                ->where('id', $process_id)
                ->first();
            if (is_null($process_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching process details!!'
                );
                echo json_encode($res);
                exit();
            }
            $application_codes = array();
            $from_stage = $request->input('curr_stage_id');
            $action = $request->input('action');
            $to_stage = $request->input('next_stage');
            $responsible_user = $request->input('responsible_user');
            $remarks = $request->input('remarks');
            $urgency = $request->input('urgency');
            $transition_params = array();
            $submission_params = array();
            //process other details
            $module_id = $process_details->module_id;
            $sub_module_id = $process_details->sub_module_id;
            $section_id = $process_details->section_id;
            $application_status_id = getApplicationTransitionStatus($from_stage, $action, $to_stage);
            //application details
            foreach ($application_details as $key => $application_detail) {
                //transitions
                $transition_params[] = array(
                    'application_id' => $application_detail->id,
                    'application_code' => $application_detail->application_code,
                    'application_status_id' => $application_status_id,
                    'process_id' => $process_id,
                    'from_stage' => $from_stage,
                    'to_stage' => $to_stage,
                    'directive_id' => $directive_id,
                    'author' => $user_id,
                    'remarks' => $remarks,
                    'created_on' => Carbon::now(),
                    'created_by' => $user_id
                );
                //submissions
                $submission_params[] = array(
                    'application_id' => $application_detail->id,
                    'process_id' => $process_id,
                    'view_id'=>$application_detail->view_id,
                    'application_code' => $application_detail->application_code,
                    'reference_no' => $application_detail->reference_no,
                    'tracking_no' => $application_detail->tracking_no,
                    'usr_from' => $user_id,
                    'usr_to' => $responsible_user,
                    'previous_stage' => $from_stage,
                    'current_stage' => $to_stage,
                    'module_id' => $module_id,
                    'sub_module_id' => $sub_module_id,
                    'section_id' => $section_id,
                    'application_status_id' => $application_status_id,
                    'urgency' => $urgency,
                    'directive_id' => $directive_id,
                    'applicant_id' => $application_detail->applicant_id,
                    'remarks' => $remarks,
                    'date_received' => Carbon::now(),
                    'created_on' => Carbon::now(),
                    'created_by' => $user_id
                );
                $application_codes[] = array($application_detail->application_code);
            }
            //application update
            $update_params = array(
                'workflow_stage_id' => $to_stage,
                'application_status_id' => $application_status_id
            );
            $app_update = DB::table($table_name . ' as t1')
                ->whereIn('id', $selected_ids)
                ->update($update_params);
            if ($app_update < 1) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while updating application details!!'
                );
                echo json_encode($res);
                exit();
            }
            if ($invalidate_checklist === true) {
                inValidateApplicationChecklist($module_id, $sub_module_id, $section_id, $checklist_category, $application_codes);
            }
            //transitions update
            DB::table('tra_applications_transitions')
                ->insert($transition_params);
            //submissions update
            DB::table('tra_submissions')
                ->insert($submission_params);
            updateInTraySubmissionsBatch($selected_ids, $application_codes, $from_stage, $user_id);
            DB::commit();
            $res = array(
                'success' => true,
                'message' => 'Application Submitted Successfully!!'
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
        echo json_encode($res);
        return true;
    }

    public function processRecommendationApplicationSubmission(Request $request, $recommendation_table)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $process_id = $request->input('process_id');
        $recommendation_id = $request->input('action');
        $remarks = $request->input('remarks');
        $table_name = $request->input('table_name');
        $user_id = $this->user_id;
        try {
            $data = array(
                'application_id' => $application_id,
                'application_code' => $application_code,
                'process_id' => $process_id,
                'table_name' => $table_name,
                'recommendation_id' => $recommendation_id,
                'remarks' => $remarks,
                'created_by' => $user_id
            );
            $res = insertRecord($recommendation_table, $data, $user_id);
            if ($res['success'] == false) {
                echo json_encode($res);
                exit();
            }
            $this->processNormalApplicationSubmission($request);
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
            echo json_encode($res);
            exit();
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
            echo json_encode($res);
            exit();
        }
    }

    public function updateApplicationSubmission($request, $application_details, $application_status_id)
    {
        $application_id = $request->input('application_id');
        $process_id = $request->input('process_id');
        $user_id = $this->user_id;
        try {
            //get process other details
            $process_details = DB::table('wf_tfdaprocesses')
                ->where('id', $process_id)
                ->first();
            if (is_null($process_details)) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching process details!!'
                );
                echo json_encode($res);
                exit();
            }
            $from_stage = $request->input('curr_stage_id');
            $to_stage = $request->input('next_stage');
            $responsible_user = $request->input('responsible_user');
            $remarks = $request->input('remarks');
            $urgency = $request->input('urgency');
            $directive_id = $request->input('directive_id');
            //application details
            $application_code = $application_details->application_code;
            $ref_no = $application_details->reference_no;
            $view_id = $application_details->view_id;
            $tracking_no = $application_details->tracking_no;
            $applicant_id = $application_details->applicant_id;
            //process other details
            $module_id = $process_details->module_id;
            $sub_module_id = $process_details->sub_module_id;
            $section_id = $process_details->section_id;
            //transitions
            $transition_params = array(
                'application_id' => $application_id,
                'application_code' => $application_code,
                'application_status_id' => $application_status_id,
                'process_id' => $process_id,
                'from_stage' => $from_stage,
                'to_stage' => $to_stage,
                'author' => $user_id,
                'remarks' => $remarks,
                'directive_id' => $directive_id,
                'created_on' => Carbon::now(),
                'created_by' => $user_id
            );
            DB::table('tra_applications_transitions')
                ->insert($transition_params);
            //submissions
            $submission_params = array(
                'application_id' => $application_id,
                'process_id' => $process_id,
                'view_id'=>$view_id,
                'application_code' => $application_code,
                'reference_no' => $ref_no,
                'tracking_no' => $tracking_no,
                'usr_from' => $user_id,
                'usr_to' => $responsible_user,
                'previous_stage' => $from_stage,
                'current_stage' => $to_stage,
                'module_id' => $module_id,
                'sub_module_id' => $sub_module_id,
                'section_id' => $section_id,
                'application_status_id' => $application_status_id,
                'urgency' => $urgency,
                'applicant_id' => $applicant_id,
                'remarks' => $remarks,
                'directive_id' => $directive_id,
                'date_received' => Carbon::now(),
                'created_on' => Carbon::now(),
                'created_by' => $user_id
            );
            DB::table('tra_submissions')
                ->insert($submission_params);
            updateInTraySubmissions($application_id, $application_code, $from_stage, $user_id);
            DB::commit();
            $res = array(
                'success' => true,
                'message' => 'Application Submitted Successfully!!'
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
        echo json_encode($res);
        return true;
    }

    public function updateInTrayReading(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $current_stage = $request->input('current_stage');
        $user_id = $this->user_id;
        $res = updateInTrayReading($application_id, $application_code, $current_stage, $user_id);
        return \response()->json($res);
    }

    public function getSubmissionRecommendations(Request $request)
    {
        $stage_id = $request->input('stage_id');
        $recommendation_type = $request->input('recommendation_type');
        try {
            $qry = DB::table('par_application_recommendations');
            if (isset($stage_id) && $stage_id != '') {
                $qry->where('stage_id', $stage_id);
            }
            if (isset($recommendation_type) && $recommendation_type != '') {
                $qry->where('recommendation_type_id', $recommendation_type);
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

    public function getApplicationStatuses(Request $request)
    {
        $workflow_id = $request->input('workflow_id');
        try {//assumptions no workflow is shared among processes
            $qry1 = DB::table('wf_tfdaprocesses')
                ->select('module_id', 'sub_module_id')
                ->where('workflow_id', $workflow_id);
            $data = $qry1->first();
            if (is_null($data)) {
                $where = array();
            } else {
                $where = array(
                    'module_id' => $data->module_id,
                    'sub_module_id' => $data->sub_module_id
                );
            }
            $qry2 = DB::table('par_application_statuses as t1')
                ->join('par_system_statuses as t2', 't1.status_id', '=', 't2.id')
                ->select('t2.*')
                ->where($where);
            $results = $qry2->get();
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

    public function getApplicationReturnDirectives(Request $request)
    {
        $category_id = $request->input('category_id');
        try {
            $qry = DB::table('par_application_return_directives');
            if (isset($category_id) && $category_id != '') {
                $qry->whereIn('category_id', array(1, $category_id));
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

    public function getApplicationTransitioning(Request $req)
    {
        $application_id = $req->input('application_id');
        $application_code = $req->input('application_code');
        $where = array(
            'application_id' => $application_id,
            'application_code' => $application_code
        );
        try {
            $qry = DB::table('tra_applications_transitions as t1')
                ->leftJoin('users as t2', 't1.author', '=', 't2.id')
                ->join('wf_workflow_stages as t3', 't1.from_stage', '=', 't3.id')
                ->join('wf_workflow_stages as t4', 't1.to_stage', '=', 't4.id')
                ->leftJoin('par_application_return_directives as t5', 't1.directive_id', '=', 't5.id')
                ->select(DB::raw("t3.name as from_stage_name,t4.name as to_stage_name,t1.remarks,t1.created_on as changes_date,
                t5.name as directive,CONCAT_WS(' ',decrypt(t2.first_name),decrypt(t2.last_name)) as author"))
                ->where($where)
                ->orderBy('t1.id');
            $data = $qry->get();
            $res = array(
                'success' => true,
                'results' => $data,
                'message' => 'All is well'
            );
        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
        return response()->json($res);
    }

    public function getFormFieldsAuth(Request $request)
    {
        $process_id = $request->input('process_id');
        $form_id = $request->input('form_id');
        try {
            $qry = DB::table('tra_process_form_auth as t1')
                ->join('par_key_form_fields as t2', 't1.field_id', '=', 't2.id')
                ->join('par_form_field_types as t3', 't2.field_type_id', '=', 't3.id')
                ->where('t1.process_id', $process_id)
                ->where('t1.form_id', $form_id)
                ->select('t1.id', 't2.field_name', 't3.name as field_type');
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

    public function getProcessOtherPartsAuth(Request $request)
    {
        $process_id = $request->input('process_id');
        try {
            $qry = DB::table('tra_process_otherparts_auth as t1')
                ->where('t1.process_id', $process_id)
                ->select('t1.id', 't1.part_id');
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

    public function getAlterationFormFieldsAuth(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $where = array(
            'application_id' => $application_id,
            'application_code' => $application_code
        );
        try {
            $qry = DB::table('tra_alt_formparts_amendments as t1')
                ->join('par_key_form_fields as t2', 't1.field_id', '=', 't2.id')
                ->join('par_form_field_types as t3', 't2.field_type_id', '=', 't3.id')
                ->where($where)
                ->select('t1.id', 't2.field_name', 't3.name as field_type');
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

    public function getAlterationOtherPartsAuth(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $where = array(
            'application_id' => $application_id,
            'application_code' => $application_code
        );
        try {
            $qry = DB::table('tra_alt_otherparts_amendments as t1')
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

    public function getApplicationAlterationFormFields(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $form_id = $request->input('form_id');
        try {
            $qry = DB::table('par_key_form_fields as t1')
                ->leftJoin('tra_alt_formparts_amendments as t2', function ($join) use ($application_id, $application_code) {
                    $join->on('t1.id', '=', 't2.field_id')
                        //->on('t2.application_code', '=', DB::raw($application_code));
                        ->where('t2.application_code', '=', $application_code);
                })
                ->select('t1.*', 't2.id as is_editable')
                ->where('t1.form_id', '=', $form_id);
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

    public function getApplicationAlterationOtherParams(Request $request)
    {
        $module_id = $request->input('module_id');
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        try {
            $qry = DB::table('par_alteration_setup as t1')
                ->leftJoin('tra_alt_otherparts_amendments as t2', function ($join) use ($application_id, $application_code) {
                    $join->on('t1.id', '=', 't2.part_id')
                        ->where('t2.application_code', '=', $application_code);
                })
                ->where('t1.is_form_tied', 2)
                ->where('t1.module_id', $module_id)
                ->select('t1.*', 't2.id as is_editable');
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

    public function getApplicationAlterationForms(Request $request)
    {
        $module_id = $request->input('module_id');
        try {
            $qry = DB::table('par_alteration_setup as t1')
                ->where('t1.is_form_tied', 1);
            if (isset($module_id) && $module_id != '') {
                $qry->where('module_id', $module_id);
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

    public function getPortalApplicationStatuses()
    {
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_statuses');
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

    public function getApplicationWorkflowActionDetails($action_id)
    {
        $transition_details = DB::table('wf_workflow_actions')
            ->where('id', $action_id)
            ->first();
        if (is_null($transition_details)) {
            $res = array(
                'success' => false,
                'message' => 'Problem encountered getting action details!!'
            );
            echo json_encode($res);
            exit();
        }
        return $transition_details;
    }

    public function getWorkflowDetails(Request $req)
    {
        try {
            $results = DB::table('wf_workflows as t1')
                ->leftJoin('sub_modules as t4', 't1.sub_module_id', '=', 't4.id')
                ->leftJoin('modules as t3', 't1.module_id', '=', 't3.id')
                ->leftJoin('par_sections as t5', 't1.section_id', '=', 't5.id')
                ->select('t1.*', 't3.name as module_name', 't4.name as sub_module', 't5.name as section_name')
                ->get();

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

    public function getWorkflowInterfacedetails(Request $req)
    {
        try {
            $results = DB::table('wf_workflow_interfaces as t1')
                ->leftJoin('sub_modules as t4', 't1.sub_module_id', '=', 't4.id')
                ->leftJoin('modules as t3', 't1.module_id', '=', 't3.id')
                ->leftJoin('par_sections as t5', 't1.section_id', '=', 't5.id')
                ->select('t1.*', 't3.name as module_name', 't4.name as sub_module', 't5.name as section_name')
                ->get();

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
}
