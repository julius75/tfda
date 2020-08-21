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

class FeeType extends AbstractParameter
{
    protected $fillable = [
        "name",
        "gl_code",
        "created_by",
        "created_at",
        "is_enabled",
        "dola",
        "altered_by"
    ];

    protected $table = "par_fee_types";


    /**
     * Save records to the par_fee_types table
     * @param $request \http\Env\Request : the laravel request object
     * @param $id Integer : unique identifier of the exchange rate to be saved
     * @return array response indicating success or failure (boolean)
     *         and a text message
     */
    public static function saveFeeType($request, $id) {
        if($id != null) {
            $fee_type = DB::table('par_fee_types')-> where('id', $id) -> get();
            if($fee_type == null) {
                return [
                    "success" =>  false,
                    "message" => "A record with that id does not exist"
                ];
            }
            $previousData = convertStdClassObjToArray($fee_type);
            return updateRecord('par_fee_types', $previousData,[
                ['id', '=', $id]
            ],[
                'name' => $request->input('name'),
                'gl_code' => $request->input('gl_code'),
                'altered_by' => \Auth::user()->id,
                'dola' => Carbon::now()->format('Y-m-d H:i:s')
            ], \Auth::user()->id);
        } else {
            return insertRecord('par_fee_types', [
                'id' => $id,
                'name' => $request -> input('name'),
                'gl_code' => $request -> input('gl_code'),
                'altered_by' => \Auth::user()->id,
                'dola' => Carbon::now()->format('Y-m-d H:i:s'),
                'created_by' => \Auth::user()->id,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s')
            ], \Auth::user()->id);
        }
    }

    use GetDataTrait;

}