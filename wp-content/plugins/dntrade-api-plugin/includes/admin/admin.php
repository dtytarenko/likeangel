<?php
/*
Plugin Name: DNTrade API Plugin
Description: Plugin description goes here.
Version: 1.0
Author: DNTrade LTD
*/

// Функція для виведення сторінки налаштувань плагіну
function dntrade_api_render_settings_page() {
    ?>
    <div class="wrap">
        <h2>Токен доступу до API DNTrade</h2>
        <form method="post" action="options.php">
            <?php settings_fields('dntrade_api_plugin_settings'); ?>
            <?php do_settings_sections('dntrade-api-settings'); ?>
            <?php submit_button('Зберегти'); ?>
        </form>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
	$('#generate_token_button').on('click', function(e) {
	    e.preventDefault();
	    var token = generateToken(256);
	    $('#dntrade_api_access_token').val(token);
	    return;
	});

	function generateToken(length) {
	    var charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	    var token = '';
	    for (var i = 0; i < length; i++) {
		var randomIndex = Math.floor(Math.random() * charset.length);
		token += charset[randomIndex];
	    }
	    return token;
	}
    });
    </script>
    <?php
}

// Функція для виведення секції налаштувань плагіну
function dntrade_api_plugin_settings_section_callback() {
    echo 'Згенеруйте токен авторизації, збережіть його та вставте у відповідне поле у налаштуванні інтеграції Woocommerce у вашому кабінеті DNTrade';
}

// Функція для виведення поля для токену
function dntrade_api_access_token_callback() {
    $options = get_option('dntrade_api_plugin_settings');
    echo '<textarea style="width: 350px; height: 150px; margin-right: 10px;" type="text" id="dntrade_api_access_token" name="dntrade_api_plugin_settings[access_token]" value="' . esc_attr($options['access_token']) . '">' . esc_attr($options['access_token']) . '</textarea>';
    echo '<button id="generate_token_button" role="button" class="button">Згенерувати Token</button>';    
}

// Реєстрація сторінки налаштувань плагіну
function dntrade_api_plugin_settings_page() {
    add_options_page(
        'DNTrade API Token',
        'DNTrade API',
        'manage_options',
        'dntrade-api-settings',
        'dntrade_api_render_settings_page'
    );
}
add_action('admin_menu', 'dntrade_api_plugin_settings_page');

// Реєстрація полів для сторінки налаштувань плагіну
function dntrade_api_plugin_settings_init() {
    register_setting(
        'dntrade_api_plugin_settings',
        'dntrade_api_plugin_settings'
    );

    add_settings_section(
        'dntrade_api_plugin_settings_section',
        'DNTrade API Token',
        'dntrade_api_plugin_settings_section_callback',
        'dntrade-api-settings'
    );

    add_settings_field(
        'dntrade_api_access_token',
        'Токен авторизації',
        'dntrade_api_access_token_callback',
        'dntrade-api-settings',
        'dntrade_api_plugin_settings_section'
    );
}
add_action('admin_init', 'dntrade_api_plugin_settings_init');

// Збереження налаштувань токену
function save_dntrade_api_token() {
    if (isset($_POST['dntrade_api_plugin_settings']['access_token'])) {
        update_option('dntrade_api_token', sanitize_text_field($_POST['dntrade_api_plugin_settings']['access_token']));
    }
}
add_action('admin_init', 'save_dntrade_api_token');
