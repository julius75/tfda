<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 2/19/2019
 * Time: 5:17 PM
 */

namespace App\Modules\Configurations\Entities;


use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $table='email_messages_templates';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}