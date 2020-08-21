<?php

namespace App\Modules\OrganisationConfig\Entities;

use Illuminate\Database\Eloquent\Model;

class Directorate extends Model
{
    protected $guarded=[];
    protected $table='par_directorates';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
