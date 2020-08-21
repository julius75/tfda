<?php

namespace App\Modules\Workflow\Entities;

use Illuminate\Database\Eloquent\Model;

class SubModule extends Model
{
    protected $table='sub_modules';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
