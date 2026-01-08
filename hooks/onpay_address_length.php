<?php

$maxLength = 50;

add_hook('ClientAreaFooterOutput', 1, function ($vars) use ($maxLength) {
    if (($vars['filename'] ?? '') !== 'cart' || ($_REQUEST['a'] ?? '') !== 'checkout') {
        return '';
    }

    // Load language string
    $msg = Lang::trans('onpay_address_maxlength');

    // Escape for JS context
    $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);

    return <<<HTML
<script>
(function () {
  var MAX = {$maxLength};
  var MESSAGE = {$msg};

  function setupField(input) {
    if (!input || input.dataset.onpayBound) return;
    input.dataset.onpayBound = "1";

    input.setAttribute('maxlength', String(MAX));

    var error = document.createElement('div');
    error.className = 'onpay-address-error';
    error.style.cssText = 'color:#b30000;font-size:12px;margin-top:4px;display:none;';
    error.textContent = MESSAGE;

    input.parentNode.appendChild(error);

    function check() {
      if (input.value.length > MAX) {
        input.value = input.value.slice(0, MAX);
      }
      error.style.display = input.value.length >= MAX ? 'block' : 'none';
    }

    input.addEventListener('input', check);
    check();
  }

  function apply() {
    setupField(document.querySelector('input[name="address1"]'));
    setupField(document.querySelector('input[name="address2"]'));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', apply);
  } else {
    apply();
  }
})();
</script>
HTML;
});

add_hook('ShoppingCartValidateCheckout', 1, function ($vars) use ($maxLength) {
    $errors = [];

    if(strlen($vars['address1']) > $maxLength) {
        $errors[] = Lang::trans('onpay_address1_maxlength');
    }
    if(strlen($vars['address2']) > $maxLength) {
        $errors[] = Lang::trans('onpay_address2_maxlength');
    }

    if(!empty($errors)) {
        return $errors;
    }
});