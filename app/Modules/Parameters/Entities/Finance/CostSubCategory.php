<?php
/**
 * Created by Softclans.
 * User: robinson
 * Date: 9/12/18
 * Time: 12:29 PM
 */

namespace App\Modules\Parameters\Entities\Finance;


use App\Modules\Parameters\Entities\AbstractParameter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Parameters\Entities\GetDataTrait;


class CostSubCategory extends AbstractParameter {
    protected $fillable = [
        "name",
        "code",
        "description",
        "created_by",
        "created_at",
        "is_enabled",
        "dola",
        "altered_by"
    ];

    protected $table = 'par_cost_sub_categories';

    /**
     * Save records to the par_cost_categories table
     * @param $request \http\Env\Request : the laravel request object
     * @param $id Integer : unique identifier of the location to be saved
     * @return array response indicating success or failure (boolean)
     *         and a text message
     */
    public static function saveCostSubCategory($request, $id) {
        
        if($id != null) {
            $district = DB::table('par_cost_sub_categories')-> where('id', $id) -> get();
            if($district == null) {
                return [
                    "success" =>  false,
                    "message" => "A record with that id does not exist"
                ];
            }
            $previousData = convertStdClassObjToArray($district);
            return updateRecord('par_cost_sub_categories', $previousData,[
                ['id', '=', $id]
            ],[
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'cost_category_id' => $request->input('cost_category_id'),
                'altered_by' => \Auth::user()->id,
                'dola' => Carbon::now()->format('Y-m-d H:i:s')
            ], \Auth::user()->id);
        } else {
            return insertRecord('par_cost_sub_categories', [
                'id' => $id,
                'name' => $request -> input('name'),
                'description' => $request -> input('description'),
                'cost_category_id' => $request->input('cost_category_id'),
                'altered_by' => \Auth::user()->id,
                'dola' => Carbon::now()->format('Y-m-d H:i:s'),
                'created_by' => \Auth::user()->id,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s')
            ], \Auth::user()->id);
        }
    }

    public static function getData($start, $limit, $doRetrieveAll, $filters) {
        $rawSql = "par_cost_sub_categories.*, par_cost_categories.name as cost_category";
        $db = DB::table("par_cost_sub_categories")
            ->join("par_cost_categories", "par_cost_sub_categories.cost_category_id", '=', "par_cost_categories.id")
            //->join("par_cost_centers", "par_cost_categories.cost_center_id", '=', "par_cost_centers.id")
            ->select(DB::raw($rawSql));
            
        return self::getFk($start, $limit, $doRetrieveAll, __CLASS__, $db, $filters);
    }
}