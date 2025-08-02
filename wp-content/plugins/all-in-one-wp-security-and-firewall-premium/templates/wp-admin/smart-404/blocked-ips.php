<?php if (!defined('AIOWPS_PREMIUM_PATH')) die('No direct access allowed'); ?>

<div class="aio_blue_box">
			<?php
			echo '<p>' . __('This tab displays the list of all permanently blocked IP addresses due to 404 events.', 'all-in-one-wp-security-and-firewall-premium') . '</p>' .
				'<p>' . __('NOTE: This feature does NOT use the .htaccess file to permanently block the IP addresses so it should be compatible with all web servers running WordPress.', 'all-in-one-wp-security-and-firewall-premium') . '</p>'.
				'<p>' . __('You can also view a list of all 404 events by clicking the following button:', 'all-in-one-wp-security-and-firewall-premium') . '</p>'.
				'<p><a class="button" href="admin.php?page=' . AIOWPSEC_BRUTE_FORCE_MENU_SLUG . '&tab=404-detection" target="_blank">' . __('View all 404 events', 'all-in-one-wp-security-and-firewall-premium') . '</a></p>';
			?>
		</div>

		<div class="postbox">
			<h3 class="hndle"><label
					for="title"><?php _e('Permanently blocked IP addresses', 'all-in-one-wp-security-and-firewall-premium');?></label>
			</h3>

			<div class="inside">
				<?php
				// Fetch, prepare, sort, and filter our data...
				$blocked_ip_list->prepare_items();
				?>
				<form id="tables-filter" method="get">
					<!-- For plugins, we also need to ensure that the form posts back to our current page -->
					<input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>"/>
					<?php
					$blocked_ip_list->search_box('Search', 'search_permanent_block');
					if (isset($_GET["tab"]) && preg_match('/^[-_a-z0-9]+$/i', $_GET['tab'])) {
						echo '<input type="hidden" name="tab" value="' . esc_attr($_GET["tab"]) . '" />';
					}
					?>
					<!-- Now we can render the completed list table -->
					<?php $blocked_ip_list->display(); ?>
				</form>
			</div>
		</div>
