# Laravel EpayServer Payment Gateway

Send payment transactions to Epay Server Visanet service.
You must have an active account for this to work.
The package automatically validates all input data.

## Installation

`composer require softlogic-gt/laravel-epayserver`

Set your environment variables

```
LARAVEL_EPAY_SERVER_TEST=true
LARAVEL_EPAY_SERVER_AFFILLIATION=
LARAVEL_EPAY_SERVER_TERMINAL=
LARAVEL_EPAY_SERVER_USER=
LARAVEL_EPAY_SERVER_PASSWORD=
```

## Usage

### Sale

```
use SoftlogicGT\LaravelEpayServer\LaravelEpayServer;

$creditCard = '4000000000000416';
$expirationMonth = '2';
$expirationYear = '26';
$cvv2 = '123';
$amount = 1230.00;
$externalId = '557854';

$response = LaravelEpayServer::sale($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId);
```

It will throw an exception if any error is received from Epay Server, or an object with the following info:

`[[auditNumber] => 111111 [referenceNumber] => 254555555 [authorizationNumber] => 022226 [responseCode] => 00 [messageType] => 0210]`
