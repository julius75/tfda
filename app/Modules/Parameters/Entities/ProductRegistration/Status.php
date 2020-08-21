<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/11/18
 * Time: 2:52 PM
 */

namespace App\Modules\Parameters\Entities\ProductRegistration;

use App\Modules\Parameters\Entities\AbstractParameter;
use App\Modules\Parameters\Entities\GetDataTrait;

class Status extends AbstractParameter
{
    protected $fillable = [
        "name",
        "description",
        "code",
        "created_by",
        "created_at",
        "dola",
        "altered_by",
        "is_enabled"
    ];

    protected $table = "par_statuses";

    public function productApplication() {
        return $this->hasMany("ProductApplication");
    }

    use GetDataTrait;
}