<?php

if (!defined('ABSPATH')) die('Access denied.');

class Simba_Two_Factor_Authentication_Premium_Shortcodes {
	
	private $simba_tfa;
	
	/**
	 * Class constructor
	 *
	 * @param Object $simba_tfa
	 */
	public function __construct($simba_tfa) {
		
		$this->simba_tfa = $simba_tfa;
		
		$shortcodes = array('twofactor_user_settings_enabled', 'twofactor_user_qrcode', 'twofactor_user_emergencycodes', 'twofactor_user_advancedsettings', 'twofactor_user_privatekeys', 'twofactor_user_privatekeys_reset', 'twofactor_user_currentcode', 'twofactor_user_presstorefresh', 'twofactor_conditional');
		
		foreach ($shortcodes as $shortcode) {
			add_shortcode($shortcode, array($this, 'shortcode_'.$shortcode));
		}
		
	}
	
	/**
	 * Called by the twofactor_conditional shortcode
	 *
	 * @param Array $atts - shortcode attributes
	 * @param String|Null $content
	 *
	 * @return String - shortcode output
	 */
	public function shortcode_twofactor_conditional($atts, $content = null) {
		
		global $current_user;
		
		// Valid: available, unavailable, active, inactive (which implies available)
		$atts = shortcode_atts(array(
			'onlyif' => 'active'
		), $atts);
		
		if (!in_array($atts['onlyif'], array('active', 'inactive', 'available', 'unavailable'))) return '(twofactor_conditional: Unrecognised value for the "onlyif" parameter)';
		
		$condition = $atts['onlyif'];
		$condition_fulfilled = false;
		
		if ($this->simba_tfa->is_activated_for_user($current_user->ID)) {
			if ('available' == $condition) {
				$condition_fulfilled = true;
			} elseif ('inactive' == $condition && !$this->simba_tfa->is_activated_by_user($current_user->ID)) {
				$condition_fulfilled = true;
			} elseif ('active' == $condition  && $this->simba_tfa->is_activated_by_user($current_user->ID)) {
				$condition_fulfilled = true;
			}
		} elseif ('unavailable' == $condition) {
			$condition_fulfilled = true;
		}
		
		return $condition_fulfilled ? do_shortcode($content) : '';
		
	}
	
	public function shortcode_twofactor_user_presstorefresh($atts, $content = null) {
		global $current_user;
		
		if (!$this->simba_tfa->is_activated_for_user($current_user->ID)) {
			return $this->get_not_activated_message();
		} else {
			$this->simba_tfa->get_controller('totp')->add_footer();
			return '<span class="simbaotp_refresh">'.do_shortcode($content).'</span>';
		}
	}
	
	/**
	 * Get a message to display when TFA is not available for the current user
	 *
	 * @return String
	 */
	private function get_not_activated_message() {
		global $current_user;
		return empty($current_user->ID) ? '('.__('Not logged in.', 'all-in-one-wp-security-and-firewall-premium').')' : __('Two factor authentication is not available for your user.', 'all-in-one-wp-security-and-firewall-premium');
		
	}
	
	public function shortcode_twofactor_user_currentcode($atts, $content = null) {
		global $current_user;
		if (!$this->simba_tfa->is_activated_for_user($current_user->ID)) {
			return $this->get_not_activated_message();
		} else {
			return $this->simba_tfa->get_controller('totp')->current_otp_code($current_user->ID);
		}
		
	}
	
	public function shortcode_twofactor_user_privatekeys($atts, $content = null) {
		global $current_user;
		
		// Valid: full, plain, base32, base64
		$atts = shortcode_atts(array(
			'type' => 'full'
		), $atts);
		
		if (!$this->simba_tfa->is_activated_for_user($current_user->ID)) {
			return $this->get_not_activated_message();
		} else {
			ob_start();
			$this->simba_tfa->get_controller('totp')->print_private_keys($atts['type']);
			return ob_get_clean();
		}
	}
	
	public function shortcode_twofactor_user_privatekeys_reset($atts, $content = null) {
		global $current_user;
		
		if (!$this->simba_tfa->is_activated_for_user($current_user->ID)) {
			return $this->get_not_activated_message();
		} else {
			return $this->simba_tfa->get_controller('totp')->reset_link(false);
		}
	}
	
	public function shortcode_twofactor_user_advancedsettings($atts, $content = null) {
		global $current_user;
		
		if (!$this->simba_tfa->is_activated_for_user($current_user->ID)) {
			return $this->get_not_activated_message();
		} else {
			ob_start();
			$this->simba_tfa->get_controller('totp')->advanced_settings_box(array($this, 'save_settings_button'));
			$this->simba_tfa->load_frontend()->save_settings_javascript_output();
			return ob_get_clean();
			
		}
	}
	
	/**
	 * Print out a button for saving settings
	 */
	public function save_settings_button() {
		echo '<button style="margin-left: 4px;margin-bottom: 10px" class="simbatfa_settings_save button button-primary">'.__('Save Settings', 'all-in-one-wp-security-and-firewall-premium').'</button>';
	}
	
	public function shortcode_twofactor_user_emergencycodes($atts, $content = null) {
		global $current_user;
		
		if (!$this->simba_tfa->is_activated_for_user($current_user->ID)) {
			return $this->get_not_activated_message();
		} else {
			return $this->simba_tfa->get_controller('totp')->get_emergency_codes_as_string($current_user->ID, true);
		}
		
	}
	
	public function shortcode_twofactor_user_qrcode($atts, $content = null) {
		
		global $current_user;
		$totp_controller = $this->simba_tfa->get_controller('totp');
		
		$totp_controller->add_footer();
		
		ob_start();
		
		if (!$this->simba_tfa->is_activated_for_user($current_user->ID)) {
			echo $this->get_not_activated_message();
		} else {
			
			$url = preg_replace('/^https?:\/\//', '', site_url());
			
			$tfa_priv_key_64 = get_user_meta($current_user->ID, 'tfa_priv_key_64', true);
			
			if (!$tfa_priv_key_64) $tfa_priv_key_64 = $totp_controller->addPrivateKey($current_user->ID);
			
			$tfa_priv_key = trim($totp_controller->getPrivateKeyPlain($tfa_priv_key_64, $current_user->ID), "\x00..\x1F");
			
			$tfa_priv_key_32 = Base32::encode($tfa_priv_key);
			
			$algorithm_type = $totp_controller->get_user_otp_algorithm($current_user->ID);
			
			?>
			
			<p title="<?php echo sprintf(__("Private key: %s (base 32: %s)", 'all-in-one-wp-security-and-firewall-premium'), $tfa_priv_key, $tfa_priv_key_32);?>">
			<?php $qr_url = $totp_controller->tfa_qr_code_url($algorithm_type, $url, $tfa_priv_key) ?>
			<div class="simbaotp_qr_container" data-qrcode="<?php echo esc_attr($qr_url); ?>"></div>
			</p>
			
			<?php
		}
		
		return ob_get_clean();
		
	}
	
	/**
	 * Called by the twofactor_user_settings_enabled shortcode
	 *
	 * @param Array $atts - shortcode attributes
	 * @param String|Null $content
	 *
	 * @return String - shortcode output
	 */
	public function shortcode_twofactor_user_settings_enabled($atts, $content = null) {
		
		global $current_user;
		
		// Valid: show_current | require_current
		$atts = shortcode_atts(array(
			'style' => 'show_current'
		), $atts);
		
		ob_start();
		
		if (!$this->simba_tfa->is_activated_for_user($current_user->ID)) {
			echo $this->get_not_activated_message();
		} else {
			$this->simba_tfa->load_frontend()->settings_enable_or_disable_output($atts['style']);
		}
		
		return ob_get_clean();
		
	}

}
