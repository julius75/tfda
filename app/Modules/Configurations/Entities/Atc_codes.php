<?php

namespace App\Modules\Configurations\Entities;

use Illuminate\Database\Eloquent\Model;

class Atc_codes extends Model
{
    protected $table='par_atc_codes';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
