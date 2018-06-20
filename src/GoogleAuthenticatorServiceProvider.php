<?php

namespace DogeDev\GoogleAuthenticator;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class GoogleAuthenticatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('2fa', function ($attribute, $value, $parameters, $validator) {

            if (empty($parameters)) {

                return Auth::user()->verifyCode($value);

            } else {

                return Route::input($parameters[0])->verifyCode($value);
            }
        });

        Validator::replacer('2fa', function ($message, $attribute, $rule, $parameters) {

            return "Invalid code submitted for 2 Factor Authentication";
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
