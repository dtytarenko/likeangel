<?php

namespace WCPM\Classes\Admin;

use WCPM\Classes\Helpers;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Notifications {

	private static $instance;

	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {

		add_action('admin_enqueue_scripts', [ __CLASS__, 'inject_admin_scripts' ]);

		add_action('admin_enqueue_scripts', [ __CLASS__, 'wpm_admin_css' ]);

		add_action('admin_notices', function () {
			if (Environment::is_allowed_notification_page()) {
				if (wpm_fs()->is__premium_only()) {
					self::license_expired_warning__premium_only();
				}

				if (defined('EXPERIMENTAL_PMW_OPPORTUNITIES_TAB') && EXPERIMENTAL_PMW_OPPORTUNITIES_TAB) {
					self::opportunities_notification();
				}
			}
		});
	}

	public static function inject_admin_scripts() {
		wp_enqueue_script(
			'pmw-notifications',
			PMW_PLUGIN_DIR_PATH . 'js/admin/notifications.js',
			[ 'jquery' ],
			PMW_CURRENT_VERSION,
			true
		);

		wp_localize_script(
			'pmw-notifications',
			'pmwNotificationsApi',
			[
				'root'  => esc_url_raw(rest_url()),
				'nonce' => wp_create_nonce('wp_rest'),
			]
		);
	}

	public static function wpm_admin_css( $hook_suffix ) {

		// Only output the css on PMW pages and the order page
//		if (self::is_not_allowed_to_show_pmw_notification()) {
//			return;
//		}

		wp_enqueue_style(
			'pmw-notifications-css',
			PMW_PLUGIN_DIR_PATH . 'css/notifications.css',
			[],
			PMW_CURRENT_VERSION
		);
	}

	// Only show the notification on the dashboard and on the PMW settings page
	private static function is_not_allowed_to_show_pmw_notification() {
		return !self::is_allowed_to_show_pmw_notification();
	}

	private static function is_allowed_to_show_pmw_notification() {

		if (!self::can_current_page_show_pmw_notification()) {
			return false;
		}

		// Only show the notifications to admins and shop managers
		$user = wp_get_current_user();
		if (!in_array('administrator', $user->roles, true) && !in_array('shop_manager', $user->roles, true)) {
			return false;
		}

		return true;
	}

	public static function can_current_page_show_pmw_notification() {

		global $hook_suffix;

		$allowed_pages = [ 'page_wpm', 'index.php', 'dashboard' ];

		/**
		 * We can't use in_array because woocommerce_page_wpm
		 * is malformed on certain installs, but the substring
		 * page_wpm is fine. So we need to search for partial
		 * matches.
		 * */
		foreach ($allowed_pages as $allowed_page) {
			if (strpos($hook_suffix, $allowed_page) !== false) {
				return true;
			}
		}

		return false;
	}

	public static function payment_gateway_accuracy_warning() {

		// Only show the warning on the dashboard and on the PMW settings page
		if (self::is_not_allowed_to_show_pmw_notification()) {
			return;
		}

		$pg_report = get_transient('pmw_tracking_accuracy_analysis_weighted');

		// Only run if the weighted payment gateway analysis has been created
		if (!$pg_report) {
			return;
		}

		// Only run if the total of the PGs is in status warning

		// Only run if the user has not dismissed the notification for a specific time period

		?>
		<div class="pmw-payment-gateway-notification notice notice-error is-dismissible">
			<p>
				<?php
				esc_html_e('Payment Gateway Accuracy Warning', 'woocommerce-google-adwords-conversion-tracking-tag');
				?>
			</p>
		</div>
		<?php
	}

	public static function plugin_is_incompatible( $name, $version, $slug, $link = '', $wpm_doc_link = '' ) {
		?>
		<div
				class="notice notice-error <?php echo esc_js($slug); ?>-incompatible-plugin-error"
		>
			<p>
				<span>
					<?php esc_html_e('The following plugin is not compatible with the Pixel Manager for WooCommerce: ', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</span>
				<span>
					<a href="<?php echo esc_url($link); ?>" target="_blank">
						<?php echo esc_js($name); ?>
					</a>
					(<?php esc_html_e('Version', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>: <?php echo esc_js($version); ?>)
				</span>
				<br>
				<span>

					<?php esc_html_e('Please disable the plugin as soon as possible.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</span><br>
				<span>

					<?php esc_html_e('Find more information about the the reason in our documentation: ', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</span><a
						href="<?php echo esc_url($wpm_doc_link); ?>"
						target="_blank">
					<?php esc_html_e('Learn more', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</a><br>
			</p>
			<p>

			</p>
			<div style="margin-bottom: 10px; display: flex; justify-content: space-between">

				<div id="<?php echo esc_js($slug); ?>-incompatible-plugin-error-dismissal-button"
					 class="button incompatible-plugin-error-dismissal-button"
					 style="white-space:normal;"
					 data-notification-id="<?php echo esc_js($slug); ?>">
					<?php esc_html_e('Click here to dismiss this warning forever', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</div>
				<div
						style="white-space:normal; bottom:0; right: 0; margin-bottom: 0; margin-right: 5px;align-self: flex-end;">
					<a href="<?php echo esc_url(Documentation::get_link('the_dismiss_button_doesnt_work_why')); ?>"
					   target="_blank">
						<?php esc_html_e('If the dismiss button is not working, here\'s why >>', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</a>
				</div>
			</div>

		</div>
		<?php
	}

	public static function license_expired_warning__premium_only() {

		if (!wpm_fs()->can_use_premium_code__premium_only()) {

			$saved_notifications = get_option(PMW_DB_NOTIFICATIONS_NAME);

			// If is dashboard, and saved date is in the future then return (don't show)
			if (Helpers::is_dashboard() && isset($saved_notifications['show_license_expired_warning']) && $saved_notifications['show_license_expired_warning'] > time()) {
				return;
			}

			?>
			<div id="license-expired-warning" class="notice notice-error pmw license-expired-warning">
				<div>
					<div style="color:red; margin-top: 10px; ">
				<span>

					<?php
					esc_html_e('Your license for the Pixel Manager for WooCommerce has been removed or has expired. Automatic updates and all premium features have been disabled.', 'woocommerce-google-adwords-conversion-tracking-tag');
					?>
				</span> <a href="<?php echo esc_url(Documentation::get_link('license_expired_warning')); ?>"
						   target="_blank">
							<?php esc_html_e('More info', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
						</a><br>
					</div>

					<div id="pmw-purchase-new-license-button" class="button" style="margin: 10px 0 10px 0">

						<a href="<?php echo( esc_url_raw('//sweetcode.com/pricing') ); ?>" target="_blank"
						   style="text-decoration: none;">
							<?php esc_html_e('Purchase a new license', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
						</a>
					</div>
				</div>

				<?php if (Helpers::is_dashboard()) : ?>
					<div style=" margin-bottom: 10px; text-align: right;">

						<div id="pmw-dismiss-license-expiry-message-button"
							 class="button pmw-notification-dismiss-button"
							 style="white-space:normal; margin-bottom: 6px"
							 data-notification-id="license-expired"
						>
							<?php esc_html_e('Click here to dismiss this warning', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
						</div>
						<div class="pmw dismiss-link-info">
							<a href="<?php echo esc_url(Documentation::get_link('the_dismiss_button_doesnt_work_why')); ?>"
							   target="_blank">
								<?php esc_html_e('If the dismiss button is not working, here\'s why >>', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
							</a>
						</div>
					</div>
				<?php endif; ?>

			</div>

			<?php
		}
	}

	public static function dismiss_expired_license_warning__premium_only() {
		$saved_notifications                                 = get_option(PMW_DB_NOTIFICATIONS_NAME);
		$saved_notifications['show_license_expired_warning'] = time() + MONTH_IN_SECONDS * 6;
		update_option(PMW_DB_NOTIFICATIONS_NAME, $saved_notifications);
		wp_send_json_success();
	}

	public static function opportunities_notification() {

		if (Opportunities::active_opportunities_available()) {

			?>
			<div id="active-opportunities-notification"
				 class="notice notice-info pmw active-opportunities-notification"
				 style="padding: 8px;display: flex;flex-direction: row;justify-content: space-between;">
				<div>
					<div style="color:black;">
						<span>
							<?php esc_html_e('The Pixel Manager has detected new opportunities which can help improve tracking and campaign performance.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
						</span>
					</div>

					<a href="<?php echo( esc_url_raw('/wp-admin/admin.php?page=wpm&section=opportunities') ); ?>"
					   style="text-decoration: none;box-shadow: none;">
						<div id="pmw-purchase-new-license-button" class="button" style="margin: 10px 0 10px 0">
							<?php esc_html_e('Show the opportunities', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
						</div>
					</a>
				</div>

				<div style="text-align: right;display: flex;flex-direction: column;">
					<div id="pmw-dismiss-opportunities-message-button"
						 class="button pmw-notification-dismiss-button"
						 style="white-space:normal;margin-bottom: 6px;text-align: center;"
						 data-notification-id="opportunity-message"
					><?php esc_html_e('Click here to dismiss this notification', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</div>
					<div class="pmw dismiss-link-info" style="margin-top: auto">
						<a href="<?php echo esc_url(Documentation::get_link('the_dismiss_button_doesnt_work_why')); ?>"
						   target="_blank">
							<?php esc_html_e('If the dismiss button is not working, here\'s why >>', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
						</a>
					</div>
				</div>
			</div>

			<?php
		}
	}
}
