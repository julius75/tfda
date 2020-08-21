<?php

namespace App\Modules\Administration\Entities;

use Illuminate\Database\Eloquent\Model;

class ProcessAccessLevel extends Model
{
    protected $guarded=[];
    protected $table='par_processes_accesslevels';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
