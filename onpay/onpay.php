<?php
// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../onpay/vendor/autoload.php';

App::load_function('gateway');
App::load_function('invoice');

use \OnPay\API\PaymentWindow;
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

$currency = $_POST['opg_currency'];

// We get our gateway settings here
$gatewayId = _determineValue($gatewayParams['gatewayID'], $currency);
$windowSecret = _determineValue($gatewayParams['windowSecret'], $currency);
$apiKey = _determineValue($gatewayParams['apiKey'], $currency);
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

$returnUrl = $_POST['returnUrl'];
$cardId = $_POST['cardid'];
$clientId = $_POST['opg_user_id'];
$gatewayAmount = $_POST['opg_amount'];
$invoiceId = $_POST['opg_invoice_id'];
$invoiceNumber = $_POST['opg_invoice_number'];
$additionalInfoHmac = $_POST['opg_hmac_sha1'];

// We calculate the hmac for the POST data except the cardid and returnUrl. It prevents people from tampering with
// the invoice ID, the amount and the user ID
if(_calculateInternalSecret($_POST, $windowSecret) != $_POST['opg_hmac_sha1']) {
    logTransaction($gatewayParams['name'], $_POST, 'Possible tampering');
    header('Location: ' . $returnUrl . '&paymentfailed=true');
    die;
}

// We get the payment methods for the particular user and the card ID to only get one result
$payMethod = localAPI('GetPayMethods', [
    'clientid' => $clientId,
    'paymethodid' => $cardId,

]);

if($payMethod['result'] == 'success' && count($payMethod['paymethods']) == 1) {
    $payMethodGateway = $payMethod['paymethods'][0]['gateway_name'];
    $payMethodUserId = $payMethod['clientid'];
    $payMethodRemoteToken = $payMethod['paymethods'][0]['remote_token'];

    // We check whether the payment method returned are indeed for this gateway, else it will simply fail.
    if($payMethodGateway != $gatewayModuleName) {
        logTransaction($gatewayParams['name'], $_POST, 'Gateway Mismatch');
        header('Location: ' . $returnUrl . '&paymentfailed=true');
        die;
    }

    try {
        $transactionDetails = $onPayAPI->subscription()->createTransactionFromSubscription($payMethodRemoteToken, (int)$gatewayAmount, $invoiceNumber);
        $paymentResult = $onPayAPI->transaction()->captureTransaction($transactionDetails->uuid);
    } catch (\Exception $e) {
        $errorData = [
            (array)$e->getMessage(),
            $_POST,
        ];
        logTransaction($gatewayParams['name'], $errorData, 'Failure');
        sendMessage("Credit Card Payment Failed", $invoiceId);
        header('Location: ' . $returnUrl . '&paymentfailed=true');
        die;
    }

    addInvoicePayment($invoiceId, $transactionDetails->transactionNumber, $transactionDetails->amount / 100, 0, $gatewayModuleName);

    logTransaction($gatewayParams['name'], $_POST, 'Success');
    header('Location: ' . $returnUrl . '&paymentsuccess=true');

} else {
    // We fail here since there was more than 1 payment method returned. That shouldn't be possible.
    logTransaction($gatewayParams['name'], $_POST, 'Failure');
    sendMessage("Credit Card Payment Failed", $invoiceId);
    header('Location: ' . $returnUrl . '&paymentfailed=true');
    die;
}
