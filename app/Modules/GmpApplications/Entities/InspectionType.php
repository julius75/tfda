<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 1/6/2019
 * Time: 10:51 AM
 */

namespace App\Modules\GmpApplications\Entities;


use Illuminate\Database\Eloquent\Model;

class InspectionType extends  Model
{
    protected $table = 'inspection_types';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}