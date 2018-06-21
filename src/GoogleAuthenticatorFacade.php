<?php

namespace DogeDev\GoogleAuthenticator;

use Illuminate\Support\Facades\Facade;

class GoogleAuthenticatorFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return new \PHPGangsta_GoogleAuthenticator();
    }
}
