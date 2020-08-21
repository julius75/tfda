<?php

namespace App\Modules\Administration\Entities;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    protected $table='par_groups';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
