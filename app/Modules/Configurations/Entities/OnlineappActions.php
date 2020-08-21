<?php

namespace App\Modules\Configurations\Entities;

use Illuminate\Database\Eloquent\Model;

class OnlineappActions extends Model
{
    protected $connection= 'portal_db';
    protected $table='wb_statuses_actions';
    
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
