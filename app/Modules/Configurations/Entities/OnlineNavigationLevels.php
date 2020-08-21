<?php

namespace App\Modules\Configurations\Entities;

use Illuminate\Database\Eloquent\Model;

class OnlineNavigationLevels extends Model
{
    protected $connection = 'portal_db';
    protected $table='wb_navigation_levels';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
