<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 8/29/2018
 * Time: 11:05 AM
 */

namespace App\Modules\OrganisationConfig\Entities;

use Illuminate\Database\Eloquent\Model;
class Zone extends Model
{
    protected $guarded=[];
    protected $table='par_zones';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
