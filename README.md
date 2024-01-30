# OnPay WHMCS Module

This module implements the OnPay payment gateway into WHMCS.

### Supported features
- Allow customer to pay using the card both for new and recurring items (reuse the card).
- Set the payment window design from OnPay
- Allow charging the card through WHMCS admin
- Allow refunds through WHMCS admin
- Allows multiple gateway IDs, window designs, secrets and api keys.

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

2: Language files can be found in `lang/overrides`, and can be copied/merged to the corresponding `lang/overrides` directory in WHMCS.

3: Activate the module in System Settings -> Payment Gateways

4: Configure the module as per the instructions in the settings page.

4.1: `Window Design` is not required, it will simply default to the normal OnPay layout.

## Handling multiple settlement currencies

There's cases where you want to handle multiple OnPay.io merchant accounts, e.g. when having multiple settlement currencies with Clearhaus which results in two API keys on the clearhaus side, which then in turn, also requires two OnPay.io merchant accounts.

To address this, the configuration is flexible to accommodate this. In its simplest form you can simply set the default configuration, as you'd normally do for `gatewayID`, `windowDesign`, `windowSecret` and `apiKey`, this will apply globally across all transactions.

A basic configuration example is as follows:
```
gatewayID: 3021017461000000
windowDesign: customWindow
windowSecret: XXXXXXXXXXXX
apiKey: XXXXXXXXXXXX
```

However, we can also control this per currency using the format `DEFAULT:XXXX,CURRENCY_CODE:YYYY`.
This will be matched against the currency set on the client's account, so if we have `DEFAULT:XXXX,USD:YYYY` defined, and a customer pays in USD, we'll use the `YYYY` value, however if the customer pays in DKK or EUR, we'll use `XXXX` as the value, since that's the configured fallback value.

As a small example:
```
gatewayID: DEFAULT:3021017461000000,DKK:3021017461000001,EUR:3021017461000002
windowDesign: DEFAULT:customWindow,DKK:dkkWindow
windowSecret: DEFAULT:XXXXXXXXXXXX,DKK:YYYYYYYYYYY,EUR:ZZZZZZZZZZZZZZ
apiKey: DEFAULT:XXXXXXXXXXXX,DKK:YYYYYYYYYYY,EUR:ZZZZZZZZZZZZZZ
```

Here we change gatewayID depending on `DEFAULT`, `DKK` and `EUR`, but for windowDesign we only change it for `DEFAULT` and `DKK` (so EUR will use `DEFAULT`).

If you're defining currencies without `DEFAULT`, we'll use the first currency in the list. For a list of accepted currencies, you can look at [https://onpay.io/docs/technical/index.html#accepted-currencies](https://onpay.io/docs/technical/index.html#accepted-currencies).