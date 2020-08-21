<?php

namespace App\Modules\Administration\Entities;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $guarded=[];
    protected $table='tra_permissions';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
