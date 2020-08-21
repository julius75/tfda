<?php

namespace App\Modules\Configurations\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ConfigurationsController extends Controller
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
        return view('configurations::index');
    }

    public function saveConfigCommonData(Request $req)
    {
        try {
            $user_id = \Auth::user()->id;
            $post_data = $req->all();
            $table_name = $post_data['table_name'];
            $id = $post_data['id'];
            $unsetData=$req->input('unset_data');
            //unset unnecessary values
            unset($post_data['_token']);
            unset($post_data['table_name']);
            unset($post_data['model']);
            unset($post_data['id']);
            unset($post_data['unset_data']);
            if(isset($unsetData)){
                $unsetData= explode(",", $unsetData);
                $post_data=unsetArrayData($post_data,$unsetData);
            }
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
    
    public function saveSystemModuleData(Request $req)
    {
        try {
            $user_id = \Auth::user()->id;
            $post_data = $req->all();
            $table_name = 'modules';
            $id = $post_data['id'];
            $unsetData=$req->input('unset_data');
            //unset unnecessary values
            unset($post_data['_token']);
            unset($post_data['model']);
            unset($post_data['id']);
            unset($post_data['unset_data']);
            
            if(isset($unsetData)){
                $unsetData= explode(",", $unsetData);
                $post_data=unsetArrayData($post_data,$unsetData);
            }
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
    public function getConfigParamFromModel(Request $request)
    {
        $model_name = $request->input('model_name');
        $strict_mode = $request->input('strict_mode');
        try {
            $model = 'App\\Modules\\Configurations\\Entities\\' . $model_name;
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

    public function getChecklistTypes(Request $request)
    {
        $checklist_category=$request->input('checklist_category');
        $module_id=$request->input('module_id');
        $sub_module_id=$request->input('sub_module_id');
        $section_id=$request->input('section_id');
        try {
            $qry = DB::table('par_checklist_types as t1')
                ->join('par_checklist_categories as t2','t1.checklist_category_id','=','t2.id')
                ->join('modules as t3','t1.module_id','=','t3.id')
                ->join('sub_modules as t4','t1.sub_module_id','=','t4.id')
                ->join('par_sections as t5','t1.section_id','=','t5.id')
                ->select('t1.*','t2.name as category_name','t3.name as module','t4.name as sub_module','t5.name as section');
            if (isset($checklist_category) && $checklist_category!='') {
                $qry->where('t1.checklist_category_id',$checklist_category);
            }
            if (isset($module_id) && $module_id!='') {
                $qry->where('t1.module_id',$module_id);
            }
            if (isset($sub_module_id) && $sub_module_id!='') {
                $qry->where('t1.sub_module_id',$sub_module_id);
            }
            if (isset($section_id) && $section_id!='') {
                $qry->where('t1.section_id',$section_id);
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

    public function getChecklistItems(Request $request)
    {
        $checklist_type=$request->input('checklist_type');
        try {
            $qry = DB::table('par_checklist_items as t1')
                ->join('par_checklist_types as t2','t1.checklist_type_id','=','t2.id')
                ->select('t1.*','t2.name as type_name');
            if (isset($checklist_type) && $checklist_type!='') {
                $qry->where('t1.checklist_type_id',$checklist_type);
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

    public function deleteConfigRecord(Request $req)
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

    public function softDeleteConfigRecord(Request $req)
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

    public function undoConfigSoftDeletes(Request $req)
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

    public function getAllApplicationStatuses(Request $request)
    {
        try {
            $qry = DB::table('par_application_statuses as t1')
                ->join('modules as t2', 't1.module_id', '=', 't2.id')
                ->join('sub_modules as t3', 't1.sub_module_id', '=', 't3.id')
                ->leftJoin('par_confirmations as t4', 't1.status', '=', 't4.id')
                ->leftjoin('par_system_statuses as t5', 't1.status_id', '=', 't5.id')
                ->select('t1.*', 't5.name as status_name', 't2.name as module_name', 't3.name as sub_module_name','t4.name as is_initial');
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

    public function getAlterationParameters()
    {
        try{
            $qry=DB::table('par_alteration_setup as t1')
                ->join('par_confirmations as t2','t1.is_form_tied','=','t2.id')
                ->leftJoin('par_key_forms as t3','t1.form_id','=','t3.id')
                ->join('modules as t4','t1.module_id','=','t4.id')
                ->select('t1.*','t2.name as form_specific','t3.name as form_name','t4.name as module_name');
            $results=$qry->get();
            $res=array(
                'success'=>true,
                'results'=>$results,
                'message'=>'All is well'
            );
        }catch(\Exception $exception){
            $res=array(
                'success'=>false,
                'message'=>$exception->getMessage()
            );
        }catch(\Throwable $throwable){
            $res=array(
                'success'=>false,
                'message'=>$throwable->getMessage()
            );
        }
        return \response()->json($res);
    }
    public function getproductApplicationParameters(Request $req)
    {
        try{
            $filters = $req->filters;

            $table_name = $req->table_name.' as t1';

            $qry=DB::table($table_name)
                ->leftJoin('par_sections as t2','t1.section_id','=','t2.id')
                ->select('t1.*','t2.name as section_name');
            
                if($filters != ''){
                    $filters = (array)json_decode($filters);
                    $results=$qry->where($filters);
                }
            $results=$qry->get();
            
            $res=array(
                'success'=>true,
                'results'=>$results,
                'message'=>'All is well'
            );
        }catch(\Exception $exception){
            $res=array(
                'success'=>false,
                'message'=>$exception->getMessage()
            );
        }catch(\Throwable $throwable){
            $res=array(
                'success'=>false,
                'message'=>$throwable->getMessage()
            );
        }
        return \response()->json($res);
    }
    
    public function getproductGeneraicNameParameters(Request $req)
    {
        try{
            $filters = $req->filters;

            
            $table_name = $req->table_name.' as t1';

            $qry=DB::table($table_name)
                ->join('par_sections as t2','t1.section_id','=','t2.id')
                ->leftJoin('par_atc_codes as t3','t1.atc_code_id','=','t3.id')
                ->select('t1.*','t3.name as atc_code','t3.description as atc_code_description', 't2.name as section_name');

                if($filters != ''){
                    $filters = (array)json_decode($filters);
                    $section_id = $filters['section_id'];

                    $results=$qry->where(array('t1.section_id'=>$section_id));
                }
            $results=$qry->get();
            
            $res=array(
                'success'=>true,
                'results'=>$results,
                'message'=>'All is well'
            );
        }catch(\Exception $exception){
            $res=array(
                'success'=>false,
                'message'=>$exception->getMessage()
            );
        }catch(\Throwable $throwable){
            $res=array(
                'success'=>false,
                'message'=>$throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getVariationCategoriesParameters(Request $req){

        try{
            $filters = $req->filters;

            $variation_type_id = $req->variation_type_id;
            $table_name = $req->table_name.' as t1';

            $qry=DB::table($table_name)
                ->join('par_typeof_variations as t2','t1.variation_type_id','=','t2.id')
                ->join('modules as t3','t1.module_id','=','t3.id')
                ->join('sub_modules as t4','t1.sub_module_id','=','t4.id')
                ->join('par_sections as t5','t1.section_id','=','t5.id')
                ->select('t1.*','t2.name as type_of_variation', 't3.name as module_name','t4.name as sub_module_name','t5.name as section_name');

                if(validateIsNumeric($variation_type_id)){
                    $results=$qry->where(array('t1.variation_type_id'=>$variation_type_id));
                }
            $results=$qry->get();
            
            $res=array(
                'success'=>true,
                'results'=>$results,
                'message'=>'All is well'
            );
        }catch(\Exception $exception){
            $res=array(
                'success'=>false,
                'message'=>$exception->getMessage()
            );
        }catch(\Throwable $throwable){
            $res=array(
                'success'=>false,
                'message'=>$throwable->getMessage()
            );
        }
        return \response()->json($res);

    }
    public function getsystemSubModules(Request $req){
        try{
            $filters = $req->filters;
            $module_id = $req->module_id;
            $table_name = $req->table_name.' as t1';

            $qry=DB::table($table_name)
                ->join('modules as t2','t1.module_id','=','t2.id')
                ->select('t1.*','t2.name as module_name');

                if($filters != ''){
                    $filters = (array)json_decode($filters);
                    $module_id = $filters['module_id'];

                    $results=$qry->where(array('t1.module_id'=>$module_id));
                }
                if(validateIsnumeric($module_id)){
                    $results=$qry->where(array('t1.module_id'=>$module_id));
                }
            $results=$qry->get();
            
            $res=array(
                'success'=>true,
                'results'=>$results,
                'message'=>'All is well'
            );
        }catch(\Exception $exception){
            $res=array(
                'success'=>false,
                'message'=>$exception->getMessage()
            );
        }catch(\Throwable $throwable){
            $res=array(
                'success'=>false,
                'message'=>$throwable->getMessage()
            );
        }
        return \response()->json($res);

    }
    
    public function getRefnumbersformats(Request $req){
        try{
            $filters = $req->filters;
            $table_name = 'refnumbers_formats as t1';
            $qry=DB::table($table_name)
                ->leftJoin('modules as t2','t1.module_id','=','t2.id')
                ->leftJoin('sub_modules as t3','t1.sub_module_id','=','t3.id')
                ->leftJoin('referencenumbers_types as t4','t1.refnumbers_type_id','=','t4.id')
                ->select('t1.*','t2.name as module_name','t3.name as sub_module_name', 't4.name as refnumbers_type_name');
            $results=$qry->get();
            $res=array(
                'success'=>true,
                'results'=>$results,
                'message'=>'All is well'
            );
        }catch(\Exception $exception){
            $res=array(
                'success'=>false,
                'message'=>$exception->getMessage()
            );
        }catch(\Throwable $throwable){
            $res=array(
                'success'=>false,
                'message'=>$throwable->getMessage()
            );
        }
        return \response()->json($res);

    }
    public function getregistrationexpirytime_span(Request $req){
        try{
            $filters = $req->filters;

            $table_name = 'par_registration_expirytime_span as t1';

            $qry=DB::table($table_name)
                ->leftJoin('modules as t2','t1.module_id','=','t2.id')
                ->leftJoin('sub_modules as t3','t1.sub_module_id','=','t3.id')
                ->leftJoin('par_sections as t4','t1.section_id','=','t4.id')
                ->leftJoin('par_timespan_defination as t5','t1.timespan_defination_id','=','t5.id')
                ->select('t1.*','t2.name as module_name','t3.name as sub_module_name', 't4.name as section_name','t5.name as timespan_defination' );

            $results=$qry->get();
            
            $res=array(
                'success'=>true,
                'results'=>$results,
                'message'=>'All is well'
            );
        }catch(\Exception $exception){
            $res=array(
                'success'=>false,
                'message'=>$exception->getMessage()
            );
        }catch(\Throwable $throwable){
            $res=array(
                'success'=>false,
                'message'=>$throwable->getMessage()
            );
        }
        return \response()->json($res);

    }
    
    public function getsystemModules(Request $req){
        try{
            $filters = $req->filters;

            $table_name = $req->table_name.' as t1';

            $qry=DB::table($table_name)
                ->select('t1.*');

            $results=$qry->get();
            
            $res=array(
                'success'=>true,
                'results'=>$results,
                'message'=>'All is well'
            );
        }catch(\Exception $exception){
            $res=array(
                'success'=>false,
                'message'=>$exception->getMessage()
            );
        }catch(\Throwable $throwable){
            $res=array(
                'success'=>false,
                'message'=>$throwable->getMessage()
            );
        }
        return \response()->json($res);

    }
    
    public function getproductSubCategoryParameters(Request $req)
    {
        try{
            $table_name = $req->table_name.' as t1';

            $qry=DB::table($table_name)
                ->join('par_sections as t2','t1.section_id','=','t2.id')
                ->join('par_product_categories as t3','t1.product_category_id','=','t3.id')
                ->select('t1.*','t2.name as section_name', 't3.name as product_category');
            $results=$qry->get();
            
            $res=array(
                'success'=>true,
                'results'=>$results,
                'message'=>'All is well'
            );
        }catch(\Exception $exception){
            $res=array(
                'success'=>false,
                'message'=>$exception->getMessage()
            );
        }catch(\Throwable $throwable){
            $res=array(
                'success'=>false,
                'message'=>$throwable->getMessage()
            );
        }
        return \response()->json($res);
    }
}
