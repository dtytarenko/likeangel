<?php

if (!defined('ABSPATH')) die('Access denied.');

class Simba_Two_Factor_Authentication_Premium {

	private $simba_tfa;
	
	private $login_forms = array();

	/**
	 * Class constructor
	 *
	 * @param Object $simba_tfa - Simba_Two_Factor_Authentication(_version) main plugin class
	 */
	public function __construct($simba_tfa) {
		
		$this->simba_tfa = $simba_tfa;
		
		add_action('plugins_loaded', array($this, 'plugins_loaded'));
		
		add_filter('simba_tfa_support_url', function() { return 'https://www.simbahosting.co.uk/s3/support/tickets/'; });
		
		add_filter('simba_tfa_login_enqueue_localize', array($this, 'simba_tfa_login_enqueue_localize'));
		
		add_filter('simba_tfa_tfa_from_password', array($this, 'simba_tfa_tfa_from_password'), 10, 2);
	}

	/**
	 * Runs upon the WP action plugins_loaded
	 */
	public function plugins_loaded() {
		
		// WP-Members support; also used by bbPress
		add_action('login_form', array($this->simba_tfa, 'login_enqueue_scripts'));
		add_action('elementor/element/login/section_login_content/after_section_start', array($this->simba_tfa, 'login_enqueue_scripts'));
		
		// Ultimate Member
		add_filter('um_custom_authenticate_error_codes', function($codes) {
			// This plugin requires you do indicate all WP_Error codes that have messages that should be displayed to the user - otherwise, it just displays a message that the password was wrong (which may be untrue)
			$codes[] = 'authentication_failed';
			$codes[] = 'tfa_required';
			$codes[] = 'tfa_user_not_found';
			$codes[] = 'failed_to_get_priv_keys';
			return $codes;
		});
		add_action('um_pre_login_shortcode', array($this->simba_tfa, 'login_enqueue_scripts'));
		
		// Mark login forms from the Gravity Forms User Registration Add-On
		add_filter('gform_userregistration_login_form', function($form) {
			$form['_simba_tfa_is_login_form'] = true;
			return $form;
		});
		
		// Enqueue scripts for a Gravity Forms User Registration Add-On form
		add_action('gform_enqueue_scripts', function($form) {
			if (is_array($form) && !empty($form['_simba_tfa_is_login_form'])) {
				$this->simba_tfa->login_enqueue_scripts();
			}
		});
		
		$load_classes = apply_filters('simba_tfa_premium_load_classes', array(
			'Simba_Two_Factor_Authentication_Premium_Shortcodes' => 'shortcodes.php',
			'Simba_Two_Factor_Authentication_Emergency_Codes' => 'emergency-codes.php',
			'Simba_Two_Factor_Authentication_Trusted_Devices' => 'trusted-devices.php',
			'Simba_Two_Factor_Authentication_WooCommerce_Bot_Protection' => 'woocommerce-bot-protection.php',
			'Simba_Two_Factor_Authentication_Required_For_User' => 'required-for-user.php',
			'Simba_Two_Factor_Authentication_WooCommerce_Account_Settings' => 'woocommerce-account-settings.php',
			'Simba_Two_Factor_Authentication_Administrate_Other_Users' => 'administrate-other-users.php',
		));
		
		foreach ($load_classes as $class => $file) {
			if (!class_exists($class)) require __DIR__.'/'.$file;
			new $class($this->simba_tfa);
		}
		
	}
	
	/**
	 * Runs upon the WP filter simba_tfa_login_enqueue_localize. Added April 2020.
	 *
	 * @param Array $localize
	 *
	 * @return Array
	 */
	public function simba_tfa_login_enqueue_localize($localize) {
		// Support Elementor login forms
		$localize['login_form_selectors'] .= ', form.elementor-login';
		// Support Gravity Forms User Registration Add-On forms
		$localize['login_form_selectors'] .= ', form.elementor-login, .gf_login_form form';
		$localize['login_form_selectors'] .= ', .um-login form';
		return $localize;
	}
	
	/**
	 * Support appending the TFA code to the password
	 *
	 * @param Boolean|Array $from_password
	 * @param String		$password
	 *
	 * @return Boolean|Array
	 */
	public function simba_tfa_tfa_from_password($from_password, $password) {
		if (!is_array($from_password) && preg_match('/([0-9]{6})$/', $password)) {
			return array('password' => substr($password, 0, strlen($password)-6), 'tfa_code' => substr($password, -6));
		}
		return $from_password;
	}

}
