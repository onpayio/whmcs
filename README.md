# OnPay WHMCS Module

This module implements the OnPay payment gateway into WHMCS.

### Supported features
- Allow customer to pay using the card both for new and recurring items (reuse the card).
- Set the payment window design from OnPay
- Allow charging the card through WHMCS admin
- Allow refunds through WHMCS admin

### TODO:
- Implement MobilePay subscriptions when OnPay supports it.

### Not implemented
- Deleting a card from WHMCS **will not** delete the subscription (remote token) at OnPay. WHMCS does not currently implement a function or hook on card deletion that would allow this functionality to be added.
- Calculating the card fees.


## Requirements

- OnPay Subscription addon (99 DKK/month).
- WHMCS 7.9 or newer

## Installation

1: Copy files into modules/gateways:

```
onpay.php -> modules/gateways/onpay.php
onpay -> modules/gateways/onpay
callback/onpay.php -> modules/gateways/callbacks/onpay.php
```

2: Activate the module in System Settings -> Payment Gateways

3: Configure the module as per the instructions in the settings page.

3.1: `Window Design` is not required, it will simply default to the normal OnPay layout.
