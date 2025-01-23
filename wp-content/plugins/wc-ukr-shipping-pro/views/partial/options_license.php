<?php
    echo $uiHelper->textField(
        'wc_ukr_shipping[license_key]',
        __('License key', 'wc-ukr-shipping-pro'),
        wc_ukr_shipping_get_option('wc_ukr_shipping_license_key')
    );
?>