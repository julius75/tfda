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

class PaymentInterval extends AbstractParameter
{
    protected $fillable = [
        "name",
        "duration",
        "unit",
        "fixed",
        "fixed_entry_point",
        "notification_time_interval",
        "notification_time_interval_unit",
        "created_by",
        "created_at",
        "is_enabled",
        "dola",
        "altered_by"
    ];

    protected $table = "par_payment_intervals";


    /**
     * Save records to the par_payment_intervals table
     * @param $request \http\Env\Request : the laravel request object
     * @param $id Integer : unique identifier of the exchange rate to be saved
     * @return array response indicating success or failure (boolean)
     *         and a text message
     */
    public static function savePaymentInterval($request, $id) {
        if($id != null) {
            $fee_type = DB::table('par_payment_intervals')-> where('id', $id) -> get();
            if($fee_type == null) {
                return [
                    "success" =>  false,
                    "message" => "A record with that id does not exist"
                ];
            }
            $previousData = convertStdClassObjToArray($fee_type);
            return updateRecord('par_payment_intervals', $previousData,[
                ['id', '=', $id]
            ],[
                'name' => $request->input('name'),
                'duration' => $request->input('duration'),
                'unit' => $request->input('unit'),
                'fixed' => $request->input('fixed'),
                'fixed_entry_point' => $request->input('fixed_entry_point'),
                'notification_time_interval' => $request->input('notification_time_interval') ? $request->input('notification_time_interval') : 0,
                'notification_time_interval_unit' => $request->input('notification_time_interval_unit'),
                'altered_by' => \Auth::user()->id,
                'dola' => Carbon::now()->format('Y-m-d H:i:s')
            ], \Auth::user()->id);
        } else {
            return insertRecord('par_payment_intervals', [
                'id' => $id,
                'name' => $request->input('name'),
                'duration' => $request->input('duration'),
                'unit' => $request->input('unit'),
                'fixed' => $request->input('fixed'),
                'fixed_entry_point' => $request->input('fixed_entry_point'),
                'notification_time_interval' => $request->input('notification_time_interval') ? $request->input('notification_time_interval') : 0,
                'notification_time_interval_unit' => $request->input('notification_time_interval_unit'),
                'altered_by' => \Auth::user()->id,
                'dola' => Carbon::now()->format('Y-m-d H:i:s'),
                'created_by' => \Auth::user()->id,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s')
            ], \Auth::user()->id);
        }
    }

    use GetDataTrait;

}