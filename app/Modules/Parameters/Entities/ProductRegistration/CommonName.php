<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/11/18
 * Time: 12:12 PM
 */

namespace App\Modules\Parameters\Entities\ProductRegistration;

use App\Modules\Parameters\Entities\AbstractParameter;
use App\Modules\Parameters\Entities\GetDataTrait;

class CommonName extends AbstractParameter
{
    protected $fillable = [
        "name",
        "description",
        "section_id",
        "created_by",
        "created_at",
        "is_enabled",
        "dola",
        "altered_by"
    ];

    public function atcCommonNames() {
        return $this->hasMany("AtcCommonName");
    }

    protected $table = "par_common_names";

    use GetDataTrait;
}