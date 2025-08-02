<?php

if (!defined('ABSPATH')) die('Access denied.');

class Simba_Two_Factor_Authentication_WooCommerce_Account_Settings {
	
	private $simba_tfa;
	
	/**
	 * Class constructor
	 *
	 * @param Object $simba_tfa
	 */
	public function __construct($simba_tfa) {
		
		$this->simba_tfa = $simba_tfa;
		add_action('woocommerce_after_edit_account_form', array($this, 'woocommerce_after_edit_account_form'));
		add_filter('simba_tfa_settings_woocommerce', array($this, 'simba_tfa_settings_woocommerce'));
	}
	
	/**
	 * Runs upon the WP action woocommerce_after_edit_account_form
	 */
	public function woocommerce_after_edit_account_form() {
		
		if (!$this->simba_tfa->get_option('tfa_wc_add_section')) return;
		
		echo apply_filters('simba_tfa_woocommerce_after_edit_account_form', '<div id="simba_tfa_woocommerce_user_settings"><h2>'.__('Two factor settings', 'all-in-one-wp-security-and-firewall-premium').'</h2>'.do_shortcode('[twofactor_user_settings]').'</div>');
		
	}
	
	/**
	 * Runs upon the WP filter simba_tfa_settings_woocommerce
	 *
	 * @return String - filtered value
	 */
	public function simba_tfa_settings_woocommerce() {
		
		if (!function_exists('WC')) return;
		
		$wc_add_section = $this->simba_tfa->get_option('tfa_wc_add_section');
		
		ob_start();
		?>
		
		<form method="post" action="options.php" style="margin-top: 12px">
		<?php settings_fields('simba_tfa_woocommerce_group'); ?>
		<?php _e('Choose whether you want two-factor authentication settings to appear in the WooCommerce account area.', 'all-in-one-wp-security-and-firewall-premium'); ?>
		<p>
		<input type="checkbox" id="tfa_wc_add_section" name="tfa_wc_add_section" value="1" <?php echo $wc_add_section ? 'checked="checked"' :''; ?>> <label for="tfa_wc_add_section"><?php echo htmlspecialchars(__('Add two-factor settings to the WooCommerce "My Account" area', 'all-in-one-wp-security-and-firewall-premium')); ?></label>
		
		<?php do_action('simba_tfa_woocommerce_after_account_setting'); ?>
		
		</p>
		<?php submit_button(); ?>
		</form>
		<?php
		
		return ob_get_clean();
	}
	
}
