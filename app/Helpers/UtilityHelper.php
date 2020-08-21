<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 7/26/2017
 * Time: 1:46 PM
 */

namespace App\Helpers;

use Carbon\Carbon;
use PHPJasper as JasperPHP;
use PDF;
use Illuminate\Support\Facades\DB;
use App\Modules\Workflow\Entities\SerialTracker;

class UtilityHelper
{

    static function getTimeDiffHrs($time1, $time2)
    {
        $t1 = StrToTime($time1);
        $t2 = StrToTime($time2);
        $diff = $t1 - $t2;
        $hours = $diff / (60 * 60);
        return $hours;
    }

    static function is_connected()
    {
        $connected = @fsockopen("www.google.com", 80);
        //website, port  (try 80 or 443)
        if ($connected) {
            $is_conn = true; //action when connected
            // fclose($connected);
        } else {
            $is_conn = false; //action in connection failure
        }
        return $is_conn;

    }

    static function formatMoney($money)
    {
        if ($money == '' || $money == 0) {
            $money = '00';
        }
        return is_numeric($money) ? number_format((round($money)), 2, '.', ',') : round($money);
    }

    static function converter1($date)
    {
        $date = str_replace('/', '-', $date);
        $dateConverted = date('Y-m-d H:i:s', strtotime($date));
        return $dateConverted;
    }

    static function converter2($date)
    {
        $date = date_create($date);
        $dateConverted = date_format($date, "d/m/Y H:i:s");
        return $dateConverted;
    }

    static function converter11($date)
    {
        $date = str_replace('/', '-', $date);
        $dateConverted = date('Y-m-d', strtotime($date));
        return $dateConverted;
    }

    static function converter22($date)
    {
        $date = date_create($date);
        $dateConverted = date_format($date, "d/m/Y");
        return $dateConverted;
    }

    static function json_output($data = array(), $content_type = 'json')
    {

        if ($content_type == 'html') {
            header('Content-Type: text/html; charset=utf-8');
        } else {
            header('Content-type: text/plain');
        }

        $data = utf8ize($data);
        echo json_encode($data);

    }

    static function utf8ize($d)
    {
        if (is_array($d))
            foreach ($d as $k => $v)
                $d[$k] = utf8ize($v);

        else if (is_object($d))
            foreach ($d as $k => $v)
                $d->$k = utf8ize($v);

        else
            return utf8_encode($d);

        return $d;
    }

    static function formatDate($date)
    {
        if ($date == '0000-00-00 00:00:00' || $date == '0000-00-00' || strstr($date, '1970-00') != false || strstr($date, '1970') != false) {
            return '';
        } else {
            return ($date == '' or $date == null) ? '0000-00-00' : date('Y-m-d', strtotime($date));
        }
    }

    static function formatDaterpt($date)
    {
        if ($date == '0000-00-00 00:00:00' || $date == '0000-00-00' || strstr($date, '1970-00') != false || strstr($date, '1970') != false) {
            return '';
        } else {
            return ($date == '' or $date == null) ? '' : date('d-m-Y', strtotime($date));
        }
    }

    static function returnUniqueArray($arr, $key)
    {
        $uniquekeys = array();
        $output = array();
        foreach ($arr as $item) {
            if (!in_array($item[$key], $uniquekeys)) {
                $uniquekeys[] = $item[$key];
                $output[] = $item;
            }
        }
        return $output;
    }

    static function getApplicationInitialStatus($module_id, $sub_module_id)
    {
        $statusDetails = (object)array(
            'status_id' => '',
            'name' => ''
        );
        $where = array(
            'module_id' => $module_id,
            'sub_module_id' => $sub_module_id
        );
        $results = DB::table('par_application_statuses as t1')
            ->join('par_system_statuses as t2', 't1.status_id', '=', 't2.id')
            ->select('t1.status_id', 't2.name')
            ->where($where)
            ->where('t1.status', 1)
            ->first();
        if (!is_null($results)) {
            $statusDetails = $results;
        }
        return $statusDetails;
    }

    static function generateApplicationCode($sub_module_id, $table_name)
    {
        $last_id = 01;
        $max_details = DB::table($table_name)
            ->select(DB::raw("MAX(id) as last_id"))
            ->first();
        if (!is_null($max_details)) {
            $last_id = $max_details->last_id + 1;
        }
        $application_code = $sub_module_id . $last_id;
        return $application_code;
    }

    static function generatePremiseRefNumber($ref_id, $codes_array, $year, $process_id, $zone_id, $user_id)
    {
        $where = array(
            'year' => $year,
            'process_id' => $process_id,
            'zone_id' => $zone_id
        );
        $serial_num_tracker = new SerialTracker();
        $serial_track = $serial_num_tracker->where($where)->first();
        if ($serial_track == '' || is_null($serial_track)) {
            $current_serial_id = 1;
            $serial_num_tracker->year = $year;
            $serial_num_tracker->process_id = $process_id;
            $serial_num_tracker->zone_id = $zone_id;
            $serial_num_tracker->created_by = $user_id;
            $serial_num_tracker->last_serial_no = $current_serial_id;
            $serial_num_tracker->save();
        } else {
            $last_serial_id = $serial_track->last_serial_no;
            $current_serial_id = $last_serial_id + 1;
            $update_data = array(
                'last_serial_no' => $current_serial_id,
                'altered_by' => $user_id
            );
            $serial_num_tracker->where($where)->update($update_data);
        }
        $serial_no = str_pad($current_serial_id, 4, 0, STR_PAD_LEFT);
        $reg_year = substr($year, -2);
        $codes_array['serial_no'] = $serial_no;
        $codes_array['reg_year'] = $reg_year;
        $ref_number = self::generateRefNumber($codes_array, $ref_id);
        return $ref_number;
    }

    static function generateProductsRefNumber($ref_id, $codes_array, $year, $process_id, $zone_id, $user_id)
    {
        $where = array(
            'year' => $year,
            'process_id' => $process_id,
            'zone_id' => $zone_id
        );
        $serial_num_tracker = new SerialTracker();
        $serial_track = $serial_num_tracker->where($where)->first();
        if ($serial_track == '' || is_null($serial_track)) {
            $current_serial_id = 1;
            $serial_num_tracker->year = $year;
            $serial_num_tracker->process_id = $process_id;
            $serial_num_tracker->zone_id = $zone_id;
            $serial_num_tracker->created_by = $user_id;
            $serial_num_tracker->last_serial_no = $current_serial_id;
            $serial_num_tracker->save();
        } else {
            $last_serial_id = $serial_track->last_serial_no;
            $current_serial_id = $last_serial_id + 1;
            $update_data = array(
                'last_serial_no' => $current_serial_id,
                'altered_by' => $user_id
            );
            $serial_num_tracker->where($where)->update($update_data);
        }
        $serial_no = str_pad($current_serial_id, 4, 0, STR_PAD_LEFT);
        $reg_year = substr($year, -2);
        $codes_array['serial_no'] = $serial_no;
        $codes_array['reg_year'] = $reg_year;
        $ref_number = self::generateRefNumber($codes_array, $ref_id);
        return $ref_number;
    }

    static function generateProductsSubRefNumber($reg_product_id, $table_name, $ref_id, $codes_array, $sub_module_id, $user_id)
    {

        $app_counter = DB::table($table_name)
            ->where(array('reg_product_id' => $reg_product_id, 'sub_module_id' => $sub_module_id))
            ->count();
        $serial_no = $app_counter + 1;

        $codes_array['serial_no'] = $serial_no;
        $ref_number = self::generateRefNumber($codes_array, $ref_id);
        return $ref_number;
    }

    static function generateRefNumber($codes_array, $ref_id)
    {
        $serial_format = DB::table('refnumbers_formats')
            ->where('id', $ref_id)
            ->value('ref_format');
        $arr = explode("|", $serial_format);
        $serial_variables = $serial_format = DB::table('refnumbers_variables')
            ->select('identifier')
            ->get();
        $serial_variables = convertStdClassObjToArray($serial_variables);
        $serial_variables = convertAssArrayToSimpleArray($serial_variables, 'identifier');
        $ref = '';
        foreach ($arr as $code) {
            if (in_array($code, $serial_variables)) {
                isset($codes_array[$code]) ? $code = $codes_array[$code] : $code;
            }
            $ref = $ref . $code;
        }
        return $ref;
    }

    static function unsetArrayData($postData, $unsetData)
    {
        foreach ($unsetData as $unsetDatum) {
            unset($postData[$unsetDatum]);
        }
        return $postData;
    }

    static function formatBytes($size, $precision)
    {
        if ($size > 0) {
            $size = (int)$size;
            $base = log($size) / log(1024);
            $suffixes = array(' bytes', ' KB', ' MB', ' GB', ' TB');
            return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
        } else {
            return $size;
        }
    }

    static function generateInvoiceNo($user_id)
    {
        $registration_year = date("Y");
        $qry = DB::table('invoice_serials');
        $qry1 = $qry->where('registration_year', $registration_year);
        $last_serial = $qry->value('last_serial');
        if (is_numeric($last_serial) && $last_serial != '') {
            $serial_no = $last_serial + 1;
            $update_params = array(
                'last_serial' => $serial_no,
                'dola' => Carbon::now(),
                'altered_by' => $user_id
            );
            $qry1->update($update_params);
        } else {
            $serial_no = 1;
            $insert_params = array(
                'registration_year' => $registration_year,
                'last_serial' => $serial_no,
                'created_on' => Carbon::now(),
                'created_by' => $user_id
            );
            $qry->insert($insert_params);
        }
        $serial_no = $serial_no = str_pad($serial_no, 4, 0, STR_PAD_LEFT);
        $invoice_no = $registration_year . $serial_no;
        return $invoice_no;
    }

    static function generateReceiptNo($user_id)
    {
        $registration_year = date("Y");
        $qry = DB::table('receipt_serials');
        $qry1 = $qry->where('registration_year', $registration_year);
        $last_serial = $qry->value('last_serial');
        if (is_numeric($last_serial) && $last_serial != '') {
            $serial_no = $last_serial + 1;
            $update_params = array(
                'last_serial' => $serial_no,
                'dola' => Carbon::now(),
                'altered_by' => $user_id
            );
            $qry1->update($update_params);
        } else {
            $serial_no = 1;
            $insert_params = array(
                'registration_year' => $registration_year,
                'last_serial' => $serial_no,
                'created_on' => Carbon::now(),
                'created_by' => $user_id
            );
            $qry->insert($insert_params);
        }
        $serial_no = str_pad($serial_no, 4, 0, STR_PAD_LEFT);
        $receipt_no = $registration_year . $serial_no;
        return $receipt_no;
    }

    static function getApplicationPaymentsRunningBalance($application_id, $application_code, $invoice_id)
    {
        //get invoiced amount
        $qry1 = DB::table('tra_invoice_details as t1')
            ->select(DB::raw("SUM((t1.total_element_amount*t1.exchange_rate)) as invoiced_amount"))
            ->where('t1.invoice_id', $invoice_id)
            ->groupBy('t1.invoice_id');
        $results1 = $qry1->first();
        $invoiced_amount = 0;
        if (!is_null($results1)) {
            $invoiced_amount = $results1->invoiced_amount;
        }
        //get total payments
        $qry2 = DB::table('tra_payments as t2')
            ->select(DB::raw("SUM((t2.amount_paid*t2.exchange_rate)) as paid_amount"))
            ->where('t2.application_id', $application_id)
            ->where('t2.application_code', $application_code)
            ->groupBy('t2.application_id', 't2.application_code');
        $results2 = $qry2->first();
        $paid_amount = 0;
        if (!is_null($results2)) {
            $paid_amount = $results2->paid_amount;
        }
        $running_balance = $paid_amount - $invoiced_amount;
        $details = array(
            'invoice_amount' => $invoiced_amount,
            'running_balance' => $running_balance
        );
        return $details;
    }

    static function getPermitSignatory($process_id, $workflow_stage_id)
    {
        $qry = DB::table('par_default_signatories')
            ->where('process_id', $process_id);
        if (isset($workflow_stage_id) && $workflow_stage_id != '') {
            $qry->where('workflow_stage_id', $workflow_stage_id);
        }
        $signatory = $qry->value('user_id');
        return $signatory;
    }

    static function generateProductRegistrationNo($zone_id, $section_id, $classification_id, $product_type_id, $device_type_id, $table_name, $user_id, $ref_id)
    {
        //redefine the reference serials to accomodate
        $zone_code = getSingleRecordColValue('par_zones', array('id' => $zone_id), 'zone_code');
        $section_code = getSingleRecordColValue('par_sections', array('id' => $section_id), 'code');
        $class_code = getSingleRecordColValue('par_classifications', array('id' => $classification_id), 'code');
        $prodtype_code = getSingleRecordColValue('par_product_types', array('id' => $product_type_id), 'code');
        $prodtype_code = $section_code . $prodtype_code;
        $registration_year = date('Y');
        $reg_year = $registration_year;
        if ($ref_id == 10 || $ref_id == 13) {
            $reg_year = substr($registration_year, -2);
        }
        $where = array(
            'section_id' => $section_id,
            'registration_year' => $registration_year,
            'zone_id' => $zone_id,
            'table_name' => $table_name
        );
        $qry = DB::table('product_registration_serials');
        $qry_where = $qry->where($where);
        $details = $qry->first();
        if (!is_null($details)) {
            $last_serial = $details->last_serial;
            $serial_no = $last_serial + 1;
            $update_params = array(
                'last_serial' => $serial_no,
                'altered_by' => $user_id
            );
            $qry_where->update($update_params);
        } else {
            $last_serial = 0;
            $serial_no = $last_serial + 1;
            $insert_params = array(
                'section_id' => $section_id,
                'table_name' => $table_name,
                'last_serial' => $serial_no,
                'classification_id' => $classification_id,
                'product_type_id' => $product_type_id,
                'device_type_id' => $device_type_id,
                'registration_year' => $registration_year,
                'created_by' => $user_id
            );
            $qry->insert($insert_params);
        }

        $serial_no = str_pad($serial_no, 4, 0, STR_PAD_LEFT);

        $codes_array = array(
            'zone_code' => $zone_code,
            'reg_year' => $reg_year,
            'section_code' => $section_code,
            'prodtype_code' => $prodtype_code,
            'class_code' => $class_code,
            'serial_no' => $serial_no
        );

        $permit_no = self::generateRefNumber($codes_array, $ref_id);
        return $permit_no;
    }

    static function generatePremisePermitNo($zone_id, $section_id, $table_name, $user_id, $ref_id)
    {
        $zone_code = getSingleRecordColValue('par_zones', array('id' => $zone_id), 'zone_code');
        $section_code = getSingleRecordColValue('par_sections', array('id' => $section_id), 'code');
        $registration_year = date('Y');
        $reg_year = $registration_year;
        if ($ref_id == 10 || $ref_id == 13) {
            $reg_year = substr($registration_year, -2);
        }
        $where = array(
            'section_id' => $section_id,
            'registration_year' => $registration_year,
            'zone_id' => $zone_id,
            'table_name' => $table_name
        );
        $qry = DB::table('premise_permit_serials');
        $qry_where = $qry->where($where);
        $details = $qry->first();
        if (!is_null($details)) {
            $last_serial = $details->last_serial;
            $serial_no = $last_serial + 1;
            $update_params = array(
                'last_serial' => $serial_no,
                'altered_by' => $user_id
            );
            $qry_where->update($update_params);
        } else {
            $last_serial = 0;
            $serial_no = $last_serial + 1;
            $insert_params = array(
                'section_id' => $section_id,
                'table_name' => $table_name,
                'last_serial' => $serial_no,
                'zone_id' => $zone_id,
                'registration_year' => $registration_year,
                'created_by' => $user_id
            );
            $qry->insert($insert_params);
        }
        $serial_no = str_pad($serial_no, 4, 0, STR_PAD_LEFT);
        $codes_array = array(
            'zone_code' => $zone_code,
            'reg_year' => $reg_year,
            'section_code' => $section_code,
            'serial_no' => $serial_no
        );
        $permit_no = self::generateRefNumber($codes_array, $ref_id);
        return $permit_no;
    }

    static function updateInTraySubmissions($application_id, $application_code, $from_stage, $user_id)
    {
        try {
            $update = array(
                'isRead' => 1,
                'isDone' => 1,
                'isComplete' => 1
            );
            DB::table('tra_submissions')
                ->where('application_id', $application_id)
                ->where('application_code', $application_code)
                ->where('current_stage', $from_stage)
                ->where('usr_to', $user_id)
                ->update($update);
            $res = array(
                'success' => true,
                'message' => 'Update successful!!'
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

    static function updateInTraySubmissionsBatch($application_ids, $application_codes, $from_stage, $user_id)
    {
        try {
            $update = array(
                'isRead' => 1,
                'isDone' => 1,
                'isComplete' => 1
            );
            DB::table('tra_submissions')
                ->whereIn('application_id', $application_ids)
                ->whereIn('application_code', $application_codes)
                ->where('current_stage', $from_stage)
                ->where('usr_to', $user_id)
                ->update($update);
            $res = array(
                'success' => true,
                'message' => 'Update successful!!'
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

    static function updateInTrayReading($application_id, $application_code, $current_stage, $user_id)
    {
        try {
            DB::table('tra_submissions')
                ->where('application_id', $application_id)
                ->where('application_code', $application_code)
                ->where('current_stage', $current_stage)
                ->where('usr_to', $user_id)
                ->update(array('isRead' => 1));
            $res = array(
                'success' => true,
                'message' => 'Update successful!!'
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

    static function getApplicationTransitionStatus($prev_stage, $action, $next_stage, $static_status)
    {
        if (isset($static_status) && $static_status != '') {
            return $static_status;
        }
        $where = array(
            'stage_id' => $prev_stage,
            'action_id' => $action,
            'nextstage_id' => $next_stage
        );
        $status = DB::table('wf_workflow_transitions')
            ->where($where)
            ->value('application_status_id');
        return $status;
    }

    static function updateApplicationQueryRef($application_id, $application_code, $ref_no, $table_name, $user_id)
    {
        $where = array(
            'application_id' => $application_id,
            'application_code' => $application_code
        );
        $counter = DB::table('tra_application_query_reftracker')
            ->where($where)
            ->count();
        if ($counter > 0) {
            $serial_no = $counter + 1;
        } else {
            $serial_no = 1;
        }
        $codes_array = array(
            'ref_no' => $ref_no,
            'serial_no' => $serial_no
        );
        $queryRefNo = self::generateRefNumber($codes_array, 6);
        $insert_params = array(
            'application_id' => $application_id,
            'application_code' => $application_code,
            'query_ref' => $queryRefNo,
            'table_name' => $table_name,
            'created_on' => Carbon::now(),
            'created_by' => $user_id
        );
        DB::table('tra_application_query_reftracker')
            ->insert($insert_params);
    }

    static function inValidateApplicationChecklist($module_id, $sub_module_id, $section_id, $checklist_category, $application_codes)
    {
        $where = array(
            'module_id' => $module_id,
            'sub_module_id' => $sub_module_id,
            'section_id' => $section_id,
            'checklist_category_id' => $checklist_category
        );
        DB::table('checklistitems_responses as t1')
            ->whereIn('application_code', $application_codes)
            ->whereIn('checklist_item_id', function ($query) use ($where) {
                $query->select(DB::raw('t2.id'))
                    ->from('par_checklist_items as t2')
                    ->whereIn('t2.checklist_type_id', function ($query) use ($where) {
                        $query->select(DB::raw('t3.id'))
                            ->from('par_checklist_types as t3')
                            ->where($where);
                    });
            })
            ->update(array('status' => 0));
    }

    static function uploadFile($req, $params, $table_name, $folder, $user_id)
    {
        try {
            $res = array();
            if ($req->hasFile('uploaded_doc')) {
                $file = $req->file('uploaded_doc');
                $origFileName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileSize = $file->getClientSize();
                //$folder = '\resources\uploads';
                $destination = getcwd() . $folder;
                $savedName = str_random(5) . time() . '.' . $extension;
                $file->move($destination, $savedName);
                $params['initial_filename'] = $origFileName;
                $params['savedname'] = $savedName;
                $params['filesize'] = formatBytes($fileSize);
                $params['filetype'] = $extension;
                $params['server_filepath'] = $destination;
                $params['server_folder'] = $folder;
                $params['created_on'] = Carbon::now();
                $params['created_by'] = $user_id;
                $res = insertRecord($table_name, $params, $user_id);
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
        return $res;
    }

    static function checkForOngoingApplications($registered_id, $table_name, $reg_column, $process_id)
    {
        $qry = DB::table($table_name . ' as t1')
            ->join('wf_workflow_stages as t2', 't1.workflow_stage_id', '=', 't2.id')
            ->where('t1.process_id', $process_id)
            ->where('t1.' . $reg_column, $registered_id)
            ->whereIn('t2.stage_status', array(1, 2));

        $app_details = $qry->first();
        if (is_null($app_details)) {
            $res = array(
                'exists' => false,
                'ref_no' => ''
            );
        } else {
            $res = array(
                'exists' => true,
                'ref_no' => $app_details->reference_no
            );
        }

        return $res;
    }

    static function checkForProductOngoingApplications($reg_product_id, $table_name, $reg_column, $process_id)
    {
        $qry = DB::table($table_name . ' as t1')
            ->join('wf_workflow_stages as t2', 't1.workflow_stage_id', '=', 't2.id')
            ->where('t1.process_id', $process_id)
            ->where('t1.' . $reg_column, $reg_product_id)
            ->whereIn('t2.stage_status', array(1, 2));

        $app_details = $qry->first();

        //must have an approval
        if (is_null($app_details)) {
            $res = array(
                'exists' => false,
                'ref_no' => ''
            );
        } else {
            $res = array(
                'exists' => true,
                'ref_no' => $app_details->reference_no
            );
        }

        return $res;
    }

    static function getPortalApplicationsTable($module_id)
    {
        $portal_app_tables = array(
            'products' => '',
            'premises' => 'wb_premises_applications',
            'gmp' => 'wb_gmp_applications',
            'clinical_trial' => 'wb_clinical_trial_applications'
        );
        $module_name = '';
        if ($module_id == 1) {
            $module_name = 'products';
        } else if ($module_id == 2) {
            $module_name = 'premises';
        } else if ($module_id == 3) {
            $module_name = 'gmp';
        } else if ($module_id == 7) {
            $module_name = 'clinical_trial';
        }
        return $portal_app_tables[$module_name];
    }

    static function validateIsNumeric($value)
    {
        if (is_numeric($value) && $value > 0) {
            return true;
        } else {
            return false;
        }

    }

    static function getPermitExpiryDate($approval_date, $duration, $duration_mode)
    {
        $approval_date = Carbon::parse($approval_date);
        $expiry_date = Carbon::now();
        if ($duration_mode == 1) {//month
            $expiry_date = $approval_date->addMonths($duration);
        } else if ($duration_mode == 2) {//year
            $expiry_date = $approval_date->addYears($duration);
        }
        return $expiry_date;
    }

    static function getApplicationExpiryDate($approval_date, $sub_module_id, $module_id, $section_id)
    {
        $expiry_date = '';
        $table_name = 'par_registration_expirytime_span as t1';
        $data = DB::table($table_name)
            ->leftJoin('modules as t2', 't1.module_id', '=', 't2.id')
            ->leftJoin('sub_modules as t3', 't1.sub_module_id', '=', 't3.id')
            ->leftJoin('par_sections as t4', 't1.section_id', '=', 't2.id')
            ->leftJoin('par_timespan_defination as t5', 't1.timespan_defination_id', '=', 't5.id')
            ->select('time_span', 't5.name as timespan_defination')
            ->where(array('sub_module_id' => $sub_module_id, 't1.module_id' => $module_id, 'section_id' => $section_id))
            ->first();
        if ($data) {
            $time_span = $data->time_span;
            $timespan_defination = $data->timespan_defination;
            $expiry_date = date('Y-m-d', strtotime($approval_date . " + $time_span  $timespan_defination"));
            $expiry_date = date('Y-m-d', strtotime($expiry_date . ' - 1 days'));

        }
        return $expiry_date;
    }

    static function saveApplicationRegistrationDetails($table_name, $registration_data, $where_statement, $user_id)
    {
        if (recordExists($table_name, $where_statement)) {
            //update
            $prev_data = getPreviousRecords($table_name, $where_statement);

            $res = updateRecord($table_name, $prev_data['results'], $where_statement, $registration_data, $user_id);

        } else {
            //insert
            $res = insertRecord($table_name, $registration_data, $user_id);

        }
        return $res;
    }

    static function getProductPrimaryReferenceNo($where_statement, $applications_table)
    {
        $sub_module_id = 7; //primary sub-module
        $primary_ref = DB::table($applications_table . ' as t1')
            ->join('tra_product_information as t2', 't1.product_id', '=', 't2.id')
            ->where($where_statement)
            ->value('reference_no');

        return $primary_ref;
    }

    static function getPreviousProductRegistrationDetails($where_statement, $applications_table)
    {
        $sub_module_id = 7; //primary sub-module //tra_registered_products
        $data = DB::table($applications_table . ' as t1')
            ->join('tra_product_information as t2', 't1.tra_product_id', '=', 't2.id')
            ->join('tra_product_applications as t3', 't2.id', '=', 't3.product_id')
            ->join('tra_approval_recommendations as t4', function ($join) {
                $join->on("t3.application_code", "=", "t4.application_code")
                    ->on("t3.id", "=", "t4.application_id");
            })
            ->where($where_statement)
            ->select('t4.*', 't1.status_id', 't1.validity_status_id', 't1.registration_status_id', 't1.prev_product_id as regprev_product_id', 't2.id as prev_product_id')
            ->first();

        return $data;
    }

    static function returnMessage($results)
    {
        return count(convertStdClassObjToArray($results)) . ' records fetched!!';
    }

    static function returnParamFromArray($dataArray, $dataValue)
    {
        $dataPrint = array_filter($dataArray, function ($var) use ($dataValue) {
            return ($var['id'] == $dataValue);
        });
        $data = array();
        foreach ($dataPrint as $rec) {
            $data = array('name' => $rec['name'],
                'id' => $rec['id']
            );
        }
        if (!empty($data)) {
            return $data['name'];
        } else {
            return '';
        }

    }

    //save details
    static function funcSaveOnlineProductOtherdetails($portal_product_id, $product_id, $user_id)
    {
        $portal_db = DB::connection('portal_db');
        $previous_prodingredients = $portal_db->table('wb_product_ingredients as t2')
            ->select(DB::raw("$product_id as product_id, ingredient_type_id,ingredient_id,specification_type_id,strength,proportion,ingredientssi_unit_id,inclusion_reason_id,acceptance_id, $user_id as created_by, now() as created_on"))
            ->where('product_id', $portal_product_id)
            ->get();
        $previous_prodingredients = convertStdClassObjToArray($previous_prodingredients);
        DB::table('tra_product_ingredients')
            ->insert($previous_prodingredients);
        //packaging
        $previous_prodpackaging = $portal_db->table('wb_product_packaging as t2')
            ->select(DB::raw("$product_id as product_id, container_type_id,container_id,container_material_id,closure_material_id,seal_type_id,retail_packaging_size,packaging_units_id,unit_pack,product_unit, $user_id as created_by, now() as created_on"))
            ->where('product_id', $portal_product_id)
            ->get();
        $previous_prodpackaging = convertStdClassObjToArray($previous_prodpackaging);
        DB::table('tra_product_packaging')
            ->insert($previous_prodpackaging);
        //nutrients
        $previous_prodnutrients = $portal_db->table('wb_product_nutrients as t2')
            ->select(DB::raw("$product_id as product_id, nutrients_category_id,nutrients_id,units_id,proportion,$user_id as created_by, now() as created_on"))
            ->where('product_id', $portal_product_id)
            ->get();
        $previous_prodnutrients = convertStdClassObjToArray($previous_prodnutrients);
        DB::table('tra_product_nutrients')
            ->insert($previous_prodnutrients);
        //nutrients
        $previous_prodmanufacturers = $portal_db->table('wb_product_manufacturers as t2')
            ->select(DB::raw("$product_id as product_id, manufacturer_id,manufacturer_role_id,manufacturer_status_id,manufacturer_type_id,active_ingredient_id,$user_id as created_by, now() as created_on"))
            ->where('product_id', $portal_product_id)
            ->get();
        $previous_prodmanufacturers = convertStdClassObjToArray($previous_prodmanufacturers);

        DB::table('tra_product_manufacturers')
            ->insert($previous_prodmanufacturers);


        $previous_prodgmpinspection = $portal_db->table('wb_product_gmpinspectiondetails as t2')
            ->select(DB::raw("$product_id as product_id, reg_manufacturer_site_id,$user_id as created_by, now() as created_on"))
            ->where('product_id', $portal_product_id)
            ->get();
        $previous_prodgmpinspection = convertStdClassObjToArray($previous_prodgmpinspection);
        DB::table('tra_product_gmpinspectiondetails')
            ->insert($previous_prodgmpinspection);
    }

    static function generateApplicationViewID()
    {
        $view_id = 'tfda' . str_random(10) . date('s');
        return $view_id;
    }

}