<?php

namespace App\Modules\Parameters\Entities;

use Illuminate\Database\Eloquent\Model;

class Modules extends Model
{
    protected $table = 'modules';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
