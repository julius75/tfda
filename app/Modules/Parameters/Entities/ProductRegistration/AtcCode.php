<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/11/18
 * Time: 12:07 PM
 */

namespace App\Modules\Parameters\Entities\ProductRegistration;

use App\Modules\Parameters\Entities\AbstractParameter;
use App\Modules\Parameters\Entities\GetDataTrait;

class AtcCode extends AbstractParameter
{
    protected $fillable = [
        "atc_code",
        "description",
        "is_enabled",
        "created_by",
        "altered_by",
        "dola"
    ];

    public function atcCommonNames() {
        return $this->hasMany("AtcCommonName");
    }

    protected $table = "par_atc_codes";

    use GetDataTrait;
}