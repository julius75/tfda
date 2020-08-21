<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 9/26/18
 * Time: 9:42 AM
 */

namespace App\Modules\Parameters\Entities\PremiseRegistration;

use App\Modules\Parameters\Entities\AbstractParameter;
use App\Modules\Parameters\Entities\GetDataTrait;

class BusinessScale extends AbstractParameter
{
    protected  $fillable = [
        "name",
        "description",
        "is_enabled",
        "created_by",
        "altered_by",
        "dola"
    ];

    protected $table = 'par_business_scales';

    use GetDataTrait;
}