<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 8/15/2018
 * Time: 5:29 PM
 */

namespace App\Modules\UserManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    protected $table = 'par_gender';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}