<?php if (!defined('AIOWPS_PREMIUM_PATH')) die('No direct access allowed');

	if (empty($aio_wp_security_premium->configs->get_value('aiowps_premium_maxmind_key'))) {
			echo '<div class="notice notice-warning aio_yellow_box"><p>'. htmlspecialchars(__('Please add the MaxMind license key below to use the "Country Blocking" and "Smart 404" features.', 'all-in-one-wp-security-and-firewall-premium')).'</p></div>';
	}
	
?>

<h2> <?php _e('Integration', 'all-in-one-wp-security-and-firewall-premium'); ?> </h2>
		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php _e('MaxMind Geolocation', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
			<div class="inside">
				<p><?php _e('An integration for utilizing MaxMind to do Geolocation lookups, please note that this integration will only do country lookups.', 'all-in-one-wp-security-and-firewall-premium');?></p>
				<div class="aio_blue_box">
					<p>
					<?php
						_e('The key that will be used when dealing with MaxMind Geolocation services.', 'all-in-one-wp-security-and-firewall-premium');
						/* translators: %s: Maxmind documentation & signup url */
						printf(' ' . __('You can read how to generate a MaxMind Geolocation key from <a href="%s" target="_blank">here</a>.', 'all-in-one-wp-security-and-firewall-premium'), 'https://support.maxmind.com/hc/en-us/articles/4407111582235-Generate-a-License-Key');
						printf(' ' . __('To create a MaxMind account signup <a href="%s" target="_blank">here</a>.', 'all-in-one-wp-security-and-firewall-premium'), 'https://www.maxmind.com/en/geolite2/signup');
					?>
					</p>
				</div>
				<form action="" method="POST" autocomplete="off">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"> <?php _e('MaxMind License Key', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
							<td>
								<input type="password" autocomplete="new-password" id="aiowps_premium_maxmind_key" size="50" name="aiowps_premium_maxmind_key" class="input password-input" value="<?php if ($aio_wp_security_premium->configs->get_value('aiowps_premium_maxmind_key')) echo esc_attr($aio_wp_security_premium->configs->get_value('aiowps_premium_maxmind_key')); ?>">
								<button type="button" id="aiowps-premium-maxmind-key" class="button button-secondary wp-hide-pw hide-if-no-js aiowps_maxmind_show_hide_key" aria-label="<?php esc_attr_e('Show password', 'all-in-one-wp-security-and-firewall-premium');?>">
									<span class="dashicons dashicons-visibility" aria-hidden="true" id="aiowps_show_hide_icon"></span> <b class="aiowps_show_hide_text"><?php _e('Show', 'all-in-one-wp-security-and-firewall-premium');?></b>
								</button>
								
							</td>
							
						</tr>
					</table>
					<?php wp_nonce_field('aiowpsec-maxmind-key-save-nonce'); ?>
					<input type="submit" name="aiowps_save_maxmind_settings" value="<?php _e('Save Settings', 'all-in-one-wp-security-and-firewall-premium'); ?>" class="button-primary" />
				</form>
			</div>

		</div>	