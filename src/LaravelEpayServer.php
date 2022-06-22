<?php
namespace SoftlogicGT\LaravelEpayServer;

use SoapClient;

class LaravelEpayServer
{
    public static function sale()
    {
        return self::common();
    }

    protected static function common()
    {
        $url = config('laravel-epayserver.test') ? 'https://epaytestvisanet.com.gt/?wsdl' : 'https://epayvisanet.com.gt/?wsdl';

        $soapClient = new SoapClient($url, ["trace" => 1]);
        $res        = $soapClient->AuthorizationRequest([]);

        return $res->AuthorizationResponse;
    }
}
