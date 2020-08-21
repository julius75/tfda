<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 9/19/18
 * Time: 2:29 PM
 */

namespace App\Modules\Parameters\Entities\Finance;

use App\Modules\Parameters\Entities\AbstractParameter;
use App\Modules\Parameters\Entities\GetDataTrait;

class Currency extends AbstractParameter
{
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

    protected $table = "par_currencies";

    public function exchangeRates() {
        $this -> hasMany("ExchangeRate");
    }

    use GetDataTrait;
}