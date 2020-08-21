<?php

namespace App\Modules\Administration\Entities;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $guarded=[];
    protected $table='par_menus';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
