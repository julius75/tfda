<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 1/26/2019
 * Time: 8:57 PM
 */

namespace App\Modules\Parameters\Entities;


use Illuminate\Database\Eloquent\Model;

class ProductRegApprovals extends  Model
{
    protected $table = 'par_productreg_decisions';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}