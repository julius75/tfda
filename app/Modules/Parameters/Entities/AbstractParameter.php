<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 8/23/18
 * Time: 2:52 PM
 */

namespace App\Modules\Parameters\Entities;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery\Exception;

abstract  class AbstractParameter extends Model
{
    public $timestamps = false;

    public static function deleteData($table,$id, $action) {
        $result = null;
        $parameterData = DB::table($table)-> where('id', $id) -> get();
        if($parameterData == null) {
            $result = [
                "success" =>  false,
                "message" => "A record with that id does not exist"
            ];
        }

        $previousData = convertStdClassObjToArray($parameterData);
        if($action === 'actual') {
            $result = deleteRecord($table, $previousData,[
                ['id', '=', $id]
            ],Auth::user()->id);
        } else if($action === 'soft') {
            $result = softDeleteRecord($table, $previousData,[
                ['id', '=', $id]
            ],Auth::user()->id);
        } else {
            $result = undoSoftDeletes($table, $previousData,[
                ['id', '=', $id]
            ],Auth::user()->id);
        }

        return $result;
    }

    /**
     * Save records to the par_countries table
     * @param $request \http\Env\Request : the laravel request object
     * @param $id Integer : unique identifier of the location to be saved
     * @param $table string : name of the location table to save the
     *         record to.
     * @return array response indicating success or failure (boolean)
     *         and a text message
     */
    public static function saveData($request, $table, $id, $foreignKey = null) {
        $result = null;

        if($id != null) {
            $previousData = DB::table($table)-> where('id', $id) -> get();
            if($previousData == null) {
                $result =  [
                    "success" =>  false,
                    "message" => "A record with that id does not exist"
                ];
            }
            $previousData = convertStdClassObjToArray($previousData);
            if(!$foreignKey) {
                $result =  updateRecord($table, $previousData,[
                    ['id', '=', $id]
                ],[
                    'name' => $request -> input('name'),
                    'description' => $request -> input('description'),
                    'altered_by' => \Auth::user()->id,
                    'dola' => Carbon::now()->format('Y-m-d H:i:s')
                ], \Auth::user()->id);
            } else {
                $result =  updateRecord($table, $previousData,[
                    ['id', '=', $id]
                ],[
                    'name' => $request -> input('name'),
                    'description' => $request -> input('description'),
                    $foreignKey =>$request->input($foreignKey),
                    'altered_by' => \Auth::user()->id,
                    'dola' => Carbon::now()->format('Y-m-d H:i:s')
                ], \Auth::user()->id);
            }

        } else {
            if(!$foreignKey) {
                $result =  insertRecord($table, [
                    'id' => $id,
                    'name' => $request -> input('name'),
                    'description' => $request -> input('description'),
                    'altered_by' => \Auth::user()->id,
                    'dola' => Carbon::now()->format('Y-m-d H:i:s'),
                    'created_by' => \Auth::user()->id,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s')
                ], \Auth::user()->id);
            } else {
                $result =  insertRecord($table, [
                    'id' => $id,
                    'name' => $request -> input('name'),
                    'description' => $request -> input('description'),
                    $foreignKey =>$request->input($foreignKey),
                    'altered_by' => \Auth::user()->id,
                    'dola' => Carbon::now()->format('Y-m-d H:i:s'),
                    'created_by' => \Auth::user()->id,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s')
                ], \Auth::user()->id);
            }

        }

        return $result;
    }

    protected static function get($start, $limit, $doRetrieveAll, $model, $filters = null) {
        if($doRetrieveAll) {
            $filters["is_enabled"] = [1];
        }

        $table = new $model;
        $db = DB::table($table->getTable())->latest();
        if($filters) {//edited by Kip
            $db->where($filters);
           /* foreach($filters as $key => $filter) {
                $db = $db->whereIn($key, $filter);
            }*/
        }
        $records = $db->get();
        $count = $records->count();
        /*if(isset($start) && isset($limit)) {
            $records = $records->slice($start) -> take($limit);
        }*/

        $result = [
            "records" => $records,
            "total" => $count
        ];

        return $result;
    }

    protected static function getFk($start, $limit, $doRetrieveAll, $model, $db, $filters = null) {
        $table = (new $model)->getTable();
        if($doRetrieveAll) {
            $filters["$table.is_enabled"] = [1];
        }

        if($filters) {//Edited by Kip
            $db=$db->where($filters);
           /* foreach($filters as $key => $filter) {
                $db = $db->whereIn($table.".".$key, $filter);
            }*/
        }
        $records = $db->get();
        $count = $records->count();
        if(isset($start) && isset($limit)) {
            $records = $records->slice($start) -> take($limit);
        }

        $result = [
            "records" => $records,
            "total" => $count
        ];

        return $result;
    }

    protected static function getFkOneJoin($start, $limit, $doRetrieveAll, $model, $join, $filters = null) {
        $table = (new $model)->getTable();
        $table_fk = $join['table_fk'];
        $table_col_alias = $join['table_col_alias'];
        $table_fk_col = $join['table_fk_col'];
        $rawSql = "$table.*, par_$table_fk.name  as $table_col_alias";
        $db = DB::table($table)
            ->join("par_".$table_fk, $table.".".$table_fk_col, '=', "par_".$table_fk.".id")
            ->select(DB::raw($rawSql));

        return self::getFk($start, $limit, $doRetrieveAll, $model, $db, $filters);
    }

    protected static function merge($mergeToId, $foreignKeyCol, $tableName, $idList) {
        try {
            DB::transaction(function () use($mergeToId, $foreignKeyCol, $tableName, $idList) {
                $tables = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
                foreach($tables as $table) {
                    $cols = Schema::getColumnListing($table);
                    $hasForeignKey = false;
                    foreach($cols as $col) {
                        if($col === $foreignKeyCol) {
                            $hasForeignKey = true;
                            break;
                        }
                    }

                    if($hasForeignKey) {
                        $records = DB::table($table)
                            ->whereIn($foreignKeyCol, $idList)
                            ->get();
                        foreach($records as $record) {
                            $previousData = convertStdClassObjToArray([$record]);
                            updateRecordNoTransaction($table, $previousData,
                                [
                                    $foreignKeyCol => $record->$foreignKeyCol
                                ],
                                [
                                    $foreignKeyCol => $mergeToId,
                                    'altered_by' => \Auth::user()->id,
                                    'dola' => Carbon::now()->format('Y-m-d H:i:s')
                                ],
                                Auth::user()->id
                            );
                        }
                    }
                }
                $records = DB::table($tableName)
                        ->whereIn('id', $idList)
                        ->get();
                foreach($records as $record) {
                    $previousData = convertStdClassObjToArray([$record]);
                    softDeleteRecordNoTransaction($tableName, $previousData,[
                        ['id', '=', $record->id]
                    ],Auth::user()->id);
                }
                $previousData = DB::table($tableName)
                    ->where('id', '=', $mergeToId)
                    ->get();
                $previousData = convertStdClassObjToArray($previousData);
                undoSoftDeletesNoTransaction($tableName,$previousData,
                    [['id', '=', $mergeToId]], Auth::user()->id);
            });
        } catch (\Exception $exception) {
            return array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            return array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }

        return array(
            'success' => true,
            'message' => 'Data merged Successfully!!'
        );
    }
}