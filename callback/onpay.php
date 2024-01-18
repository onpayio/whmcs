<?php
// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../onpay/vendor/autoload.php';

App::load_function('gateway');
App::load_function('invoice');

use \OnPay\API\PaymentWindow;
use \OnPay\API\Util\Currency;
use \OnPay\StaticToken;
use WHMCS\Database\Capsule;

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$onpayUuid = $_GET['onpay_uuid'];
$onpayNumber = $_GET['onpay_number'];
$onpayCurrency = $_GET['onpay_currency'];
$onpayMethod = $_GET['onpay_method'];
$invoiceId = $_GET['opg_invoice_id'];

// onpay_currency is ISO 4217 currency code (e.g. 208 for DKK, 978 for EUR, 840 for USD) so we need to convert it to the 3 letter code.
$onpayCurrency = new Currency((int)$_GET['onpay_currency']);

$onpayCurrencyAlpha3 = $onpayCurrency->getAlpha3();

// We get our gateway settings here
$gatewayId = _determineValue($gatewayParams['gatewayID'], $onpayCurrencyAlpha3);
$windowSecret = _determineValue($gatewayParams['windowSecret'], $onpayCurrencyAlpha3);
$apiKey = _determineValue($gatewayParams['apiKey'], $onpayCurrencyAlpha3);
$systemUrl = $gatewayParams['systemurl'];
$sandboxValue = $gatewayParams['sandboxMode'];
$sandboxMode = 0;
if($sandboxValue == "on")
{
    $sandboxMode = 1;
}
// Set up the payment Window for validating the payment
$payment = new PaymentWindow();
$payment->setSecret($windowSecret);

// Set up the OnPayAPI to do further transactions
$staticToken = new StaticToken($apiKey);
$onPayAPI = new \OnPay\OnPayAPI($staticToken, [
    'client_id' => $systemUrl,
]);


if($payment->validatePayment($_GET)) {
    // Validate our own opg hmac
    if(_calculateInternalSecret($_GET, $windowSecret) != $_GET['opg_hmac_sha1']) {
        logTransaction($gatewayParams['name'], $_GET, 'Possible tampering');
        die;
    }

    $onpayUuidTransaction = $_GET['onpay_uuid_transaction'];
    $onpayNumberTransaction = $_GET['onpay_number_transaction'];

    checkCbInvoiceID($invoiceId);
    checkCbTransID($onpayNumberTransaction);

    try {
        $transactionDetails = $onPayAPI->transaction()->captureTransaction($onpayUuidTransaction);
    } catch (\Exception $e) {
        $errorData = [
            (array)$e->getMessage(),
            $_GET,
        ];
        logTransaction($gatewayParams['name'], $errorData, 'Failure');
        sendMessage("Credit Card Payment Failed", $invoiceId);
        die;
    }

    $invoiceData = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);
    $userId = $invoiceData['userid'];
    $onpayCardmask = $_GET['onpay_cardmask'];
    $cardLastFour = substr($_GET['onpay_cardmask'], -4);
    $cardType = $_GET['onpay_cardtype'];
    $cardMonth = sprintf("%02d", $transactionDetails->expiryMonth);
    $cardYear = substr($transactionDetails->expiryYear, -2);
    $cardExpire = "$cardMonth$cardYear";

    // We successfully captured the transaction, so let's save the data to the database
    $result = invoiceSaveRemoteCard($invoiceId, $cardLastFour, $cardType, $cardExpire, $onpayUuid);

    addInvoicePayment($invoiceId, $transactionDetails->transactionNumber, $transactionDetails->amount / 100, 0, $gatewayModuleName);

    logTransaction($gatewayParams['name'], $_GET, 'Success');
} else {
    logTransaction($gatewayParams['name'], $_GET, 'Failure');
    sendMessage("Credit Card Payment Failed", $invoiceId);
}