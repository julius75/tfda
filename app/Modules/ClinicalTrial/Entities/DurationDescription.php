<?php

namespace App\Modules\ClinicalTrial\Entities;

use Illuminate\Database\Eloquent\Model;

class DurationDescription extends Model
{
    protected $table='clinical_trial_duration_desc';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
