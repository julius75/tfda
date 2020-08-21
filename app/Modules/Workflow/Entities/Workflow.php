<?php

namespace App\Modules\Workflow\Entities;

use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    protected $table='wf_workflows';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
