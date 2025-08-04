<?php

if (!defined('ABSPATH')) die('Access denied.');

class Simba_Two_Factor_Authentication_Emergency_Codes {
	
	private $simba_tfa;
	
	/**
	 * Class constructor
	 *
	 * @param Object $simba_tfa
	 */
	public function __construct($simba_tfa) {
		
		$this->simba_tfa = $simba_tfa;
		
		add_filter('simba_tfa_emergency_codes_user_settings', array($this, 'simba_tfa_emergency_codes_user_settings'), 10, 2);
		
		// Add the codes to the variables passed via AJAX
		add_filter('simba_tfa_fetch_assort_vars', array($this, 'simba_tfa_fetch_assort_vars'), 10, 3);
		
		add_action('simba_tfa_adding_private_key', array($this, 'generate_emergency_codes'), 10, 4);
		add_action('simba_tfa_emergency_codes_empty', array($this, 'generate_emergency_codes'), 10, 4);
		add_action('simba_tfa_emergency_code_used', array($this, 'simba_tfa_emergency_code_used'), 10, 2);
	}
	
	/**
	 * Called by the WP action simba_tfa_emergency_code_used
	 *
	 * Let the user know that an emergency code was used, and that they may need to generate some more.
	 *
	 * @param Integer $user_id
	 * @param Array	  $emergency_codes
	 */
	public function simba_tfa_emergency_code_used($user_id, $emergency_codes) {
		
		$extra = empty($emergency_codes) ? "\r\n".__('Your must now go to the Two Factor Authentication settings to generate some new emergency codes if you wish to use any emergency codes in future.', 'all-in-one-wp-security-and-firewall-premium') : '';
		
		$user = get_userdata($user_id);
		if (!is_object($user) || empty($user->user_email)) return;
		
		wp_mail(
			$user->user_email,
			home_url().': '.__('emergency login code used', 'all-in-one-wp-security-and-firewall-premium'), 
			sprintf(__('An emergency code was used to login (username: %s) on this website: ', 'all-in-one-wp-security-and-firewall-premium'), $user->user_login).home_url()."\r\n\r\n".
			sprintf(__('You now have %s emergency code(s) remaining.', 'all-in-one-wp-security-and-firewall-premium'), count($emergency_codes))."\r\n".
			$extra
		);
		
	}
	
	/**
	 * Called by the WP action simba_tfa_adding_private_key
	 * When a new private key is added, we create some emergency codes.
	 *
	 * @param String $algorithm - 'totp' or 'hotp'
	 * @param Integer $user_id
	 * @param String $code - this is the stored private key (i.e. after encryption)
	 * @param Object $totp - TOTP class object
	 */
	public function generate_emergency_codes($algorithm, $user_id, $code, $totp) {
		if ('hotp' == $algorithm) {
			// Add some emergency codes as well. Take 8 digits from events 1,2,3
			$this->set_emergency_codes($user_id, array(
				$this->get_otp($algorithm, $user_id, $code, $totp, 1),
				$this->get_otp($algorithm, $user_id, $code, $totp, 2),
				$this->get_otp($algorithm, $user_id, $code, $totp, 3)
			));
		} else {
			// Add some emergency codes as well. The weakness of the random number routine here does not matter, since the private key is also used (for regular 6-digit codes, the time is completely known)
			$rand = time() + 30 * rand(0, 100000);
			$this->set_emergency_codes($user_id, array(
				$this->get_otp($algorithm, $user_id, $code, $totp, $rand),
				$this->get_otp($algorithm, $user_id, $code, $totp, $rand+120),
				$this->get_otp($algorithm, $user_id, $code, $totp, $rand+240),
			));
		}
	}
	
	/**
	 * Set the list of emergency codes
	 *
	 * @param Integer $user_id
	 * @param Array	  $codes
	 *
	 * @return Integer|Boolean - see https://developer.wordpress.org/reference/functions/update_user_meta/
	 */
	private function set_emergency_codes($user_id, $codes) {
		return update_user_meta($user_id, 'simba_tfa_emergency_codes_64', $codes);
	}
	
	/**
	 * Get the list of emergency codes
	 *
	 * @param String		  $algorithm - 'hotp' or 'totp
	 * @param Integer		  $user_id	 - WP user ID
	 * @param String		  $code - this is the stored private key (i.e. after encryption)
	 * @param Object		  $totp - Simba_TFA_Provider_totp
	 * @param Integer|Boolean $counter
	 *
	 * @return String
	 */
	private function get_otp($algorithm, $user_id, $code, $totp, $counter = false) {
		if ('hotp' == $algorithm) {
			return $totp->encryptString($totp->generateOTP($user_id, $code, 8, $counter), $user_id);
		} else {
			return $totp->encryptString($totp->generateOTP($user_id, $code, 8, $counter), $user_id);
		}
	}
	
	/**
	 * Runs upon the WP filter simba_tfa_emergency_codes_user_settings
	 *
	 * @param String  $m
	 * @param Integer $user_id
	 *
	 * @return String
	 */
	public function simba_tfa_emergency_codes_user_settings($m, $user_id) {
		
		$m = __("You have three emergency codes that can be used. Keep them in a safe place; if you lose your authentication device, then you can use them to log in.", 'all-in-one-wp-security-and-firewall-premium').' '.__("These can only be used once each.", 'all-in-one-wp-security-and-firewall-premium');
		$m .= '<br><br>';
		$m .= '<strong>'.__('Your emergency codes are:', 'all-in-one-wp-security-and-firewall-premium').'</strong> '.$this->simba_tfa->get_controller('totp')->get_emergency_codes_as_string($user_id, true);
		return $m;
	}
	
	/**
	 * Runs upon the WP action simba_tfa_fetch_assort_vars
	 *
	 * @param Array	  $vars
	 * @param Object  $totp_controller
	 * @param WP_User $current_user
	 */
	public function simba_tfa_fetch_assort_vars($vars, $totp_controller, $current_user) {
		$vars['emergency_str'] = $totp_controller->get_emergency_codes_as_string($current_user->ID);
		return $vars;
	}
	
}
