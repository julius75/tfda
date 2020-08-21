<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 1/23/2019
 * Time: 11:06 AM
 */

namespace App\Modules\ClinicalTrial\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

trait ClinicalTrialTrait
{

    public function processClinicalTrialApplicationsSubmission(Request $request)
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
            $proceed = updatePortalApplicationStatus($application_id, $portal_status_id, $table_name, 'wb_clinical_trial_applications');
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

    public function processClinicalTrialManagersApplicationSubmission(Request $request)
    {
        $action = $request->input('action');
        $sub_module_id = $request->input('sub_module_id');
        //get workflow action details
        $action_details = $this->getApplicationWorkflowActionDetails($action);
        $keep_status = $action_details->keep_status;
        $action_type = $action_details->action_type_id;
        $approval_submission = $action_details->is_approval_submission;

        if ($sub_module_id == 10) {//todo New Applications
            if ($approval_submission == 1) {
                $this->processNewApprovalApplicationSubmission($request, $keep_status);
            }
        } else if ($sub_module_id == 11) {//todo Amendment Applications
            if ($approval_submission == 1) {
                $this->processSubsequentApprovalApplicationSubmission($request);
            }
        } else {
            $res = array(
                'success' => false,
                'message' => 'Unknown sub module selected!!'
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

    public function saveClinicalTrialApplicationApprovalDetails(Request $request, $sub_module_id)
    {
        $res = $this->saveClinicalTrialApplicationRecommendationDetails($request);
        return $res;
    }

    public function saveClinicalTrialApplicationRecommendationDetails(Request $request)
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
                $id = $request->input('recommendation_id');
                $process_id = $request->input('process_id');
                $workflow_stage_id = $request->input('workflow_stage_id');
                $decision_id = $request->input('decision_id');
                $comment = $request->input('comment');
                $approved_by = $request->input('approved_by');
                $approval_date = $request->input('approval_date');
                $dg_signatory = $request->input('dg_signatory');
                $signatory = $request->input('permit_signatory');
                $user_id = $this->user_id;
                $expiry_date = getPermitExpiryDate($approval_date, $app_details->study_duration, $app_details->duration_desc);
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
                    'approved_by' => $approved_by,
                    'expiry_date' => $expiry_date,
                    'dg_signatory' => $dg_signatory,
                    'permit_signatory' => $permit_signatory
                );
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
                    $prev_data_results[0]['record_id'] = $id;
                    $prev_decision_id = $prev_data_results[0]['decision_id'];
                    $prev_data_results[0]['update_by'] = $user_id;
                    $prev_data_results[0]['recommendation_id'] = $prev_data_results[0]['id'];
                    unset($prev_data_results[0]['id']);
                    DB::table('tra_approval_recommendations_log')
                        ->insert($prev_data_results);

                    if ($decision_id == 1) {
                        $qry->update(array('application_status_id' => 6));
                        //permit
                        if ($prev_decision_id != 1) {
                            $permit_no = generatePremisePermitNo($app_details->zone_id, $app_details->section_id, $table_name, $user_id, 13);
                            $params['permit_no'] = $permit_no;
                        }
                    } else {
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
                        //permits
                        $permit_no = generatePremisePermitNo($app_details->zone_id, $app_details->section_id, $table_name, $user_id, 13);
                        $params['permit_no'] = $permit_no;
                        $qry->update(array('application_status_id' => 6));
                    } else {
                        $premiseUpdateParams['premise_reg_no'] = null;
                        $qry->update(array('application_status_id' => 7));
                        $params['permit_no'] = '';
                        $params['expiry_date'] = null;
                    }
                    $res = insertRecord('tra_approval_recommendations', $params, $user_id);
                    $id = $res['record_id'];
                }
                DB::table('tra_clinical_trial_applications')
                    ->where('id', $application_id)
                    ->update(array('permit_id' => $id));
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

    public function processRenewalClinicalTrialApprovalApplicationSubmission($application_id, $table_name, $formAmendmentDetails, $othersAmendmentDetails, $gmp_type_id)
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

    public function saveClinicalTrialOnlineApplicationDetails(Request $request)
    {
        $portal_application_id = $request->input('application_id');
        $responsible_user = $request->input('responsible_user');
        $urgency = $request->input('urgency');
        $comment = $request->input('remarks');
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_clinical_trial_applications as t1')
                ->where('id', $portal_application_id);
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
            $tracking_no = $results->tracking_no;
            $sub_module_id = $results->sub_module_id;
            $zone_code = getSingleRecordColValue('par_zones', array('id' => $results->zone_id), 'zone_code');
            $section_code = getSingleRecordColValue('par_sections', array('id' => $results->section_id), 'code');
            $codes_array = array(
                'section_code' => $section_code,
                'zone_code' => $zone_code
            );
            if ($sub_module_id == 10) {//new
                $ref_id = 12;
            } else if ($sub_module_id == 11) {//amendment
                $ref_id = 14;
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
            $application_code = $results->application_code;//generateApplicationCode($sub_module_id, 'tra_clinical_trial_applications');
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
                'module_id' => $results->module_id,
                'sub_module_id' => $results->sub_module_id,
                'zone_id' => $results->zone_id,
                'section_id' => $results->section_id,
                'process_id' => $process_details->id,
                'workflow_stage_id' => $workflow_details->id,
                'application_status_id' => $app_status_id,
                'portal_id' => $portal_application_id,
                'date_received' => Carbon::now(),
                'sponsor_id' => $results->sponsor_id,
                'investigator_id' => $results->investigator_id,
                'study_title' => $results->study_title,
                'protocol_no' => $results->protocol_no,
                'version_no' => $results->version_no,
                'date_of_protocol' => $results->date_of_protocol,
                'clearance_no' => $results->clearance_no,
                'study_duration' => $results->study_duration,
                'duration_desc' => $results->duration_desc,
                'received_by' => $user_id

            );
            $application_insert = insertRecord('tra_clinical_trial_applications', $application_details, $user_id);
            if ($application_insert['success'] == false) {
                DB::rollBack();
                return $application_insert;
            }
            $mis_application_id = $application_insert['record_id'];
            $reg_params = array(
                'tra_clinical_trial_id' => $mis_application_id,
                'registration_status' => 1,
                'validity_status' => 1,
                'created_by' => $user_id
            );
            createInitialRegistrationRecord('registered_clinical_trials', 'tra_clinical_trial_applications', $reg_params, $mis_application_id, 'reg_clinical_trial_id');
            //study sites details
            $site_details = $portal_db->table('wb_clinical_trial_sites')
                ->select(DB::raw("id as portal_id,$mis_application_id as application_id,study_site_id,
                NOW() as created_on,$user_id as created_by"))
                ->where('application_id', $results->id)
                ->get();
            $site_details = convertStdClassObjToArray($site_details);
            $site_details = unsetPrimaryIDsInArray($site_details);
            DB::table('clinical_trial_sites')
                ->insert($site_details);
            //investigators
            $investigator_details = $portal_db->table('wb_clinical_trial_investigators')
                ->select(DB::raw("id as portal_id,$mis_application_id as application_id,category_id,investigator_id,
                study_site_id,NOW() as created_on,$user_id as created_by"))
                ->where('application_id', $results->id)
                ->get();
            $investigator_details = convertStdClassObjToArray($investigator_details);
            $investigator_details = unsetPrimaryIDsInArray($investigator_details);
            DB::table('clinical_trial_investigators')
                ->insert($investigator_details);
            //IMP products
            $product_details = $portal_db->table('wb_clinical_trial_products')
                ->select(DB::raw("id as portal_id,$mis_application_id as application_id,product_category_id,brand_name
                registration_no,registration_date,identification_mark,product_desc,market_location_id,dosage_form_id,country_id,
                common_name_id,manufacturer_id,routes_of_admin_id,product_strength,si_unit_id,
                NOW() as created_on,$user_id as created_by"))
                ->where('application_id', $results->id)
                ->get();
            $product_details = convertStdClassObjToArray($product_details);
            $product_details = unsetPrimaryIDsInArray($product_details);
            foreach ($product_details as $product_detail) {
                $mis_product_id = DB::table('clinical_trial_products')
                    ->insertGetId($product_detail);
                //product ingredients
                $ingredient_details = $portal_db->table('wb_impproduct_ingredients')
                    ->select(DB::raw("id as portal_id,$mis_product_id as product_id,ingredient_id,ingredient_type_id,specification_id,strength,si_unit_id,
                      inclusion_reason_id,NOW() as created_on,$user_id as created_by"))
                    ->where('product_id', $product_detail['portal_id'])
                    ->get();
                $ingredient_details = convertStdClassObjToArray($ingredient_details);
                $ingredient_details = unsetPrimaryIDsInArray($ingredient_details);
                DB::table('impproduct_ingredients')
                    ->insert($ingredient_details);
            }
            $portal_params = array(
                'application_status_id' => 3,
                'reference_no' => $ref_no
            );
            $portal_where = array(
                'id' => $portal_application_id
            );
            updatePortalParams('wb_clinical_trial_applications', $portal_params, $portal_where);
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

    public function getClinicalTrialInvoiceDetails($application_id)
    {
        $qry = DB::table('tra_clinical_trial_applications as t1')
            ->join('wf_tfdaprocesses as t2', 't1.process_id', '=', 't2.id')
            ->join('modules as t4', 't1.module_id', '=', 't4.id')
            ->select(DB::raw("t1.reference_no,t2.name as process_name,t4.invoice_desc as module_name,
                     CONCAT(t1.study_title,'(',CONCAT_WS(',',CONCAT('Protocol No:',t1.protocol_no),CONCAT('Version No:',t1.version_no)),')') as module_desc
                     "))
            ->where('t1.id', $application_id);
        $invoice_details = $qry->first();
        return $invoice_details;
    }

}