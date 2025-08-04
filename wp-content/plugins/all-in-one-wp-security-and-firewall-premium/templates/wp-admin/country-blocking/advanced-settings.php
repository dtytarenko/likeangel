<?php if (!defined('AIOWPS_PREMIUM_PATH')) die('No direct access.'); ?>

<h2><?php _e('Country blocking advanced settings', 'all-in-one-wp-security-and-firewall-premium'); ?></h2>
		<div class="postbox">
		<h3 class="hndle"><label for="title"><?php _e('Use AJAX for country blocking checks', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
		<div class="inside">
		<div class="aio_blue_box">
			<?php
			echo '<p>'.__('If you are using a caching solution on your website such as WP Optimize or similar, then you will need to enable this feature.', 'all-in-one-wp-security-and-firewall-premium').'</p>';
			?>
		</div>
			
		<form action="" method="POST">
		<?php wp_nonce_field('aiowpsec-cb-ip-settings-nonce'); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Enable country blocking AJAX', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
				<td>
					<div class="aiowps_switch_container">
						<?php AIOWPSecurity_Utility_UI::setting_checkbox(__('Enable this if you are using caching (such as WP Optimize etc.)', 'all-in-one-wp-security-and-firewall-premium'), 'aiowps_cb_ajax_enabled', '1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_ajax_enabled')); ?>
					</div>
				</td>
			</tr>
		</table>
		<input type="submit" name="aiowps_save_cb_advanced_settings" value="<?php _e('Save settings', 'all-in-one-wp-security-and-firewall-premium');?>" class="button-primary" />
		</form>
		</div></div>