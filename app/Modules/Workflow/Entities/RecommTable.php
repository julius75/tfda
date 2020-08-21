<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 2/16/2019
 * Time: 12:46 AM
 */

namespace App\Modules\Workflow\Entities;


use Illuminate\Database\Eloquent\Model;

class RecommTable extends Model
{
    protected $table='par_recommendation_tables';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}