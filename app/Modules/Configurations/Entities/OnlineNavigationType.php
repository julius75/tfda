<?php

namespace App\Modules\Configurations\Entities;

use Illuminate\Database\Eloquent\Model;

class OnlineNavigationType extends Model
{
    protected $connection = 'portal_db';
    protected $table='wb_navigation_types';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
