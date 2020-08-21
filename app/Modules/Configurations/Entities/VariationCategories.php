<?php

namespace App\Modules\Configurations\Entities;

use Illuminate\Database\Eloquent\Model;

class VariationCategories extends Model
{
    protected $table='par_variations_categories';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
