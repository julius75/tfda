<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 9/21/18
 * Time: 9:36 AM
 */

namespace App\Modules\Parameters\Entities\Finance;

use App\Modules\Parameters\Entities\AbstractParameter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Modules\Parameters\Entities\GetDataTrait;

class TransactionType extends AbstractParameter
{
    protected $fillable = [
        "t_code",
        "name",
        "description",
        "t_type",
        "output",
        "system_invoice",
        "system_receipt",
        "created_by",
        "created_at",
        "is_enabled",
        "dola",
        "altered_by"
    ];

    protected $table = "par_transaction_types";


    /**
     * Save records to the par_transaction_types table
     * @param $request \http\Env\Request : the laravel request object
     * @param $id Integer : unique identifier of the exchange rate to be saved
     * @return array response indicating success or failure (boolean)
     *         and a text message
     */
    public static function saveTransactionType($request, $id) {
        if($id != null) {
            $fee_type = DB::table('par_transaction_types')-> where('id', $id) -> get();
            if($fee_type == null) {
                return [
                    "success" =>  false,
                    "message" => "A record with that id does not exist"
                ];
            }
            $previousData = convertStdClassObjToArray($fee_type);
            return updateRecord('par_transaction_types', $previousData,[
                ['id', '=', $id]
            ],[
                't_code' => $request->input('t_code'),
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                't_type' => $request->input('t_type'),
                'output' => $request->input('output'),
                'system_invoice' => $request->input('system_invoice') ? $request->input('system_invoice') : false,
                'system_receipt' => $request->input('system_receipt') ? $request->input('system_receipt') : false,
                'altered_by' => \Auth::user()->id,
                'dola' => Carbon::now()->format('Y-m-d H:i:s')
            ], \Auth::user()->id);
        } else {
            return insertRecord('par_transaction_types', [
                'id' => $id,
                't_code' => $request->input('t_code'),
                'name' => $request -> input('name'),
                'description' => $request->input('description'),
                't_type' => $request->input('t_type'),
                'output' => $request->input('output'),
                'system_invoice' => $request->input('system_invoice') ? $request->input('system_invoice') : false,
                'system_receipt' => $request->input('system_receipt') ? $request->input('system_receipt') : false,
                'altered_by' => \Auth::user()->id,
                'dola' => Carbon::now()->format('Y-m-d H:i:s'),
                'created_by' => \Auth::user()->id,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s')
            ], \Auth::user()->id);
        }
    }

    use GetDataTrait;

}