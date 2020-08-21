<?php

namespace App\Modules\Tradermanagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class TradermanagementController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        //return view('tradermanagement::index');
    }
    public function saveTraderInformation(Request $req){
        //save the data
        try{
            $id = $req->id;
            
            $identification_no =$req->identification_no; 
            $status_id = $req->status_id;
            $email =$req->email; 
            $user_id = \Auth::user()->id;
            $table_name = 'wb_trader_account';
            $where = array('identification_no'=>$identification_no);
            $trader_data = $req->all();
            unset($trader_data['portal_id']);

            unset($trader_data['id']);
            if (validateIsNumeric($identification_no)) {
                if (recordExists($table_name, $where)) {

                    $trader_data['dola'] = Carbon::now();
                    $trader_data['altered_by'] = $user_id;
                    $previous_data = getPreviousRecords($table_name, $where);
                    if ($previous_data['success'] == false) {
                        return $previous_data;
                    }
                    $previous_data = $previous_data['results'];
                    //dms function call with validation 
                    
                    $res = updateRecord($table_name, $previous_data, $where, $trader_data, $user_id);
                    
                    $res = updateRecord($table_name, $previous_data, $where, $trader_data, $user_id,'portal_db');
                    
                    if($res['success']){
                        $res['message'] ='Trader Account details Updated Successfully';
                    }
                }
            }
            //update the details on the Online portal too
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
    public function updateAccountApprovalStatus(Request $req){
        //save the data
        try{
            $id = $req->id;
            
            $identification_no =$req->identification_no; 
            $status_id = $req->status_id;
            $remarks = $req->remarks;
            
            $email =$req->email; 
            $user_id = \Auth::user()->id;
            $table_name = 'wb_trader_account';
            $where = array('identification_no'=>$identification_no);
           $trader_data = array('status_id'=>$status_id);
            if (validateIsNumeric($identification_no)) {
                if (recordExists($table_name, $where)) {

                    $trader_data['dola'] = Carbon::now();
                    $trader_data['altered_by'] = $user_id;
                    $previous_data = getPreviousRecords($table_name, $where);
                    if ($previous_data['success'] == false) {
                        return $previous_data;
                    }
                    $previous_data = $previous_data['results'];
                    //dms function call with validation 
                   
                    $portal_id = $previous_data[0]['portal_id'];
                   
                    $res = updateRecord($table_name, $previous_data, $where, $trader_data, $user_id);
                    
                    $res = updateRecord($table_name, $previous_data, $where, $trader_data, $user_id,'portal_db');
                    
                    $where = array('trader_id'=>$portal_id);

                    $res = updateRecord('wb_traderauthorised_users', $previous_data, $where, $trader_data, $user_id,'portal_db');
                    if($res['success']){
                        $approval_data = array('identification_no'=>$identification_no, 
                                              'status_id'=>$status_id,
                                              'remarks'=>$remarks,
                                              'approval_by'=>$user_id);
                        $approval_data['created_on'] = Carbon::now();
                         $approval_data['created_by'] = $user_id;               
                        $res = insertRecord('wb_trader_accountapprovals', $approval_data, $user_id);
  
                        $res['message'] ='Trader Account Status Update saved successfully';
                    }
                }
            }
            //update the details on the Online portal too
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
    
    public function gettraderAccountsManagementDetails(Request $req){
                try{
                        $start = $req->start;
                        $limit = $req->limit;

                        $status_id = $req->status_id;
                        $search_field = $req->search_field;
                        $search_value = $req->search_value;
                        $limit = $req->limit;
                        
                        $sql = DB::table('wb_trader_account as t1')
                                    ->leftJoin('par_trader_categories as t2', 't1.trader_category_id','=','t2.id')
                                    ->leftJoin('par_account_statuses as t3', 't1.status_id','=','t3.id')
                                    ->leftJoin('par_countries as t4', 't1.country_id','=', 't4.id')
                                    ->leftJoin('par_regions as t5', 't1.region_id','=', 't5.id');
                                    
                                    if(validateIsNumeric($status_id)){
                                        $sql = $sql->where(array('status_id'=>$status_id));
                                       
                                    }
                                    $where_like = '';
                                    if($search_field != '' && $search_value != ''){
                                        $sql = $sql->where($search_field,'like','%'.$search_value.'%');
                                       
                                    }

                        $total_rows =  $sql->select('t1.id')->count();
                            
                        $data =  $sql->select(DB::raw("t1.*, t2.name as trader_category, t3.name as account_status, t4.name as country_name,t5.name as region_name "))
                                        ->offset($start*$limit)
                                        ->limit($limit)
                                        ->get();
                        $res = array('success'=>true, 'totals'=>$total_rows, 'results'=>$data);

                        
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
    public function gettraderUsersAccountsManagementDetails(Request $req){
        try{
               
                $trader_id = $req->trader_id;
                
                $data = DB::connection('portal_db')->table('wb_trader_account as t1')
                            ->join('wb_traderauthorised_users as t2', 't1.id','=','t2.trader_id')
                            ->select(DB::raw("t2.email,t2.telephone_no, t1.identification_no, t2.created_on "))
                            ->where('t1.id',$trader_id)
                            ->get();
                          
                $res = array('success'=>true,  'results'=>$data);

                
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
    
    public function getTraderStatusesCounter(){
        try{
            
            $data = array('pending_approval'=>$this->returnStatuscounter(5),
                         'registered_traders'=>$this->returnStatuscounter(1),
                         'rejected_traders'=>$this->returnStatuscounter(4),
                         'dormant_account'=>$this->returnStatuscounter(2));
            $res = array('success'=>true, 'results'=>$data);

            
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
    function returnStatuscounter($status_id){
        $counter = 0;

        $counter = DB::table('wb_trader_account as t1')
                ->join('par_account_statuses as t3', 't1.status_id','=','t3.id')
                ->where(array('status_id'=>$status_id))
                ->count();
        
         return  $counter;  
    }
    public function getDownloadTinCertificateUrl(Request $req){
        try{
               
                $trader_id = $req->trader_id;
                
                $data = DB::table('tra_traderdocuments_uploads as t1')
                            ->select(DB::raw("t1.*"))
                            ->where('t1.trader_id',$trader_id)
                            ->first();
                if($data){
                    $auth_response = authDms('');
                    $ticket = $auth_response['ticket'];
                    $node_ref = $data->node_ref;
                    $url = downloadDocumentUrl($ticket,$node_ref,'');
                    $res = array('success'=>true, 'message'=>'Document  found', 'document_url'=>$url);
                }
                else{
                    $res = array('success'=>false, 'message'=>'Document not found');
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
}
