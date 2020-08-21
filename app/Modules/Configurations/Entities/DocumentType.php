<?php

namespace App\Modules\Configurations\Entities;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table='par_document_types';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
