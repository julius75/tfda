<?php

namespace App\Modules\GmpApplications\Entities;

use Illuminate\Database\Eloquent\Model;

class GmpProductLineDescription extends Model
{
    protected $table = 'gmp_product_descriptions';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
