<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 7/25/2017
 * Time: 11:46 AM
 */

namespace App\Providers;

use App\Auth\CustomUserProvider;
use Illuminate\Support\ServiceProvider;

class CustomAuthProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

        /* $this->app['auth']->extend('custom',function()
         {
             return new CustomUserProvider();
         });*/
        \Auth::provider('custom', function ($app, array $config) {
            // Return an instance of Illuminate\Contracts\Auth\UserProvider...
            return new CustomUserProvider();
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

