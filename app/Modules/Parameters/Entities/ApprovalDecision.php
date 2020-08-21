<?php

namespace App\Modules\Parameters\Entities;

use Illuminate\Database\Eloquent\Model;

class ApprovalDecision extends Model
{
    protected $table = 'par_approval_decisions';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
