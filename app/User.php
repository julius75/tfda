<?php

namespace App;

use Illuminate\Notifications\Notifiable,
    Illuminate\Foundation\Auth\User as Authenticatable,
    Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

   /* public function findForPassport($username) {
        return self::where('username', $username)->first(); // change column name whatever you use in credentials
    }*/
}
