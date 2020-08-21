<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/30/18
 * Time: 3:02 PM
 */

namespace App\Modules\Parameters\Entities;

class PortalParameter extends AbstractParameter
{
    protected $fillable = [
        "name",
        "description",
        "created_by",
        "created_on"
    ];

    protected $table = "par_portal_parameters";

    use GetDataTrait;
}