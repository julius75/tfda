<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 7/17/2018
 * Time: 3:37 PM
 */

namespace App\Modules\UserManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class Title extends Model
{
    protected $table = 'par_titles';
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';

    public function user()
    {
        return $this->hasMany(App\user);
    }

    public function getNameAttribute($value)
    {
        return ucfirst($value);
    }
}