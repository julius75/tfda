<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 1/6/2019
 * Time: 10:51 AM
 */

namespace App\Modules\GmpApplications\Entities;


use Illuminate\Database\Eloquent\Model;

class GmpType extends  Model
{
    protected $table = 'gmplocation_details';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}