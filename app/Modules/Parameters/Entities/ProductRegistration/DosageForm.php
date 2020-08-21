<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/11/18
 * Time: 12:34 PM
 */

namespace App\Modules\Parameters\Entities\ProductRegistration;

use App\Modules\Parameters\Entities\AbstractParameter;
use App\Modules\Parameters\Entities\GetDataTrait;

class DosageForm extends AbstractParameter
{
    protected $fillable = [
        "name",
        "description",
        "created_by",
        "created_at",
        "is_enabled",
        "dola",
        "altered_by"
    ];

    protected $table = "par_dosage_forms";

    use GetDataTrait;
}