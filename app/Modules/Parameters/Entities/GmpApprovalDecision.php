<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 1/5/2019
 * Time: 5:46 PM
 */

namespace App\Modules\Parameters\Entities;


use Illuminate\Database\Eloquent\Model;

class GmpApprovalDecision extends  Model
{
    protected $table = 'par_gmpapproval_decisions';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}