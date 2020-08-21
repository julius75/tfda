<?php

namespace App\Modules\Dashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Modules\Dashboard\Entities\SystemGuideline;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
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

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('dashboard::index');
    }

    public function saveDashCommonData(Request $req)
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

    public function getInTrayItems(Request $request)
    {
        $user_id = $this->user_id;
        $filter = $request->input('filter');
        $section_id = $request->input('section_id');
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $whereClauses = array();
        $filter_string = '';
        if (isset($filter)) {
            $filters = json_decode($filter);
            if ($filters != NULL) {
                foreach ($filters as $filter) {
                    switch ($filter->property) {
                        case 'tracking_no' :
                            $whereClauses[] = "t1.tracking_no like '%" . ($filter->value) . "%'";
                            break;
                        case 'reference_no' :
                            $whereClauses[] = "t1.reference_no like '%" . ($filter->value) . "%'";
                            break;
                        case 'applicant_name' :
                            $whereClauses[] = "t9.name like '%" . ($filter->value) . "%'";
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
            $qry = DB::table('tra_submissions as t1')
                ->join('wf_tfdaprocesses as t2', 't1.process_id', '=', 't2.id')
                ->join('wf_workflow_stages as t3', 't1.previous_stage', '=', 't3.id')
                ->join('wf_workflow_stages as t4', 't1.current_stage', '=', 't4.id')
                ->join('par_system_statuses as t5', 't1.application_status_id', '=', 't5.id')
                ->join('par_submission_urgencies as t6', 't1.urgency', '=', 't6.id')
                ->join('users as t7', 't1.usr_from', '=', 't7.id')
                ->join('users as t8', 't1.usr_to', '=', 't8.id')
                ->leftJoin('wb_trader_account as t9', 't1.applicant_id', '=', 't9.id')
                ->select(DB::raw("t1.*, t1.current_stage as workflow_stage_id, t1.application_id as active_application_id, t2.name as process_name,
                    t3.name as prev_stage, t4.name as workflow_stage,t4.is_general,t5.name as application_status,t6.name as urgencyName,t6.name as urgency_name,
                    CONCAT_WS(' ',decrypt(t7.first_name),decrypt(t7.last_name)) as from_user,CONCAT_WS(' ',decrypt(t8.first_name),decrypt(t8.last_name)) as to_user,
                    t9.name as applicant_name"))
                ->where('usr_to', $user_id)
                ->where('isDone', 0);
            if ($filter_string != '') {
                $qry->whereRAW($filter_string);
            }
            if (isset($section_id) && $section_id != '') {
                $qry->where('t1.section_id', $section_id);
            }
            if (isset($module_id) && $module_id != '') {
                $qry->where('t1.module_id', $module_id);
            }
            if (isset($sub_module_id) && $sub_module_id != '') {
                $qry->where('t1.sub_module_id', $sub_module_id);
            }
            if (isset($workflow_stage_id) && $workflow_stage_id != '') {
                $qry->where('t1.current_stage', $workflow_stage_id);
            }
            $qry->orderBy('t1.id', 'DESC');
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

    public function getOutTrayItems()
    {
        $user_id = $this->user_id;
        try {
            $qry = DB::table('tra_submissions as t1')
                ->join('wf_tfdaprocesses as t2', 't1.process_id', '=', 't2.id')
                ->join('wf_workflow_stages as t3', 't1.previous_stage', '=', 't3.id')
                ->join('wf_workflow_stages as t4', 't1.current_stage', '=', 't4.id')
                ->join('par_system_statuses as t5', 't1.application_status_id', '=', 't5.id')
                ->join('par_submission_urgencies as t6', 't1.urgency', '=', 't6.id')
                ->join('users as t7', 't1.usr_from', '=', 't7.id')
                ->leftJoin('users as t8', 't1.usr_to', '=', 't8.id')
                ->join('wb_trader_account as t9', 't1.applicant_id', '=', 't9.id')
                ->select(DB::raw("t1.*, t1.current_stage as workflow_stage_id, t1.application_id as active_application_id, t2.name as process_name,
                    t3.name as prev_stage, t4.name as workflow_stage,t5.name as application_status,t6.name as urgencyName,t6.name as urgency_name,
                    CONCAT_WS(' ',decrypt(t7.first_name),decrypt(t7.last_name)) as from_user,CONCAT_WS(' ',decrypt(t8.first_name),decrypt(t8.last_name)) as to_user,
                    t9.name as applicant_name"))
                ->where('usr_from', $user_id)
                ->where('isComplete', 0);
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

    public function getSystemGuidelines(Request $request)
    {
        $menu_id = $request->input('menu_id');
        try {
            $qry = SystemGuideline::where('menu_id', $menu_id);
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
