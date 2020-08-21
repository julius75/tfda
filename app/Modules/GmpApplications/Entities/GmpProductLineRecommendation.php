<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 1/5/2019
 * Time: 2:46 PM
 */

namespace App\Modules\GmpApplications\Entities;


use Illuminate\Database\Eloquent\Model;

class GmpProductLineRecommendation extends  Model
{
    protected $table = 'gmp_prodlinerecommenddesc';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}