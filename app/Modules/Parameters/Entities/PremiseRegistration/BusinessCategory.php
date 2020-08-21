<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 9/27/18
 * Time: 2:28 PM
 */

namespace App\Modules\Parameters\Entities\PremiseRegistration;


use App\Modules\Parameters\Entities\AbstractParameter;
use App\Modules\Parameters\Entities\GetDataTrait;

class BusinessCategory extends AbstractParameter
{
    protected  $fillable = [
        "name",
        "description",
        "is_enabled",
        "created_by",
        "altered_by",
        "dola"
    ];

    protected $table = 'par_business_categories';

    use GetDataTrait;

}