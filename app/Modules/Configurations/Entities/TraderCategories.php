<?php

namespace App\Modules\Configurations\Entities;

use Illuminate\Database\Eloquent\Model;

class TraderCategories extends Model
{
    protected $table = 'par_trader_categories';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
