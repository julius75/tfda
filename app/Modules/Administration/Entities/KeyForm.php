<?php

namespace App\Modules\Administration\Entities;

use Illuminate\Database\Eloquent\Model;

class KeyForm extends Model
{
    protected $guarded=[];
    protected $table='par_key_forms';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
