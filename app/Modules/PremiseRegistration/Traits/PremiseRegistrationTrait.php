<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 11/20/2018
 * Time: 2:31 PM
 */

namespace App\Modules\PremiseRegistration\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

trait PremiseRegistrationTrait
{

    public function processPremiseApplicationSubmission(Request $request)
    {
        $directive_id = $request->input('directive_id');
        $action = $request->input('action');
        $prev_stage = $request->input('curr_stage_id');
        $section_id = $request->input('section_id');
        $sub_module_id = $request->input('sub_module_id');
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        $keep_status = false;
        $update_portal_status = false;
        $portal_status_id = 0;
        if ($sub_module_id == 1) {//todo New Registration Applications
            if ($section_id == 1) {//todo FOOD
                if ($prev_stage == 3) {//INVOICING
                    $update_portal_status = true;
                    $portal_status_id = 4;
                }
                if ($prev_stage == 6) {//PAYMENTS
                    $update_portal_status = true;
                    $portal_status_id = 5;
                }
                if ($update_portal_status == true) {
                    $proceed = updatePortalApplicationStatus($application_id, $portal_status_id, $table_name, 'wb_premises_applications');
                    if ($proceed == false) {
                        echo json_encode($proceed);
                        exit();
                    }
                }
                if ($prev_stage == 1) {//RECEIVING
                    if ($action == 17) {//queried
                        $this->processReceivingQueriedApplicationSubmission($request);
                    } else if ($action == 30) {//rejected
                        $this->processReceivingRejectedApplicationSubmission($request);
                    } else {//recommended
                        $this->processNormalApplicationSubmission($request);
                    }
                } else if ($prev_stage == 17) {//INSPECTION
                    $valid = $this->validatePremiseInspectionApplication($application_code);
                    if ($valid == false) {
                        DB::rollBack();
                        $res = array(
                            'success' => false,
                            'message' => 'Please enter inspection details first!!'
                        );
                        echo json_encode($res);
                        exit();
                    }
                    $this->processRecommendationApplicationSubmission($request, 'tra_inspection_recommendations');
                } else if ($prev_stage == 9) {//EVALUATION
                    if ($action == 39) {//return to inspection
                        if ($directive_id == 2) {//redo inspection
                            $this->processPremiseReturnApplicationSubmissionsWithChecklists($request, 3);
                        } else {
                            $this->processNormalApplicationSubmission($request);
                        }
                    } else {
                        $this->processRecommendationApplicationSubmission($request, 'tra_evaluation_recommendations');
                    }
                } else if ($prev_stage == 14) {//APPROVALS
                    $this->singleNewPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalApplicationSubmission($request, $keep_status);
                }
            } else if ($section_id == 2) {//todo DRUGS
                if ($prev_stage == 21) {//INVOICING
                    $update_portal_status = true;
                    $portal_status_id = 4;
                }
                if ($prev_stage == 22) {//PAYMENTS
                    $update_portal_status = true;
                    $portal_status_id = 5;
                }
                if ($update_portal_status == true) {
                    $proceed = updatePortalApplicationStatus($application_id, $portal_status_id, $table_name, 'wb_premises_applications');
                    if ($proceed == false) {
                        echo json_encode($proceed);
                        exit();
                    }
                }
                if ($prev_stage == 5) {//RECEIVING
                    if ($action == 41) {//queried
                        $this->processReceivingQueriedApplicationSubmission($request);
                    } else if ($action == 42) {//rejected
                        $this->processReceivingRejectedApplicationSubmission($request);
                    } else {//recommended
                        $this->processNormalApplicationSubmission($request);
                    }
                } else if ($prev_stage == 24) {//INSPECTION
                    $valid = $this->validatePremiseInspectionApplication($application_code);
                    if ($valid == false) {
                        DB::rollBack();
                        $res = array(
                            'success' => false,
                            'message' => 'Please enter inspection details first!!'
                        );
                        echo json_encode($res);
                        exit();
                    }
                    $this->processRecommendationApplicationSubmission($request, 'tra_inspection_recommendations');
                } else if ($prev_stage == 26) {//EVALUATION
                    if ($action == 57) {//return to inspection
                        if ($directive_id == 2) {//redo inspection
                            $this->processPremiseReturnApplicationSubmissionsWithChecklists($request, 3);
                        } else {
                            $this->processNormalApplicationSubmission($request);
                        }
                    } else {
                        $this->processRecommendationApplicationSubmission($request, 'tra_evaluation_recommendations');
                    }
                } else if ($prev_stage == 27) {//APPROVALS
                    $this->singleNewPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalApplicationSubmission($request);
                }
            } else if ($section_id == 3) {//todo COSMETICS
                if ($prev_stage == 32) {//INVOICING
                    $update_portal_status = true;
                    $portal_status_id = 4;
                }
                if ($prev_stage == 33) {//PAYMENTS
                    $update_portal_status = true;
                    $portal_status_id = 5;
                }
                if ($update_portal_status == true) {
                    $proceed = updatePortalApplicationStatus($application_id, $portal_status_id, $table_name, 'wb_premises_applications');
                    if ($proceed == false) {
                        echo json_encode($proceed);
                        exit();
                    }
                }
                if ($prev_stage == 11) {//RECEIVING
                    if ($action == 67) {//queried
                        $this->processReceivingQueriedApplicationSubmission($request);
                    } else if ($action == 68) {//rejected
                        $this->processReceivingRejectedApplicationSubmission($request);
                    } else {//recommended
                        $this->processNormalApplicationSubmission($request);
                    }
                } else if ($prev_stage == 35) {//INSPECTION
                    $valid = $this->validatePremiseInspectionApplication($application_code);
                    if ($valid == false) {
                        DB::rollBack();
                        $res = array(
                            'success' => false,
                            'message' => 'Please enter inspection details first!!'
                        );
                        echo json_encode($res);
                        exit();
                    }
                    $this->processRecommendationApplicationSubmission($request, 'tra_inspection_recommendations');
                } else if ($prev_stage == 37) {//EVALUATION
                    if ($action == 86) {//return to inspection
                        if ($directive_id == 2) {//redo inspection
                            $this->processPremiseReturnApplicationSubmissionsWithChecklists($request, 3);
                        } else {
                            $this->processNormalApplicationSubmission($request);
                        }
                    } else {
                        $this->processRecommendationApplicationSubmission($request, 'tra_evaluation_recommendations');
                    }
                } else if ($prev_stage == 39) {//APPROVALS
                    $this->singleNewPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalApplicationSubmission($request);
                }
            } else if ($section_id == 4) {//todo MEDICAL DEVICES
                if ($prev_stage == 44) {//INVOICING
                    $update_portal_status = true;
                    $portal_status_id = 4;
                }
                if ($prev_stage == 45) {//PAYMENTS
                    $update_portal_status = true;
                    $portal_status_id = 5;
                }
                if ($update_portal_status == true) {
                    $proceed = updatePortalApplicationStatus($application_id, $portal_status_id, $table_name, 'wb_premises_applications');
                    if ($proceed == false) {
                        echo json_encode($proceed);
                        exit();
                    }
                }
                if ($prev_stage == 43) {//RECEIVING
                    if ($action == 88) {//queried
                        $this->processReceivingQueriedApplicationSubmission($request);
                    } else if ($action == 89) {//rejected
                        $this->processReceivingRejectedApplicationSubmission($request);
                    } else {//recommended
                        $this->processNormalApplicationSubmission($request);
                    }
                } else if ($prev_stage == 47) {//INSPECTION
                    $valid = $this->validatePremiseInspectionApplication($application_code);
                    if ($valid == false) {
                        DB::rollBack();
                        $res = array(
                            'success' => false,
                            'message' => 'Please enter inspection details first!!'
                        );
                        echo json_encode($res);
                        exit();
                    }
                    $this->processRecommendationApplicationSubmission($request, 'tra_inspection_recommendations');
                } else if ($prev_stage == 49) {//EVALUATION
                    if ($action == 100) {//return to inspection
                        if ($directive_id == 2) {//redo inspection
                            $this->processPremiseReturnApplicationSubmissionsWithChecklists($request, 3);
                        } else {
                            $this->processNormalApplicationSubmission($request);
                        }
                    } else {
                        $this->processRecommendationApplicationSubmission($request, 'tra_evaluation_recommendations');
                    }
                } else if ($prev_stage == 51) {//APPROVALS
                    $this->singleNewPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalApplicationSubmission($request);
                }
            } else {
                $res = array(
                    'success' => false,
                    'message' => 'Unknown section selected!!'
                );
                echo json_encode($res);
                exit();
            }
        } else if ($sub_module_id == 2) {//todo Renewal Applications
            if ($section_id == 1) {//todo FOOD
                if ($prev_stage == 57) {//INVOICING
                    $update_portal_status = true;
                    $portal_status_id = 4;
                }
                if ($prev_stage == 58) {//PAYMENTS
                    $update_portal_status = true;
                    $portal_status_id = 5;
                }
                if ($update_portal_status == true) {
                    $proceed = updatePortalApplicationStatus($application_id, $portal_status_id, $table_name, 'wb_premises_applications');
                    if ($proceed == false) {
                        echo json_encode($proceed);
                        exit();
                    }
                }
                if ($prev_stage == 56) {//RECEIVING
                    if ($action == 114) {//queried
                        $this->processReceivingQueriedApplicationSubmission($request);
                    } else if ($action == 115) {//rejected
                        $this->processReceivingRejectedApplicationSubmission($request);
                    } else {//recommended
                        $this->processNormalApplicationSubmission($request);
                    }
                } else if ($prev_stage == 60) {//INSPECTION
                    $valid = $this->validatePremiseInspectionApplication($application_code);
                    if ($valid == false) {
                        DB::rollBack();
                        $res = array(
                            'success' => false,
                            'message' => 'Please enter inspection details first!!'
                        );
                        echo json_encode($res);
                        exit();
                    }
                    $this->processRecommendationApplicationSubmission($request, 'tra_inspection_recommendations');
                } else if ($prev_stage == 62) {//EVALUATION
                    if ($action == 126) {//return to inspection
                        if ($directive_id == 2) {//redo inspection
                            $this->processPremiseReturnApplicationSubmissionsWithChecklists($request, 3);
                        } else {
                            $this->processNormalApplicationSubmission($request);
                        }
                    } else {
                        $this->processRecommendationApplicationSubmission($request, 'tra_evaluation_recommendations');
                    }
                } else if ($prev_stage == 64) {//APPROVALS
                    $this->singleRenewalPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalApplicationSubmission($request);
                }
            } else if ($section_id == 2) {//todo DRUGS
                if ($prev_stage == 68) {//INVOICING
                    $update_portal_status = true;
                    $portal_status_id = 4;
                }
                if ($prev_stage == 69) {//PAYMENTS
                    $update_portal_status = true;
                    $portal_status_id = 5;
                }
                if ($update_portal_status == true) {
                    $proceed = updatePortalApplicationStatus($application_id, $portal_status_id, $table_name, 'wb_premises_applications');
                    if ($proceed == false) {
                        echo json_encode($proceed);
                        exit();
                    }
                }
                if ($prev_stage == 67) {//RECEIVING
                    if ($action == 132) {//queried
                        $this->processReceivingQueriedApplicationSubmission($request);
                    } else if ($action == 133) {//rejected
                        $this->processReceivingRejectedApplicationSubmission($request);
                    } else {//recommended
                        $this->processNormalApplicationSubmission($request);
                    }
                } else if ($prev_stage == 71) {//INSPECTION
                    $valid = $this->validatePremiseInspectionApplication($application_code);
                    if ($valid == false) {
                        DB::rollBack();
                        $res = array(
                            'success' => false,
                            'message' => 'Please enter inspection details first!!'
                        );
                        echo json_encode($res);
                        exit();
                    }
                    $this->processRecommendationApplicationSubmission($request, 'tra_inspection_recommendations');
                } else if ($prev_stage == 73) {//EVALUATION
                    if ($action == 145) {//return to inspection
                        if ($directive_id == 2) {//redo inspection
                            $this->processPremiseReturnApplicationSubmissionsWithChecklists($request, 3);
                        } else {
                            $this->processNormalApplicationSubmission($request);
                        }
                    } else {
                        $this->processRecommendationApplicationSubmission($request, 'tra_evaluation_recommendations');
                    }
                } else if ($prev_stage == 75) {//APPROVALS
                    $this->singleRenewalPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalApplicationSubmission($request);
                }
            } else if ($section_id == 3) {//todo COSMETICS
                if ($prev_stage == 80) {//INVOICING
                    $update_portal_status = true;
                    $portal_status_id = 4;
                }
                if ($prev_stage == 81) {//PAYMENTS
                    $update_portal_status = true;
                    $portal_status_id = 5;
                }
                if ($update_portal_status == true) {
                    $proceed = updatePortalApplicationStatus($application_id, $portal_status_id, $table_name, 'wb_premises_applications');
                    if ($proceed == false) {
                        echo json_encode($proceed);
                        exit();
                    }
                }
                if ($prev_stage == 79) {//RECEIVING
                    if ($action == 149) {//queried
                        $this->processReceivingQueriedApplicationSubmission($request);
                    } else if ($action == 150) {//rejected
                        $this->processReceivingRejectedApplicationSubmission($request);
                    } else {//recommended
                        $this->processNormalApplicationSubmission($request);
                    }
                } else if ($prev_stage == 83) {//INSPECTION
                    $valid = $this->validatePremiseInspectionApplication($application_code);
                    if ($valid == false) {
                        DB::rollBack();
                        $res = array(
                            'success' => false,
                            'message' => 'Please enter inspection details first!!'
                        );
                        echo json_encode($res);
                        exit();
                    }
                    $this->processRecommendationApplicationSubmission($request, 'tra_inspection_recommendations');
                } else if ($prev_stage == 85) {//EVALUATION
                    if ($action == 162) {//return to inspection
                        if ($directive_id == 2) {//redo inspection
                            $this->processPremiseReturnApplicationSubmissionsWithChecklists($request, 3);
                        } else {
                            $this->processNormalApplicationSubmission($request);
                        }
                    } else {
                        $this->processRecommendationApplicationSubmission($request, 'tra_evaluation_recommendations');
                    }
                } else if ($prev_stage == 87) {//APPROVALS
                    $this->singleRenewalPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalApplicationSubmission($request);
                }
            } else if ($section_id == 4) {//todo MEDICAL DEVICES
                if ($prev_stage == 92) {//INVOICING
                    $update_portal_status = true;
                    $portal_status_id = 4;
                }
                if ($prev_stage == 93) {//PAYMENTS
                    $update_portal_status = true;
                    $portal_status_id = 5;
                }
                if ($update_portal_status == true) {
                    $proceed = updatePortalApplicationStatus($application_id, $portal_status_id, $table_name, 'wb_premises_applications');
                    if ($proceed == false) {
                        echo json_encode($proceed);
                        exit();
                    }
                }
                if ($prev_stage == 91) {//RECEIVING
                    if ($action == 167) {//queried
                        $this->processReceivingQueriedApplicationSubmission($request);
                    } else if ($action == 168) {//rejected
                        $this->processReceivingRejectedApplicationSubmission($request);
                    } else {//recommended
                        $this->processNormalApplicationSubmission($request);
                    }
                } else if ($prev_stage == 95) {//INSPECTION
                    $valid = $this->validatePremiseInspectionApplication($application_code);
                    if ($valid == false) {
                        DB::rollBack();
                        $res = array(
                            'success' => false,
                            'message' => 'Please enter inspection details first!!'
                        );
                        echo json_encode($res);
                        exit();
                    }
                    $this->processRecommendationApplicationSubmission($request, 'tra_inspection_recommendations');
                } else if ($prev_stage == 97) {//EVALUATION
                    if ($action == 180) {//return to inspection
                        if ($directive_id == 2) {//redo inspection
                            $this->processPremiseReturnApplicationSubmissionsWithChecklists($request, 3);
                        } else {
                            $this->processNormalApplicationSubmission($request);
                        }
                    } else {
                        $this->processRecommendationApplicationSubmission($request, 'tra_evaluation_recommendations');
                    }
                } else if ($prev_stage == 99) {//APPROVALS
                    $this->singleRenewalPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalApplicationSubmission($request);
                }
            } else {
                $res = array(
                    'success' => false,
                    'message' => 'Unknown section selected!!'
                );
                echo json_encode($res);
                exit();
            }
        } else if ($sub_module_id == 3) {//todo Alteration Applications
            if ($section_id == 1) {//todo FOOD
                if ($prev_stage == 105) {//RECEIVING
                    if ($action == 199) {//queried
                        $this->processReceivingQueriedApplicationSubmission($request);
                    } else if ($action == 200) {//rejected
                        $this->processReceivingRejectedApplicationSubmission($request);
                    } else {//recommended
                        $this->processNormalApplicationSubmission($request);
                    }
                } else if ($prev_stage == 106) {//APPROVALS
                    $this->singleNewPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalApplicationSubmission($request);
                }
            } else if ($section_id == 2) {//todo DRUGS
                if ($prev_stage == 108) {//RECEIVING
                    if ($action == 207) {//queried
                        $this->processReceivingQueriedApplicationSubmission($request);
                    } else if ($action == 208) {//rejected
                        $this->processReceivingRejectedApplicationSubmission($request);
                    } else {//recommended
                        $this->processNormalApplicationSubmission($request);
                    }
                } else if ($prev_stage == 109) {//APPROVALS
                    $this->singleNewPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalApplicationSubmission($request);
                }
            } else if ($section_id == 3) {//todo COSMETICS
                if ($prev_stage == 111) {//RECEIVING
                    if ($action == 209) {//queried
                        $this->processReceivingQueriedApplicationSubmission($request);
                    } else if ($action == 210) {//rejected
                        $this->processReceivingRejectedApplicationSubmission($request);
                    } else {//recommended
                        $this->processNormalApplicationSubmission($request);
                    }
                } else if ($prev_stage == 112) {//APPROVALS
                    $this->singleNewPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalApplicationSubmission($request);
                }
            } else if ($section_id == 4) {//todo MEDICAL DEVICES
                if ($prev_stage == 114) {//RECEIVING
                    if ($action == 211) {//queried
                        $this->processReceivingQueriedApplicationSubmission($request);
                    } else if ($action == 212) {//rejected
                        $this->processReceivingRejectedApplicationSubmission($request);
                    } else {//recommended
                        $this->processNormalApplicationSubmission($request);
                    }
                } else if ($prev_stage == 115) {//APPROVALS
                    $this->singleNewPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalApplicationSubmission($request);
                }
            } else {
                $res = array(
                    'success' => false,
                    'message' => 'Unknown section selected!!'
                );
                echo json_encode($res);
                exit();
            }
        } else {
            $res = array(
                'success' => false,
                'message' => 'Unknown sub module selected!!'
            );
            echo json_encode($res);
            exit();
        }
    }

    public function processPremiseManagersApplicationSubmission($request)
    {
        $action = $request->input('action');
        $prev_stage = $request->input('curr_stage_id');
        $section_id = $request->input('section_id');
        $sub_module_id = $request->input('sub_module_id');
        $keep_status = false;
        if ($sub_module_id == 1) {//todo New Registration Applications
            if ($section_id == 1) {//todo FOOD
                if ($prev_stage == 14) {//APPROVALS
                    $keep_status = true;
                    $this->processNewApprovalApplicationSubmission($request, $keep_status);
                } else if ($prev_stage == 20) {//MANAGER QUERY
                    if ($action == 37) {//forward to customer
                        $this->submitApplicationFromManagerQueryToCustomer($request);
                    } else {
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    }
                } else if ($prev_stage == 13) {//MANAGER REVIEW
                    if ($action == 22) {//return to evaluation
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    } else {
                        $this->processNormalManagersApplicationSubmission($request, $keep_status);
                    }
                } else {
                    $this->processNormalManagersApplicationSubmission($request, $keep_status);
                }
            } else if ($section_id == 2) {//todo DRUGS
                if ($prev_stage == 27) {//APPROVALS
                    $keep_status = true;
                    $this->processNewApprovalApplicationSubmission($request, $keep_status);
                } else if ($prev_stage == 29) {//MANAGER QUERY
                    if ($action == 65) {//forward to customer
                        $this->submitApplicationFromManagerQueryToCustomer($request);
                    } else {
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    }
                } else if ($prev_stage == 31) {//MANAGER REVIEW
                    if ($action == 62) {//return to evaluation
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    } else {
                        $this->processNormalManagersApplicationSubmission($request, $keep_status);
                    }
                } else {
                    $this->processNormalManagersApplicationSubmission($request, $keep_status);
                }
            } else if ($section_id == 3) {//todo COSMETICS
                if ($prev_stage == 39) {//APPROVALS
                    $keep_status = true;
                    $this->processNewApprovalApplicationSubmission($request, $keep_status);
                } else if ($prev_stage == 41) {//MANAGER QUERY
                    if ($action == 111) {//forward to customer
                        $this->submitApplicationFromManagerQueryToCustomer($request);
                    } else {
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    }
                } else if ($prev_stage == 38) {//MANAGER REVIEW
                    if ($action == 80) {//return to evaluation
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    } else {
                        $this->processNormalManagersApplicationSubmission($request, $keep_status);
                    }
                } else {
                    $this->processNormalManagersApplicationSubmission($request, $keep_status);
                }
            } else if ($section_id == 4) {//todo MEDICAL DEVICES
                if ($prev_stage == 51) {//APPROVALS
                    $keep_status = true;
                    $this->processNewApprovalApplicationSubmission($request, $keep_status);
                } else if ($prev_stage == 54) {//MANAGER QUERY
                    if ($action == 105) {//forward to customer
                        $this->submitApplicationFromManagerQueryToCustomer($request);
                    } else {
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    }
                } else if ($prev_stage == 50) {//MANAGER REVIEW
                    if ($action == 112) {//return to evaluation
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    } else {
                        $this->processNormalManagersApplicationSubmission($request, $keep_status);
                    }
                } else {
                    $this->processNormalManagersApplicationSubmission($request, $keep_status);
                }
            } else {
                $res = array(
                    'success' => false,
                    'message' => 'Unknown section selected!!'
                );
                echo json_encode($res);
                exit();
            }
        } else if ($sub_module_id == 2) {//todo Renewal Applications
            if ($section_id == 1) {//todo FOOD
                if ($prev_stage == 66) {//MANAGER QUERY
                    if ($action == 186) {//forward to customer
                        $this->submitApplicationFromManagerQueryToCustomer($request);
                    } else {
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    }
                } else if ($prev_stage == 63) {//MANAGER REVIEW
                    if ($action == 129) {//return to evaluation
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    } else {
                        $this->processNormalManagersApplicationSubmission($request);
                    }
                } else if ($prev_stage == 64) {//APPROVALS
                    $this->processSubsequentApprovalApplicationSubmission($request);
                    //$this->batchPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalManagersApplicationSubmission($request);
                }
            } else if ($section_id == 2) {//todo DRUGS
                if ($prev_stage == 77) {//MANAGER QUERY
                    if ($action == 189) {//forward to customer
                        $this->submitApplicationFromManagerQueryToCustomer($request);
                    } else {
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    }
                } else if ($prev_stage == 74) {//MANAGER REVIEW
                    if ($action == 147) {//return to evaluation
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    } else {
                        $this->processNormalManagersApplicationSubmission($request);
                    }
                } else if ($prev_stage == 75) {//APPROVALS
                    $this->processSubsequentApprovalApplicationSubmission($request);
                    //$this->batchPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalManagersApplicationSubmission($request);
                }
            } else if ($section_id == 3) {//todo COSMETICS
                if ($prev_stage == 89) {//MANAGER QUERY
                    if ($action == 195) {//forward to customer
                        $this->submitApplicationFromManagerQueryToCustomer($request);
                    } else {
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    }
                } else if ($prev_stage == 86) {//MANAGER REVIEW
                    if ($action == 164) {//return to evaluation
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    } else {
                        $this->processNormalManagersApplicationSubmission($request);
                    }
                } else if ($prev_stage == 87) {//APPROVALS
                    $this->processSubsequentApprovalApplicationSubmission($request);
                    //$this->batchPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalManagersApplicationSubmission($request);
                }
            } else if ($section_id == 4) {//todo MEDICAL DEVICES
                if ($prev_stage == 101) {//MANAGER QUERY
                    if ($action == 192) {//forward to customer
                        $this->submitApplicationFromManagerQueryToCustomer($request);
                    } else {
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    }
                } else if ($prev_stage == 98) {//MANAGER REVIEW
                    if ($action == 182) {//return to evaluation
                        $this->processManagerQueryReturnApplicationSubmission($request);
                    } else {
                        $this->processNormalManagersApplicationSubmission($request);
                    }
                } else if ($prev_stage == 99) {//APPROVALS
                    $this->processSubsequentApprovalApplicationSubmission($request);
                    //$this->batchPremiseApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalManagersApplicationSubmission($request);
                }
            } else {
                $res = array(
                    'success' => false,
                    'message' => 'Unknown section selected!!'
                );
                echo json_encode($res);
                exit();
            }
        } else if ($sub_module_id == 3) {//todo Alteration Applications
            if ($section_id == 1) {//todo FOOD
                if ($prev_stage == 106) {//APPROVALS
                    $this->batchPremiseAlterationApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalManagersApplicationSubmission($request);
                }
            } else if ($section_id == 2) {//todo DRUGS
                if ($prev_stage == 109) {//APPROVALS
                    $this->batchPremiseAlterationApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalManagersApplicationSubmission($request);
                }
            } else if ($section_id == 3) {//todo COSMETICS
                if ($prev_stage == 112) {//APPROVALS
                    $this->batchPremiseAlterationApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalManagersApplicationSubmission($request);
                }
            } else if ($section_id == 4) {//todo MEDICAL DEVICES
                if ($prev_stage == 115) {//APPROVALS
                    $this->batchPremiseAlterationApplicationApprovalSubmission($request);
                } else {
                    $this->processNormalManagersApplicationSubmission($request);
                }
            } else {
                $res = array(
                    'success' => false,
                    'message' => 'Unknown section selected!!'
                );
                echo json_encode($res);
                exit();
            }
        } else {
            $res = array(
                'success' => false,
                'message' => 'Unknown sub module selected!!'
            );
            echo json_encode($res);
            exit();
        }
    }

    public function updateQueriedPremiseApplicationPortal(Request $request, $application_details)
    {
        $user_id = $this->user_id;
        $remarks = $request->input('remarks');
        $urgency = $request->input('urgency');
        //update portal status
        $portal_db = DB::connection('portal_db');
        $update = $portal_db->table('wb_premises_applications')
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

    public function updateRejectedPremiseApplicationPortal(Request $request, $application_details)
    {
        $user_id = $this->user_id;
        $remarks = $request->input('remarks');
        $urgency = $request->input('urgency');
        //update portal status
        $portal_db = DB::connection('portal_db');
        $update = $portal_db->table('wb_premises_applications')
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

    public function processPremiseReturnApplicationSubmissionsWithChecklists($request, $checklist_category)
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
                exit();
            }
            inValidateApplicationChecklist($application_details->module_id, $application_details->sub_module_id, $application_details->section_id, $checklist_category, array($application_details->application_code));
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

    public function updatePremiseManagerQueryToCustomerPortal($portal_ids)
    {
        $portal_db = DB::connection('portal_db');
        //update portal status
        $update = $portal_db->table('wb_premises_applications')
            ->whereIn('id', $portal_ids)
            ->update(array('application_status_id' => 8));
        if ($update < 1) {
            return false;
        } else {
            return true;
        }
    }

    public function singleNewPremiseApplicationApprovalSubmission($request)
    {
        $application_code = $request->input('application_code');
        try {
            $valid = $this->validatePremiseApprovalApplication($application_code);
            if ($valid == false) {
                $res = array(
                    'success' => false,
                    'message' => 'Please capture recommendation details first!!'
                );
                echo json_encode($res);
                exit();
            }
            $this->processNormalApplicationSubmission($request, true);
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

    public function batchPremiseApplicationApprovalSubmission(Request $request)
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
                $view_id=generateApplicationViewID();
                $application_status_id = $application_detail->application_status_id;
                if ($application_detail->decision_id == 1) {
                    $portal_status_id = 10;
                    $this->updateRegistrationTable($application_detail->reg_premise_id, $application_detail->premise_id, $module_id);
                    /*$response = $this->processRenewalPremiseApprovalApplicationSubmission($application_detail->id, $table_name, $formAmendmentDetails, $othersAmendmentDetails);
                    $success = $response['success'];
                    if ($success == false) {
                        DB::rollBack();
                        echo json_encode($response);
                        return false;
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

    public function singleRenewalPremiseApplicationApprovalSubmission(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        $process_id = $request->input('process_id');
        try {
            $valid = $this->validatePremiseApprovalApplication($application_code);
            if ($valid == false) {
                $res = array(
                    'success' => false,
                    'message' => 'Please capture recommendation details first!!'
                );
                echo json_encode($res);
                exit();
            }
            //check decision
            $decision_id = DB::table('tra_approval_recommendations')
                ->where(array('application_id' => $application_id, 'application_code' => $application_code))
                ->value('decision_id');
            if ($decision_id == 1) {//granted

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

                $response = $this->processRenewalPremiseApprovalApplicationSubmission($application_id, $table_name, $formAmendmentDetails, $othersAmendmentDetails);
                $success = $response['success'];
                if ($success == false) {
                    echo json_encode($response);
                    exit();
                }
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

    public function processRenewalPremiseApprovalApplicationSubmission($application_id, $table_name, $formAmendmentDetails, $othersAmendmentDetails)
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
            $premise_id = $application_details->premise_id;
            $temp_details = DB::table('tra_premises')
                ->where('id', $premise_id)
                ->first();
            if (is_null($temp_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching (temp)premise details!!'
                );
                return $res;
            }
            $init_premise_id = $temp_details->init_premise_id;
            $current_permit_id = $temp_details->permit_id;
            //premise log data
            $log_data = DB::table('tra_premises as t1')
                ->select(DB::raw("t1.*,t1.id as premise_id,$user_id as log_by,NOW() as log_date"))
                ->where('id', $init_premise_id)
                ->first();
            //todo: update renewal changes
            //1. Basic premise info
            if ($formAmendmentDetails->changed == '') {
                //No changes on basic premise info
            } else {
                $this->updateAlterationBasicDetails($formAmendmentDetails, $premise_id, $init_premise_id, $log_data);
            }
            //2. Personnel(id 2) and Business(id 3) details
            if (count($othersAmendmentDetails) > 0) {
                foreach ($othersAmendmentDetails as $othersAmendmentDetail) {
                    if ($othersAmendmentDetail->part_id == 2) {
                        //update personnel details
                        $this->updateAlterationPersonnelDetails($premise_id, $init_premise_id);
                    }
                    if ($othersAmendmentDetail->part_id == 3) {
                        //update business details
                        $this->updateAlterationBusinessDetails($premise_id, $init_premise_id);
                    }
                }
            }
            updateRenewalPermitDetails($init_premise_id, $current_permit_id, 'tra_premises');

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

    public function batchPremiseAlterationApplicationApprovalSubmission(Request $request)
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
            //$application_status_id = getApplicationTransitionStatus($from_stage, $action, $to_stage);
            $portal_table_name = getPortalApplicationsTable($module_id);
            //application details
            foreach ($application_details as $key => $application_detail) {
                $application_status_id = $application_detail->application_status_id;
                if ($application_detail->decision_id == 1) {
                    $portal_status_id = 10;
                    $this->updatePremiseAlterationPermitDetails($application_detail->premise_id);
                    $this->updateRegTableRecordTraIDOnApproval($application_detail, $module_id);
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

    public function updatePremiseAlterationPermitDetails($premise_id)
    {
        $user_id = $this->user_id;
        try {
            //get application_details
            $current_details = DB::table('tra_premises')
                ->where('id', $premise_id)
                ->first();
            if (is_null($current_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching (current)premise details!!'
                );
                return $res;
            }
            $init_premise_id = $current_details->init_premise_id;
            $current_permit_id = $current_details->permit_id;
            //premise log data
            $log_data = DB::table('tra_premises as t1')
                ->select(DB::raw("t1.*,t1.id as premise_id,$user_id as log_by,NOW() as log_date"))
                ->where('id', $init_premise_id)
                ->first();
            $init_permit_id = $log_data->permit_id;

            $initPermitDetails = DB::table('tra_approval_recommendations as t1')
                ->select('t1.permit_no', 't1.approval_date', 't1.expiry_date')
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

    public function processAlterationPremiseApprovalApplicationSubmission($application_id, $table_name)
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
            $premise_id = $application_details->premise_id;
            $application_code = $application_details->application_code;
            $temp_details = DB::table('tra_premises')
                ->where('id', $premise_id)
                ->first();
            if (is_null($temp_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching (temp)premise details!!'
                );
                return $res;
            }
            $init_premise_id = $temp_details->init_premise_id;
            $temp_permit_id = $temp_details->permit_id;
            //premise log data
            $log_data = DB::table('tra_premises as t1')
                ->select(DB::raw("t1.*,t1.id as premise_id,$user_id as log_by,NOW() as log_date"))
                ->where('id', $init_premise_id)
                ->first();
            $init_permit_id = $log_data->permit_id;
            //todo get alteration requests
            //1. Basic premise info
            $formAmendmentDetails = DB::table('tra_alt_formparts_amendments as t1')
                ->join('par_key_form_fields as t2', 't1.field_id', '=', 't2.id')
                ->select(DB::raw("GROUP_CONCAT(t2.field_name) AS changed"))
                ->where('t1.application_code', $application_code)
                ->first();
            if ($formAmendmentDetails->changed == '') {
                //No changes on basic premise info
            } else {
                $this->updateAlterationBasicDetails($formAmendmentDetails, $premise_id, $init_premise_id, $log_data);
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
                        $this->updateAlterationPersonnelDetails($premise_id, $init_premise_id);
                    }
                    if ($othersAmendmentDetail->part_id == 3) {
                        //update business details
                        $this->updateAlterationBusinessDetails($premise_id, $init_premise_id);
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

    public function updateAlterationBasicDetails($formAmendmentDetails, $premise_id, $init_premise_id, $log_data)
    {
        unset($log_data->id);
        $log_data = convertStdClassObjToArray($log_data);
        DB::table('tra_premises_log')
            ->insert($log_data);

        $amended_premise_details = DB::table('tra_premises')
            ->select(DB::raw($formAmendmentDetails->changed))
            ->where('id', $premise_id)
            ->first();
        $amended_premise_details = convertStdClassObjToArray($amended_premise_details);
        DB::table('tra_premises')
            ->where('id', $init_premise_id)
            ->update($amended_premise_details);
    }

    public function updateAlterationPersonnelDetails($temp_premise_id, $init_premise_id)
    {
        $user_id = $this->user_id;
        //initial
        $init_qry = DB::table('tra_premises_personnel')
            ->where('premise_id', $init_premise_id);
        $init_details = $init_qry->select(DB::raw("tra_premises_personnel.*,$user_id as log_by,NOW() as log_date"))
            ->get();
        $init_details = convertStdClassObjToArray($init_details);
        $log_insert = DB::table('tra_premises_personnel_log')
            ->insert($init_details);
        $init_qry->delete();
        //Temp
        $temp_qry = DB::table('tra_premises_personnel as t2')
            ->select(DB::raw("t2.temp_premise_id,t2.init_premise_id,t2.personnel_id,t2.position_id,t2.personnel_qualification_id,
            t2.start_date,t2.end_date,t2.status_id,t2.created_by,t2.altered_by,t2.created_on,t2.dola,t2.portal_id,t2.is_temporal,$init_premise_id as premise_id"))
            ->where('premise_id', $temp_premise_id);
        $temp_details = $temp_qry->get();
        $temp_details = convertStdClassObjToArray($temp_details);
        $init_insert = DB::table('tra_premises_personnel')
            ->insert($temp_details);
    }

    public function updateAlterationBusinessDetails($temp_premise_id, $init_premise_id)
    {
        $user_id = $this->user_id;
        //initial
        $init_qry = DB::table('tra_premises_otherdetails')
            ->where('premise_id', $init_premise_id);
        $init_details = $init_qry->select(DB::raw("tra_premises_otherdetails.*,$user_id as log_by,NOW() as log_date"))
            ->get();
        $init_details = convertStdClassObjToArray($init_details);
        $log_insert = DB::table('tra_premises_otherdetails_log')
            ->insert($init_details);
        $init_qry->delete();
        //Temp
        $temp_qry = DB::table('tra_premises_otherdetails as t2')
            ->select(DB::raw("t2.temp_premise_id,t2.init_premise_id,t2.business_type_id,t2.business_type_detail_id,
            t2.created_by,t2.altered_by,t2.created_on,t2.dola,t2.portal_id,t2.is_temporal,$init_premise_id as premise_id"))
            ->where('premise_id', $temp_premise_id);
        $temp_details = $temp_qry->get();
        $temp_details = convertStdClassObjToArray($temp_details);
        $init_insert = DB::table('tra_premises_otherdetails')
            ->insert($temp_details);
    }

    public function updateAlterationPermitDetails($temp_permit_id, $init_permit_id)//deprecated
    {
        //1. update signatory details of the initial permit
        //2. update other details of the temporary permit..no need
        $initUpdateQry = DB::table('tra_approval_recommendations as t1')
            ->where('t1.id', $temp_permit_id);
        /*$tempUpdateQry=DB::table('tra_approval_recommendations as t2')
            ->where('t2.id', $init_permit_id);*/

        $initPermitUpdateParams = clone $initUpdateQry
            ->select('t1.dg_signatory', 't1.permit_signatory')
            ->first();
        $initPermitUpdateParams = convertStdClassObjToArray($initPermitUpdateParams);

        /*$tempPermitUpdateParams=$tempUpdateQry
            ->select('t2.permit_no', 't2.approval_date','t2.expiry_date')
            ->first();
        $tempPermitUpdateParams = convertStdClassObjToArray($tempPermitUpdateParams);*/

        DB::table('tra_approval_recommendations')
            ->where('id', $init_permit_id)
            ->update($initPermitUpdateParams);
        /* DB::table('tra_approval_recommendations')
             ->where('id', $temp_permit_id)
             ->update($tempPermitUpdateParams);*/
    }

    //VALIDATION FUNCTIONS
    public function validatePremiseReceivingQueriedApplication($application_id)
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

    public function validatePremiseInspectionApplication($application_code)
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

    public function validatePremiseApprovalApplication($application_code)
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

    public function savePremiseApplicationApprovalDetails(Request $request, $sub_module_id)
    {
        if ($sub_module_id == 3) {
            $res = $this->savePremiseApplicationAlterationRecommendationDetails($request);
        } else {
            $res = $this->savePremiseApplicationRecommendationDetails($request);
        }
        return $res;
    }

    public function savePremiseApplicationRecommendationDetails(Request $request)
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
                $premiseUpdateParams['certificate_issue_date'] = $approval_date;
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
                        $premiseUpdateParams['premise_reg_no'] = $app_details->reference_no;
                        $premise_status_id = 2;
                        $qry->update(array('application_status_id' => 6));
                        //permit
                        if ($prev_decision_id != 1) {
                            $permit_no = generatePremisePermitNo($app_details->zone_id, $app_details->section_id, $table_name, $user_id, 5);
                            $params['permit_no'] = $permit_no;
                        }
                    } else {
                        $premiseUpdateParams['premise_reg_no'] = null;
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
                        $premiseUpdateParams['premise_reg_no'] = $app_details->reference_no;
                        $premise_status_id = 2;
                        //permits
                        $permit_no = generatePremisePermitNo($app_details->zone_id, $app_details->section_id, $table_name, $user_id, 5);
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
                DB::table('tra_premises')
                    ->where('id', $app_details->premise_id)
                    ->update($premiseUpdateParams);
                DB::table('tra_premises_applications')
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

    public function savePremiseApplicationAlterationRecommendationDetails(Request $request)
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
                //$premiseUpdateParams = array();
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
                    $prev_data_results[0]['update_by'] = $user_id;
                    $prev_data_results[0]['recommendation_id'] = $prev_data_results[0]['id'];
                    unset($prev_data_results[0]['id']);
                    DB::table('tra_approval_recommendations_log')
                        ->insert($prev_data_results);
                    if ($decision_id == 1) {
                        $qry->update(array('application_status_id' => 6));
                    } else {
                        $qry->update(array('application_status_id' => 7));
                    }
                    $res = updateRecord('tra_approval_recommendations', $prev_data['results'], $where, $params, $user_id);
                } else {
                    //insert
                    $params['created_on'] = Carbon::now();
                    $params['created_by'] = $user_id;
                    if ($decision_id == 1) {
                        $qry->update(array('application_status_id' => 6));
                    } else {
                        $qry->update(array('application_status_id' => 7));
                    }
                    $res = insertRecord('tra_approval_recommendations', $params, $user_id);
                    $id = $res['record_id'];
                }
                DB::table('tra_premises')
                    ->where('id', $app_details->premise_id)
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

    public function savePremiseOnlineApplicationDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $responsible_user = $request->input('responsible_user');
        $urgency = $request->input('urgency');
        $comment = $request->input('remarks');
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {
            $portal_db = DB::connection('portal_db');
            $qry = $portal_db->table('wb_premises_applications as t1')
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
            $zone_code = getSingleRecordColValue('par_zones', array('id' => $results->zone_id), 'zone_code');
            $section_code = getSingleRecordColValue('par_sections', array('id' => $results->section_id), 'code');
            $codes_array = array(
                'section_code' => $section_code,
                'zone_code' => $zone_code
            );
            if ($sub_module_id == 1) {//new
                $ref_id = 1;
            } else if ($sub_module_id == 2) {//renewal
                $ref_id = 7;
            } else if ($sub_module_id == 3) {//alteration
                $ref_id = 8;
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
            $application_code = $results->application_code;// generateApplicationCode($sub_module_id, 'tra_premises_applications');
            //applicant details
            $applicant_details = $portal_db->table('wb_trader_account')
                ->where('id', $results->trader_id)
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
            $applicant_email = $applicant_details->email;
            //premise main details
            $premise_details = $portal_db->table('wb_premises')
                ->where('id', $results->premise_id)
                ->first();
            if (is_null($premise_details)) {
                DB::rollBack();
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while getting premise details, consult System Admin!!'
                );
                return $res;
            }
            $premise_details->portal_id = $results->premise_id;
            $premise_details->applicant_id = $applicant_id;
            $premise_details->created_by = $this->user_id;
            $premise_details = convertStdClassObjToArray($premise_details);
            unset($premise_details['id']);
            unset($premise_details['trader_id']);
            unset($premise_details['mis_dola']);
            unset($premise_details['mis_altered_by']);
            $prem_insert = insertRecord('tra_premises', $premise_details, $user_id);
            if ($prem_insert['success'] == false) {
                DB::rollBack();
                return $prem_insert;
            }
            $premise_id = $prem_insert['record_id'];
            //premise other details
            $premise_otherdetails = $portal_db->table('wb_premises_otherdetails')
                ->where('premise_id', $results->premise_id)
                ->select(DB::raw("id as portal_id,$premise_id as premise_id,business_type_id,business_type_detail_id,$user_id as created_by"))
                ->get();
            $premise_otherdetails = convertStdClassObjToArray($premise_otherdetails);
            $premise_otherdetails = unsetPrimaryIDsInArray($premise_otherdetails);
            DB::table('tra_premises_otherdetails')
                ->insert($premise_otherdetails);
            //premise personnel details
            $premise_personneldetails = $portal_db->table('wb_premises_personnel')
                ->where('premise_id', $results->premise_id)
                ->select(DB::raw("id as portal_id,$premise_id as premise_id,personnel_id,position_id,personnel_qualification_id,start_date,end_date,status_id,$user_id as created_by"))
                ->get();
            $premise_personneldetails = convertStdClassObjToArray($premise_personneldetails);
            $premise_personneldetails = unsetPrimaryIDsInArray($premise_personneldetails);
            DB::table('tra_premises_personnel')
                ->insert($premise_personneldetails);
            //application details
            $app_status = getApplicationInitialStatus($results->module_id, $results->sub_module_id);
            $app_status_id = $app_status->status_id;
            $application_status = getSingleRecordColValue('par_system_statuses', array('id' => $app_status_id), 'name');
            $application_details = array(
                'reference_no' => $ref_no,
                'tracking_no' => $tracking_no,
                'view_id' => $view_id,
                'applicant_id' => $applicant_id,
                'application_code' => $application_code,
                'premise_id' => $premise_id,
                'module_id' => $results->module_id,
                'sub_module_id' => $results->sub_module_id,
                'zone_id' => $results->zone_id,
                'section_id' => $results->section_id,
                'process_id' => $process_details->id,
                'workflow_stage_id' => $workflow_details->id,
                'application_status_id' => $app_status_id,
                'portal_id' => $portal_application_id
            );
            $application_insert = insertRecord('tra_premises_applications', $application_details, $user_id);
            if ($application_insert['success'] == false) {
                DB::rollBack();
                return $application_insert;
            }
            $mis_application_id = $application_insert['record_id'];
            $reg_params = array(
                'tra_premise_id' => $mis_application_id,
                'registration_status' => 1,
                'validity_status' => 1,
                'created_by' => $user_id
            );
            createInitialRegistrationRecord('registered_premises', 'tra_premises_applications', $reg_params, $mis_application_id, 'reg_premise_id');
            $portal_params = array(
                'application_status_id' => 3,
                'reference_no' => $ref_no
            );
            $portal_where = array(
                'id' => $portal_application_id
            );
            updatePortalParams('wb_premises_applications', $portal_params, $portal_where);
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
                'premise_id' => $premise_id,
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
            //send email
            $vars = array(
                '{tracking_no}' => $tracking_no
            );
            onlineApplicationNotificationMail(2, $applicant_email, $vars);
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

    public function getPremiseInvoiceDetails($application_id)
    {
        $qry = DB::table('tra_premises_applications as t1')
            ->join('wf_tfdaprocesses as t2', 't1.process_id', '=', 't2.id')
            ->join('tra_premises as t3', 't1.premise_id', '=', 't3.id')
            ->join('modules as t4', 't1.module_id', '=', 't4.id')
            ->select(DB::raw("t1.reference_no,t2.name as process_name,t4.invoice_desc as module_name,
                     CONCAT_WS(', ',t3.name,t3.physical_address) as module_desc"))
            ->where('t1.id', $application_id);
        $invoice_details = $qry->first();
        return $invoice_details;
    }

}