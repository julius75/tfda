<?php

namespace App\Modules\Configurations\Entities;

use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    protected $table='wf_tfdaprocesses';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
