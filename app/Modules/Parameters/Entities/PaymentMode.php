<?php

namespace App\Modules\Parameters\Entities;

use Illuminate\Database\Eloquent\Model;

class PaymentMode extends Model
{
    protected $table = 'par_payment_modes';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}
