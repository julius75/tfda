<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 8/22/18
 * Time: 4:08 PM
 */

namespace App\Modules\Parameters\Entities\Locations;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Modules\Parameters\Entities\AbstractParameter;
use App\Modules\Parameters\Entities\GetDataTrait;

class Country extends AbstractParameter
{
    protected  $fillable = [
        "name",
        "description",
        "is_enabled",
        "created_by",
        "altered_by",
        "dola"
    ];

    protected $table = 'par_countries';

    public function regions() {
        return $this -> hasMany("Region");
    }


    use GetDataTrait;
}