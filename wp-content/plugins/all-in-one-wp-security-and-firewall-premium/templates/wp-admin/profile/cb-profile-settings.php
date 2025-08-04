<?php if (!defined('AIOWPS_PREMIUM_PATH')) die('No direct access.'); ?>

<h3><?php esc_html_e('Region-locked login', 'all-in-one-wp-security-and-firewall-premium'); ?></h3>
<table class="form-table">
	<tr>
		<th><label for="aiowps_cb_region_locked_login_enabled"><?php esc_html_e('Enable region-locked login', 'all-in-one-wp-security-and-firewall-premium'); ?></label></th>
		<td>
			<input type="checkbox" id="aiowps_cb_region_locked_login_enabled" name="aiowps_cb_region_locked_login_enabled" <?php checked($aiowps_cb_region_locked_login_enabled, true); ?>>
			<span class="description"><?php esc_html_e('Enable login restriction based on selected regions.', 'all-in-one-wp-security-and-firewall-premium'); ?></span>
			<input type="hidden" name="aiowps_cb_cookie_secret" id="aiowps_cb_cookie_secret" value="<?php echo wp_generate_uuid4(); ?>">
		</td>
	</tr>
	<tr>
		<th><label for="aiowps_cb_blocking_action"><?php esc_html_e('Blocking action', 'all-in-one-wp-security-and-firewall-premium'); ?></label></th>
		<td>
			<select id="aiowps_cb_blocking_action" name="aiowps_cb_blocking_action">
				<option value="redirect" <?php selected($aiowps_cb_blocking_action, 'redirect'); ?>><?php esc_html_e('redirect', 'all-in-one-wp-security-and-firewall-premium'); ?></option>
			</select>
			<span class="description"><?php esc_html_e('Set the type of blocking action you would like to perform', 'all-in-one-wp-security-and-firewall-premium'); ?></span>
		</td> 
	</tr>
	
	<?php
		$countries_array = $aio_wp_security_premium->country_tasks_obj->country_codes;

		$redirect_url = '';
		if ('1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_login_global_url_enabled')) {
			$redirect_url = $aio_wp_security_premium->configs->get_value('aiowps_cb_login_global_url');
		} elseif ("" != $aiowps_cb_redirect_url) {
			$redirect_url = $aiowps_cb_redirect_url;
		} else {
			$redirect_url = "http://127.0.0.1";
		}
	?>

	<tr>
		<th><label for="aiowps_cb_redirect_url"><?php esc_html_e('Redirect URL', 'all-in-one-wp-security-and-firewall-premium'); ?></label></th>
		<td>
		<?php
		$is_global_url_enabled = ('1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_login_global_url_enabled'));
		$description = $is_global_url_enabled ? esc_html__('Global settings applied', 'all-in-one-wp-security-and-firewall-premium') : esc_html__('Set the value for the URL where you want to send users to', 'all-in-one-wp-security-and-firewall-premium');
		$disabled_attribute = $is_global_url_enabled ? 'disabled' : '';
		?>

		<input type="text" size="50" name="aiowps_cb_redirect_url" value="<?php echo esc_attr($redirect_url); ?>" <?php echo $disabled_attribute; ?>>
		<span class="description"><?php echo $description; ?></span>
		</td> 
	</tr>
	<tr>
		<th><label><?php esc_html_e('Blocked countries', 'all-in-one-wp-security-and-firewall-premium'); ?></label></th>
		<td>
			<input type="checkbox" id="aiowps_cb_general_select_all" name="aiowps_cb_general_select_all" value="1" <?php if (count($countries_array) === count($aiowps_cb_blocked_countries)) echo 'checked="checked"';?>><label for="aiowps_cb_general_select_all"><?php esc_html_e('Select/De-select All', 'all-in-one-wp-security-and-firewall-premium'); ?></label>
			<ul class="aiowps-checkbox-grid">
				<?php
				asort($countries_array);
				foreach ($countries_array as $c_code => $c_name) {
					$checked = in_array($c_code, $aiowps_cb_blocked_countries) ? ' checked="checked"' : '';
					echo '<li><input type="checkbox" class="aiowps_country_checkbox" id="aiowps_country_checkbox_' . esc_attr($c_code) . '" name="aiowps_cb_blocked_countries[]" ' . $checked . ' value="' . esc_attr($c_code) . '" ><label for="aiowps_country_checkbox_' . esc_attr($c_code) . '">' . esc_html($c_name) . '</label></li>';
				}
				?>
			</ul>
			<span class="description"><?php esc_html_e('Select countries to block.', 'all-in-one-wp-security-and-firewall-premium'); ?></span>
		</td>
	</tr>
</table>
<script>
	jQuery(function($) {
		jQuery('.form-table').on('click', '#aiowps_cb_general_select_all', function(){
		jQuery('.aiowps_country_checkbox').prop('checked', this.checked);
		});
	});
</script>