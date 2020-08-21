<?php

namespace App\Modules\Administration\Entities;

use Illuminate\Database\Eloquent\Model;

class AccessLevel extends Model
{
    protected $table='par_accesslevels';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
