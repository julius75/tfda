<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Modules\PremiseRegistration\Traits\PremiseRegistrationTrait;
use App\Modules\GmpApplications\Traits\GmpApplicationsTrait;
use App\Modules\ClinicalTrial\Traits\ClinicalTrialTrait;
use App\Modules\ProductRegistration\Traits\ProductsRegistrationTrait;

class CommonController extends Controller
{

    use PremiseRegistrationTrait;
    use GmpApplicationsTrait;
    use ClinicalTrialTrait;
    use ProductsRegistrationTrait;
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

    public function getCommonParamFromModel(Request $request)
    {
        $model_name = $request->input('model_name');
        try {
            $model = 'App\\' . $model_name;
            $results = $model::all()->toArray();
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
        }
        return response()->json($res);
    }

    public function saveApplicationApprovalDetails(Request $request)
    {
        $table_name = $request->input('table_name');
        $application_id = $request->input('application_id');
        $res = array();
        $qry = DB::table($table_name)
            ->where('id', $application_id);
        $app_details = $qry->first();

        if (is_null($app_details)) {
            $res = array(
                'success' => false,
                'message' => 'Problem encountered while getting application details!!'
            );
            return response()->json($res);
        }
        $module_id = $app_details->module_id;
        $sub_module_id = $app_details->sub_module_id;
        if ($module_id == 1) {//Products
            $res = $this->saveProductApplicationApprovalDetails($request, $sub_module_id);
        } else if ($module_id == 2) {//Premises
            $res = $this->savePremiseApplicationApprovalDetails($request, $sub_module_id);
        } else if ($module_id == 3) {//Gmp
            $res = $this->saveGmpApplicationApprovalDetails($request, $sub_module_id);
        } else if ($module_id == 7) {//Clinical Trial
            $res = $this->saveClinicalTrialApplicationApprovalDetails($request, $sub_module_id);
        }
        return \response()->json($res);
    }

    public function checkInvoicePaymentsLimit(Request $request)
    {
        $module_id = $request->input('module_id');
        $section_id = $request->input('section_id');
        $currency_id = $request->input('currency_id');
        $amount = $request->input('amount');
        try {
            $where = array(
                'section_id' => $section_id,
                'module_id' => $module_id,
                'currency_id' => $currency_id
            );
            $limit_amount = DB::table('invoicespayments_limitsetup')
                ->where($where)
                ->value('limit_amount');
            if (is_numeric($limit_amount) && $limit_amount > 1) {
                if ($amount > $limit_amount) {
                    $res = array(
                        'status_code' => 2,//limit exceeded
                        'limit_amount' => $limit_amount
                    );
                } else {
                    $res = array(
                        'status_code' => 1,//limit not exceeded
                        'limit_amount' => $limit_amount
                    );
                }
            } else {
                $res = array(
                    'status_code' => 3,//limit not set
                    'limit_amount' => $limit_amount
                );
            }
        } catch (\Exception $exception) {
            $res = array(
                'status_code' => 4,//error
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'status_code' => 4,//error
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    public function saveApplicationPaymentDetails(Request $request)
    {
        $user_id = $this->user_id;
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $amount = $request->input('amount_paid');
        $currency_id = $request->input('currency_id');
        $applicant_id = $request->input('applicant_id');
        $section_id = $request->input('section_id');
        $module_id = $request->input('module_id');
        $sub_module_id = $request->input('sub_module_id');
        $invoice_id = $request->input('invoice_id');
        $non_gepg_reason = $request->input('non_gepg_reason');
        $receipt_no = generateReceiptNo($user_id);
        $exchange_rate = getSingleRecordColValue('par_exchange_rates', array('currency_id' => $currency_id), 'exchange_rate');
        $params = array(
            'application_id' => $application_id,
            'application_code' => $application_code,
            'applicant_name' => $request->input('applicant_name'),
            'amount_paid' => $amount,
            'invoice_id' => $invoice_id,
            'receipt_no' => $receipt_no,
            'manual_receipt_no' => $request->input('manual_receipt_no'),
            'trans_date' => $request->input('trans_date'),
            'currency_id' => $currency_id,
            'applicant_id' => $applicant_id,
            'section_id' => $section_id,
            'module_id' => $module_id,
            'sub_module_id' => $sub_module_id,
            'receipt_type_id' => $request->input('receipt_type_id'),
            'payment_mode_id' => $request->input('payment_mode_id'),
            'trans_ref' => $request->input('trans_ref'),
            'bank_id' => $request->input('bank_id'),
            'drawer' => $request->input('drawer'),
            'exchange_rate' => $exchange_rate,
            'created_on' => Carbon::now(),
            'created_by' => $user_id,
            'non_gepg_reason'=>$non_gepg_reason
        );
        try {
            $res = insertRecord('tra_payments', $params, $user_id);

            generatePaymentRefDistribution($invoice_id, $res['record_id'], $amount, $currency_id, $user_id);
            $payment_details = getApplicationPaymentsRunningBalance($application_id, $application_code, $invoice_id);
            $res['balance'] = $payment_details['running_balance'];
            $res['invoice_amount'] = $payment_details['invoice_amount'];
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

    public function submitQueriedOnlineApplication(Request $request)
    {
        $application_id = $request->input('application_id');
        $table_name = $request->input('table_name');
        try {
            $portal_db = DB::connection('portal_db');
            //get application details
            $app_details = $portal_db->table($table_name . ' as t1')
                ->join('wb_trader_account as t2', 't1.trader_id', '=', 't2.id')
                ->select('t1.tracking_no', 't2.email')
                ->where('t1.id', $application_id)
                ->first();
            if (is_null($app_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching application details!!'
                );
                return \response()->json($res);
            }
            $tracking_no = $app_details->tracking_no;
            $applicant_email = $app_details->email;
            $portal_db->table($table_name)
                ->where('id', $application_id)
                ->update(array('application_status_id' => 17));
            //send email
            $vars = array(
                '{tracking_no}' => $tracking_no
            );
            onlineApplicationNotificationMail(3, $applicant_email, $vars);
            $res = array(
                'success' => true,
                'message' => 'Request executed successfully!!'
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

    public function submitRejectedOnlineApplication(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        $comment = $request->input('comment');
        $rejection_params = array(
            'application_id' => $application_id,
            'application_code' => $application_code,
            'remark' => $comment,
            'mis_created_by' => $this->user_id
        );
        try {
            $portal_db = DB::connection('portal_db');
            //get application details
            $app_details = $portal_db->table($table_name . ' as t1')
                ->join('wb_trader_account as t2', 't1.trader_id', '=', 't2.id')
                ->select('t1.tracking_no', 't2.email')
                ->where('t1.id', $application_id)
                ->first();
            if (is_null($app_details)) {
                $res = array(
                    'success' => false,
                    'message' => 'Problem encountered while fetching application details!!'
                );
                return \response()->json($res);
            }
            $tracking_no = $app_details->tracking_no;
            $applicant_email = $app_details->email;
            $portal_db->table('wb_rejection_remarks')
                ->insert($rejection_params);
            $portal_db->table($table_name)
                ->where('id', $application_id)
                ->update(array('application_status_id' => 18));
            //send email
            $vars = array(
                '{tracking_no}' => $tracking_no
            );
            onlineApplicationNotificationMail(4, $applicant_email, $vars);
            $res = array(
                'success' => true,
                'message' => 'Request executed successfully!!'
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

    public function getApplicationApprovalDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        try {
            $where = array(
                't1.application_id' => $application_id,
                't1.application_code' => $application_code
            );
            $qry = DB::table('tra_approval_recommendations as t1')
                ->select('t1.*', 't1.id as recommendation_id')
                ->where($where)
                ->orderBy('t1.id', 'DESC');
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

    public function saveApplicationInvoicingDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $reference_no = $request->input('reference_no');
        $invoice_id = $request->input('invoice_id');
        $module_id = $request->input('module_id');
        $applicant_id = $request->input('applicant_id');
        $paying_currency_id = $request->input('paying_currency_id');
        $isLocked = $request->input('isLocked');
        $is_fast_track = $request->input('is_fast_track');
        $details = $request->input();
        $user_id = $this->user_id;
        unset($details['_token']);
        unset($details['application_id']);
        unset($details['application_code']);
        unset($details['invoice_id']);
        unset($details['applicant_id']);
        unset($details['paying_currency_id']);
        unset($details['isLocked']);
        unset($details['is_fast_track']);
        unset($details['module_id']);
        unset($details['reference_no']);
        if (!is_numeric($isLocked) || $isLocked == '') {
            $isLocked = 0;
        }
        try {
            $res = array();

            DB::transaction(function () use (&$res, $reference_no, $is_fast_track, $module_id, $user_id, $isLocked, $paying_currency_id, $applicant_id, $application_id, $application_code, $invoice_id, $details) {
                $table_name = getSingleRecordColValue('modules', array('id' => $module_id), 'table_name');
                $applicant_details = getTableData('wb_trader_account', array('id' => $applicant_id));
                if (is_null($applicant_details)) {
                    $res = array(
                        'success' => false,
                        'message' => 'Problem encountered while getting applicant details!!'
                    );
                    return response()->json($res);
                }
                $applicant_name = $applicant_details->name;
                $applicant_email = $applicant_details->email;
                $applicant_name = strtoupper($applicant_name);
                $paying_exchange_rate = getExchangeRate($paying_currency_id);
                $due_date_counter = env('INVOICE_DUE_DAYS', 7);
                $date_today = Carbon::now();
                $due_date = $date_today->addDays($due_date_counter);
                $user = \Auth::user();
                $prepared_by = aes_decrypt($user->first_name) . ' ' . aes_decrypt($user->last_name);
                $invoice_params = array(
                    'applicant_id' => $applicant_id,
                    'applicant_name' => $applicant_name,
                    'paying_currency_id' => $paying_currency_id,
                    'paying_exchange_rate' => $paying_exchange_rate,
                    'isLocked' => $isLocked,
                    'payment_terms' => 'Due in ' . $due_date_counter . ' Days',
                    'created_on' => Carbon::now()
                );
                $invoicing_date = Carbon::now();
                if ($isLocked == 1) {
                    $invoice_params['date_of_invoicing'] = $invoicing_date;
                    $invoice_params['prepared_by'] = $prepared_by;
                    $invoice_params['due_date'] = $due_date;
                }
                //update application (is fast track) status
                DB::table($table_name)
                    ->where('id', $application_id)
                    ->update(array('is_fast_track' => $is_fast_track));
                if (isset($invoice_id) && $invoice_id != '') {
                    $invoice_no = getSingleRecordColValue('tra_application_invoices', array('id' => $invoice_id), 'invoice_no');
                    $previous_data = getPreviousRecords('tra_application_invoices', array('id' => $invoice_id));
                    if ($previous_data['success'] == false) {
                        return \response()->json($previous_data);
                    }
                    $previous_data = $previous_data['results'];
                    updateRecord('tra_application_invoices', $previous_data, array('id' => $invoice_id), $invoice_params, $user_id);
                } else {
                    $invoice_no = generateInvoiceNo($user_id);
                    $invoice_params['invoice_no'] = $invoice_no;
                    $invoice_params['application_id'] = $application_id;
                    $invoice_params['application_code'] = $application_code;
                    $invoice_params['applicant_id'] = $applicant_id;
                    $res = insertRecord('tra_application_invoices', $invoice_params, $user_id);
                    if ($res['success'] == false) {
                        return \response()->json($res);
                    }
                    $invoice_id = $res['record_id'];
                }
                $params = array();
                $invoice_amount = 0;
                foreach ($details as $detail) {
                    $invoice_amount += ($detail['cost'] * $detail['exchange_rate'] * $detail['quantity']);
                    //check
                    $element_costs_id = $detail['element_costs_id'];
                    $where_check = array(
                        'invoice_id' => $invoice_id,
                        'element_costs_id' => $element_costs_id
                    );
                    if (DB::table('tra_invoice_details')
                            ->where($where_check)
                            ->count() < 1) {
                        $params[] = array(
                            'invoice_id' => $invoice_id,
                            'element_costs_id' => $element_costs_id,
                            'element_amount' => $detail['cost'],
                            'currency_id' => $detail['currency_id'],
                            'exchange_rate' => $detail['exchange_rate'],
                            'quantity' => $detail['quantity'],
                            'total_element_amount' => ($detail['cost'] * $detail['quantity'])
                        );
                    } else {
                        $update = array(
                            'quantity' => $detail['quantity'],
                            'total_element_amount' => ($detail['cost'] * $detail['quantity']),
                            'dola' => Carbon::now(),
                            'altered_by' => $user_id
                        );
                        DB::table('tra_invoice_details')
                            ->where($where_check)
                            ->update($update);
                    }
                }
                DB::table('tra_invoice_details')->insert($params);
                DB::table('tra_application_invoices')
                    ->where('id', $invoice_id)
                    ->update(array('invoice_amount' => $invoice_amount));
                if ($isLocked == 1) {
                    $vars = array(
                        '{reference_no}' => $reference_no,
                        '{invoice_no}' => $invoice_no,
                        '{invoice_date}' => $invoicing_date
                    );
                    $params = array(
                        'application_code' => $application_code,
                        'invoice_id' => $invoice_id
                    );
                    $report = generateJasperReport('invoiceReport', 'invoice', 'pdf', $params);
                    applicationInvoiceEmail(5, $applicant_email, $vars, $report, 'invoice_' . $invoice_no);
                }
                $res = array(
                    'success' => true,
                    'invoice_id' => $invoice_id,
                    'invoice_no' => $invoice_no,
                    'message' => 'Invoice details saved successfully!!'
                );
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
        return \response()->json($res);
    }

    public function removeInvoiceCostElement(Request $request)
    {
        $item_ids = $request->input();
        try {
            DB::table('tra_invoice_details')
                ->whereIn('id', $item_ids)
                ->delete();
            $res = array(
                'success' => true,
                'message' => 'Selected Invoice items removed successfully!!'
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

    public function getApplicationApplicantDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $applicant_id = $request->input('applicant_id');
        $table_name = $request->input('table_name');

        try {
            $qry = DB::table('vw_trader_details')
                ->select('id as applicant_id', 'name as applicant_name', 'contact_person', 'physical_address', 'postal_address', 'district_name', 'region_name', 'country_name', 'telephone_no')
                ->where('id', function ($query) use ($table_name, $application_id) {
                    $query->select(DB::raw('applicant_id'))
                        ->from($table_name)
                        ->where('id', $application_id);
                });
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

    public function saveApplicationChecklistDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $screening_details = $request->input('screening_details');
        $screening_details = json_decode($screening_details);
        $table_name = 'checklistitems_responses';
        $user_id = $this->user_id;
        try {
            $insert_params = array();
            foreach ($screening_details as $screening_detail) {
                $item_resp_id = $screening_detail->item_resp_id;
                if (isset($item_resp_id) && $item_resp_id != '') {
                    $where = array(
                        'id' => $item_resp_id
                    );
                    $pass_status = $screening_detail->pass_status;
                    if (DB::table('checklistitems_queries')
                            ->where('item_resp_id', $item_resp_id)
                            ->where('status', '<>', 4)
                            ->count() > 0) {
                        $pass_status = 2;
                    }
                    $update_params = array(
                        'pass_status' => $pass_status,
                        'comment' => $screening_detail->comment,
                        'dola' => Carbon::now(),
                        'altered_by' => $user_id
                    );
                    $prev_data = getPreviousRecords($table_name, $where);
                    updateRecord($table_name, $prev_data['results'], $where, $update_params, $user_id);
                } else {
                    $insert_params[] = array(
                        'application_id' => $application_id,
                        'application_code' => $application_code,
                        'checklist_item_id' => $screening_detail->checklist_item_id,
                        'pass_status' => $screening_detail->pass_status,
                        'comment' => $screening_detail->comment,
                        'created_on' => Carbon::now(),
                        'created_by' => $user_id
                    );
                }
            }
            if (count($insert_params) > 0) {
                DB::table($table_name)
                    ->insert($insert_params);
            }
            $res = array(
                'success' => true,
                'message' => 'Screening details saved successfully!!'
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

    public function getApplicationComments(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $comment_type_id = $request->input('comment_type_id');
        try {
            $qry = DB::table('tra_applications_comments as t1')
                ->join('users as t2', 't1.created_by', '=', 't2.id')
                ->join('wf_workflow_stages as t3', 't1.workflow_stage_id', '=', 't3.id')
                ->select(DB::raw("t1.*, CONCAT_WS(' ',decrypt(t2.first_name),decrypt(t2.last_name)) as author, t3.name as stage_name"))
                ->where('t1.application_id', $application_id)
                ->where('t1.application_code', $application_code);
            if (isset($workflow_stage_id) && $workflow_stage_id != '') {
                $qry->where('t1.workflow_stage_id', $workflow_stage_id);
            }
            if (isset($comment_type_id) && $comment_type_id != '') {
                $qry->where('t1.comment_type_id', $comment_type_id);
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

    public function saveCommonData(Request $req)
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
            unset($post_data['unset_data']);
            $unsetData = $req->input('unset_data');
            if (isset($unsetData)) {
                $unsetData = explode(",", $unsetData);
                $post_data = unsetArrayData($post_data, $unsetData);
            }

            $table_data = $post_data;
            //add extra params
            $table_data['created_on'] = Carbon::now();
            $table_data['created_by'] = $user_id;
            $where = array(
                'id' => $id
            );
            $res = array();
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

    public function deleteCommonRecord(Request $req)
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

    public function getApplicationInvoiceDetails(Request $request)
    {
        $invoice_id = $request->input('invoice_id');
        try {
            $qry = Db::table('tra_invoice_details as t1')
                ->join('element_costs as t2', 't1.element_costs_id', 't2.id')
                ->join('cost_elements as t3', 't2.element_id', 't3.id')
                ->join('cost_sub_elements as t4', 't2.sub_element_id', 't4.id')
                ->join('par_currencies as t5', 't2.currency_id', 't5.id')
                ->join('par_cost_sub_categories as t6', 't2.sub_cat_id', 't6.id')
                ->join('par_cost_categories as t7', 't6.cost_category_id', 't7.id')
                ->select('t2.id', 't1.id as invoice_detail_id', 't1.exchange_rate', 't1.invoice_id', 't1.element_costs_id',
                    't1.element_amount as cost', 't5.id as currency_id', 't3.name as element', 't4.name as sub_element', 't5.name as currency',
                    't6.name as sub_category', 't7.name as category', 't1.quantity','t1.total_element_amount')
                ->where('t1.invoice_id', $invoice_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well!!'
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

    public function getElementCosts(Request $request)
    {
        $feeType = $request->input('fee_type');
        $costSubCat = $request->input('cost_subcategory');
        $where = array(
            't1.feetype_id' => $feeType,
            't1.sub_cat_id' => $costSubCat
        );
        try {
            $qry = DB::table('element_costs as t1')
                ->join('cost_elements as t2', 't1.element_id', 't2.id')
                ->join('cost_sub_elements as t3', 't1.sub_element_id', 't3.id')
                ->join('par_currencies as t4', 't1.currency_id', 't4.id')
                ->join('par_cost_sub_categories as t5', 't1.sub_cat_id', 't5.id')
                ->join('par_cost_categories as t6', 't5.cost_category_id', 't6.id')
                ->join('par_exchange_rates as t7', 't4.id', 't7.currency_id')
                ->select('t1.*', 't1.id as element_costs_id', 't4.id as currency_id', 't2.name as element', 't3.name as sub_element',
                    't4.name as currency', 't5.name as sub_category', 't6.name as category', 't7.exchange_rate')
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

    public function getApplicationPaymentDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $where = array(
            'application_id' => $application_id,
            'application_code' => $application_code
        );
        try {
            $qry = DB::table('tra_payments as t1')
                ->join('par_payment_modes as t2', 't1.payment_mode_id', '=', 't2.id')
                ->join('par_currencies as t3', 't1.currency_id', '=', 't3.id')
                ->join('par_receipt_types as t4', 't1.receipt_type_id', '=', 't4.id')
                ->select(DB::raw("t1.*,t2.name as payment_mode,t3.name as currency,t4.name as receipt_type,
                    IF(t1.receipt_type_id=1,t1.receipt_no,t1.manual_receipt_no) as receipt_no"))
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

}
