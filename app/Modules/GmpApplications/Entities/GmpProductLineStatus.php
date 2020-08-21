<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 1/5/2019
 * Time: 2:45 PM
 */

namespace App\Modules\GmpApplications\Entities;


use Illuminate\Database\Eloquent\Model;

class GmpProductLineStatus extends Model
{
    protected $table = 'gmp_productlinestatus';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}