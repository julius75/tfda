<?php

namespace App\Modules\DocumentManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
