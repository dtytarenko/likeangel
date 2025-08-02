<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!defined('WP_CLI') || !WP_CLI || !class_exists('WP_CLI_Command')) return;

/**
 * AIOWPS_Wpcli_Commands class for aiowps command and subcommand to run from command line interface.
 *
 * @package WordPress
 *
 * @access public
 */
class AIOS_CLI_Command extends WP_CLI_Command {
	/**
	 * Set command to execute based on passed config name and paramters // here php doc fromat used for the --help descripption.
	 * wp aios set security-features off
	 * wp aios set firewall off
	 * wp aios set firewall on
	 * wp aios set login-lockout on
	 * wp aios set login-lockout off
	 * wp aios set debug on
	 * wp aios set debug off
	 * wp aios set php-backtrace-in-email on
	 * wp aios set php-backtrace-in-email off
	 * wp aios set simplemath-recaptcha on
	 * wp aios set simplemath-recaptcha off
	 * wp aios set google-recaptcha on
	 * wp aios set google-recaptcha off
	 * wp aios set google-recaptcha-site-key AAxxxxxxxxxxxxxxxxxxxxx
	 * wp aios set google-recaptcha-secret-key Axxxxxxxxxxxxxxxxxxxxx
	 * wp aios set turnstile-recaptcha on
	 * wp aios set turnstile-recaptcha off
	 * wp aios set turnstile-recaptcha-site-key 0x4Axxxxxxxxxxxxxxxxxxxxx
	 * wp aios set turnstile-recaptcha-secret-key 0x4xxxxxxxxxxxxxxxxxxxxx
	 * wp aios set maintenance-mode off
	 * wp aios set copy-protection on
	 * wp aios set copy-protection off
	 *
	 * @param array $args
	 * @global type $aio_wp_security
	 * @return void
	 */
	public function set($args) {
		global $aio_wp_security;
		$message = '';
		$allowed_configs = array(
			'copy-protection',
			'login-lockout',
			'debug',
			'php-backtrace-in-email',
			'maintenance-mode',
			'simplemath-recaptcha',
			'google-recaptcha',
			'google-recaptcha-site-key',
			'google-recaptcha-secret-key',
			'turnstile-recaptcha',
			'turnstile-recaptcha-site-key',
			'turnstile-recaptcha-secret-key',
			'security-features',
			'firewall',
		);
				
		if (!isset($args[0])) {
			WP_CLI::line(__('set command called without an option name.', 'all-in-one-wp-security-and-firewall-premium'));
			return;
		}
		
		if (!in_array($args[0], $allowed_configs)) {
			WP_CLI::line(sprintf(__('set %s command not allowed.', 'all-in-one-wp-security-and-firewall-premium'), $args[0]));
			return;
		}
		
		if (!isset($args[1])) {
			WP_CLI::line(__('set command called without an option value.', 'all-in-one-wp-security-and-firewall-premium'));
			return;
		}
		
		if ($this->is_on($args[1]) && 'copy-protection' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_'.$this->field_name($args[0]), '1', true);
		} elseif ($this->is_off($args[1]) && 'copy-protection' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_'.$this->field_name($args[0]), '', true);
		} elseif ($this->is_on($args[1]) && ('debug' === $args[0] || 'php-backtrace-in-email' === $args[0])) {
			$aio_wp_security->configs->set_value('aiowps_enable_'.$this->field_name($args[0]), '1', true);
		} elseif ($this->is_off($args[1]) && ('debug' === $args[0] || 'php-backtrace-in-email' === $args[0])) {
			$aio_wp_security->configs->set_value('aiowps_enable_'.$this->field_name($args[0]), '', true);
		} elseif ($this->is_on($args[1]) && 'login-lockout' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_enable_login_lockdown', '1', true);
		} elseif ($this->is_off($args[1]) && 'login-lockout' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_enable_login_lockdown', '', true);
		} elseif ($this->is_off($args[1]) && 'maintenance-mode' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_site_lockout', '', true);
		} elseif ($this->is_on($args[1]) && 'firewall' === $args[0]) {
			$result = AIOWPSecurity_Settings_Tasks::enable_basic_firewall();
			$message = $this->get_message($result);
		} elseif ($this->is_off($args[1]) && 'firewall' === $args[0]) {
			$result = AIOWPSecurity_Settings_Tasks::disable_all_firewall_rules();
			$message = $this->get_message($result);
		} elseif ($this->is_off($args[1]) && 'security-features' === $args[0]) {
			$result = AIOWPSecurity_Settings_Tasks::disable_all_security_features();
			$message = $this->get_message($result);
		} elseif ($this->is_on($args[1]) && 'simplemath-recaptcha' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_default_captcha', 'simple-math', true);
			$aio_wp_security->configs->set_value('aiowps_enable_login_captcha', '1', true);
		} elseif ($this->is_off($args[1]) && 'simplemath-recaptcha' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_default_captcha', 'none', true);
			$aio_wp_security->configs->set_value('aiowps_enable_login_captcha', '', true);
		} elseif ($this->is_on($args[1]) && 'google-recaptcha' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_default_captcha', 'google-recaptcha-v2', true);
			$aio_wp_security->configs->set_value('aiowps_enable_login_captcha', '1', true);
		} elseif ($this->is_off($args[1]) && 'google-recaptcha' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_default_captcha', 'none', true);
			$aio_wp_security->configs->set_value('aiowps_enable_login_captcha', '', true);
		} elseif ('google-recaptcha-site-key' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_recaptcha_site_key', $args[1], true);
		} elseif ('google-recaptcha-secret-key' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_recaptcha_secret_key', $args[1], true);
		} elseif ($this->is_on($args[1]) && 'turnstile-recaptcha' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_default_captcha', 'cloudflare-turnstile', true);
			$aio_wp_security->configs->set_value('aiowps_enable_login_captcha', '1', true);
		} elseif ($this->is_off($args[1]) && 'turnstile-recaptcha' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_default_captcha', 'none', true);
			$aio_wp_security->configs->set_value('aiowps_enable_login_captcha', '', true);
		} elseif ('turnstile-recaptcha-site-key' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_turnstile_site_key', $args[1], true);
		} elseif ('turnstile-recaptcha-secret-key' === $args[0]) {
			$aio_wp_security->configs->set_value('aiowps_turnstile_secret_key', $args[1], true);
		} else {
			$message = sprintf(__('Command to set %s with value %s is not allowed.', 'all-in-one-wp-security-and-firewall-premium'), $args[0], $args[1]);
		}
		
		if ('' !== $message) {
			WP_CLI::line($message);
		} else {
			WP_CLI::line(sprintf(__('Success: set %s to value %s.', 'all-in-one-wp-security-and-firewall-premium'), $args[0], $args[1]));
		}
	}
	
	/**
	 * Message string
	 *
	 * @param array $result
	 * @return string
	 */
	private function get_message($result) {
		return isset($result['updated']) ? $result['updated'] : (is_array($result['error']) ? implode(" ", $result['error']) : $result['error']);
	}
	
	
	/**
	 * Check if on command called or not
	 *
	 * @param string $arg
	 * @return boolean
	 */
	private function is_on($arg) {
		return ('on' === $arg) ? true : false;
	}
	
	/**
	 * Check if off command called or not
	 *
	 * @param string $arg
	 * @return boolean
	 */
	private function is_off($arg) {
		return ('off' === $arg) ? true : false;
	}
	
	/**
	 * Convert command to related field name
	 *
	 * @param string $str
	 * @return string
	 */
	private function field_name($str) {
		return str_replace('-', '_', $str);
	}
	
	/**
	 * Reset command to execute reset all settings
	 * wp aios reset --all
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @return void
	 */
	public function reset($args, $assoc_args) {
		if (!isset($args[0]) && isset($assoc_args['all']) && true === $assoc_args['all']) {
			WP_CLI::confirm(__('Do you want to proceed?', 'all-in-one-wp-security-and-firewall-premium'));
			$result = AIOWPSecurity_Settings_Tasks::reset_all_settings();
			$message = $this->get_message($result);
			WP_CLI::line($message);
		} else {
			WP_CLI::line(__('reset --all is only supported right now.', 'all-in-one-wp-security-and-firewall-premium'));
		}
	}
}

WP_CLI::add_command('aios', 'AIOS_CLI_Command');
