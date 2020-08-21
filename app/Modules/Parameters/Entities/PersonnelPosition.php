<?php

namespace App\Modules\Parameters\Entities;

use Illuminate\Database\Eloquent\Model;

class PersonnelPosition extends Model
{
    protected $table = 'par_personnel_positions';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
