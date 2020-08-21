<?php

namespace App\Modules\Administration\Entities;

use Illuminate\Database\Eloquent\Model;

class ComponentPermission extends Model
{
    protected $table='par_menuitems_processes';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
