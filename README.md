# Laravel EpayServer Payment Gateway

Send payment transactions to Epay Server Visanet service.
You must have an active account for this to work.
The package automatically validates all input data.

## Installation

`composer require softlogic-gt/laravel-epayserver`

Set your environment variables

```
EPAY_SERVER_TEST=true
EPAY_SERVER_AFFILLIATION=
EPAY_SERVER_TERMINAL=
EPAY_SERVER_USER=
EPAY_SERVER_PASSWORD=
```

## Usage

In the constructor, if the email is specified, a confirmation receipt is sent. The default subject is `Comprobante de pago`.

### Sale

```
use SoftlogicGT\LaravelEpayServer\LaravelEpayServer;

$creditCard = '4000000000000416';
$expirationMonth = '2';
$expirationYear = '26';
$cvv2 = '123';
$amount = 1230.00;
$externalId = '557854';

$server = new LaravelEpayServer(
    [
        'receipt' => [
            'email'   => 'email@email.com',
            'subject' => 'My custom subject',
            'name'    => 'The name to print on the receipt'
        ]
    ]
);

$response = $server->sale($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId);
```

It will throw an exception if any error is received from Epay Server, or an object with the following info:

`[[auditNumber] => 111111 [referenceNumber] => 254555555 [authorizationNumber] => 022226 [responseCode] => 00 [messageType] => 0210]`
