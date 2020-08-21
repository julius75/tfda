<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/11/18
 * Time: 2:47 PM
 */

namespace App\Modules\Parameters\Entities\ProductRegistration;

use App\Modules\Parameters\Entities\AbstractParameter;
use App\Modules\Parameters\Entities\GetDataTrait;

class AssessmentProcedure extends AbstractParameter
{
    protected $fillable = [
        "name",
        "code",
        "description",
        "created_by",
        "created_at",
        "dola",
        "altered_by",
        "is_enabled"
    ];

    protected $table = "assessment_procedures";

    public function productApplications() {
        return $this->hasMany("ProductApplication");
    }

    use GetDataTrait;
}