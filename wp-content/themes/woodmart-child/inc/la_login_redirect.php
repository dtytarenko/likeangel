<?php
/**
 * Plugin Name: LA Login Redirect
 * Description: Redirects users to My Account page after login.
 * Author: Like Angel
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_filter('woocommerce_login_redirect', 'la_custom_login_redirect', 10, 2);

function la_custom_login_redirect($redirect, $user) {
    // Якщо користувач не адміністратор – редірект на /my-account/
    if (!in_array('administrator', (array) $user->roles)) {
        return wc_get_page_permalink('myaccount');
    }
    return $redirect;
}
