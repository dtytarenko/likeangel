<?php if (!defined('AIOWPS_PREMIUM_PATH')) die('No direct access.'); ?>

<h2><?php _e('Secondary country blocking settings', 'all-in-one-wp-security-and-firewall-premium'); ?></h2>
		<form action="" method="POST">
		<?php wp_nonce_field('aiowpsec-secondary-country-blocking-settings-nonce'); ?>

		<div class="postbox">
		<h3 class="hndle"><label for="title"><?php _e('Enable secondary country blocking', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
		<div class="inside">
			<div class="aio_blue_box">
				<?php
				echo '<p>'.__('The secondary settings on this page allow you to further refine how you block visitors based on the specific pages and posts they try to visit.', 'all-in-one-wp-security-and-firewall-premium').
				'<br />'.__('The countries you selected in the "General settings" tab will be blocked from the whole site (You can think of the "General settings" as the first line of blocking defence).', 'all-in-one-wp-security-and-firewall-premium').
				'<br />'.__('The settings on this page are for those countries which have not been blocked sitewide.', 'all-in-one-wp-security-and-firewall-premium') . ' ' . __('These settings allow you to select which countries will be blocked or allowed access to specific pages or posts specified in the configuration below (You can think of this as the second line of blocking defence)', 'all-in-one-wp-security-and-firewall-premium').
				'<br />'.__('This feature can be very useful for eCommerce situations such as when merchants wish to confine their online sales to people from certain countries due to shipping or tax constraints.', 'all-in-one-wp-security-and-firewall-premium').'</p>';
				// '<br />'.__('NOTE: These settings will apply to those visitors who were not blocked by the "General settings" configuration,  If your "General settings" config is disabled or no country is selected, then the "Secondary settings" will apply to all visitors.', 'all-in-one-wp-security-and-firewall-premium').'</p>';
				?>
			</div>
			<div class="aiowps_cb_yellow_box">
				<?php
				echo __('NOTE: These settings will apply to visitors who are not blocked by the "General settings" configuration.', 'all-in-one-wp-security-and-firewall-premium') . ' ' . __('If your "General settings" configuration is disabled or no country is selected, then the "Secondary settings" will apply to all visitors.', 'all-in-one-wp-security-and-firewall-premium');
				?>
			</div>
			<?php
			$saved_blocked_countries = '' == $aio_wp_security_premium->configs->get_value('aiowps_cb_secondary_blocked_countries') ? array() : $aio_wp_security_premium->configs->get_value('aiowps_cb_secondary_blocked_countries');
			$countries_array = $aio_wp_security_premium->country_tasks_obj->country_codes;
			?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Enable secondary country blocking', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
					<td>
						<div class="aiowps_switch_container">
							<?php AIOWPSecurity_Utility_UI::setting_checkbox(__('Enable this if you want the secondary country blocking feature.', 'all-in-one-wp-security-and-firewall-premium'), 'aiowps_cb_enable_secondary_country_blocking', '1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_enable_secondary_country_blocking')); ?>
						</div>
					</td>
				</tr>
			</table>
		</div></div>
		<div class="postbox">
		<h3 class="hndle"><label for="title"><?php _e('Country page blocking options', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
		<div class="inside">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Blocking action', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
				<td>
					<select id="aiowps_cb_secondary_blocking_action" name="aiowps_cb_secondary_blocking_action">
						<option value="0" <?php selected($aio_wp_security_premium->configs->get_value('aiowps_cb_secondary_blocking_action'), '0'); ?>><?php _e('redirect', 'all-in-one-wp-security-and-firewall-premium'); ?></option>
					</select>
				<span class="description"><?php _e('Set the type of blocking action you would like to perform', 'all-in-one-wp-security-and-firewall-premium'); ?></span>
				</td> 
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Enter post/page IDs to protect:', 'all-in-one-wp-security-and-firewall-premium'); ?></th>
				<td>
					<textarea name="aiowps_secondary_cb_protected_post_ids" rows="5" cols="50"><?php echo $aio_wp_security_premium->configs->get_value('aiowps_secondary_cb_protected_post_ids'); ?></textarea>
					<br />
					<span class="description"><?php _e('Enter one or more page/post IDs you wish to block/redirect certain countries for (Each entry must be on a new line).', 'all-in-one-wp-security-and-firewall-premium'); ?></span>
					<span class="aiowps_more_info_anchor"><span class="aiowps_more_info_toggle_char">+</span><span class="aiowps_more_info_toggle_text"><?php _e('More info', 'all-in-one-wp-security-and-firewall-premium'); ?></span></span>
					<div class="aiowps_more_info_body">
							<?php
							echo '<p class="description">'.__('Each post/page ID must be on a new line.', 'all-in-one-wp-security-and-firewall-premium').'</p>';
							echo '<p class="description">'.__('To find the ID simply edit the post or page and look in your browser bar, the ID is value after the "post." parameter.', 'all-in-one-wp-security-and-firewall-premium').'</p>';
							echo '<p class="description">'.__('Example: wp-admin/post.php?post=<strong>528</strong>&action=edit', 'all-in-one-wp-security-and-firewall-premium').'</p>';
							echo '<p class="description">'.__('The post/page ID in this example is 528.', 'all-in-one-wp-security-and-firewall-premium').'</p>';
							?>
					</div>

				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Redirect URL', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
				<td><input type="text" size="50" name="aiowps_cb_secondary_redirect_url" value="<?php echo $aio_wp_security_premium->configs->get_value('aiowps_cb_secondary_redirect_url'); ?>" />
				<span class="description"><?php _e('Set the value for the URL where you want to send the blocked visitor to', 'all-in-one-wp-security-and-firewall-premium'); ?></span>
				<span class="aiowps_more_info_anchor"><span class="aiowps_more_info_toggle_char">+</span><span class="aiowps_more_info_toggle_text"><?php _e('More info', 'all-in-one-wp-security-and-firewall-premium'); ?></span></span>
				<div class="aiowps_more_info_body">
						<?php
						echo '<p class="description">'.__('Example: If you sell products which ship only in your country, you could create a special page for visitors from other countries.', 'all-in-one-wp-security-and-firewall-premium'). ' ' . __('On this page you can explain why they can\'t view your store pages.', 'all-in-one-wp-security-and-firewall-premium').'</p>';
						echo '<p class="description">'.__('You would then enter the URL of that page in this setting.', 'all-in-one-wp-security-and-firewall-premium').'</p>';
						?>
				</div>
				</td> 
			</tr>
			
			<tr valign="top">
				<th scope="row"><?php _e('Select countries to block/redirect', 'all-in-one-wp-security-and-firewall-premium'); ?>:</th>
			<td>
				<input type="checkbox" id="aiowps_cb_secondary_select_all" name="aiowps_cb_secondary_select_all" value="1" <?php if (count($saved_blocked_countries) === count($countries_array)) echo 'checked="checked"';?>/><label for="aiowps_cb_secondary_select_all">Select/De-select All</label>
			<ul class="aiowps-checkbox-grid">
				<?php
				asort($countries_array);
				foreach ($countries_array as $c_code => $c_name) {
					if (in_array($c_code, $saved_blocked_countries)) {
						$check_txt = ' checked="checked"';
					} else {
						$check_txt = '';
					}
					echo '<li><input type="checkbox" class="aiowps_secondary_country_checkbox" id="aiowps_secondary_country_checkbox_' . $c_code . '" name="aiowps_secondary_country_checkbox_' . $c_code . '"'.$check_txt.' value="' . $c_code . '" /><label for="aiowps_secondary_country_checkbox_' . $c_code . '">' . $c_name . '</label></li>';
				}
				?>
			</ul>
			</td>
			</tr>
		</table>
		</div></div>
		<input type="submit" name="aiowps_save_secondary_cb_settings" value="<?php _e('Save settings', 'all-in-one-wp-security-and-firewall-premium'); ?>" class="button-primary" />
		</form>
		<script type="text/javascript">
		jQuery(function($) {

			$('.form-table').on('click', '#aiowps_cb_secondary_select_all', function(){
				if (this.checked) {
					$('.aiowps_secondary_country_checkbox').prop('checked', true);
				} else {
					$('.aiowps_secondary_country_checkbox').prop('checked', false);
				}
			});
		});
		</script>