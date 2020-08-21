<?php

namespace App\Modules\Parameters\Entities;

use Illuminate\Database\Eloquent\Model;

class ReceiptType extends Model
{
    protected $table = 'par_receipt_types';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
