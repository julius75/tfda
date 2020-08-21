<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 8/2/2017
 * Time: 7:23 PM
 */

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class DbHelper
{
    public static function insertRecordNoTransaction($table_name, $table_data, $user_id,$con='mysql')
    {
        $record_id = DB::connection($con)->table($table_name)->insertGetId($table_data);
        $data = serialize($table_data);
        $audit_detail = array(
            'table_name' => $table_name,
            'table_action' => 'insert',
            'record_id' => $record_id,
            'current_tabledata' => $data,
            'ip_address' => self::getIPAddress(),
            'created_by' => $user_id,
            'created_at' => Carbon::now()
        );
        DB::table('tra_audit_trail')->insert($audit_detail);
        return $record_id;
    }

    public static function updateRecordNoTransaction($con, $table_name, $previous_data, $where_data, $current_data, $user_id)
    {
        try {

            DB::connection($con)->table($table_name)
                ->where($where_data)
                ->update($current_data);
            $record_id = $previous_data[0]['id'];
            $data_previous = serialize($previous_data);
            $data_current = serialize($current_data);
            $audit_detail = array(
                'table_name' => $table_name,
                'table_action' => 'update',
                'record_id' => $record_id,
                'prev_tabledata' => $data_previous,
                'current_tabledata' => $data_current,
                'ip_address' => self::getIPAddress(),
                'created_by' => $user_id,
                'created_at' => Carbon::now()
            );
            DB::table('tra_audit_trail')->insert($audit_detail);
            $res = array(
                'success' => true,
                'record_id'=>$record_id
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

    public static function deleteRecordNoTransaction($table_name, $previous_data, $where_data, $user_id)
    {
        $affectedRows = DB::table($table_name)->where($where_data)->delete();
        if ($affectedRows) {
            $record_id = $previous_data[0]['id'];
            $data_previous = serialize($previous_data);
            $audit_detail = array(
                'table_name' => $table_name,
                'table_action' => 'delete',
                'record_id' => $record_id,
                'prev_tabledata' => $data_previous,
                'ip_address' => self::getIPAddress(),
                'created_by' => $user_id,
                'created_at' => date('Y-m-d H:i:s')
            );
            DB::table('tra_audit_trail')->insert($audit_detail);
            return true;
        } else {
            return false;
        }
    }

    public static function softDeleteRecordNoTransaction($table_name, $previous_data, $where_data, $user_id)
    {
        $deletion_update = array(
            'is_enabled' => 0
        );
        $affectedRows = DB::table($table_name)->where($where_data)->update($deletion_update);
        if ($affectedRows > 0) {
            $current_data = $previous_data;
            $current_data[0]['is_enabled'] = 0;
            $record_id = $previous_data[0]['id'];
            $data_previous = serialize($previous_data);
            $data_current = serialize($current_data);
            $audit_detail = array(
                'table_name' => $table_name,
                'table_action' => 'softdelete',
                'record_id' => $record_id,
                'prev_tabledata' => $data_previous,
                'current_tabledata' => $data_current,
                'ip_address' => self::getIPAddress(),
                'created_by' => $user_id,
                'created_at' => Carbon::now()
            );
            DB::table('tra_audit_trail')->insert($audit_detail);
            return true;
        } else {
            return false;
        }
    }

    public static function undoSoftDeletesNoTransaction($table_name, $previous_data, $where_data, $user_id)
    {
        $deletion_update = array(
            'is_enabled' => 1
        );
        $affectedRows = DB::table($table_name)->where($where_data)->update($deletion_update);
        if ($affectedRows > 0) {
            $current_data = $previous_data;
            $current_data[0]['is_enabled'] = 1;
            $record_id = $previous_data[0]['id'];
            $data_previous = serialize($previous_data);
            $data_current = serialize($current_data);
            $audit_detail = array(
                'table_name' => $table_name,
                'table_action' => 'undosoftdelete',
                'record_id' => $record_id,
                'prev_tabledata' => $data_previous,
                'current_tabledata' => $data_current,
                'ip_address' => self::getIPAddress(),
                'created_by' => $user_id,
                'created_at' => Carbon::now()
            );
            DB::table('tra_audit_trail')->insert($audit_detail);
            return true;
        } else {
            return false;
        }
    }
    
    static function insertRecord($table_name, $table_data, $user_id,$con)
    {
        $res = array();
        try {
          
            DB::transaction(function () use ($con,$table_name, $table_data, $user_id, &$res) {
                $res = array(
                    'success' => true,
                    'record_id' => self::insertRecordNoTransaction($table_name, $table_data, $user_id,$con),
                    'message' => 'Data Saved Successfully!!'
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
        return $res;
    }

    static function insertRecordNoAudit($table_name, $table_data)
    {
        $res = array();
        try {
            DB::transaction(function () use ($table_name, $table_data, &$res) {
                DB::table($table_name)->insert($table_data);
                $res = array(
                    'success' => true,
                    'message' => 'Data Saved Successfully!!'
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
        return $res;
    }

    static function updateRecord($table_name, $previous_data, $where_data, $current_data, $user_id, $con)
    {
        $res = array();
        try {
            DB::transaction(function () use ($con, $table_name, $previous_data, $where_data, $current_data, $user_id, &$res) {
                $update = self::updateRecordNoTransaction($con, $table_name, $previous_data, $where_data, $current_data, $user_id);
                if ($update['success'] == true) {
                    $res = array(
                        'success' => true,
                        'record_id' => $update['record_id'],
                        'message' => 'Data updated Successfully!!'
                    );
                } else {
                    $res = $update;
                    /* $res = array(
                         'success' => false,
                         'message' => 'Zero number of rows affected. No record affected by the update request!!'
                     );*/
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

    static function deleteRecord($table_name, $previous_data, $where_data, $user_id)
    {
        $res = array();
        try {
            DB::transaction(function () use ($table_name, $previous_data, $where_data, $user_id, &$res) {
                if (self::deleteRecordNoTransaction($table_name, $previous_data, $where_data, $user_id)) {
                    $res = array(
                        'success' => true,
                        'message' => 'Delete request executed successfully!!'
                    );
                } else {
                    $res = array(
                        'success' => false,
                        'message' => 'Zero number of rows affected. No record affected by the delete request!!'
                    );
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

    static function softDeleteRecord($table_name, $previous_data, $where_data, $user_id)
    {
        $res = array();
        try {
            DB::transaction(function () use ($table_name, $previous_data, $where_data, $user_id, &$res) {
                if (self::softDeleteRecordNoTransaction($table_name, $previous_data, $where_data, $user_id)) {
                    $res = array(
                        'success' => true,
                        'message' => 'Delete request executed successfully!!'
                    );
                } else {
                    $res = array(
                        'success' => false,
                        'message' => 'Zero number of rows affected. No record affected by the delete request!!'
                    );
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

    static function undoSoftDeletes($table_name, $previous_data, $where_data, $user_id)
    {
        $res = array();

        try {
            DB::transaction(function () use ($table_name, $previous_data, $where_data, $user_id, &$res) {
                if (self::undoSoftDeletesNoTransaction($table_name, $previous_data, $where_data, $user_id)) {
                    $res = array(
                        'success' => true,
                        'message' => 'Delete request executed successfully!!'
                    );
                } else {
                    $res = array(
                        'success' => false,
                        'message' => 'Zero number of rows affected. No record affected by the delete request!!'
                    );
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

    static function deleteRecordNoAudit($table_name, $where_data)
    {
        $res = array();
        try {
            DB::transaction(function () use ($table_name, $where_data, &$res) {
                $affectedRows = DB::table($table_name)->where($where_data)->delete();
                if ($affectedRows) {
                    $res = array(
                        'success' => true,
                        'message' => 'Delete request executed successfully!!'
                    );
                } else {
                    $res = array(
                        'success' => false,
                        'message' => 'Zero number of rows affected. No record affected by the delete request!!'
                    );
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

    static function recordExists($table_name, $where,$con)
    {
        $recordExist = DB::connection($con)->table($table_name)->where($where)->get();
        if ($recordExist && count($recordExist) > 0) {
            return true;
        }
        return false;
    }

    static function getPreviousRecords($table_name, $where,$con)
    {
        try {
            $prev_records = DB::connection($con)->table($table_name)->where($where)->get();
            if ($prev_records && count($prev_records) > 0) {
                $prev_records = self::convertStdClassObjToArray($prev_records);
            }
            $res = array(
                'success' => true,
                'results' => $prev_records,
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
        return $res;
    }

    static function auditTrail($table_name, $table_action, $prev_tabledata, $table_data, $user_id)
    {
        $ip_address = self::getIPAddress();
        switch ($table_action) {
            case "insert":
                //get serialised data $row_array = $sql_query->result_array();
                $data = $table_data;
                $audit_detail = array(
                    'table_name' => $table_name,
                    'table_action' => $table_action,
                    'current_tabledata' => $data,
                    'ip_address' => $ip_address,
                    'created_by' => $user_id,
                    'created_at' => date('Y-m-d H:i:s')
                );
                DB::table('tra_audit_trail')->insert($audit_detail);
                $res = true;
                break;
            case "update":
                //get serialised data $row_array = $sql_query->result_array();
                $data_previous = serialize($prev_tabledata);
                $data_current = serialize($table_data);
                $audit_detail = array(
                    'table_name' => $table_name,
                    'table_action' => 'update',
                    'prev_tabledata' => $data_previous,
                    'current_tabledata' => $data_current,
                    'ip_address' => $ip_address,
                    'created_by' => $user_id,
                    'created_at' => date('Y-m-d H:i:s')
                );
                DB::table('tra_audit_trail')->insert($audit_detail);
                $res = true;
                break;
            case "delete":
                //get serialised data $row_array = $sql_query->result_array();
                $data_previous = serialize($prev_tabledata);
                $audit_detail = array(
                    'table_name' => $table_name,
                    'table_action' => 'delete',
                    'prev_tabledata' => $data_previous,
                    'ip_address' => $ip_address,
                    'created_by' => $user_id,
                    'created_at' => date('Y-m-d H:i:s')
                );
                DB::table('tra_audit_trail')->insert($audit_detail);
                $res = true;
                break;
            default:
                $res = false;
        }
        return $res;
    }

    static function getRecordValFromWhere($table_name, $where, $col)
    {
        try {
            $record = DB::table($table_name)
                ->select($col)
                ->where($where)->get();
            return self::convertStdClassObjToArray($record);
        } catch (QueryException $exception) {
            echo $exception->getMessage();
            return false;
        }
    }

    //without auditing
    static function insertReturnID($table_name, $table_data)
    {
        $insert_id = '';
        DB::transaction(function () use ($table_name, $table_data, &$insert_id) {
            try {
                $insert_id = DB::table($table_name)->insertGetId($table_data);
            } catch (QueryException $exception) {
                echo $exception->getMessage();
                $insert_id = '';
            }
        }, 5);
        return $insert_id;
    }

    static function convertStdClassObjToArray($stdObj)
    {
        return json_decode(json_encode($stdObj), true);
    }

    static function convertAssArrayToSimpleArray($assArray, $targetField)
    {
        $simpleArray = array();
        foreach ($assArray as $key => $array) {
            $simpleArray[] = $array[$targetField];
        }
        return $simpleArray;
    }

    static function getIPAddress()
    {

        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                if (strpos($ip, ",")) {
                    $exp_ip = explode(",", $ip);
                    $ip = $exp_ip[0];
                }
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $ip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
                if (strpos($ip, ",")) {
                    $exp_ip = explode(",", $ip);
                    $ip = $exp_ip[0];
                }
            } else if (getenv('HTTP_CLIENT_IP')) {
                $ip = getenv('HTTP_CLIENT_IP');
            } else {
                $ip = getenv('REMOTE_ADDR');
            }
        }
        return $ip;
    }

    static function getUserGroups($user_id)
    {
        $groups = DB::table('tra_user_group')->where('user_id', $user_id)->get();
        $groupsSimpleArray = self::convertStdClassObjToArray($groups);
        $groupsSimpleArray = self::convertAssArrayToSimpleArray($groupsSimpleArray, 'id');
        return $groupsSimpleArray;
    }

    static function getSuperUserGroupIds()
    {
        $super_groups_obj = DB::table('par_groups')
            ->where('is_super_group', 1)
            ->get();
        $super_groups_ass = self::convertStdClassObjToArray($super_groups_obj);
        $super_groups_simp = self::convertAssArrayToSimpleArray($super_groups_ass, 'id');
        return $super_groups_simp;
    }

    static function belongsToSuperGroup($user_groups)
    {
        $superUserIDs = self::getSuperUserGroupIds();
        $arr_intersect = array_intersect($superUserIDs, $user_groups);
        if (count($arr_intersect) > 0) {
            return true;
        } else {
            return false;
        }
    }

    static function getAssignedProcessStages($user_id, $module_id)
    {
        //get process stages
        $qry1 = DB::table('wf_tfdaprocesses as t1')
            ->join('wf_workflow_stages as t2', 't1.workflow_id', '=', 't2.workflow_id')
            ->select('t2.id as stage_id')
            ->where('t1.module_id', $module_id);
        $possible_stages = $qry1->get();

        $possible_stages = convertStdClassObjToArray($possible_stages);
        $possible_stages = self::convertAssArrayToSimpleArray($possible_stages, 'stage_id');

        $groups = self::getUserGroups($user_id);
        $qry2 = DB::table('wf_stages_groups')
            ->select('stage_id')
            ->whereIn('group_id', $groups);
        $all_assigned_stages = $qry2->get();
        $all_assigned_stages = convertStdClassObjToArray($all_assigned_stages);
        $all_assigned_stages = self::convertAssArrayToSimpleArray($all_assigned_stages, 'stage_id');

        return array_intersect($possible_stages, $all_assigned_stages);
    }

    static function getAssignedProcesses($user_id)
    {
        $user_groups = self::getUserGroups($user_id);
        $isSuperUser = self::belongsToSuperGroup($user_groups);
        if ($isSuperUser === true) {
            //return array();
        }
        //get keys
        $qry = DB::table('tra_processes_permissions as t1')
            ->join('par_menuitems_processes as t2', 't1.process_id', '=', 't2.id')
            ->select(DB::raw('t2.identifier as process_identifier,MAX(t1.accesslevel_id) as accessibility'))
            ->whereIn('t1.group_id', $user_groups)
            ->groupBy('t2.identifier');
        $results = $qry->get();
        $results = self::convertStdClassObjToArray($results);
        $keys = self::convertAssArrayToSimpleArray($results, 'process_identifier');
        //get values
        $qry = DB::table('tra_processes_permissions as t1')
            ->join('par_menuitems_processes as t2', 't1.process_id', '=', 't2.id')
            ->select(DB::raw('t2.identifier as process_identifier,MAX(t1.accesslevel_id) as accessibility'))
            ->whereIn('t1.group_id', $user_groups)
            ->groupBy('t2.identifier');
        $results = $qry->get();
        $results = self::convertStdClassObjToArray($results);
        $values = self::convertAssArrayToSimpleArray($results, 'accessibility');
        $combined = array_combine($keys, $values);
        return $combined;
    }

    static function getSingleRecord($table, $where)
    {
        $record = DB::table($table)->where($where)->first();
        return $record;
    }

    static function getSingleRecordColValue($table, $where, $col)
    {
        $val = DB::table($table)->where($where)->value($col);
        return $val;
    }

    static function getTableData($table_name, $where)
    {
        $qry = DB::table($table_name)
            ->where($where);
        $results = $qry->first();
        return $results;
    }

    static function updateRenewalPermitDetails($primary_id, $current_permit_id, $table_name)
    {
        DB::table($table_name)
            ->where('id', $primary_id)
            ->update(array('permit_id' => $current_permit_id));
    }

    static function updatePortalApplicationStatus($mis_application_id, $portal_status_id, $mis_table_name, $portal_table_name)
    {//application_id=mis application_id
        $portal_db = DB::connection('portal_db');
        try {
            $portal_db->beginTransaction();
            $portal_id = DB::table($mis_table_name)
                ->where('id', $mis_application_id)
                ->value('portal_id');

            $portal_db->table($portal_table_name)
                ->where('id', $portal_id)
                ->update(array('application_status_id' => $portal_status_id));
            $portal_db->commit();
            $res = array(
                'success' => true,
                'message' => 'Portal status updated successfully!!'
            );
        } catch (\Exception $exception) {
            $portal_db->rollBack();
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $portal_db->rollBack();
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return $res;
    }
    static function updatePortalApplicationStatusWithCode($application_code, $portal_table_name,$portal_status_id)
    {//application_id=mis application_id
        $portal_db = DB::connection('portal_db');
        try {
            $portal_db->beginTransaction();
            
            $portal_db->table($portal_table_name)
                ->where('application_code', $application_code)
                ->update(array('application_status_id' => $portal_status_id));
            $portal_db->commit();
            $res = array(
                'success' => true,
                'message' => 'Portal status updated successfully!!'
            );
        } catch (\Exception $exception) {
            $portal_db->rollBack();
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $portal_db->rollBack();
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return $res;
    }
    static function updatePortalParams($portal_table_name, $portal_params, $where)
    {
        $portal_db = DB::connection('portal_db');
        try {
            $portal_db->beginTransaction();
            $portal_db->table($portal_table_name)
                ->where($where)
                ->update($portal_params);
            $portal_db->commit();
            $res = array(
                'success' => true,
                'message' => 'Portal status updated successfully!!'
            );
        } catch (\Exception $exception) {
            $portal_db->rollBack();
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $portal_db->rollBack();
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return $res;
    }

    public static function getParameterItem($table_name, $record_id, $con)
    {
        $record_name = '';
        $rec = DB::connection($con)->table($table_name)->where(array('id' => $record_id))->value('name');
        if ($rec) {
            $record_name = $rec;
        }
        return $record_name;

    }
    
    static function unsetPrimaryIDsInArray($array)
    {
        foreach ($array as $key => $item) {
            unset($item['id']);
            $array[$key] = $item;
        }
        return $array;
    }

    static function createInitialRegistrationRecord($reg_table, $application_table, $reg_params, $application_id, $reg_column)
    {
        $reg_id = DB::table($reg_table)
            ->insertGetId($reg_params);
        DB::table($application_table)
            ->where('id', $application_id)
            ->update(array($reg_column => $reg_id));
    }

    static function getExchangeRate($currency_id)
    {
        $exchange_rate = DB::table('par_exchange_rates')
            ->where('currency_id', $currency_id)
            ->value('exchange_rate');
        return $exchange_rate;
    }

    static function generatePaymentRefDistribution($invoice_id, $receipt_id, $amount_paid, $paying_currency, $user_id)
    {
        $shared_qry = DB::table('tra_invoice_details as t1')
            ->where('t1.invoice_id', $invoice_id);

        $qry1 = clone $shared_qry;
        $qry1->select(DB::raw("SUM(exchange_rate*total_element_amount) as invoice_amount"));
        $results1 = $qry1->first();
        $invoice_amount = $results1->invoice_amount;

        $qry2 = clone $shared_qry;
        $qry2->select(DB::raw("(exchange_rate*total_element_amount) as element_cost,element_costs_id"));
        $elements = $qry2->get();

        $exchange_rate = self::getExchangeRate($paying_currency);
        $params = array();

        foreach ($elements as $element) {
            $params[] = array(
                'invoice_id' => $invoice_id,
                'receipt_id' => $receipt_id,
                'paid_on' => Carbon::now(),
                'exchange_rate' => $exchange_rate,
                'element_costs_id' => $element->element_costs_id,
                'currency_id' => $paying_currency,
                'amount_paid' => round((($element->element_cost / $invoice_amount) * $amount_paid), 2),
                'created_by' => $user_id
            );
        }
        DB::table('payments_references')->insert($params);
    }
    public static function getParameterItems($table_name,$filter,$con){
        $record_name = '';
         $rec = DB::connection($con)
                    ->table($table_name);

        if($filter != ''){
            $rec = $rec->where($filter);
        }
        $rec = $rec->get();
       
         return convertStdClassObjToArray($rec);
    }
}