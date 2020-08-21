<?php

namespace App\Modules\Dashboard\Entities;

use Illuminate\Database\Eloquent\Model;

class SystemGuideline extends Model
{
    protected $table='par_system_guidelines';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
