<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/11/18
 * Time: 12:20 PM
 */

namespace App\Modules\Parameters\Entities\ProductRegistration;

use App\Modules\Parameters\Entities\AbstractParameter;
use App\Modules\Parameters\Entities\GetDataTrait;

class AtcCommonName extends AbstractParameter
{
    protected $fillable = [
        "common_name_id",
        "atc_code_id"
    ];

    public function commonName() {
        return $this->belongsTo("CommonName");
    }

    public function atcCode() {
        return $this->belongsTo("AtcCode");
    }

    protected $table = "par_atc_code_common_name";

    use GetDataTrait;
}