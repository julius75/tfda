<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 9/27/18
 * Time: 11:41 AM
 */

namespace App\Modules\Parameters\Entities\PremiseRegistration;

use App\Modules\Parameters\Entities\AbstractParameter;
use App\Modules\Parameters\Entities\GetDataTrait;

class Section extends AbstractParameter
{
    protected  $fillable = [
        "name",
        "code",
        "description",
        "is_enabled",
        "created_by",
        "updated_by",
        "altered_by",
        "deleted_by",
        "deleted_at"
    ];

    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';

    protected $table = 'par_sections';

    public function businessTypes() {
        return $this->hasMany("BusinessType");
    }

    use GetDataTrait;
}