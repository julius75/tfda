<?php

namespace App\Modules\Parameters\Entities;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $table = 'par_banks';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
