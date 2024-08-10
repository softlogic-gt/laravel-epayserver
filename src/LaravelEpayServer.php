<?php
namespace SoftlogicGT\LaravelEpayServer;

use Throwable;
use SoapClient;
use Carbon\Carbon;
use LVR\CreditCard\CardCvc;
use LVR\CreditCard\CardNumber;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use SoftlogicGT\LaravelEpayServer\Jobs\SendReceipt;
use SoftlogicGT\LaravelEpayServer\Jobs\SendReversal;

class LaravelEpayServer
{
    protected $approvedInstallments = [3, 6, 10, 12, 18, 24];
    protected $receipt              = [
        'email'   => null,
        'subject' => 'Comprobante de pago',
        'name'    => '',
    ];

    protected $codes = [
        "00" => "Aprobada",
        "01" => "Refierase al emisor",
        "02" => "Refierase al emisor, condición especial",
        "03" => "comercio o proveedor de servicio no válida",
        "04" => "Recoger tarjeta",
        "05" => "Transaccion no aceptada",
        "06" => "Error",
        "07" => "Recoger tarjeta condicion especial (Otra a Robada/perdida)",
        "10" => "Aprobación Parcial",
        "11" => "Aprobación V.I.P.",
        "12" => "Transacción no válida",
        "13" => "Cantidad inválida",
        "14" => "número de cuenta no válido (no hay tal número)",
        "15" => "No existe el emisor",
        "17" => "Cancelacion del cliente",
        "19" => "Vuelva a introducir la transacción",
        "20" => "Respuesta Invalida",
        "21" => "Ninguna medida adoptada",
        "22" => "Sospecha de Mal funcionamiento",
        "25" => "No se puede localizar en el archivo de registro, o número de cuenta",
        "28" => "Archivo no está disponible temporalmente",
        "30" => "Error de formato",
        "31" => "Transaccion no soportada por el SWITCH",
        "41" => "Recoger tarjeta (tarjeta perdida)",
        "43" => "Recoger tarjeta(tarjeta robada)",
        "51" => "Insuficiencia de fondos",
        "52" => "Ninguna cuenta corriente",
        "53" => "Ninguna cuenta de ahorro",
        "54" => "La tarjeta ha caducado",
        "55" => "PIN incorrecto",
        "57" => "Transacción no permitido a los titulares de tarjetas",
        "58" => "Transacción no permitida a la terminal",
        "59" => "Sospechas de fraude",
        "61" => "La cantidad ha superado el límite",
        "62" => "Tarjeta restringida",
        "63" => "Violaciòn de seguridad",
        "65" => "Fuera de parametros transaccionales",
        "68" => "Respuesta recibida demasiado tarde",
        "75" => "Número permitido de intentos de entrada de PIN-superado",
        "76" => "No se puede localizar el mensaje anterior",
        "77" => "Mensaje anterior se encuentra una repetición o inversión, pero los datos de repet",
        "78" => "Bloqueado, primer uso",
        "80" => "Transacciones de Visa: no disponible emisor del crédito. La marca de distribuidor",
        "81" => "Error criptografico encontrado en el PIN",
        "82" => "CAM, dCVV, ICVV, o resultados negativos CVV",
        "83" => "No se puede verificar PIN",
        "85" => "No hay razón para rechazar una solicitud de verificación del número de cuenta",
        "89" => "Terminal inválida",
        "91" => "Emisor NO disponible",
        "92" => "Destino no se puede encontrar para el enrutamiento",
        "93" => "La transacción no se puede completar.  Solo se aceptan tarjetas locales Visa y Mastercard",
        "94" => "Transacción duplicada",
        "96" => "Mal funcionamiento del sistema, intente mas tarde",
        "N0" => "Fuerza CTPI",
        "se" => "Servicio de caja N3 no disponible",
        "N3" => "Servicio de caja no disponible",
        "N4" => "Solicitud de reembolso en efectivo excede el límite de emisor",
        "N7" => "CVV2 incorrecto",
        "Di" => "Sminución N7 para el fracaso CVV2",
        "P2" => "Información no válida emisor de la factura",
        "P5" => "Solicitud de PIN Cambiar / Desbloquear declinó",
        "P6" => "Inseguro PIN",
        "Au" => "Tenticación de tarjeta no Q1",
        "R0" => "Orden de Suspensión de Pago",
        "R1" => "Revocación de Autorización de Orden",
        "R3" => "Revocación de todas las autorizaciones de pedido",
        "XA" => "Avanzar al emisor",
        "XD" => "Avanzar al emisor",
        "Z3" => "No se puede ir en línea",
    ];

    public function __construct(array $config = [])
    {
        if (isset($config['receipt'])) {
            if (isset($config['receipt']['email'])) {
                $this->receipt['email'] = $config['receipt']['email'];
            }

            if (isset($config['receipt']['subject'])) {
                $this->receipt['subject'] = $config['receipt']['subject'];
            }

            if (isset($config['receipt']['name'])) {
                $this->receipt['name'] = $config['receipt']['name'];
            }
        }
    }

    public function sale($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId)
    {
        return $this->common($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId, '0200');
    }

    public function installments($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId, $installments)
    {
        $data = compact("creditCard", "expirationMonth", "expirationYear", "cvv2", "amount", "externalId", "installments");

        $rules = [
            'installments' => ['required', Rule::in($this->approvedInstallments)],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $additionalData = 'VC' . str_pad($installments, 2, "0", STR_PAD_LEFT);

        return $this->common($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId, '0200', $additionalData);
    }

    public function points($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId)
    {
        return $this->common($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId, '0200', 'LU');
    }

    public function reversal($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId)
    {
        return $this->common($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId, '0400');
    }

    protected function common($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId, $messageType, $additionalData = '')
    {
        $data = compact("creditCard", "expirationMonth", "expirationYear", "cvv2", "amount", "externalId", "messageType", "additionalData");

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

        $month = str_pad($expirationMonth, 2, "0", STR_PAD_LEFT);
        $year  = str_pad($expirationYear, 2, "0", STR_PAD_LEFT);
        $total = (int) (round($amount, 2) * 100);

        // se agrega para evitar duplicados
        $externalId = $externalId . rand(1, 100);
        $externalId = str_pad(substr($externalId, -6, 6), 6, "0", STR_PAD_LEFT);
        $ip         = request()->ip();
        if ($ip == '127.0.0.1') {
            $ip = '190.235.10.14';
        }

        $timeout = $this->getTimeout();
        try {
            ini_set("default_socket_timeout", $timeout);
            $soapClient = new SoapClient($this->getURL(), [
                "trace"              => 1,
                'connection_timeout' => $timeout,
            ]);
            $params = [
                'AuthorizationRequest' => [
                    'posEntryMode'     => '012',
                    'pan'              => $creditCard,
                    'expdate'          => $year . $month,
                    'amount'           => $total,
                    'cvv2'             => $cvv2,
                    'paymentgwIP'      => '190.111.1.198',
                    'shopperIP'        => $ip,
                    'merchantServerIP' => $ip,
                    'merchantUser'     => config('laravel-epayserver.user'),
                    'merchantPasswd'   => config('laravel-epayserver.password'),
                    'merchant'         => config('laravel-epayserver.affilliation'),
                    'terminalId'       => config('laravel-epayserver.terminal'),
                    'messageType'      => $messageType,
                    'auditNumber'      => $externalId,
                    'additionalData'   => $additionalData,
                ],
            ];
            $res = $soapClient->AuthorizationRequest($params);
        } catch (Throwable $th) {
            Log::error($th->getMessage());
            if ($messageType != '0400') {
                SendReversal::dispatch($data);
            }
            abort(500, "No fue posible realizar la transacción, intente de nuevo");
        }
        $code = $res->response->responseCode;
        //If succesful response, return full response
        if ($code == '00') {
            if ($this->receipt['email']) {
                $receiptData = [
                    'email'        => $this->receipt['email'],
                    'subject'      => $this->receipt['subject'],
                    'name'         => $this->receipt['name'],
                    'cc'           => '####-####-####-' . substr($creditCard, -4, 4),
                    'date'         => Carbon::now(),
                    'amount'       => $messageType != '0400' ? $total : -$total,
                    'ref_number'   => $res->response->referenceNumber,
                    'auth_number'  => $res->response->authorizationNumber,
                    'audit_number' => $res->response->auditNumber,
                    'merchant'     => config('laravel-epayserver.affilliation'),
                ];

                SendReceipt::dispatch($receiptData);
            }

            return $res->response;
        }
        //If error, return error from list or unknown
        if (array_key_exists($code, $this->codes)) {
            abort(400, $this->codes[$code]);
        }
        abort(400, "Error desconocido: " . $code);
    }

    public function void($auditNumber, $total, $lastDigits = "####")
    {
        $data = compact("auditNumber", "total");

        $rules = [
            'auditNumber' => 'required',
            'total'       => 'required|numeric',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $timeout = $this->getTimeout();
        $ip      = request()->ip();
        if ($ip == '127.0.0.1') {
            $ip = '190.235.10.14';
        }

        try {
            ini_set("default_socket_timeout", $timeout);
            $soapClient = new SoapClient($this->getURL(), [
                "trace"              => 1,
                'connection_timeout' => $timeout,
            ]);
            $params = [
                'AuthorizationRequest' => [
                    'posEntryMode'     => '012',
                    'paymentgwIP'      => '190.111.1.198',
                    'shopperIP'        => $ip,
                    'merchantServerIP' => $ip,
                    'merchantUser'     => config('laravel-epayserver.user'),
                    'merchantPasswd'   => config('laravel-epayserver.password'),
                    'merchant'         => config('laravel-epayserver.affilliation'),
                    'terminalId'       => config('laravel-epayserver.terminal'),
                    'auditNumber'      => $auditNumber,
                    'messageType'      => '0202',
                ],
            ];
            $res = $soapClient->AuthorizationRequest($params);
        } catch (Throwable $th) {
            Log::error($th);
            abort(500, "No fue posible realizar la reversión, intente de nuevo");
        }
        $code  = $res->response->responseCode;
        $total = (int) (round($total, 2) * -100);
        //If succesful response, return full response
        if ($code == '00') {
            if ($this->receipt['email']) {
                $receiptData = [
                    'email'        => $this->receipt['email'],
                    'subject'      => $this->receipt['subject'],
                    'name'         => $this->receipt['name'],
                    'cc'           => '####-####-####-' . $lastDigits,
                    'date'         => Carbon::now(),
                    'amount'       => $total,
                    'ref_number'   => $res->response->referenceNumber,
                    'auth_number'  => $res->response->authorizationNumber,
                    'audit_number' => $res->response->auditNumber,
                    'merchant'     => config('laravel-epayserver.affilliation'),
                ];

                SendReceipt::dispatch($receiptData);
            }

            return $res->response;
        }
        //If error, return error from list or unknown
        if (array_key_exists($code, $this->codes)) {
            abort(400, $this->codes[$code]);
        }
        abort(400, "Error desconocido: " . $code);
    }

    protected function getURL()
    {
        return config('laravel-epayserver.test') ? 'https://epaytestvisanet.com.gt/?wsdl' : 'https://epayvisanet.com.gt/paymentcommerce.asmx?WSDL';
    }

    protected function getTimeout()
    {
        return config('laravel-epayserver.test') ? 30 : 50;
    }
}
