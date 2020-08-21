<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 1/27/2019
 * Time: 1:09 PM
 */

namespace App\Modules\Parameters\Entities;


use Illuminate\Database\Eloquent\Model;

class TcRecommendationDecision extends Model
{
    protected $table = 'par_tcmeeting_decisions';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}