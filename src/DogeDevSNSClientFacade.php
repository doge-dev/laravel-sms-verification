<?php

namespace DogeDev\SMSVerification;

use Illuminate\Support\Facades\Facade;

class DogeDevSNSClientFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'DogeDevSNSClientSingleton'; }
}