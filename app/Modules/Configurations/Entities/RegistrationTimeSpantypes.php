<?php

namespace App\Modules\Configurations\Entities;

use Illuminate\Database\Eloquent\Model;

class RegistrationTimeSpantypes extends Model
{
    protected $table='registration_timespan_types';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
