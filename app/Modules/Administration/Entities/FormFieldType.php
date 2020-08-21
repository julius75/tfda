<?php

namespace App\Modules\Administration\Entities;

use Illuminate\Database\Eloquent\Model;

class FormFieldType extends Model
{
    protected $table='par_form_field_types';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
