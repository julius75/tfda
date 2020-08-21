<?php

namespace App\Modules\ClinicalTrial\Entities;

use Illuminate\Database\Eloquent\Model;

class InvestigatorCategory extends Model
{
    protected $table='clinical_investigator_cat';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
