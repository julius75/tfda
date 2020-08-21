<?php

namespace App\Modules\Workflow\Entities;

use Illuminate\Database\Eloquent\Model;

class RefNumbersVariable extends Model
{
    protected $table='refnumbers_variables';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
