<?php

namespace App\Modules\GmpApplications\Entities;

use Illuminate\Database\Eloquent\Model;

class GmpProductLineCategory extends Model
{
    protected $table = 'gmp_product_categories';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
