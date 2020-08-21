<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/2/18
 * Time: 2:34 PM
 */

namespace App\Modules\Parameters\Entities\Organizations;

use App\Modules\Parameters\Entities\AbstractParameter;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Directorate extends AbstractParameter
{
    protected $fillable = [
        "name",
        "description",
        "email",
        "telephone",
        "physicaL_address",
        "postal_address",
        "created_by",
        "created_at",
        "altered_by",
        "dola",
        "is_enabled"
    ];

    protected $table = "par_directorates";

    /**
     * Save records to the par_directorates table
     * @param $request \http\Env\Request : the laravel request object
     * @param $id Integer : unique identifier of the location to be saved
     * @return array response indicating success or failure (boolean)
     *         and a text message
     */
    public static function saveDirectorate($request, $id) {
        if($id != null) {
            $directorate = DB::table('par_directorates')-> where('id', $id) -> get();
            if($directorate == null) {
                return [
                    "success" =>  false,
                    "message" => "A record with that id does not exist"
                ];
            }
            $previousData = convertStdClassObjToArray($directorate);
            return updateRecord('par_directorates', $previousData,[
                ['id', '=', $id]
            ],[
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'email' => $request->input('email'),
                'telephone' => $request->input('telephone'),
                'physical_address' => $request->input('physical_address'),
                'postal_address' => $request->input('postal_address'),
                'altered_by' => \Auth::user()->id,
                'dola' => Carbon::now()->format('Y-m-d H:i:s')
            ], \Auth::user()->id);
        } else {
            return insertRecord('par_directorates', [
                'id' => $id,
                'name' => $request -> input('name'),
                'description' => $request -> input('description'),
                'email' => $request->input('email'),
                'telephone' => $request->input('telephone'),
                'physical_address' => $request->input('physical_address'),
                'postal_address' => $request->input('postal_address'),
                'created_by' => \Auth::user()->id,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s')
            ], \Auth::user()->id);
        }
    }
}