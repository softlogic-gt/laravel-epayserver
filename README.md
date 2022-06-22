# Laravel EpayServer Payment Gateway

## Installation

`composer require softlogic-gt/laravel-epayserver`

## Usage

`
use SoftlogicGT\LaravelEpayServer\LaravelEpayServer;

$creditCard = '4000000000000416';
$expirationMonth = '2';
$expirationYear = '26';
$cvv2 = '123';
$amount = 1230.00;
$externalId = '557854';

$response = LaravelEpayServer::sale($creditCard, $expirationMonth, $expirationYear, $cvv2, $amount, $externalId);
`
It will throw an exception if any error is received from Epay Server, or an object with the following info:

`[[auditNumber] => 111111 [referenceNumber] => 254555555 [authorizationNumber] => 022226 [responseCode] => 00 [messageType] => 0210]`
