<?php

namespace App\Modules\Administration\Entities;

use Illuminate\Database\Eloquent\Model;

class ProcessesPermission extends Model
{
    protected $guarded=[];
    protected $table='tra_processes_permissions';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
