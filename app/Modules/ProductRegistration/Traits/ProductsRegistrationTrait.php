<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 11/20/2018
 * Time: 2:31 PM
 */

namespace App\Modules\ProductRegistration\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

trait ProductsRegistrationTrait
{
    
    public function processProductsApplicationSubmission(Request $request)
    {
        $directive_id = $request->input('directive_id');
        $action = $request->input('action');
        $prev_stage = $request->input('curr_stage_id');
        $section_id = $request->input('section_id');
        $sub_module_id = $request->input('sub_module_id');
        $application_code = $request->input('application_code');

        $keep_status = $request->input('keep_status');
        
        $data = DB::table('wf_workflow_actions')
                ->where(array('stage_id'=>$request->curr_stage_id,'id'=>$request->action))
                ->select('*')
                ->first();
                
        if($data){
                 $recommendation_table = $data->recommendation_table;
                 $update_portal_status = $data->update_portal_status;
                 $portal_status_id = $data->portal_status_id;
                if($update_portal_status == 1){
                    $proceed = updatePortalApplicationStatusWithCode($application_code, 'wb_product_applications',$portal_status_id);
                    if ($proceed == false) {
                        echo json_encode($proceed);
                        exit();
                    }      
                }

                if($recommendation_table != ''){
                    $this->processRecommendationApplicationSubmission($request, $recommendation_table);
                }
                else{
                    $this->processNormalApplicationSubmission($request);
                }
        }
        else{
                 $this->processNormalApplicationSubmission($request);
        }

        
    }

    public function processProductManagersApplicationSubmission($request)
    {
        $action = $request->input('action');
        $prev_stage = $request->input('curr_stage_id');
        $section_id = $request->input('section_id');
        $sub_module_id = $request->input('sub_module_id');
        $keep_status = false;
        $data = DB::table('wf_workflow_actions')
                    ->where(array('stage_id'=>$request->curr_stage_id,'id'=>$request->action))
                    ->select('*')
                    ->first();
        
        if($data){
                $keep_status = $data->keep_status;
                if($keep_status == 1){
                     $keep_status = true;
                }
        }
        
         $this->processNormalManagersApplicationSubmission($request, $keep_status);
    }

    public function updateQueriedProductApplicationPortal(Request $request, $application_details)
    {
        $user_id = $this->user_id;
        $remarks = $request->input('remarks');
        $urgency = $request->input('urgency');
        //update portal status
        $portal_db = DB::connection('portal_db');
        $update = $portal_db->table('wb_Products_applications')
            ->where('id', $application_details->portal_id)
            ->update(array('application_status_id' => 6));
        $insert_remark = array(
            'application_id' => $application_details->portal_id,
            'remark' => $remarks,
            'urgency' => $urgency,
            'mis_created_by' => $user_id
        );
        $insert = $portal_db->table('wb_query_remarks')
            ->insert($insert_remark);
        if ($update > 0 && $insert > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function updateRejectedProductApplicationPortal(Request $request, $application_details)
    {
        $user_id = $this->user_id;
        $remarks = $request->input('remarks');
        $urgency = $request->input('urgency');
        //update portal status
        $portal_db = DB::connection('portal_db');
        $update = $portal_db->table('wb_Products_applications')
            ->where('id', $application_details->portal_id)
            ->update(array('application_status_id' => 11));
        $insert_remark = array(
            'application_id' => $application_details->portal_id,
            'remark' => $remarks,
            'urgency' => $urgency,
            'mis_created_by' => $user_id
        );
        $insert = $portal_db->table('wb_rejection_remarks')
            ->insert($insert_remark);
        if ($update > 0 && $insert > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function processProductReturnApplicationSubmissionsWithChecklists($request, $checklist_category)
    {
        $application_id = $request->input('application_id');
        $table_name = $request->input('table_name');
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
                return false;
            }
            inValidateApplicationChecklist($application_details->module_id, $application_details->sub_module_id, $application_details->section_id, $checklist_category, array($application_details->application_code));
            $this->processNormalApplicationSubmission($request);
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
            echo json_encode($res);
            return false;
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
            echo json_encode($res);
            return false;
        }
    }

    public function updateProductManagerQueryToCustomerPortal($portal_ids)
    {
        $portal_db = DB::connection('portal_db');
        //update portal status
        $update = $portal_db->table('wb_Products_applications')
            ->whereIn('id', $portal_ids)
            ->update(array('application_status_id' => 8));
        if ($update < 1) {
            return false;
        } else {
            return true;
        }
    }

    public function singleNewProductApplicationApprovalSubmission($request)
    {
        $application_code = $request->input('application_code');
        try {
            $valid = $this->validateProductApprovalApplication($application_code);
            if ($valid == false) {
                $res = array(
                    'success' => false,
                    'message' => 'Please capture recommendation details first!!'
                );
                echo json_encode($res);
                return false;
            }
            $this->processNormalApplicationSubmission($request, true);
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
            echo json_encode($res);
            return false;
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
            echo json_encode($res);
            return false;
        }
    }

    public function batchProductApplicationApprovalSubmission(Request $request)
    {
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
                return false;
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
                return false;
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

            //todo: check for allowed changes
            //1. Basic Product info
            $permit_id = 'permit_id';
            $formAmendmentDetails = DB::table('tra_process_form_auth as t1')
                ->join('par_key_form_fields as t2', 't1.field_id', '=', 't2.id')
                ->select(DB::raw("CONCAT_WS(',',GROUP_CONCAT(t2.field_name),'$permit_id') AS changed"))
                ->where('t1.process_id', $process_id)
                ->first();
            //2. Personnel(id 2) and Business(id 3) details
            $othersAmendmentDetails = DB::table('tra_process_otherparts_auth as t1')
                ->select('t1.part_id')
                ->where('t1.process_id', $process_id)
                ->get();
            //end

            //application details
            foreach ($application_details as $key => $application_detail) {
                $application_status_id = $application_detail->application_status_id;
                if ($application_detail->decision_id == 1) {
                    $this->updateRegistrationTable($application_detail->reg_Product_id, $application_detail->Product_id, $module_id);
                    /*$response = $this->processRenewalProductApprovalApplicationSubmission($application_detail->id, $table_name, $formAmendmentDetails, $othersAmendmentDetails);
                    $success = $response['success'];
                    if ($success == false) {
                        DB::rollBack();
                        echo json_encode($response);
                        return false;
                    }*/
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
                //'application_status_id' => $application_status_id
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
                return false;
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

    public function singleRenewalProductApplicationApprovalSubmission(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        $process_id = $request->input('process_id');
        try {
            $valid = $this->validateProductApprovalApplication($application_code);
            if ($valid == false) {
                $res = array(
                    'success' => false,
                    'message' => 'Please capture recommendation details first!!'
                );
                echo json_encode($res);
                return false;
            }
            //check decision
            $decision_id = DB::table('tra_approval_recommendations')
                ->where(array('application_id' => $application_id, 'application_code' => $application_code))
                ->value('decision_id');
            if ($decision_id == 1) {//granted

                //todo: check for allowed changes
                //1. Basic Product info
                $permit_id = 'permit_id';
                $formAmendmentDetails = DB::table('tra_process_form_auth as t1')
                    ->join('par_key_form_fields as t2', 't1.field_id', '=', 't2.id')
                    ->select(DB::raw("CONCAT_WS(',',GROUP_CONCAT(t2.field_name),'$permit_id') AS changed"))
                    ->where('t1.process_id', $process_id)
                    ->first();
                //2. Personnel(id 2) and Business(id 3) details
                $othersAmendmentDetails = DB::table('tra_process_otherparts_auth as t1')
                    ->select('t1.part_id')
                    ->where('t1.process_id', $process_id)
                    ->get();
                //end

                $response = $this->processRenewalProductApprovalApplicationSubmission($application_id, $table_name, $formAmendmentDetails, $othersAmendmentDetails);
                $success = $response['success'];
                if ($success == false) {
                    echo json_encode($response);
                    return false;
                }
            }
            $this->processNormalApplicationSubmission($request);
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
            echo json_encode($res);
            return false;
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
            echo json_encode($res);
            return false;
        }
    }

    public function processRenewalProductApprovalApplicationSubmission($application_id, $table_name, $formAmendmentDetails, $othersAmendmentDetails)
    {
        $user_id = $this->user_id;
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
                return $res;
            }
            $Product_id = $application_details->Product_id;
            $temp_details = DB::table('tra_Products')
                ->where('id', $Product_id)
                ->first();
            if (is_null($temp_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching (temp)Product details!!'
                );
                return $res;
            }
            $init_Product_id = $temp_details->init_Product_id;
            $current_permit_id = $temp_details->permit_id;
            //Product log data
            $log_data = DB::table('tra_Products as t1')
                ->select(DB::raw("t1.*,t1.id as Product_id,$user_id as log_by,NOW() as log_date"))
                ->where('id', $init_Product_id)
                ->first();
            //todo: update renewal changes
            //1. Basic Product info
            if ($formAmendmentDetails->changed == '') {
                //No changes on basic Product info
            } else {
                $this->updateAlterationBasicDetails($formAmendmentDetails, $Product_id, $init_Product_id, $log_data);
            }
            //2. Personnel(id 2) and Business(id 3) details
            if (count($othersAmendmentDetails) > 0) {
                foreach ($othersAmendmentDetails as $othersAmendmentDetail) {
                    if ($othersAmendmentDetail->part_id == 2) {
                        //update personnel details
                        $this->updateAlterationPersonnelDetails($Product_id, $init_Product_id);
                    }
                    if ($othersAmendmentDetail->part_id == 3) {
                        //update business details
                        $this->updateAlterationBusinessDetails($Product_id, $init_Product_id);
                    }
                }
            }
            updateRenewalPermitDetails($init_Product_id, $current_permit_id, 'tra_Products');

            $res = array(
                'success' => true,
                'message' => 'Assumed Success!!'
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
        return $res;
    }

    public function batchProductAlterationApplicationApprovalSubmission($request)
    {
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
                return false;
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
                return false;
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
            //$application_status_id = getApplicationTransitionStatus($from_stage, $action, $to_stage);
            //application details
            foreach ($application_details as $key => $application_detail) {
                $application_status_id = $application_detail->application_status_id;
                if ($application_detail->decision_id == 1) {
                    $this->updateProductAlterationPermitDetails($application_detail->Product_id);
                    $this->updateRegistrationTable($application_detail->reg_Product_id, $application_detail->Product_id, $module_id);
                    /* $response = $this->processAlterationProductApprovalApplicationSubmission($application_detail->id, $table_name);
                     $success = $response['success'];
                     if ($success == false) {
                         DB::rollBack();
                         echo json_encode($response);
                         return false;
                     }*/
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
                //'application_status_id' => $application_status_id
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
                return false;
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

    public function updateProductAlterationPermitDetails($Product_id)
    {
        $user_id = $this->user_id;
        try {
            //get application_details
            $current_details = DB::table('tra_Products')
                ->where('id', $Product_id)
                ->first();
            if (is_null($current_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching (current)Product details!!'
                );
                return $res;
            }
            $init_Product_id = $current_details->init_Product_id;
            $current_permit_id = $current_details->permit_id;
            //Product log data
            $log_data = DB::table('tra_Products as t1')
                ->select(DB::raw("t1.*,t1.id as Product_id,$user_id as log_by,NOW() as log_date"))
                ->where('id', $init_Product_id)
                ->first();
            $init_permit_id = $log_data->permit_id;

            $initPermitDetails = DB::table('tra_approval_recommendations as t1')
                ->select('t1.certificate_no', 't1.approval_date', 't1.expiry_date')
                ->where('t1.id', $init_permit_id)
                ->first();
            $initPermitDetails = convertStdClassObjToArray($initPermitDetails);

            DB::table('tra_approval_recommendations')
                ->where('id', $current_permit_id)
                ->update($initPermitDetails);

            $res = array(
                'success' => true,
                'message' => 'Assumed Success!!'
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
        return $res;
    }

    public function processAlterationProductApprovalApplicationSubmission($application_id, $table_name)
    {
        $user_id = $this->user_id;
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
                return $res;
            }
            $Product_id = $application_details->Product_id;
            $application_code = $application_details->application_code;
            $temp_details = DB::table('tra_Products')
                ->where('id', $Product_id)
                ->first();
            if (is_null($temp_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching (temp)Product details!!'
                );
                return $res;
            }
            $init_Product_id = $temp_details->init_Product_id;
            $temp_permit_id = $temp_details->permit_id;
            //Product log data
            $log_data = DB::table('tra_Products as t1')
                ->select(DB::raw("t1.*,t1.id as Product_id,$user_id as log_by,NOW() as log_date"))
                ->where('id', $init_Product_id)
                ->first();
            $init_permit_id = $log_data->permit_id;
            //todo get alteration requests
            //1. Basic Product info
            $formAmendmentDetails = DB::table('tra_alt_formparts_amendments as t1')
                ->join('par_key_form_fields as t2', 't1.field_id', '=', 't2.id')
                ->select(DB::raw("GROUP_CONCAT(t2.field_name) AS changed"))
                ->where('t1.application_code', $application_code)
                ->first();
            if ($formAmendmentDetails->changed == '') {
                //No changes on basic Product info
            } else {
                $this->updateAlterationBasicDetails($formAmendmentDetails, $Product_id, $init_Product_id, $log_data);
            }
            //2. Personnel(id 2) and Business(id 3) details
            $othersAmendmentDetails = DB::table('tra_alt_otherparts_amendments as t1')
                ->select('t1.part_id')
                ->where('t1.application_code', $application_code)
                ->get();
            if (count($othersAmendmentDetails) > 0) {
                foreach ($othersAmendmentDetails as $othersAmendmentDetail) {
                    if ($othersAmendmentDetail->part_id == 2) {
                        //update personnel details
                        $this->updateAlterationPersonnelDetails($Product_id, $init_Product_id);
                    }
                    if ($othersAmendmentDetail->part_id == 3) {
                        //update business details
                        $this->updateAlterationBusinessDetails($Product_id, $init_Product_id);
                    }
                }
            }
            //update permit details
            $this->updateAlterationPermitDetails($temp_permit_id, $init_permit_id);

            $res = array(
                'success' => true,
                'message' => 'Assumed Success!!'
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
        return $res;
    }


    //VALIDATION FUNCTIONS
    public function validateProductReceivingQueriedApplication($application_id)
    {
        $return_val = true;
        //for queried there should be unclosed queries
        $unclosed_queries = DB::table('checklistitems_responses as t1')
            ->join('checklistitems_queries as t2', 't1.id', '=', 't2.item_resp_id')
            ->where('t1.application_id', $application_id)
            ->where('t2.status', '<>', 4)
            ->count();
        if ($unclosed_queries < 1) {
            $return_val = false;
        }
        return $return_val;
    }

    public function validateProductInspectionApplication($application_code)
    {
        $return_val = true;
        //check if inspection details were captured
        $qry = DB::table('inspection_details as t1')
            ->join('inspection_inspectors as t2', 't1.id', '=', 't2.inspection_id')
            ->where('t1.application_code', $application_code);
        $count = $qry->count();
        if ($count < 1) {
            $return_val = false;
        }
        return $return_val;
    }
    
    public function validateProductApprovalApplication($application_code)
    {
        $return_val = true;
        //check if approval/recommendation details were captured
        $qry = DB::table('tra_approval_recommendations as t1')
            ->where('t1.application_code', $application_code);
        $count = $qry->count();
        if ($count < 1) {
            $return_val = false;
        }
        return $return_val;
    }

    public function saveProductApplicationApprovalDetails(Request $request, $sub_module_id)
    {
     
        if ($sub_module_id == 7) {
            $res = $this->saveProductApplicationRecommendationDetails($request);
        }else if ($sub_module_id == 8) {
            $res = $this->saveProductApplicationRenewalRecommendationDetails($request);
        }else if ($sub_module_id == 9) {
            $res = $this->saveProductApplicationAlterationRecommendationDetails($request);
        } else {
           // $res = $this->saveProductApplicationAlterationRecommendationDetails($request);
        }
        return $res;
    }

    public function saveProductApplicationRecommendationDetails(Request $request)
    {
        
        $table_name = $request->input('table_name');
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $qry = DB::table($table_name.'  as t1')
            ->join('tra_product_information as t2','t1.product_id','=','t2.id')
            ->where('t1.id', $application_id);
        $app_details = $qry->first();
        if (is_null($app_details)) {
            $res = array(
                'success' => false,
                'message' => 'Problem encountered while getting application details!!'
            );
            return $res;
        }
        $qry = DB::table($table_name.'  as t1')
            ->join('tra_product_information as t2','t1.product_id','=','t2.id')
            ->where('t1.id', $application_id);
        $res = array();
        try {
            DB::transaction(function () use ($qry, $application_id, $application_code, $table_name, $request, $app_details, &$res) {
                $ProductUpdateParams = array();
                $id = $request->input('recommendation_id');
                $process_id = $request->input('process_id');
                $workflow_stage_id = $request->input('workflow_stage_id');
                $decision_id = $request->input('decision_id');
                $comment = $request->input('comment');
                $approved_by = $request->input('approved_by');
                $approval_date = formatDate($request->input('approval_date'));
                $expiry_date = $request->input('expiry_date');
                $dg_signatory = $request->input('dg_signatory');
                $signatory = $request->input('permit_signatory');
                $user_id = $this->user_id;
                $classification_id = $app_details->classification_id;
                $section_id = $app_details->section_id;
                $product_type_id = $app_details->product_type_id;
                $device_type_id = $app_details->device_type_id;
                $sub_module_id = $app_details->sub_module_id;
                $module_id = $app_details->module_id;
                $section_id = $app_details->section_id;
                if ($dg_signatory == 1) {
                    $permit_signatory = getPermitSignatory($process_id);
                } else {
                    $permit_signatory = $signatory;
                }
                $params = array(
                    'application_id' => $application_id,
                    'application_code' => $application_code,
                    'workflow_stage_id' => $workflow_stage_id,
                    'decision_id' => $decision_id,
                    'comment' => $comment,
                    'approval_date' => $approval_date,
                    'certificate_issue_date' => $approval_date,
                    'expiry_date' => $expiry_date,
                    'approved_by' => $approved_by,
                    'dg_signatory' => $dg_signatory,
                    'permit_signatory' => $permit_signatory
                );

                        if($section_id == 1){
                            $ref_id = 14;
                        }
                        else{
                            $ref_id = 13;
                        }

                        if($decision_id == 1){

                            $params['expiry_date'] = getApplicationExpiryDate($approval_date,$sub_module_id,$module_id,$section_id);

                        }

                        $ProductUpdateParams['certificate_issue_date'] = $approval_date;
                        if (validateIsNumeric($id)) {
                            //update
                            $where = array(
                                'id' => $id
                            );
                            $params['dola'] = Carbon::now();
                            $params['altered_by'] = $user_id;
                            $prev_data = getPreviousRecords('tra_approval_recommendations', $where);
                            if ($prev_data['success'] == false) {
                                return \response()->json($prev_data);
                            }
                            $prev_data_results = $prev_data['results'];
                            $prev_decision_id = $prev_data_results[0]['decision_id'];
                            $prev_data_results[0]['record_id'] = $id;
                            $prev_data_results[0]['update_by'] = $user_id;
                            $prev_data_results[0]['recommendation_id'] = $prev_data_results[0]['id'];
                            unset($prev_data_results[0]['id']);
        
                            //permits no formats ref id 
                        
                            DB::table('tra_approval_recommendations_log')
                                ->insert($prev_data_results);
                            if ($decision_id == 1) {
                                $product_status_id = 6;
                                $portal_status_id = 10;
                                $application_status_id = 6;
                                $qry->update(array('application_status_id' => 6));
                                //permit
                                if ($prev_decision_id != 1) {
                                    $certificate_no = generateProductRegistrationNo($app_details->zone_id, $app_details->section_id,$classification_id,$product_type_id, $device_type_id,$table_name, $user_id, $ref_id);
                                    $params['certificate_no'] = $certificate_no;
                                }
                                $registration_data = array('tra_product_id'=>$app_details->product_id, 
                                                        'status_id'=>6,
                                                        'validity_status_id'=>2,
                                                        'registration_status_id'=>2,
                                                        'registration_date'=>$approval_date
                                                    );
                            } else {
                                $product_status_id = 3;
                                $portal_status_id = 11;
                                $application_status_id = 3;
                                $qry->update(array('application_status_id' => 7));
                                $params['certificate_no'] = null;
                                $registration_data = array('tra_product_id'=>$app_details->product_id, 
                                                        'status_id'=>7,
                                                        'validity_status_id'=>3,
                                                        'registration_status_id'=>3,
                                                        'registration_date'=>$approval_date
                                                    );
                            }
                            $res = updateRecord('tra_approval_recommendations', $prev_data['results'], $where, $params, $user_id);
                            
                        } else {
                            //insert
                        
                            $params['created_on'] = Carbon::now();
                            $params['created_by'] = $user_id;
                            if ($decision_id == 1) {
                                $portal_status_id = 10;
                                $product_status_id = 6;
                                //permits
                                $application_status_id = 6;
                                $certificate_no = generateProductRegistrationNo($app_details->zone_id, $app_details->section_id,$classification_id,$product_type_id, $device_type_id,$table_name, $user_id, $ref_id);
                               $params['certificate_no'] = $certificate_no;

                                $params['expiry_date'] = getApplicationExpiryDate($approval_date,$sub_module_id,$module_id,$section_id);

                                $qry->update(array('application_status_id' => 6));
                                $registration_data = array('tra_product_id'=>$app_details->product_id, 
                                                            'status_id'=>6,
                                                            'validity_status_id'=>2,
                                                            'registration_status_id'=>2,
                                                            'registration_date'=>$approval_date
                                                        );

                            } else {
                                $portal_status_id = 11;
                                $product_status_id = 6;
                                $application_status_id = 7;
                                $qry->update(array('application_status_id' => 7));
                                $params['certificate_no'] = '';
                                $params['expiry_date'] = null;
                                $registration_data = array('tra_product_id'=>$app_details->product_id, 
                                                        'status_id'=>7,
                                                        'validity_status_id'=>3,
                                                        'registration_status_id'=>3,
                                                        'registration_date'=>$approval_date,
                                                        
                                                    );
                                
                            }
                            
                            $res = insertRecord('tra_approval_recommendations', $params, $user_id);
                           
                            $id = $res['record_id'];

                        }
                        $where_statement = array('tra_product_id'=>$app_details->product_id);
                            
                        $res = saveApplicationRegistrationDetails('tra_registered_products',$registration_data,$where_statement,$user_id);
                        
                        //update Portal Status
                        updatePortalApplicationStatusWithCode($application_code, 'wb_product_applications',$portal_status_id);

                        //finally update the reqistered products details
                        if($res['success']){
                            $app_data =  array('permit_id' => $id, 
                                                'reg_product_id' => $res['record_id'], 
                                                'application_status_id'=>$application_status_id,
                                                'dola' => Carbon::now(),
                                                'altered_by' => $user_id);
                            $app_where = array('id'=>$application_id);
                            $appprev_data = getPreviousRecords('tra_product_applications', $app_where);
                            $res = updateRecord('tra_product_applications', $appprev_data['results'], $app_where,$app_data, $user_id);
                            //update applicaiton registration statuses
                            
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
        return $res;
    }
    //renewal 
    
    public function saveProductApplicationRenewalRecommendationDetails(Request $request)
    {
        $table_name = $request->input('table_name');
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $reg_product_id = $request->input('reg_product_id');
       
      
        $qry = DB::table($table_name.'  as t1')
            ->join('tra_product_information as t2','t1.product_id','=','t2.id')
            ->where('t1.id', $application_id);
        $app_details = $qry->first();
        if (is_null($app_details)) {
            $res = array(
                'success' => false,
                'message' => 'Problem encountered while getting application details!!'
            );
            return $res;
        }
        $qry = DB::table($table_name.'  as t1')
            ->join('tra_product_information as t2','t1.product_id','=','t2.id')
            ->where('t1.id', $application_id);
           
        $res = array();
        try {
           
            DB::transaction(function () use ($qry, $application_id, $application_code, $table_name, $request, $app_details,$reg_product_id, &$res) {
                $ProductUpdateParams = array();
                $id = $request->input('recommendation_id');
                $process_id = $request->input('process_id');
                $workflow_stage_id = $request->input('workflow_stage_id');
                $decision_id = $request->input('decision_id');
                $comment = $request->input('comment');
                $approved_by = $request->input('approved_by');
                $approval_date = formatDate($request->input('approval_date'));
                $expiry_date = $request->input('expiry_date');
                $dg_signatory = $request->input('dg_signatory');
                $signatory = $request->input('permit_signatory');

                $prev_product_id= $request->input('prev_product_id');
                $user_id = $this->user_id;

                $sub_module_id = $app_details->sub_module_id;
                $module_id = $app_details->module_id;
                $section_id = $app_details->section_id;
        
                if ($dg_signatory == 1) {
                    $permit_signatory = getPermitSignatory($process_id);
                } else {
                    $permit_signatory = $signatory;
                }
                //get the previous produt registration
                
                $params = array(
                    'application_id' => $application_id,
                    'application_code' => $application_code,
                    'workflow_stage_id' => $workflow_stage_id,
                    'decision_id' => $decision_id,
                    'comment' => $comment,
                    'approval_date' => $approval_date,
                    'approved_by' => $approved_by,
                    'dg_signatory' => $dg_signatory,
                    'permit_signatory' => $permit_signatory
                );
                
                        if (validateIsNumeric($id)) {
                            //update
                            $where = array(
                                'id' => $id
                            );
                            $params['dola'] = Carbon::now();
                            $params['altered_by'] = $user_id;

                            $prev_data = getPreviousRecords('tra_approval_recommendations', $where);
                            
                            if ($prev_data['success'] == false) {
                                return \response()->json($prev_data);
                            }
                            $prev_data_results = $prev_data['results'];
                            $prev_decision_id = $prev_data_results[0]['decision_id'];
                            $prev_data_results[0]['record_id'] = $id;
                            $prev_data_results[0]['update_by'] = $user_id;
                            $prev_data_results[0]['recommendation_id'] = $prev_data_results[0]['id'];
                            unset($prev_data_results[0]['id']);
                            
                            //permits no formats ref id 
                        
                            DB::table('tra_approval_recommendations_log')
                                ->insert($prev_data_results);
                               
                            if($decision_id == 1){
                                $product_status_id = 6;
                                $application_status_id = 6;
                                //permit
                                if ($prev_decision_id != 1) {
                                 //   need to get the prev data
                                    
                                    $where_statement = array('t1.prev_product_id'=>$prev_product_id); 
                                  
                                    $prev_productreg = getPreviousProductRegistrationDetails($where_statement, 'tra_registered_products');
                                   
                                    $prev_product_id = $prev_productreg->prev_product_id;

                                    $prev_productexpirydate =  $prev_productreg->expiry_date;
                                   
                                    $product_expirydate = getApplicationExpiryDate($prev_productexpirydate,$sub_module_id,$module_id,$section_id);
                                   
                                    $params['expiry_date'] = getApplicationExpiryDate($prev_productexpirydate,$sub_module_id,$module_id,$section_id);

                                    $params['certificate_issue_date'] = $prev_productexpirydate;

                                    $params['certificate_no'] = $prev_productreg->certificate_no;
                                    
                                    $registration_data = array('tra_product_id'=>$app_details->product_id, 
                                                                'status_id'=>$prev_productreg->status_id,
                                                                'validity_status_id'=>$prev_productreg->validity_status_id,
                                                                'registration_status_id'=>$prev_productreg->registration_status_id,
                                                                'prev_product_id'=>$prev_product_id,
                                                                'registration_date'=>$prev_productreg->approval_date
                                                            );
                                    $res = saveApplicationRegistrationDetails('tra_registered_products',$registration_data,array('id'=>$reg_product_id),$user_id);
                             
                                }
                               
                            } else {
                               
                                $application_status_id = 7;
                                $params['certificate_no'] = null;
                                $registration_data = array('tra_product_id'=>$app_details->product_id, 
                                                        'status_id'=>7,
                                                        'validity_status_id'=>3,
                                                        'registration_status_id'=>3,
                                                        'registration_date'=>$approval_date
                                                    );
                                 $res = saveApplicationRegistrationDetails('tra_registered_products',$registration_data,array('id'=>$reg_product_id),$user_id);
                             
                            }
                                 
                            $res = updateRecord('tra_approval_recommendations', $prev_data['results'], $where, $params, $user_id);
                            
                        } else {
                            //insert
                           
                            $application_status_id = 6;
                            $where_statement = array('t1.id'=>$reg_product_id);
                            $prev_productreg = getPreviousProductRegistrationDetails($where_statement, 'tra_registered_products');
                            $prev_product_id = $prev_productreg->prev_product_id;
                           
                            if($decision_id == 1){
                                $prev_productexpirydate =  $prev_productreg->expiry_date;
                                $expiry_date = getApplicationExpiryDate($prev_productexpirydate,$sub_module_id,$module_id,$section_id);
                                $params['expiry_date'] = date('Y-m-d H:i:s', strtotime($expiry_date));

                                $params['certificate_issue_date'] = $prev_productexpirydate;
                                $params['certificate_no'] = $prev_productreg->certificate_no;
                                
                                $registration_data = array('tra_product_id'=>$app_details->product_id, 
                                                            'status_id'=>$prev_productreg->status_id,
                                                            'validity_status_id'=>$prev_productreg->validity_status_id,
                                                            'registration_status_id'=>$prev_productreg->registration_status_id,
                                                            'prev_product_id'=>$prev_product_id,
                                                            'registration_date'=>$prev_productreg->approval_date
                                                        );
                                              
                            }else {

                                $application_status_id = 7;
                                $params['certificate_no'] = '';
                                $params['expiry_date'] = null;

                                $registration_data = array('tra_product_id'=>$app_details->product_id, 
                                                        'status_id'=>7,
                                                        'validity_status_id'=>3,
                                                        'registration_status_id'=>3,
                                                        'prev_product_id'=>$prev_product_id
                                                    );

                            }
                            $res = saveApplicationRegistrationDetails('tra_registered_products',$registration_data,array('id'=>$reg_product_id),$user_id);
                            
                            $params['created_on'] = Carbon::now();
                            $params['created_by'] = $user_id;
                            $res = insertRecord('tra_approval_recommendations', $params, $user_id);
                      
                            $id = $res['record_id'];
                            $app_data =  array('permit_id' => $id, 
                                               'application_status_id'=>$application_status_id,
                                               'dola' => Carbon::now(),
                                               'altered_by' => $user_id);
                            $app_where = array('id'=>$application_id);
                            $appprev_data = getPreviousRecords('tra_product_applications', $app_where);
                            $res = updateRecord('tra_product_applications', $appprev_data['results'], $app_where,$app_data, $user_id);
                            
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
        return $res;
    }
    //alteration
    public function saveProductApplicationAlterationRecommendationDetails(Request $request)
    {
        $table_name = $request->input('table_name');
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $reg_product_id = $request->input('reg_product_id');
       
        $qry = DB::table($table_name.'  as t1')
            ->join('tra_product_information as t2','t1.product_id','=','t2.id')
            ->where('t1.id', $application_id);
        $app_details = $qry->first();
        if (is_null($app_details)) {
            $res = array(
                'success' => false,
                'message' => 'Problem encountered while getting application details!!'
            );
            return $res;
        }
        $qry = DB::table($table_name.'  as t1')
            ->join('tra_product_information as t2','t1.product_id','=','t2.id')
            ->where('t1.id', $application_id);
        $res = array();
        try {
           
            DB::transaction(function () use ($qry, $application_id, $application_code, $table_name, $request, $app_details,$reg_product_id, &$res) {
                $ProductUpdateParams = array();
                $id = $request->input('recommendation_id');
                $process_id = $request->input('process_id');
                $workflow_stage_id = $request->input('workflow_stage_id');
                $decision_id = $request->input('decision_id');
                $comment = $request->input('comment');
                $approved_by = $request->input('approved_by');
                $approval_date = formatDate($request->input('approval_date'));
                $expiry_date = $request->input('expiry_date');
                $dg_signatory = $request->input('dg_signatory');
                $signatory = $request->input('permit_signatory');
                $user_id = $this->user_id;

                if ($dg_signatory == 1) {
                    $permit_signatory = getPermitSignatory($process_id);
                } else {
                    $permit_signatory = $signatory;
                }
                //get the previous produt registration

               
                $params = array(
                    'application_id' => $application_id,
                    'application_code' => $application_code,
                    'workflow_stage_id' => $workflow_stage_id,
                    'decision_id' => $decision_id,
                    'comment' => $comment,
                    'approval_date' => $approval_date,
                    'approved_by' => $approved_by,
                    'dg_signatory' => $dg_signatory,
                    'permit_signatory' => $permit_signatory
                );
                
                        if (validateIsNumeric($id)) {
                            //update
                            $where = array(
                                'id' => $id
                            );
                            $params['dola'] = Carbon::now();
                            $params['altered_by'] = $user_id;

                            $prev_data = getPreviousRecords('tra_approval_recommendations', $where);
                            
                            if ($prev_data['success'] == false) {
                                return \response()->json($prev_data);
                            }
                            $prev_data_results = $prev_data['results'];
                            $prev_decision_id = $prev_data_results[0]['decision_id'];
                            $prev_data_results[0]['record_id'] = $id;
                            $prev_data_results[0]['update_by'] = $user_id;
                            $prev_data_results[0]['recommendation_id'] = $prev_data_results[0]['id'];
                            unset($prev_data_results[0]['id']);
                            
                            //permits no formats ref id 
                        
                            DB::table('tra_approval_recommendations_log')
                                ->insert($prev_data_results);
                               
                            if($decision_id == 1){
                                
                              
                                $product_status_id = 6;
                                $application_status_id = 6;
                                //permit
                                if ($prev_decision_id != 1) {
                                    $where_statement = array('t1.id'=>$reg_product_id);
                                    $prev_productreg = getPreviousProductRegistrationDetails($where_statement, 'tra_registered_products');
                                    $prev_product_id = $prev_productreg->prev_product_id;
                                    
                                    $params['expiry_date'] = $prev_productreg->expiry_date;
                                    $params['certificate_issue_date'] = $prev_productreg->certificate_issue_date;
                                    $params['certificate_no'] = $prev_productreg->certificate_no;
                                    
                                    $registration_data = array('tra_product_id'=>$app_details->product_id, 
                                                                'status_id'=>$prev_productreg->status_id,
                                                                'validity_status_id'=>$prev_productreg->validity_status_id,
                                                                'registration_status_id'=>$prev_productreg->registration_status_id,
                                                                'prev_product_id'=>$prev_product_id,
                                                                'registration_date'=>$prev_productreg->approval_date
                                                            );
                                    $res = saveApplicationRegistrationDetails('tra_registered_products',$registration_data,array('id'=>$reg_product_id),$user_id);
                                   
                                }
                               
                            } else {
                                
                                if ($prev_decision_id == 1) {
                                      //rollback option save prev
                                      $where_statement = array('t1.id'=>$reg_product_id, 'tra_product_id'=>$app_details->product_id);
                                      $prev_productreg = getPreviousProductRegistrationDetails($where_statement, 'tra_registered_products');

                                      $registration_data = array('tra_product_id'=>$prev_productreg->regprev_product_id, 
                                                                'status_id'=>$prev_productreg->status_id,
                                                                'validity_status_id'=>$prev_productreg->validity_status_id,
                                                                'registration_status_id'=>$prev_productreg->registration_status_id,
                                                                'prev_product_id'=>0,
                                                                'registration_date'=>$prev_productreg->approval_date
                                                            );

                                    $res = saveApplicationRegistrationDetails('tra_registered_products',$registration_data,array('id'=>$reg_product_id),$user_id);
                                    

                                }
                                $application_status_id = 7;
                                $params['certificate_no'] = null;
                                $registration_data = array('tra_product_id'=>$app_details->product_id, 
                                                        'status_id'=>7,
                                                        'validity_status_id'=>3,
                                                        'registration_status_id'=>3,
                                                        'registration_date'=>$approval_date
                                                    );
                            }
                            
                            $res = updateRecord('tra_approval_recommendations', $prev_data['results'], $where, $params, $user_id);
                            
                        } else {
                            //insert
                           
                            $application_status_id = 6;
                            $where_statement = array('t1.id'=>$reg_product_id);
                            $prev_productreg = getPreviousProductRegistrationDetails($where_statement, 'tra_registered_products');
                            $prev_product_id = $prev_productreg->prev_product_id;
                           
                            if($decision_id == 1){

                                $params['expiry_date'] = $prev_productreg->expiry_date;
                                $params['certificate_issue_date'] = $prev_productreg->certificate_issue_date;
                                $params['certificate_no'] = $prev_productreg->certificate_no;
                                
                                $registration_data = array('tra_product_id'=>$app_details->product_id, 
                                                            'status_id'=>$prev_productreg->status_id,
                                                            'validity_status_id'=>$prev_productreg->validity_status_id,
                                                            'registration_status_id'=>$prev_productreg->registration_status_id,
                                                            'prev_product_id'=>$prev_product_id,
                                                            'registration_date'=>$prev_productreg->approval_date
                                                        );
                                $res = saveApplicationRegistrationDetails('tra_registered_products',$registration_data,array('id'=>$reg_product_id),$user_id);
                                //finally update the reqistered products details
                                               
                            }else {

                                $application_status_id = 7;
                                $params['certificate_no'] = '';
                                $params['expiry_date'] = null;

                                $registration_data = array('tra_product_id'=>$app_details->product_id, 
                                                        'status_id'=>7,
                                                        'validity_status_id'=>3,
                                                        'registration_status_id'=>3,
                                                        'registration_date'=>$approval_date
                                                    );

                                //no update on the registration statuses 

                            }

                            $params['created_on'] = Carbon::now();
                            $params['created_by'] = $user_id;
                            $res = insertRecord('tra_approval_recommendations', $params, $user_id);
                            $id = $res['record_id'];
                            $app_data =  array('permit_id' => $id, 
                                               'application_status_id'=>$application_status_id,
                                               'dola' => Carbon::now(),
                                               'altered_by' => $user_id);
                            $app_where = array('id'=>$application_id);
                            $appprev_data = getPreviousRecords('tra_product_applications', $app_where);
                            $res = updateRecord('tra_product_applications', $appprev_data['results'], $app_where,$app_data, $user_id);
                            
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
        return $res;
    }
    public function saveProductOnlineApplicationDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $responsible_user = $request->input('responsible_user');
        $urgency = $request->input('urgency');
        $comment = $request->input('remarks');
        $user_id = $this->user_id;
        
        $applications_table = 'tra_product_applications';
        DB::beginTransaction();
        try {
          
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_product_applications as t1')
            ->join('wb_product_information as t2', 't1.product_id','=','t2.id')
            ->select('t1.*','t1.reg_product_id', 't2.classification_id','t1.assessment_procedure_id')
                ->where('t1.id', $application_id);
            $results = $qry->first();

            if (is_null($results)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting portal application details, consult System Admin!!'
                );
                return $res;
            }
            $tracking_no = $results->tracking_no;
            $sub_module_id = $results->sub_module_id;
            $module_id = $results->module_id;
            $zone_id = $results->zone_id;
            $section_id = $results->section_id;
            $classification_id = $results->classification_id;
            
            $assessment_procedure_id = $results->assessment_procedure_id;
            
            $portal_application_id = $results->id;
            $reg_product_id = $results->reg_product_id;
            $portal_product_id = $results->product_id;
            //process/workflow details
            $where = array(
                'module_id' => $results->module_id,
                'sub_module_id' => $results->sub_module_id,
                'section_id' => $results->section_id
            );
            $process_details = getTableData('wf_tfdaprocesses', $where);
            if (is_null($process_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting process details, consult System Admin!!'
                );
                return $res;
            }
            $process_id = $process_details->id;
            $where2 = array(
                'workflow_id' => $process_details->workflow_id,
                'stage_status' => 1
            );
            $workflow_details = getTableData('wf_workflow_stages', $where2);
            if (is_null($workflow_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting workflow details, consult System Admin!!'
                );
                return $res;
            }

            //$ref_no = $results->reference_no;
          
                $ref_id = getSingleRecordColValue('refnumbers_formats', array('sub_module_id' => $sub_module_id, 'module_id' => $module_id, 'refnumbers_type_id' => 1), 'id');

                if($sub_module_id == 7){
                    $zone_code = getSingleRecordColValue('par_zones', array('id' => $zone_id), 'zone_code');
                    $section_code = getSingleRecordColValue('par_sections', array('id' => $section_id), 'code');
                    $class_code = getSingleRecordColValue('par_classifications', array('id' => $classification_id), 'code');

                    $assessment_code = getSingleRecordColValue('par_assessment_procedures', array('id' => $assessment_procedure_id), 'code');

                    $codes_array = array(
                        'section_code' => $section_code,
                        'zone_code' => $zone_code,
                        'class_code' => $class_code,
                        'assessment_code' => $assessment_code
                    );
                    $application_status = getApplicationInitialStatus($module_id, $sub_module_id);

                    $ref_no = generateProductsRefNumber($ref_id, $codes_array, date('Y'), $process_id, $zone_id, $user_id);
            
                }
                else{

                    $where_statement = array('sub_module_id' => 7, 't1.reg_product_id' => $reg_product_id);
                    $primary_reference_no = getProductPrimaryReferenceNo($where_statement, 'tra_product_applications');
                    $codes_array = array(
                        'ref_no' => $primary_reference_no
                    );
                    $ref_no = generateProductsSubRefNumber($reg_product_id, $applications_table, $ref_id, $codes_array, $sub_module_id, $user_id);

                }
                
            $application_code = $results->application_code;;

            $applicant_details = $portal_db->table('wb_trader_account')
                ->where('id', $results->trader_id)
                ->first();
            
                $localgent_details = $portal_db->table('wb_trader_account')
                ->where('id', $results->local_agent_id)
                ->first();
                
            if (is_null($applicant_details)) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting applicant details, consult System Admin!!'
                );
                return $res;
            }
            
            $applicant_id = getSingleRecordColValue('wb_trader_account', array('identification_no' => $applicant_details->identification_no), 'id');
            $local_agent_id = getSingleRecordColValue('wb_trader_account', array('identification_no' => $localgent_details->identification_no), 'id');
            
            $applicant_email = $applicant_details->email;
            $localagent_email = $localgent_details->email;
            
            //premise main details
            $product_details = $portal_db->table('wb_product_information')
                ->where('id', $results->product_id)
                ->first();

            if (is_null($product_details)) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting application details, consult System Admin!!'
                );
                return $res;
            }
            $product_details->portal_id = $results->product_id;
            $product_details->created_by = $this->user_id;
            $product_details = convertStdClassObjToArray($product_details);

            unset($product_details['id']);
            $product_details['created_on'] = Carbon::now();
            $product_details['created_by'] = $user_id;
            
            $prod_insert = insertRecord('tra_product_information', $product_details, $user_id);
            
            if ($prod_insert['success'] == false) {
                DB::rollBack();
                return $prod_insert;
            }
            $product_id = $prod_insert['record_id'];
            //product other information other details
            //ingredients
            funcSaveOnlineProductOtherdetails($portal_product_id, $product_id,$user_id);
            
            //application details
            //gmp inspection
            $app_status = getApplicationInitialStatus($results->module_id, $results->sub_module_id);
            $app_status_id = $app_status->status_id;
            $application_status = getSingleRecordColValue('par_system_statuses', array('id' => $app_status_id), 'name');

            $application_details = array(
                'reference_no' => $ref_no,
                'tracking_no' => $tracking_no,
                'applicant_id' => $applicant_id, 
                'local_agent_id' => $local_agent_id,
                'application_code' => $application_code,
                'product_id' => $product_id,
                'module_id' => $results->module_id,
                'sub_module_id' => $results->sub_module_id,
                'zone_id' => $results->zone_id,
                'section_id' => $results->section_id,
                'date_added'=>Carbon::now(),
                'process_id' => $process_details->id,
                'workflow_stage_id' => $workflow_details->id,
                'application_status_id' => $app_status_id,
                'portal_id' => $portal_application_id

            );
            $application_insert = insertRecord('tra_product_applications', $application_details, $user_id);
            if ($application_insert['success'] == false) {
                DB::rollBack();
                return $application_insert;
            }
            
            $mis_application_id = $application_insert['record_id'];
            $registration_data = array('tra_product_id' => $product_id,
                                'status_id' => $application_status,
                                'validity_status_id' => 1,
                                'registration_status_id' => 1
                            );

        $where_statement = array('tra_product_id' => $product_id);

        saveApplicationRegistrationDetails('tra_registered_products', $registration_data, $where_statement, $user_id);
        
        $portal_params = array(
                'application_status_id' => 3,
                'reference_no' => $ref_no
            );
            $portal_where = array(
                'id' => $portal_application_id
            );
            updatePortalParams('wb_product_applications', $portal_params, $portal_where);
            $details = array(
                'application_id' => $application_insert['record_id'],
                'application_code' => $application_code,
                'reference_no' => $ref_no,
                'application_status' => $application_status,
                'process_id' => $process_details->id,
                'process_name' => $process_details->name,
                'workflow_stage_id' => $workflow_details->id,
                'application_status_id' => $app_status_id,
                'workflow_stage' => $workflow_details->name,
                'module_id' => $results->module_id,
                'sub_module_id' => $results->sub_module_id,
                'section_id' => $results->section_id,
                'product_id' => $product_id,
                'applicant_id' => $applicant_id
            );
            //submissions
            $submission_params = array(
                'application_id' => $application_insert['record_id'],
                'process_id' => $process_details->id,
                'application_code' => $application_code,
                'reference_no' => $ref_no,
                'tracking_no' => $tracking_no,
                'usr_from' => $user_id,
                'usr_to' => $responsible_user,
                'previous_stage' => $workflow_details->id,
                'current_stage' => $workflow_details->id,
                'module_id' => $results->module_id,
                'sub_module_id' => $results->sub_module_id,
                'section_id' => $results->section_id,
                'application_status_id' => $app_status_id,
                'urgency' => $urgency,
                'applicant_id' => $applicant_id,
                'remarks' => $comment,
                'date_received' => Carbon::now(),
                'created_on' => Carbon::now(),
                'created_by' => $user_id
            );
            DB::table('tra_submissions')
                ->insert($submission_params);
            DB::commit();
            //send email
            $vars = array(
                '{tracking_no}' => $tracking_no
            );
            onlineApplicationNotificationMail(2, $applicant_email, $vars);
            //email 4 localagent_email
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
        return $res;
    }

}