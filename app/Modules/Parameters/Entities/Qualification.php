<?php

namespace App\Modules\Parameters\Entities;

use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    protected $table = 'par_personnel_qualifications';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
