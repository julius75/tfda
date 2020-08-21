<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 3/12/2019
 * Time: 12:24 PM
 */

namespace App\Modules\Surveillance\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
trait SurveillanceTrait
{

    public function processSurveillanceApplicationsSubmission(Request $request)
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

    public function processSurveillanceManagersApplicationSubmission(Request $request)
    {
        $action = $request->input('action');
        $sub_module_id = $request->input('sub_module_id');
        //get workflow action details
        $action_details = $this->getApplicationWorkflowActionDetails($action);
        $keep_status = $action_details->keep_status;
        $action_type = $action_details->action_type_id;
        $approval_submission = $action_details->is_approval_submission;

        if ($sub_module_id == 37) {//todo Non structured applications
            if ($approval_submission == 1) {
                $this->processNewApprovalApplicationSubmission($request, $keep_status);
            }
        } else if ($sub_module_id == 38) {//todo Structured Applications
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

}