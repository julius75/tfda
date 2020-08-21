<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 9/19/18
 * Time: 2:29 PM
 */

namespace App\Modules\Parameters\Entities\Finance;

use App\Modules\Parameters\Entities\AbstractParameter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExchangeRate extends AbstractParameter
{
    protected $fillable = [
        "rate",
        "currency_id",
        "description",
        "created_by",
        "created_at",
        "is_enabled",
        "dola",
        "altered_by"
    ];

    protected $table = "par_exchange_rates";

    public function currency() {
        $this -> belongsTo("Currency");
    }

    /**
     * Save records to the par_exchange_rates table
     * @param $request \http\Env\Request : the laravel request object
     * @param $id Integer : unique identifier of the exchange rate to be saved
     * @return array response indicating success or failure (boolean)
     *         and a text message
     */
    public static function saveExchangeRate($request, $id) {
        if($id != null) {
            $exchange_rate = DB::table('par_exchange_rates')-> where('id', $id) -> get();
            if($exchange_rate == null) {
                return [
                    "success" =>  false,
                    "message" => "A record with that id does not exist"
                ];
            }
            $previousData = convertStdClassObjToArray($exchange_rate);
            return updateRecord('par_exchange_rates', $previousData,[
                ['id', '=', $id]
            ],[
                'rate' => $request->input('rate'),
                'description' => $request->input('description'),
                'currency_id' => $request->input('currency_id'),
                'altered_by' => \Auth::user()->id,
                'dola' => Carbon::now()->format('Y-m-d H:i:s')
            ], \Auth::user()->id);
        } else {
            return insertRecord('par_exchange_rates', [
                'id' => $id,
                'rate' => $request -> input('rate'),
                'description' => $request -> input('description'),
                'currency_id' => $request->input('currency_id'),
                'altered_by' => \Auth::user()->id,
                'dola' => Carbon::now()->format('Y-m-d H:i:s'),
                'created_by' => \Auth::user()->id,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s')
            ], \Auth::user()->id);
        }
    }

    public static function getData($start, $limit, $doRetrieveAll, $filter) {
        $join = [
            "table_fk" => "currencies",
            "table_col_alias" => "currency_name",
            "table_fk_col" => "currency_id"
        ];
        return parent::getFkOneJoin($start, $limit, $doRetrieveAll, __CLASS__, $join, $filter);
    }
}