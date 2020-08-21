<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 8/22/18
 * Time: 4:09 PM
 */

namespace App\Modules\Parameters\Entities\Locations;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Modules\Parameters\Entities\AbstractParameter;

class Region extends AbstractParameter
{
    protected $fillable = [
        "name",
        "description",
        "is_enabled",
        "created_by",
        "altered_by",
        "dola",
        "country_id"
    ];

    protected $table = 'par_regions';

    public function country() {
        return $this -> belongsTo("Country");
    }

    public function districts() {
        return $this -> hasMany("District");
    }

    public static function getData($start, $limit, $doRetrieveAll, $filter) {
        $join = [
            "table_fk" => "countries",
            "table_col_alias" => "country_name",
            "table_fk_col" => "country_id"
        ];
        return parent::getFkOneJoin($start, $limit, $doRetrieveAll, __CLASS__, $join, $filter);
    }
}