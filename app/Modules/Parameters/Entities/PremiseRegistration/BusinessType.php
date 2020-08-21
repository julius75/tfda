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

class BusinessType extends AbstractParameter
{
    protected  $fillable = [
        "name",
        "section_id",
        "description",
        "is_enabled",
        "created_by",
        "altered_by",
    ];

    protected $table = 'par_business_types';

    public function section() {
        return $this -> belongsTo("Section");
    }

    public function businessTypeDetails() {
        return $this -> hasMany("BusinessTypeDetail");
    }

    use GetDataTrait;
}