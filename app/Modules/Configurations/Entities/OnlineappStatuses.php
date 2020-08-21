<?php

namespace App\Modules\Configurations\Entities;

use Illuminate\Database\Eloquent\Model;

class OnlineappStatuses extends Model
{
    protected $connection = 'portal_db';
    protected $table='wb_statuses';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
