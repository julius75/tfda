<?php

namespace App\Modules\OnlineServices\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Carbon;
class OnlineServicesConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('onlineservices::index');
    }
    public function getOnlinePortalServicesDetails(){
        try {
            $data = array();
            $data = DB::table('tra_online_portalservices as t1')
                        ->join('modules as t2', 't1.module_id','=','t2.id')
                        ->join('sub_modules as t3', 't1.sub_module_id','=','t3.id')
                    ->select('t1.*', 't2.name as module_name','t3.name as sub_module_name')
                    ->get();

        $res = array('results'=>$data,
                     'success' => true,
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
    
    public function getApplicationdocumentdefination(){
        try {
            $data = array();
            $records = DB::connection('portal_db')->table('wb_applicable_appdocuments as t1')
                    ->join('wb_statuses as t2', 't1.status_id', '=', 't2.id')
                    ->select('t1.*', 't2.name as status_name')
                    ->get();

                    foreach($records as $rec){
                            $process_name = getSingleRecordColValue('wf_tfdaprocesses', array('id'=>$rec->process_id), 'name');
                            $document_type = getSingleRecordColValue('par_document_types', array('id'=>$rec->document_type_id), 'name');
                            $data[] = array('id'=>$rec->id,
                                            'description'=>$rec->description,
                                            'process_name'=>$process_name,
                                            'process_id'=>$rec->process_id,
                                            'status_id'=>$rec->status_id,
                                            'status_name'=>$rec->status_name,
                                            'document_type_id'=>$rec->document_type_id,
                                            'document_type'=>$document_type,
                            );
                    }
        $res = array('results'=>$data,
                     'success' => true,
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
    
    public function getOnlineProcessTransitionsdetails(){
        try {
            $data = array();
            $records = DB::connection('portal_db')->table('wb_processstatus_transitions as t1')
                    ->join('wb_statuses as t2', 't1.current_status_id', '=', 't2.id')
                    ->join('wb_statuses as t3', 't1.next_status_id', '=', 't3.id')
                    ->select('t1.*', 't2.name as current_status','t3.name as next_status' )
                    ->get();
                    foreach($records as $rec){
                            $module_name = getSingleRecordColValue('modules', array('id'=>$rec->module_id), 'name');
                           $data[] = array('id'=>$rec->id,
                                            'description'=>$rec->description,
                                            'next_status_id'=>$rec->next_status_id,
                                            'current_status_id'=>$rec->current_status_id,
                                            'next_status'=>$rec->next_status,
                                            'current_status'=>$rec->current_status,
                                            'module_id'=>$rec->module_id,
                                            'module_name'=>$module_name
                            );
                    }
        $res = array('results'=>$data,
                     'success' => true,
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
	public function getapplicationstatusactions(Request $req){
			//the details 
		try {
				$rec = DB::connection('portal_db')->table('wb_processstatus_actions as t1')
						->select('t1.*', 't2.name as status_name', 't3.name as action_name')
						->join('wb_statuses as t2', 't1.status_id','=','t2.id')
						->join('wb_statuses_actions as t3', 't1.action_id','=','t3.id')
						->get();
            $res = array('results'=>$rec,
                            'success' => true,
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
    public function saveApplicationstatusactions(Request $req){
        try {
            $user_id = \Auth::user()->id;
            $post_data = $req->all();
            $table_name = $post_data['table_name'];
            $status_id = $post_data['status_id']; 
            $action_id = $post_data['action_id'];
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
            $where = array(
                'id' => $id
            );
            if (isset($id) && $id != "") {
                if (recordExists($table_name, $where,'portal_db')) {
                  
                    $table_data['dola'] = Carbon::now();
                    $table_data['altered_by'] = $user_id;
                    $previous_data = getPreviousRecords($table_name, $where,'portal_db');
                    if ($previous_data['success'] == false) {
                        return $previous_data;
                    }  
                    $previous_data = $previous_data['results'];
                   
                    $res = updateRecord($table_name, $previous_data, $where, $table_data, $user_id,'portal_db');
                    
                }
            } else {
                    $table_data['created_on'] = Carbon::now();
                    $table_data['created_by'] = $user_id;
                    //the data
                    // save the repository
                    $where = array(
                        'action_id' => $action_id,
                        'status_id' => $status_id
                    );

                    if (!recordExists($table_name, $where,'portal_db')) {
                        $res = insertRecord($table_name, $table_data, $user_id,'portal_db');
                        //the details 
                        
                    }else{
                        $res = array(
                            'success' => false,
                            'message' => "Data Exists"
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
    
    public function saveOnlinePortalData(Request $req){
        try {
            $user_id = \Auth::user()->id;
            $post_data = $req->all();
            $table_name = $post_data['model'];
            $id = $post_data['id'];
            $name = $req->name;

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
            
            $where = array(
                'id' => $id
            );
            $db_connect = 'portal_db';

            $where_insert = array(
                'name' => $name
            );
            if($table_name == 'tra_online_portalservices'){
                $db_connect = 'mysql';
                if(!isset($post_data['is_online'])){
                    $table_data['is_online'] =0;
                }
                $where_insert = $post_data;
            }
            
            if (isset($id) && $id != "") {
                if (recordExists($table_name, $where, $db_connect)) {
                    
                    $table_data['dola'] = Carbon::now();
                    $table_data['altered_by'] = $user_id;
                    $previous_data = getPreviousRecords($table_name, $where, $db_connect);
                    if ($previous_data['success'] == false) {
                        return $previous_data;
                    }
                    $previous_data = $previous_data['results'];
                    $res = updateRecord($table_name, $previous_data, $where, $table_data, $user_id, $db_connect);
                    
                }
            } else {
                    $table_data['created_on'] = Carbon::now();
                    $table_data['created_by'] = $user_id;
                    //the data
                    $where_insert = $post_data;
                    
                    unset($where_insert['description']);
                    unset($where_insert['code']);
                    unset($where_insert['is_enabled']);
                    if (!recordExists($table_name, $where_insert, $db_connect)) {
                        $res = insertRecord($table_name, $table_data, $user_id, $db_connect);
                        //the details 
                        $record_id = $res['record_id'];
                        $dms_node_id = '';
                    }else{
                        $res = array(
                            'success' => false,
                            'message' => "Data Exists"
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
    public function saveUniformOnlinePortalData(Request $req){
        try {
            $user_id = \Auth::user()->id;
            $post_data = $req->all();
            $table_name = $post_data['model'];
            $id = $post_data['id'];
            $record_id = $post_data['id'];
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
            
            $where = array(
                'id' => $id
            );
            if (isset($id) && $id != "") {
                if (recordExists($table_name, $where,'portal_db')) {
                    
                    $table_data['dola'] = Carbon::now();
                    $table_data['altered_by'] = $user_id;
                    $previous_data = getPreviousRecords($table_name, $where,'portal_db');
                    if ($previous_data['success'] == false) {
                        return $previous_data;
                    }
                    $previous_data = $previous_data['results'];
                    $res = updateRecord($table_name, $previous_data, $where, $table_data, $user_id,'portal_db');
                    
                }
            } else {
                     $where = $table_data;
                    $table_data['created_on'] = Carbon::now();
                    $table_data['created_by'] = $user_id;

                    if (!recordExists($table_name, $where,'portal_db')) {
                        $res = insertRecord($table_name, $table_data, $user_id,'portal_db');
                        //the details 
                        $record_id = $res['record_id'];
                       
                        $dms_node_id = '';
                    }else{
                        $res = array(
                            'success' => false,
                            'message' => "Data Exists"
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
   public function getOnlineMenuLevel0(Request $req){
            try {
                $navigation_type_id= $req->navigation_type_id;

                    $rec = DB::connection('portal_db')->table('wb_navigation_items as t1')
                            ->select('t1.*')
                            ->where(array('t1.navigation_type_id'=>$navigation_type_id, 'level'=>0) )
                            ->get();
                $res = array('results'=>$rec,
                                'success' => true,
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
   //navigations details 
   public function getSystemNavigationMenuItems(Request $request)
   {
       $row = $this->getSystemMenuItem(0, 0);
       $menus = '[';
       if (count($row)) {
           $menu_count = count($row);
           $menu_counter = 0;
           foreach ($row as $item) {
               $menu_counter++;
               $id = $item['id'];
               $name = $item['name'];
               $text = $name;
               $level = $item['level'];
               $parent_id = $item['parent_id'];
               $child_id = $item['parent_id'];
               $router_link = $item['router_link'];
               $iconCls = $item['iconCls'];
               $order_no = $item['order_no'];
               $is_online = $item['is_online'];
               $navigation_type = $item['navigation_type'];
               $navigation_type_id = $item['navigation_type_id'];

               
               $menus .= '{';
               $menus .= '"text": "' . $text . '",';
               $menus .= '"name": "' . $name . '",';
              // $menus .= '"iconCls": "' . $iconCls . '",';
               $menus .= '"menu_id": "' . $id . '",';
               $menus .= '"id": "' . $id . '",';
               //$menus .= '"access_level": "' . $access_level . '",';
               $menus .= '"router_link": "' . $router_link . '",';
               $menus .= '"level": "' . $level . '",';
               $menus .= '"navigation_type": "' . $navigation_type . '",';
               $menus .= '"order_no": "' . $order_no . '",';
               $menus .= '"navigation_type_id": "' . $navigation_type_id . '",';

               $menus .= '"is_online": "' . $is_online . '",';
               $children = $this->getSystemMenuItem(1, $id);
               if (count($children) > 0) {
                   $menus .= '"selectable": false,';
                   $children_count = count($children);
                   $children_counter = 0;
                   $menus .= '"children": [';
                   foreach ($children as $child) {
                       $children_counter++;
                       $child_id = $child['id'];
                       $child_name = $child['name'];
                       $child_text = $child_name;
                       $child_level = $child['level'];
                       $child_routerlink = $child['router_link'];
                       $child_iconCls = $child['iconCls'];
                       $child_order_no = $child['order_no'];
                       $child_is_online = $child['is_online'];
                       $child_parent_id = $child['parent_id'];
                       $child_navigation_type = $child['navigation_type'];
                       $child_type_id = $item['navigation_type_id'];

                      // $child_access_level = $this->getMenuAccessLevel($child_id);

                       $menus .= '{';
                       $menus .= '"text": "' . $child_text . '",';
                       $menus .= '"name": "' . $child_name . '",';
                       $menus .= '"iconCls": "' . $child_iconCls . '",';
                       $menus .= '"menu_id": "' . $child_id . '",';
                       $menus .= '"id": "' . $child_id . '",';
                       $menus .= '"router_link": "' . $child_routerlink . '",';
                       $menus .= '"level": "' . $child_level . '",';
                       $menus .= '"order_no": "' . $child_order_no . '",';
                       $menus .= '"is_online": "' . $child_is_online . '",';
                       $menus .= '"parent_id": ' . $child_parent_id . ',';
                       $menus .= '"navigation_type": "' . $child_navigation_type . '",';
                       $menus .= '"child_type_id": "' . $child_type_id . '",';
                        
                       $menus .= '"leaf": true';
                      
                       if ($children_counter == $children_count) {
                           $menus .= '}';
                       } else {
                           $menus .= '},';
                       }
                   }
                   $menus .= ']';
               } else {
                   $menus .= '"leaf": true';
               }
               if ($menu_counter == $menu_count) {
                   $menus .= '}';
               } else {
                   $menus .= '},';
               }
           }
       }
       $menus .= ']';
       echo $menus;
   }

   function getSystemMenuItem($level = 0, $parent_id = 0)
   {
       $where = array(
           'level' => $level,
           'parent_id'=>$parent_id
       );
      // $user_id = \Auth::user()->id;
      // $groups = getUserGroups($user_id);
     //  $belongsToSuperGroup = belongsToSuperGroup($groups);
       $qry = DB::connection('portal_db')
            ->table('wb_navigation_items as t1')
            ->join('wb_navigation_types  as t2', 't1.navigation_type_id','=','t2.id')
           ->distinct()
           ->select('t1.*', 't2.name as navigation_type')
           ->where($where);

       $menus = $qry->get();
       $menus = convertStdClassObjToArray($menus);
       return $menus;
   }
  

}
