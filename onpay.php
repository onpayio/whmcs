<?php
/**
 * @copyright Copyright (c) Hosting4Real 2021
 * @license MIT
 */

require_once 'onpay/vendor/autoload.php';

use \OnPay\API\PaymentWindow;
use OnPay\OnPayAPI;
use \OnPay\StaticToken;
use League\ISO3166\ISO3166;
use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die("This file cannot be accessed directly");
}

function onpay_MetaData()
{
    return [
        'DisplayName' => 'OnPay Payment Gateway',
        'APIVersion' => '1.1',
    ];
}

function onpay_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'OnPay Payment Gateway',
        ],
        'gatewayID' => [
            'FriendlyName' => 'Gateway ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your gateway ID here',
        ],
        'windowDesign' => [
            'FriendlyName' => 'Window Design',
            'Type' => 'text',
            'Size' => '25',
            'Default' => null,
            'Description' => 'Enter the Window Design name here for controlling the payment window layout.',
        ],
        'windowSecret' => [
            'FriendlyName' => 'Window Secret',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter the Window Secret here. You can find this key by going to https://manage.onpay.io/ -> Settings -> Payment Window',
        ],
        'apiKey' => [
            'FriendlyName' => 'API Key',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter an API Key here. You can generate such key from https://manage.onpay.io/ -> Settings -> API',
        ],
        'sandboxMode' => [
            'FriendlyName' => 'SandBox Mode',
            'Type' => 'yesno',
            'Description' => 'Whether to enable Sandbox mode or not',
        ],
    ];
}

function onpay_nolocalcc() {}

// The onpay_capture function is only used for allowing charges as an admin
function onpay_capture($params)
{
    // We get our gateway settings here
    $gatewayId = $params['gatewayID'];
    $windowSecret = $params['windowSecret'];
    $apiKey = $params['apiKey'];
    $sandboxValue = $params['sandboxMode'];
    $sandboxMode = 0;
    if($sandboxValue == "on")
    {
        $sandboxMode = 1;
    }

    // Capture Parameters
    $remoteGatewayToken = $params['gatewayid'];

    // Invoice Parameters
    $invoiceNumber = $params['invoicenum'];
    $amount = $params['amount'];
    $gatewayAmount = $amount * 100;

    $systemUrl = $params['systemurl'];

    // A token is required for a remote input gateway capture attempt
    if (!$remoteGatewayToken) {
        return [
            'status' => 'declined',
            'decline_message' => 'No Remote Token',
        ];
    }

    // Set up the OnPayAPI to do further transactions
    $staticToken = new StaticToken($apiKey);
    $onPayAPI = new OnPayAPI($staticToken, [
        'client_id' => $systemUrl,
    ]);

    $transactionDetails = $onPayAPI->subscription()->createTransactionFromSubscription($remoteGatewayToken, (int)$gatewayAmount, $invoiceNumber);
    $paymentResult = $onPayAPI->transaction()->captureTransaction($transactionDetails->uuid);
    if($paymentResult->status == 'finished') {
        return [
            'status' => 'success',
            'transid' => $paymentResult->transactionNumber,
            'fee' => 0,
            'rawdata' => (array) $paymentResult,
        ];
    }

    return [
        'status' => 'declined',
        'declinereason' => $paymentResult->status,
        'rawdata' => (array) $paymentResult,
    ];
}

function onpay_refund($params)
{
    // We get our gateway settings here
    $apiKey = $params['apiKey'];

    // Transaction Parameters
    $transactionIdToRefund = $params['transid'];
    $refundAmount = $params['amount'];
    $gatewayAmount = $refundAmount * 100;

    // System Parameters
    $systemUrl = $params['systemurl'];

    // Set up the OnPayAPI to do further transactions
    $staticToken = new StaticToken($apiKey);
    $onPayAPI = new OnPayAPI($staticToken, [
        'client_id' => $systemUrl,
    ]);

    try {
        $refundResult = $onPayAPI->transaction()->refundTransaction($transactionIdToRefund, (int)$gatewayAmount);
    } catch(\OnPay\API\Exception\ApiException $e) {
        return [
            'status' => 'declined',
            'declinereason' => $e->getMessage(),
            'rawdata' => $e->getMessage(),
        ];
    }

    if(in_array($refundResult->status, ['finished', 'refunded'])) {
        return [
            'status' => 'success',
            'transid' => $refundResult->transactionNumber,
            'fee' => 0,
            'rawdata' => (array) $refundResult,
        ];
    }

    return [
        'status' => 'declined',
        'declinereason' => (array) $refundResult,
        'rawdata' => (array) $refundResult,
    ];
}

// This generates the actual payment link on the invoice
function onpay_link($params)
{
    // We get our gateway settings here
    $gatewayId = $params['gatewayID'];
    $windowSecret = $params['windowSecret'];
    $windowDesign = $params['windowDesign'];
    $apiKey = $params['apiKey'];
    $sandboxValue = $params['sandboxMode'];
    $sandboxMode = 0;
    if($sandboxValue == "on")
    {
        $sandboxMode = 1;
    }

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $invoiceNumber = $params['invoicenum'];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $clientId = $params['clientdetails']['client_id'];
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $fullname = $params['clientdetails']['fullname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $country_numeric = (new ISO3166)->alpha2($country);
    $phone = $params['clientdetails']['phonenumber'];
    $phone_cc = $params['clientdetails']['phonecc'];
    $lang = $_SESSION['Language']; 

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $moduleName = $params['paymentmethod'];

    // Set generic variables
    $callbackUrl = $systemUrl . 'modules/gateways/callback/' . $moduleName . '.php';
    $chargeUrl = $systemUrl . 'modules/gateways/' . $moduleName . '/' . $moduleName . '.php';
    $set_lang  = array('danish' => 'da', 'english' => 'en', 'german' => 'de', 'swedish' => 'sv', 'french' => 'fr'); 

    $paymentWindow = new PaymentWindow();
    // Generic gateway settings
    $paymentWindow->setGatewayId($gatewayId);
    $paymentWindow->setSecret($windowSecret);
    $paymentWindow->setType('subscription');
    $paymentWindow->set3DSecure(true);
    $paymentWindow->setTestMode($sandboxMode);
    $paymentWindow->setDeliveryDisabled('no-reason');
    $paymentWindow->setSubscriptionWithTransaction(true);
    if($windowDesign) {
        $paymentWindow->setDesign($windowDesign);
    }

    // Transaction related settings
    $gatewayAmount = $amount * 100;
    $paymentWindow->setCurrency($currencyCode);
    $paymentWindow->setAmount($gatewayAmount);
    $paymentWindow->setReference($invoiceNumber);
    $paymentWindow->setAcceptUrl($returnUrl . "&paymentsuccessawaitingnotification=true");
    $paymentWindow->setDeclineUrl($returnUrl . "&paymentfailed=true");
    $paymentWindow->setCallbackUrl($callbackUrl);
    $paymentWindow->setLanguage($set_lang[$lang]);

    // Set customer related variables for SCA
    $paymentInfo = new PaymentWindow\PaymentInfo();
    $paymentInfo->setAccountId($clientId);
    $paymentInfo->setBillingAddressCity($city);
    $paymentInfo->setBillingAddressCountry($country_numeric['numeric']);
    $paymentInfo->setBillingAddressLine1($address1);
    $paymentInfo->setBillingAddressPostalCode($postcode);
    $paymentInfo->setName($fullname);
    $paymentInfo->setEmail($email);
    $paymentInfo->setDeliveryEmail($email);
    $paymentInfo->setPhoneHome($phone_cc, $phone);
    // Electronic delivery
    $paymentInfo->setDeliveryTimeFrame("01");
    $paymentInfo->setShippingMethod("07");

    // Provide the customer info to the payment gateway
    $paymentWindow->setInfo($paymentInfo);

    $payMethods = localAPI('GetPayMethods', ['clientid' => $clientId]);

    $onPayPayMethods = 0;

    $payMethodData = '';
    foreach($payMethods['paymethods'] as $payMethod) {
        if($payMethod['gateway_name'] == $moduleName) {
            $payMethodData .= "<option value='${payMethod['id']}'>************${payMethod['card_last_four']} ${payMethod['expiry_date']}</option>";
            $onPayPayMethods++;
        }
    }

    $additionalInfo = [
        'opg_user_id' => $clientId,
        'opg_amount' => $gatewayAmount,
        'opg_invoice_id' => $invoiceId,
        'opg_invoice_number' => $invoiceNumber,
    ];

    $additionalInfoHmac = _calculateInternalSecret($additionalInfo, $windowSecret);
    if($paymentWindow->isValid()) {
        $return_output = "";
        if($onPayPayMethods > 0) {
            $payNow = Lang::trans("onpay_pay_now");
            $return_output .= <<<EOT
            <form class="form-inline mb-3 justify-content-center" method="post" action="$chargeUrl">
                <input type="hidden" name="returnUrl" value="$returnUrl" />
                <input type="hidden" name="opg_user_id" value="$clientId" />
                <input type="hidden" name="opg_amount" value="$gatewayAmount" />
                <input type="hidden" name="opg_invoice_id" value="$invoiceId" />
                <input type="hidden" name="opg_invoice_number" value="$invoiceNumber" />
                <input type='hidden' name='opg_hmac_sha1' value='$additionalInfoHmac' />
                <div class="input-group">
                    <select class="form-control" name="cardid">
                        $payMethodData
                    </select>
                    <div class="input-group-btn input-group-append">
                        <input class="btn btn-success" type="submit" value="$payNow" />
                    </div>
                </div>
            </form>
            <hr />
 EOT;
        }
        $return_output .= "<form method='post' action='{$paymentWindow->getActionUrl()}' accept-charset='UTF-8'>";
        foreach($paymentWindow->getFormFields() as $key => $value) {
            $return_output .= "<input type='hidden' name='$key' value='$value' />";
        }

        // We can add additional fields for security, we use `opg_` so we can calculate the right hmac
        foreach($additionalInfo as $key => $value) {
            $return_output .= "<input type='hidden' name='$key' value='$value' />";
        }
        $return_output .= "<input type='hidden' name='opg_hmac_sha1' value='$additionalInfoHmac' />";
        $return_output .= "<input class='btn btn-success' type='submit' value='" . Lang::trans('onpay_use_new_card') . "'></form>";

        return $return_output;
    } else {
        return "Something went seriously wrong";
    }
}

function _calculateInternalSecret(array $params, $secret)
{
    $toHashArray = [];
    foreach($params as $key => $value) {
        if(0 === strpos($key, 'opg_') && 'opg_hmac_sha1' !== $key) {
            $toHashArray[$key] = $value;
        }
    }

    ksort($toHashArray);
    $queryString = strtolower(http_build_query($toHashArray));

    $hmac = hash_hmac('sha1', $queryString, $secret);

    return $hmac;
}
// This allows us to use the awaiting notification endpoint on invoices
add_hook('ClientAreaPageViewInvoice', 1, function($vars) {
    $gatewayModuleName = basename(__FILE__, '.php');
    if(isset($_GET['paymentsuccessawaitingnotification']) && $vars['selectedGateway'] == $gatewayModuleName) {
        return [
            'paymentSuccess' => true,
            'paymentSuccessAwaitingNotification' => true,
        ];
    }
});