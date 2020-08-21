<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 9/28/18
 * Time: 12:39 PM
 */

namespace App\Modules\Parameters\Entities\PremiseRegistration;

use App\Modules\Parameters\Entities\AbstractParameter;
use App\Modules\Parameters\Entities\GetDataTrait;

class StudyField extends AbstractParameter
{
    protected $table = 'par_personnel_studyfield';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';

    use GetDataTrait;
}