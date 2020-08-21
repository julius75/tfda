<?php

namespace App\Modules\Workflow\Entities;

use Illuminate\Database\Eloquent\Model;

class SerialTracker extends Model
{
    protected $table='processes_serials';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
