<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/11/18
 * Time: 3:52 PM
 */

namespace App\Modules\Parameters\Entities\ProductRegistration;

use App\Modules\Parameters\Entities\AbstractParameter;

class StorageCondition extends AbstractParameter
{
    protected $fillable = [
        "name",
        "description",
        "is_enabled",
        "created_by",
        "created_at",
        "altered_by",
        "dola"
    ];

    public function products() {
        return $this->hasMany("ProductInformation");
    }

    protected $table = "par_storage_conditions";
}