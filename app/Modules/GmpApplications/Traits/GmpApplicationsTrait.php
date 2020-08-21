<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 12/19/2018
 * Time: 3:24 PM
 */

namespace App\Modules\GmpApplications\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

trait GmpApplicationsTrait
{

    public function processGmpApplicationsSubmission(Request $request)
    {
        $action = $request->input('action');
        $application_id = $request->input('application_id');
        $table_name = $request->input('table_name');
        //get workflow action details
        $action_details = $this->getApplicationWorkflowActionDetails($action);
        $keep_status = $action_details->keep_status;
        $action_type = $action_details->action_type_id;
        if ($action_details->update_portal_status == 1) {
            $portal_status_id = $action_details->portal_status_id;
            $proceed = updatePortalApplicationStatus($application_id, $portal_status_id, $table_name, 'wb_gmp_applications');
            if ($proceed == false) {
                echo json_encode($proceed);
                exit();
            }
        }
        if ($action_type == 2) {//initial query
            $this->processReceivingQueriedApplicationSubmission($request);
        } else if ($action_type == 3) {//initial rejection
            $this->processReceivingRejectedApplicationSubmission($request);
        } else if ($action_type == 6) {//recommendation submission
            $recommendation_table = $action_details->recommendation_table;
            $this->processRecommendationApplicationSubmission($request, $recommendation_table);
        } else {
            $this->processNormalApplicationSubmission($request, $keep_status);
        }
    }

    public function processGmpManagersApplicationSubmission(Request $request)
    {
        $action = $request->input('action');
        $sub_module_id = $request->input('sub_module_id');
        //get workflow action details
        $action_details = $this->getApplicationWorkflowActionDetails($action);
        $keep_status = $action_details->keep_status;
        $action_type = $action_details->action_type_id;
        $approval_submission = $action_details->is_approval_submission;
        if ($sub_module_id == 5) {//todo New Applications
            if ($approval_submission == 1) {
                $this->processNewApprovalApplicationSubmission($request, $keep_status);
            }
        } else if ($sub_module_id == 6) {//todo Renewal Applications
            if ($approval_submission == 1) {
                $this->batchGmpApplicationApprovalSubmission($request);
            }
        } else {
            $res = array(
                'success' => false,
                'message' => 'Unknown section selected!!'
            );
            echo json_encode($res);
            exit();
        }
        if ($action_type == 4) {//manager query to customer
            $this->submitApplicationFromManagerQueryToCustomer($request);
        } else if ($action_type == 5) {//manager query normal submission
            $this->processManagerQueryReturnApplicationSubmission($request);
        } else {
            $this->processNormalManagersApplicationSubmission($request, $keep_status);
        }
    }

    public function saveGmpApplicationApprovalDetails(Request $request, $sub_module_id)
    {
        $res = $this->saveGmpApplicationRecommendationDetails($request);
        return $res;
    }

    public function saveGmpApplicationRecommendationDetails(Request $request)
    {
        $table_name = $request->input('table_name');
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $qry = DB::table($table_name)
            ->where('id', $application_id);
        $app_details = $qry->first();
        if (is_null($app_details)) {
            $res = array(
                'success' => false,
                'message' => 'Problem encountered while getting application details!!'
            );
            return $res;
        }
        $res = array();
        try {
            DB::transaction(function () use ($qry, $application_id, $application_code, $table_name, $request, $app_details, &$res) {
                $premiseUpdateParams = array();
                $id = $request->input('recommendation_id');
                $process_id = $request->input('process_id');
                $workflow_stage_id = $request->input('workflow_stage_id');
                $decision_id = $request->input('decision_id');
                $comment = $request->input('comment');
                $approved_by = $request->input('approved_by');
                $approval_date = $request->input('approval_date');
                $expiry_date = $request->input('expiry_date');
                $dg_signatory = $request->input('dg_signatory');
                $signatory = $request->input('permit_signatory');
                $user_id = $this->user_id;
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
                    'expiry_date' => $expiry_date,
                    'approved_by' => $approved_by,
                    'dg_signatory' => $dg_signatory,
                    'permit_signatory' => $permit_signatory
                );
                //$premiseUpdateParams['certificate_issue_date'] = $approval_date;
                if (isset($id) && $id != '') {
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
                    DB::table('tra_approval_recommendations_log')
                        ->insert($prev_data_results);
                    if ($decision_id == 1) {
                        //$premiseUpdateParams['premise_reg_no'] = $app_details->reference_no;
                        $premise_status_id = 2;
                        $qry->update(array('application_status_id' => 6));
                        //permit
                        if ($prev_decision_id != 1) {
                            $permit_no = generatePremisePermitNo($app_details->zone_id, $app_details->section_id, $table_name, $user_id, 10);
                            $params['permit_no'] = $permit_no;
                        }
                    } else {
                        //$premiseUpdateParams['premise_reg_no'] = null;
                        $premise_status_id = 3;
                        $qry->update(array('application_status_id' => 7));
                        $params['permit_no'] = '';
                        $params['permit_no'] = null;
                    }
                    $res = updateRecord('tra_approval_recommendations', $prev_data['results'], $where, $params, $user_id);
                } else {
                    //insert
                    $params['created_on'] = Carbon::now();
                    $params['created_by'] = $user_id;
                    if ($decision_id == 1) {
                        //$premiseUpdateParams['premise_reg_no'] = $app_details->reference_no;
                        $premise_status_id = 2;
                        //permits
                        $permit_no = generatePremisePermitNo($app_details->zone_id, $app_details->section_id, $table_name, $user_id, 10);
                        $params['permit_no'] = $permit_no;
                        $qry->update(array('application_status_id' => 6));
                    } else {
                        $premiseUpdateParams['premise_reg_no'] = null;
                        $premise_status_id = 3;
                        $qry->update(array('application_status_id' => 7));
                        $params['permit_no'] = '';
                        $params['expiry_date'] = null;
                    }
                    $res = insertRecord('tra_approval_recommendations', $params, $user_id);
                    $id = $res['record_id'];
                }
                $premiseUpdateParams['permit_id'] = $id;
                $premiseUpdateParams['status_id'] = $premise_status_id;
                //$prev_records = getPreviousRecords('tra_premises', array('id' => $app_details->premise_id));
                if ($app_details->sub_module_id == 2) {//for renewals there is no update of reg_no
                    unset($premiseUpdateParams['premise_reg_no']);
                    unset($premiseUpdateParams['certificate_issue_date']);
                }
                DB::table('tra_manufacturing_sites')
                    ->where('id', $app_details->manufacturing_site_id)
                    ->update($premiseUpdateParams);
                DB::table('tra_gmp_applications')
                    ->where('id', $application_id)
                    ->update(array('permit_id' => $id));
                //$res= updateRecord('tra_premises', $prev_records['results'], array('id' => $app_details->premise_id), $premiseUpdateParams, $user_id);
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

    public function batchGmpApplicationApprovalSubmission(Request $request)
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

            //todo: check for allowed changes
            //1. Basic premise info
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
            $portal_table_name = getPortalApplicationsTable($module_id);
            //application details
            foreach ($application_details as $key => $application_detail) {
                $application_status_id = $application_detail->application_status_id;
                $view_id = generateApplicationViewID();
                if ($application_detail->decision_id == 1) {
                    $portal_status_id = 10;
                    $this->updateRegistrationTable($application_detail->reg_site_id, $application_detail->manufacturing_site_id, $module_id);
                    /*$response = $this->processRenewalGmpApprovalApplicationSubmission($application_detail->id, $table_name, $formAmendmentDetails, $othersAmendmentDetails, $gmp_type_id);
                    $success = $response['success'];
                    if ($success == false) {
                        DB::rollBack();
                        echo json_encode($response);
                        exit();
                    }*/
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

    public function processRenewalGmpApprovalApplicationSubmission($application_id, $table_name, $formAmendmentDetails, $othersAmendmentDetails, $gmp_type_id)
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
            $site_id = $application_details->manufacturing_site_id;
            $temp_details = DB::table('tra_manufacturing_sites')
                ->where('id', $site_id)
                ->first();
            if (is_null($temp_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching (temp)site details!!'
                );
                return $res;
            }
            $init_site_id = $temp_details->init_site_id;
            $current_permit_id = $temp_details->permit_id;
            //site log data
            $log_data = DB::table('tra_manufacturing_sites as t1')
                ->select(DB::raw("t1.*,t1.id as manufacturing_site_id_id,$user_id as log_by,NOW() as log_date"))
                ->where('id', $init_site_id)
                ->first();
            //todo: update renewal changes
            //1. Basic premise info
            if ($gmp_type_id == 1) {
                if ($formAmendmentDetails->changed == '') {
                    //No changes on basic premise info
                } else {
                    $this->updateGmpAlterationBasicDetails($formAmendmentDetails, $site_id, $init_site_id, $log_data);
                }
            }
            //2. Personnel(id 5) and Business(id 6) details
            if (count($othersAmendmentDetails) > 0) {
                foreach ($othersAmendmentDetails as $othersAmendmentDetail) {
                    if ($othersAmendmentDetail->part_id == 5) {
                        //update personnel details
                        $this->updateGmpAlterationPersonnelDetails($site_id, $init_site_id);
                    }
                    if ($othersAmendmentDetail->part_id == 6) {
                        //update business details
                        $this->updateGmpAlterationBusinessDetails($site_id, $init_site_id);
                    }
                }
            }
            updateRenewalPermitDetails($init_site_id, $current_permit_id, 'tra_manufacturing_sites');

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

    public function updateGmpProductLineDetails($site_id, $init_site_id)
    {
        $mainQry = DB::table('gmp_product_details');

        $currentQry = clone $mainQry;
        $currentQry->where('manufacturing_site_id', $site_id);

        $initialQry = clone $mainQry;
        $initialQry->where('manufacturing_site_id', $init_site_id);
        $backup_records = clone $initialQry;
        $backup_records = $backup_records->get();
        $backup_records = convertStdClassObjToArray($backup_records);
        $delete_init = clone $initialQry;

    }

    public function updateGmpAlterationBasicDetails($formAmendmentDetails, $site_id, $init_site_id, $log_data)
    {
        unset($log_data->id);
        $log_data = convertStdClassObjToArray($log_data);
        DB::table('tra_manufacturing_sites_log')
            ->insert($log_data);

        $amended_premise_details = DB::table('tra_manufacturing_sites')
            ->select(DB::raw($formAmendmentDetails->changed))
            ->where('id', $site_id)
            ->first();
        $amended_premise_details = convertStdClassObjToArray($amended_premise_details);
        DB::table('tra_manufacturing_sites')
            ->where('id', $init_site_id)
            ->update($amended_premise_details);
    }

    public function updateGmpAlterationPersonnelDetails($temp_site_id, $init_site_id)
    {
        $user_id = $this->user_id;
        //initial
        $init_qry = DB::table('tra_manufacturing_sites_personnel')
            ->where('manufacturing_site_id', $init_site_id);
        $init_details = $init_qry->select(DB::raw("tra_manufacturing_sites_personnel.*,$user_id as log_by,NOW() as log_date"))
            ->get();
        $init_details = convertStdClassObjToArray($init_details);
        $log_insert = DB::table('tra_mansites_personnel_log')
            ->insert($init_details);
        $init_qry->delete();
        //Temp
        $temp_qry = DB::table('tra_manufacturing_sites_personnel as t2')
            ->select(DB::raw("t2.init_site_id,t2.name,t2.telephone,t2.position_id,t2.status_id,t2.email_address,t2.postal_address,
            t2.fax,t2.created_by,t2.altered_by,t2.created_on,t2.dola,$init_site_id as manufacturing_site_id"))
            ->where('manufacturing_site_id', $temp_site_id);
        $temp_details = $temp_qry->get();
        $temp_details = convertStdClassObjToArray($temp_details);
        $init_insert = DB::table('tra_manufacturing_sites_personnel')
            ->insert($temp_details);
    }

    public function updateGmpAlterationBusinessDetails($temp_site_id, $init_site_id)
    {
        $user_id = $this->user_id;
        //initial
        $init_qry = DB::table('tra_mansite_otherdetails')
            ->where('manufacturing_site_id', $init_site_id);
        $init_details = $init_qry->select(DB::raw("tra_mansite_otherdetails.*,$user_id as log_by,NOW() as log_date"))
            ->get();
        $init_details = convertStdClassObjToArray($init_details);
        $log_insert = DB::table('tra_mansite_otherdetails_log')
            ->insert($init_details);
        $init_qry->delete();
        //Temp
        $temp_qry = DB::table('tra_mansite_otherdetails as t2')
            ->select(DB::raw("t2.init_site_id,t2.business_type_id,t2.business_type_detail_id,
            t2.created_by,t2.altered_by,t2.created_on,t2.dola,t2.portal_id,$init_site_id as manufacturing_site_id"))
            ->where('manufacturing_site_id', $temp_site_id);
        $temp_details = $temp_qry->get();
        $temp_details = convertStdClassObjToArray($temp_details);
        $init_insert = DB::table('tra_mansite_otherdetails')
            ->insert($temp_details);
    }

    public function saveGmpOnlineApplicationDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $responsible_user = $request->input('responsible_user');
        $urgency = $request->input('urgency');
        $comment = $request->input('remarks');
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_gmp_applications as t1')
                ->where('id', $application_id);
            $results = $qry->first();
            if (is_null($results)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting portal application details, consult System Admin!!'
                );
                return $res;
            }
            $portal_application_id = $results->id;
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
            $tracking_no = $results->tracking_no;
            $sub_module_id = $results->sub_module_id;
            $gmp_type_id = $results->gmp_type_id;
            $zone_code = getSingleRecordColValue('par_zones', array('id' => $results->zone_id), 'zone_code');
            $section_code = getSingleRecordColValue('par_sections', array('id' => $results->section_id), 'code');
            $gmp_code = getSingleRecordColValue('gmplocation_details', array('id' => $gmp_type_id), 'location_code');
            $codes_array = array(
                'section_code' => $section_code,
                'zone_code' => $zone_code,
                'gmp_type' => $gmp_code
            );
            if ($sub_module_id == 5) {//new
                $ref_id = 9;
            } else if ($sub_module_id == 6) {//renewal
                $ref_id = 11;
            } else {
                //unknown
                $res = array(
                    'success' => false,
                    'message' => 'Unknown sub module, consult System Admin!!'
                );
                return $res;
            }
            $view_id=generateApplicationViewID();
            $ref_no = generatePremiseRefNumber($ref_id, $codes_array, date('Y'), $process_details->id, $results->zone_id, $user_id);
            $application_code = $results->application_code;//generateApplicationCode($sub_module_id, 'tra_gmp_applications');
            //applicant details
            $applicant_details = $portal_db->table('wb_trader_account')
                ->where('id', $results->applicant_id)
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
            //site main details
            $site_details = $portal_db->table('wb_manufacturing_sites')
                ->where('id', $results->manufacturing_site_id)
                ->first();
            if (is_null($site_details)) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting site details, consult System Admin!!'
                );
                return $res;
            }
            $ltr_details = $portal_db->table('wb_trader_account')
                ->where('id', $results->local_agent_id)
                ->first();
            if (is_null($ltr_details)) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting LTR details, consult System Admin!!'
                );
                return $res;
            }
            $ltr_id = getSingleRecordColValue('wb_trader_account', array('identification_no' => $ltr_details->identification_no), 'id');

            $site_details->portal_id = $results->manufacturing_site_id;
            $site_details->applicant_id = $applicant_id;
            $site_details->ltr_id = $ltr_id;
            $site_details->created_by = $this->user_id;
            $site_details = convertStdClassObjToArray($site_details);
            unset($site_details['id']);
            //unset($site_details['trader_id']);
            unset($site_details['mis_dola']);
            unset($site_details['mis_altered_by']);
            $site_insert = insertRecord('tra_manufacturing_sites', $site_details, $user_id);
            if ($site_insert['success'] == false) {
                DB::rollBack();
                return $site_insert;
            }
            $site_id = $site_insert['record_id'];
            //site other details
            $site_otherdetails = $portal_db->table('wb_mansite_otherdetails')
                ->where('manufacturing_site_id', $results->manufacturing_site_id)
                ->select(DB::raw("id as portal_id,$site_id as manufacturing_site_id,business_type_id,business_type_detail_id,$user_id as created_by"))
                ->get();
            $site_otherdetails = convertStdClassObjToArray($site_otherdetails);
            unset($site_otherdetails['id']);
            DB::table('tra_mansite_otherdetails')
                ->insert($site_otherdetails);
            //site personnel details
            $site_personneldetails = $portal_db->table('wb_manufacturing_sites_personnel')
                ->where('manufacturing_site_id', $results->manufacturing_site_id)
                ->select(DB::raw("id as portal_id,$site_id as manufacturing_site_id,name,telephone,email_address,postal_address,fax,position_id,status_id,$user_id as created_by"))
                ->get();
            $site_personneldetails = convertStdClassObjToArray($site_personneldetails);
            unset($site_personneldetails['id']);
            DB::table('tra_manufacturing_sites_personnel')
                ->insert($site_personneldetails);
            //product line details
            $site_productdetails = $portal_db->table('wb_gmp_product_details')
                ->where('manufacturing_site_id', $results->manufacturing_site_id)
                ->select(DB::raw("id as portal_id,$site_id as manufacturing_site_id,product_line_id,category_id,prodline_description_id,$user_id as created_by"))
                ->get();
            $site_productdetails = convertStdClassObjToArray($site_productdetails);
            unset($site_productdetails['id']);
            DB::table('gmp_product_details')
                ->insert($site_productdetails);
            //application details
            $app_status = getApplicationInitialStatus($results->module_id, $results->sub_module_id);
            $app_status_id = $app_status->status_id;
            $application_status = getSingleRecordColValue('par_system_statuses', array('id' => $app_status_id), 'name');
            $application_details = array(
                'reference_no' => $ref_no,
                'view_id' => $view_id,
                'tracking_no' => $tracking_no,
                'applicant_id' => $applicant_id,
                'application_code' => $application_code,
                'manufacturing_site_id' => $site_id,
                'gmp_type_id' => $results->gmp_type_id,
                'module_id' => $results->module_id,
                'sub_module_id' => $results->sub_module_id,
                'zone_id' => $results->zone_id,
                'section_id' => $results->section_id,
                'process_id' => $process_details->id,
                'workflow_stage_id' => $workflow_details->id,
                'application_status_id' => $app_status_id,
                'portal_id' => $portal_application_id
            );
            $application_insert = insertRecord('tra_gmp_applications', $application_details, $user_id);
            if ($application_insert['success'] == false) {
                DB::rollBack();
                return $application_insert;
            }
            $mis_application_id = $application_insert['record_id'];
            $reg_params = array(
                'tra_site_id' => $mis_application_id,
                'registration_status' => 1,
                'validity_status' => 1,
                'created_by' => $user_id
            );
            createInitialRegistrationRecord('registered_manufacturing_sites', 'tra_gmp_applications', $reg_params, $mis_application_id, 'reg_site_id');
            $portal_params = array(
                'application_status_id' => 3,
                'reference_no' => $ref_no
            );
            $portal_where = array(
                'id' => $portal_application_id
            );
            updatePortalParams('wb_gmp_applications', $portal_params, $portal_where);
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
                'premise_id' => $site_id,
                'manufacturing_id' => $site_id,
                'applicant_id' => $applicant_id
            );
            //submissions
            $submission_params = array(
                'application_id' => $application_insert['record_id'],
                'view_id' => $view_id,
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

    public function getGmpInvoiceDetails($application_id)
    {
        $qry = DB::table('tra_gmp_applications as t1')
            ->join('wf_tfdaprocesses as t2', 't1.process_id', '=', 't2.id')
            ->join('tra_manufacturing_sites as t3', 't1.manufacturing_site_id', '=', 't3.id')
            ->join('modules as t4', 't1.module_id', '=', 't4.id')
            ->select(DB::raw("t1.reference_no,t2.name as process_name,t4.invoice_desc as module_name,
                     CONCAT_WS(', ',t3.name,t3.physical_address) as module_desc"))
            ->where('t1.id', $application_id);
        $invoice_details = $qry->first();
        return $invoice_details;
    }

}