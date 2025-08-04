<?php if (!defined('AIOWPS_PREMIUM_PATH')) die('No direct access.'); ?>

<h2><?php _e('Country blocking login whitelist', 'all-in-one-wp-security-and-firewall-premium'); ?></h2>
		<div class="aio_blue_box">
			<?php
			echo '<p>'.__('This feature gives you the option of allowing certain IP addresses or ranges from blocked countries to have access to your WordPress site.', 'all-in-one-wp-security-and-firewall-premium').'</p>';
			?>
		</div>
		<div class="postbox">
		<h3 class="hndle"><label for="title"><?php _e('Country blocking IP whitelist settings', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
		<div class="inside">
		<form action="" method="POST">
		<?php wp_nonce_field('aiowpsec-cb-whitelist-settings-nonce'); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Enable country blocking IP whitelist', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
				<td>
					<div class="aiowps_switch_container">
						<?php AIOWPSecurity_Utility_UI::setting_checkbox(__('Enable this for the whitelisting of selected IP addresses specified in the settings below', 'all-in-one-wp-security-and-firewall-premium'), 'aiowps_cb_enable_whitelisting', '1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_enable_whitelisting')); ?>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Enter whitelisted IP addresses:', 'all-in-one-wp-security-and-firewall-premium'); ?></th>
				<td>
					<textarea name="aiowps_cb_allowed_ip_addresses" rows="5" cols="50"><?php echo esc_textarea(wp_unslash(-1 == $result ? $_POST['aiowps_cb_allowed_ip_addresses'] : $aio_wp_security_premium->configs->get_value('aiowps_cb_allowed_ip_addresses'))); ?></textarea>
					<br />
					<span class="description"><?php _e('Enter one or more IP addresses or IP ranges you wish to include in your whitelist.', 'all-in-one-wp-security-and-firewall-premium');?><?php _e('The addresses specified here will have access to your site even if they come from a country which is blocked.', 'all-in-one-wp-security-and-firewall-premium');?></span>
					<?php $aio_wp_security->include_template('info/ip-address-ip-range-info.php');?>

				</td>
			</tr>
		</table>
		<input type="submit" name="aiowps_save_cb_whitelist_settings" value="<?php _e('Save settings', 'all-in-one-wp-security-and-firewall-premium'); ?>" class="button-primary" />
		</form>
		</div></div>