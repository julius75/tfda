<?php

namespace App\Modules\GmpApplications\Entities;

use Illuminate\Database\Eloquent\Model;

class GmpProductLine extends Model
{
    protected $table = 'gmp_product_lines';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
