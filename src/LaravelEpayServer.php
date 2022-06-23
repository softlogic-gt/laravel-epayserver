<?php
namespace SoftlogicGT\LaravelEpayServer;

use SoapClient;
use LVR\CreditCard\CardCvc;
use LVR\CreditCard\CardNumber;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LaravelEpayServer
{
    protected static $codes = [
        "00" => "Aprobada",
        "01" => "Refiérase al Emisor",
        "02" => "Refiérase al Emisor",
        "05" => "Transacción No Aceptada",
        "12" => "Transacción Inválida",
        "13" => "Monto Inválido",
        "19" => "Transacción no realizada, intente de nuevo 31 Tarjeta no soportada por switch",
        "35" => "Transacción ya ha sido ANULADA",
        "36" => "Transacción a ANULAR no EXISTE",
        "37" => "Transacción de ANULACION REVERSADA",
        "38" => "Transacción a ANULAR con Error",
        "41" => "Tarjeta Extraviada",
        "43" => "Tarjeta Robada",
        "51" => "No tiene fondos disponibles",
        "57" => "Transacción no permitida",
        "58" => "Transacción no permitida en la terminal",
        "65" => "Límite de actividad excedido",
        "80" => "Fecha de Expiración inválida",
        "89" => "Terminal inválida",
        "91" => "Emisor no disponible",
        "94" => "Transacción duplicada",
        "96" => "Error del sistema, intente más tarde",
    ];

    public static function sale($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId)
    {
        return self::common($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId, '0200');
    }

    protected static function common($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId, $messageType)
    {
        ini_set("default_socket_timeout", 10);
        $data = compact("creditCard", "expirationMonth", "expirationYear", "cvv2", "amount", "externalId", "messageType");

        $rules = [
            'creditCard'      => ['required', new CardNumber],
            'cvv2'            => ['required', new CardCvc($creditCard)],
            'expirationMonth' => 'required|numeric|lte:12|gte:1',
            'expirationYear'  => 'required|numeric|lte:99|gte:1',
            'amount'          => 'required|numeric',
            'externalId'      => 'required',
            'messageType'     => 'required',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $month      = str_pad($expirationMonth, 2, "0", STR_PAD_LEFT);
        $year       = str_pad($expirationYear, 2, "0", STR_PAD_LEFT);
        $total      = (int) (round($amount, 2) * 100);
        $externalId = str_pad(substr($externalId, -6, 6), 6, "0", STR_PAD_LEFT);

        $url        = config('laravel-epayserver.test') ? 'https://epaytestvisanet.com.gt/?wsdl' : 'https://epayvisanet.com.gt/?wsdl';
        $soapClient = new SoapClient($url, ["trace" => 1]);
        $params     = [
            'AuthorizationRequest' => [
                'posEntryMode'     => '012',
                'pan'              => $creditCard,
                'expdate'          => $year . $month,
                'amount'           => $total,
                'cvv2'             => $cvv2,
                'paymentgwIP'      => request()->ip(),
                'shopperIP'        => request()->ip(),
                'merchantServerIP' => request()->ip(),
                'merchantUser'     => config('laravel-epayserver.user'),
                'merchantPasswd'   => config('laravel-epayserver.password'),
                'merchant'         => config('laravel-epayserver.affilliation'),
                'terminalId'       => config('laravel-epayserver.terminal'),
                'messageType'      => $messageType,
                'auditNumber'      => $externalId,
                'additionalData'   => '',
            ],
        ];

        $res  = $soapClient->AuthorizationRequest($params);
        $code = $res->response->responseCode;
        //If succesful response, return full response
        if ($code == '00') {
            return $res->response;
        }
        //If error, return error from list or unknown
        if (array_key_exists($code, self::$codes)) {
            abort(400, self::$codes[$code]);
        }
        abort(400, "Error desconocido: " . $code);
    }
}
