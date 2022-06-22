<?php
namespace SoftlogicGT\LaravelEpayServer\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelEpayServer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-epay-server';
    }
}
