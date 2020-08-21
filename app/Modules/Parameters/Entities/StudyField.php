<?php

namespace App\Modules\Parameters\Entities;

use Illuminate\Database\Eloquent\Model;

class StudyField extends Model
{
    protected $table = 'par_personnel_studyfield';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
