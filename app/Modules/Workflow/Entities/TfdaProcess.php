<?php

namespace App\Modules\Workflow\Entities;

use Illuminate\Database\Eloquent\Model;

class TfdaProcess extends Model
{
    protected $table='wf_tfdaprocesses';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
