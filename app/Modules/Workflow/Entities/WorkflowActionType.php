<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 2/18/2019
 * Time: 2:10 PM
 */

namespace App\Modules\Workflow\Entities;


use Illuminate\Database\Eloquent\Model;

class WorkflowActionType extends Model
{
    protected $table='wf_workflowaction_types';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}