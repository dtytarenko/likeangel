<?php

// TODO move script for copying debug info into a proper .js enqueued file, or switch tabs to JavaScript switching and always save all settings at the same time

namespace WCPM\Classes\Admin;

use WCPM\Classes\Helpers;
use WCPM\Classes\Options;
use WCPM\Classes\Pixels\Google\Google;
use WCPM\Classes\Pixels\Pixel_Manager;
use WP_Post;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Admin {

	public  $ip;
	private $options;
	private $plugin_hook;
	private $google;

	private static $instance;

	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {

		$this->options = Options::get_options();

		$this->plugin_hook = 'woocommerce_page_wpm';
		$this->google      = new Google($this->options);

		add_action('admin_enqueue_scripts', [ $this, 'wpm_admin_scripts' ]);

		add_action('admin_enqueue_scripts', [ $this, 'pmw_edit_order_scripts' ]);

		add_action('admin_enqueue_scripts', [ $this, 'wpm_admin_css' ]);

		// add the admin options page
		add_action('admin_menu', [ $this, 'plugin_admin_add_page' ], 99);

		// install a settings page in the admin console
		add_action('admin_init', [ $this, 'wpm_plugin_admin_init' ]);

		// add admin scripts to plugins.php page
		add_action('load-plugins.php', [ $this, 'freemius_load_deactivation_button_js' ]);

		// Load textdomain
		add_action('init', [ $this, 'load_plugin_textdomain' ]);

		wpm_fs()->add_filter('templates/checkout.php', [ $this, 'fs_inject_additional_scripts' ]);
		wpm_fs()->add_filter('checkout/purchaseCompleted', [ $this, 'fs_after_purchase_js' ]);
		// end __construct

		if (wpm_fs()->can_use_premium_code__premium_only() && $this->google->is_ga4_data_api_active()) {

			// https://stackoverflow.com/a/45617265/4688612
			// https://stackoverflow.com/a/37780501/4688612
			add_action('add_meta_boxes', function () {

				$screen = Helpers::is_wc_hpos_enabled() && wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
					? wc_get_page_screen_id('shop-order')
					: 'shop_order';

				add_meta_box(
					'ga4-attribution-modal',
					esc_html__('GA4 Attribution', 'woocommerce-google-adwords-conversion-tracking-tag') . $this->html_beta(),
					[ $this, 'ga4_attribution_modal__premium_only' ],
					$screen,
					'side',
					'core'
				);
			});
		}
	}

	public function ga4_attribution_modal__premium_only( $post_or_order_object ) {

		$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order($post_or_order_object->ID) : $post_or_order_object;

		?>
		<div id="order-attribution-data" data-order-id="<?php esc_html_e($order->get_id()); ?>">
			<span>fetching data...</span>
		</div>
		<?php
	}

	private function if_is_wpm_admin_page() {

		$_get = Helpers::get_input_vars(INPUT_GET);

		if (!empty($_get['page']) && 'wpm' === $_get['page']) {
			return true;
		} else {
			return false;
		}
	}

	// This function is only called when our plugin's page loads!
	public function freemius_load_deactivation_button_js() {
		add_action('admin_enqueue_scripts', [ $this, 'freemius_enqueue_deactivation_button_js' ]);
	}


	public function freemius_enqueue_deactivation_button_js() {
		wp_enqueue_script(
			'freemius-enqueue-deactivation-button',
			PMW_PLUGIN_DIR_PATH . 'js/admin/wpm-admin-freemius.p1.min.js',
			[ 'jquery' ],
			PMW_CURRENT_VERSION,
			true
		);
	}

	public function fs_after_purchase_js( $js_function ) {
		return "
		function ( response ) {

            let
                isTrial = (null != response.purchase.trial_ends),
                isSubscription = (null != response.purchase.initial_amount),
                total = isTrial ? 0 : (isSubscription ? response.purchase.initial_amount : response.purchase.gross).toString(),
                productName = 'Pixel Manager for WooCommerce',
                // storeUrl = 'https://sweetcode.com',
                storeName = 'SweetCode';
            
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
    
            gtag('js', new Date());            
    
            gtag('config', 'UA-39746956-10', {'anonymize_ip': true});
            gtag('config', 'G-2QE000DX8D');
            gtag('config', 'AW-406204436');
            
            gtag('event', 'purchase', {
                'send_to':['UA-39746956-10', 'G-2QE000DX8D'],
                'transaction_id':response.purchase.id.toString(),
                'currency': response.purchase.currency.toUpperCase(),
                'discount':0,
                'items':[{
                    'id':response.purchase.plan_id.toString(),
                    'quantity':1,
                    'price':total,
                    'name':productName,
                    'category': 'Plugin',
                }],
                'affiliation': storeName,
                'value':response.purchase.initial_amount.toString()
            });
            
            gtag('event', 'conversion', {
              'send_to': 'AW-406204436/XrUYCK3J8YoCEJTg2MEB',
              'value': response.purchase.initial_amount.toString(),
              'currency': response.purchase.currency.toUpperCase(),
              'transaction_id': response.purchase.id.toString()
            });
            
            var _dcq = _dcq || [];
			var _dcs = _dcs || {};
			_dcs.account = '5594556';
	
			(function() {
				var dc = document.createElement('script');
				dc.type = 'text/javascript'; dc.async = true;
				dc.src = '//tag.getdrip.com/5594556.js';
				var s = document.getElementsByTagName('script')[0];
				s.parentNode.insertBefore(dc, s);
			})();
			
			window._dcq.push([
				'track',
				'Placed an order',
			]);
			
			window._dcq.push([
				'track',
				'purchase',
				{
					value: total * 100,
					currency_code: response.purchase.currency.toUpperCase(),
				}
			]);
  
        }";
	}

	// phpcs:disable
	public function fs_inject_additional_scripts( $html ) {
		return '<script async src="https://www.googletagmanager.com/gtag/js?id=UA-39746956-10"></script>' . $html;
	}
	// phpcs:enable

	public function wpm_admin_css( $hook_suffix ) {

		// Only output the css on PMW pages and the order page
		if (
			!( strpos($hook_suffix, 'page_wpm')
				|| Helpers::is_orders_page()
				|| Helpers::is_edit_order_page()
				|| Helpers::is_dashboard()
			)
		) {
			return;
		}

		wp_enqueue_style(
			'wpm-admin',
			PMW_PLUGIN_DIR_PATH . 'css/admin.css',
			[],
			PMW_CURRENT_VERSION
		);
	}

	public function wpm_admin_scripts( $hook_suffix ) {

		// Only output the remaining scripts on PMW settings page
		if (!strpos($hook_suffix, 'page_wpm')) {
			return;
		}

		wp_enqueue_script(
			'wpm-admin',
			PMW_PLUGIN_DIR_PATH . 'js/admin/wpm-admin.p1.min.js',
			[ 'jquery' ],
			PMW_CURRENT_VERSION,
			false
		);

		wp_localize_script(
			'wpm-admin',
			'pmwAdminApi',
			[
				'root'  => esc_url_raw(rest_url()),
				'nonce' => wp_create_nonce('wp_rest'),
			]
		);

//        wp_enqueue_script('wpm-script-blocker-warning', WPM_PLUGIN_DIR_PATH . 'js/admin/script-blocker-warning.js', ['jquery'], WPM_CURRENT_VERSION, false);
//        wp_enqueue_script('wpm-admin-helpers', WPM_PLUGIN_DIR_PATH . 'js/admin/helpers.js', ['jquery'], WPM_CURRENT_VERSION, false);
//        wp_enqueue_script('wpm-admin-tabs', WPM_PLUGIN_DIR_PATH . 'js/admin/tabs.js', ['jquery'], WPM_CURRENT_VERSION, false);

		wp_enqueue_script(
			'wpm-selectWoo',
			PMW_PLUGIN_DIR_PATH . 'js/admin/selectWoo.full.min.js',
			[ 'jquery' ],
			PMW_CURRENT_VERSION,
			false
		);

		wp_enqueue_style(
			'wpm-selectWoo',
			PMW_PLUGIN_DIR_PATH . 'css/selectWoo.min.css',
			[],
			PMW_CURRENT_VERSION
		);

		// enqueue https://fast.wistia.com/assets/external/E-v1.js
		wp_enqueue_script(
			'wistia',
			'https://fast.wistia.com/assets/external/E-v1.js',
			[],
			PMW_CURRENT_VERSION,
			false
		);
	}

	public function pmw_edit_order_scripts( $hook_suffix ) {

//		error_log('hook_suffix: ' . $hook_suffix);
//		error_log('get_current_screen: ' . get_current_screen()->id);

		// Only output the remaining scripts on PMW settings page
		if ('shop_order' !== get_current_screen()->id) {
			return;
		}

//		error_log('pmw_edit_order_scripts');

		if (
			wpm_fs()->can_use_premium_code__premium_only()
			&& Options::is_ga4_data_api_enabled()
		) {

			wp_enqueue_script(
				'pmw-edit-order',
				PMW_PLUGIN_DIR_PATH . 'js/admin/edit-order-page.js',
				[ 'jquery' ],
				PMW_CURRENT_VERSION,
				false
			);

			wp_localize_script(
				'pmw-edit-order',
				'pmwAdminApi',
				[
					'root'  => esc_url_raw(rest_url()),
					'nonce' => wp_create_nonce('wp_rest'),
				]
			);
		}
	}

	// Load text domain function
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'woocommerce-google-adwords-conversion-tracking-tag',
			false,
			dirname(plugin_basename(__FILE__)) . '/languages/'
		);
	}

	// add the admin options page
	public function plugin_admin_add_page() {
		//add_options_page('WPM Plugin Page', 'WPM Plugin Menu', 'manage_options', 'wpm', array($this, 'wpm_plugin_options_page'));
		add_submenu_page(
			$this->get_submenu_parent_slug(),
			esc_html__('Pixel Manager', 'woocommerce-google-adwords-conversion-tracking-tag'),
			esc_html__('Pixel Manager', 'woocommerce-google-adwords-conversion-tracking-tag'),
			'manage_options',
			'wpm',
			[ $this, 'plugin_options_page' ]
		);
	}

	private function get_submenu_parent_slug() {

		if (Environment::is_woocommerce_active()) {
			return 'woocommerce';
		} else {
			return 'options-general.php';
		}
	}

	// add the admin settings and such
	public function wpm_plugin_admin_init() {

		register_setting(
			'wpm_plugin_options_group',
			'wgact_plugin_options',
			[ '\WCPM\Classes\Admin\Validations', 'options_validate' ]
		);

		// don't load the UX if we are not on the plugin UX page
		if (!$this->if_is_wpm_admin_page()) {
			return;
		}

		$this->add_section_main();
		$this->add_section_advanced();
		if (Environment::is_woocommerce_active()) {
			$this->add_section_dynamic_remarketing();
		}
		if (defined('EXPERIMENTAL_PMW_OPPORTUNITIES_TAB') && EXPERIMENTAL_PMW_OPPORTUNITIES_TAB) {
			$this->add_section_opportunities();
		}
//		$this->add_section_opportunities();
		if (Environment::is_woocommerce_active()) {
			$this->add_section_diagnostics();
		}
		$this->add_section_support();
		$this->add_section_author();
	}

	public function add_section_main() {
		$section_ids = [
			'title'         => esc_html__('Main', 'woocommerce-google-adwords-conversion-tracking-tag'),
			'slug'          => 'main',
			'settings_name' => 'wpm_plugin_main_section',
		];

		self::output_section_data_field($section_ids);

		add_settings_section(
			$section_ids['settings_name'],
			esc_html__($section_ids['title'], 'woocommerce-google-adwords-conversion-tracking-tag'),
			[ $this, 'plugin_section_main_description' ],
			'wpm_plugin_options_page'
		);

		$this->add_section_main_subsection_statistics($section_ids);
		$this->add_section_main_subsection_marketing($section_ids);
		$this->add_section_main_subsection_optimization($section_ids);
	}

	public static function add_subsection_div( $section_ids, $sub_section_ids ) {
		add_settings_field(
			'wpm_plugin_subsection_' . $sub_section_ids['slug'] . '_opening_div',
			esc_html__(
				$sub_section_ids['title'],
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			function () use ( $section_ids, $sub_section_ids ) {
				self::subsection_generic_opening_div_html($section_ids, $sub_section_ids);
			},
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);
	}

	public function add_section_main_subsection_statistics( $section_ids ) {

		/**
		 * Set up the subsection
		 */

		// configuration
		$sub_section_ids = [
			'title' => esc_html__(
				'Statistics',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'slug'  => 'statistics',
		];

		// add the subsection div
		self::add_subsection_div($section_ids, $sub_section_ids);

		/**
		 * Add the settings fields
		 */

		add_settings_field(
			'wpm_plugin_analytics_4_measurement_id',
			esc_html__(
				'Google Analytics 4',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'option_html_google_analytics_4_id' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		if (Options::is_ga3_enabled()) { // GA3 is decommissioned. Only show this option if GA3 is enabled.
			add_settings_field(
				'wpm_plugin_analytics_ua_property_id',
				esc_html__(
					'Google Analytics UA',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'option_html_google_analytics_universal_property' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		// add the field for the Hotjar pixel
		add_settings_field(
			'wpm_plugin_hotjar_site_id',
			esc_html__(
				'Hotjar site ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'option_html_hotjar_site_id' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);
	}

	public function add_section_main_subsection_marketing( $section_ids ) {

		/**
		 * Set up the subsection
		 */

		// configuration
		$sub_section_ids = [
			'title' => esc_html__(
				'Marketing',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'slug'  => 'marketing',
		];

		// add the subsection div
		self::add_subsection_div($section_ids, $sub_section_ids);

		/**
		 * Add the settings fields
		 */

		// add the field for the Google Ads conversion id
		add_settings_field(
			'wpm_plugin_conversion_id',
			esc_html__(
				'Google Ads Conversion ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'option_html_google_ads_conversion_id' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		if (Environment::is_woocommerce_active()) {

			// add the field for the conversion label
			add_settings_field(
				'wpm_plugin_conversion_label',
				esc_html__(
					'Google Ads Purchase Conversion Label',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'option_html_google_ads_conversion_label' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		// add the field for the conversion label
		add_settings_field(
			'wpm_plugin_facebook_pixel_id',
			esc_html__(
				'Meta (Facebook) pixel ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'option_html_facebook_pixel_id' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		/**
		 * Pro version only
		 */

		if (wpm_fs()->can_use_premium_code__premium_only() || Options::pro_version_demo_active()) {

			if (Helpers::is_experiment()) {

				// Add the field for the Adroll advertiser ID
				add_settings_field(
					'pmw_adroll_advertiser_id',
					esc_html__(
						'Adroll advertiser ID',
						'woocommerce-google-adwords-conversion-tracking-tag'
					) . $this->html_beta(),
					[ $this, 'option_html_adroll_advertiser_id' ],
					'wpm_plugin_options_page',
					$section_ids['settings_name']
				);

				// Add the field for the Adroll pixel ID
				add_settings_field(
					'pmw_adroll_pixel_id',
					esc_html__(
						'Adroll pixel ID',
						'woocommerce-google-adwords-conversion-tracking-tag'
					) . $this->html_beta(),
					[ $this, 'option_html_adroll_pixel_id' ],
					'wpm_plugin_options_page',
					$section_ids['settings_name']
				);

				// Add the field for the LinkedIn partner ID
				add_settings_field(
					'pmw_linkedin_partner_id',
					esc_html__(
						'LinkedIn partner ID',
						'woocommerce-google-adwords-conversion-tracking-tag'
					) . $this->html_beta(),
					[ $this, 'option_html_linkedin_partner_id' ],
					'wpm_plugin_options_page',
					$section_ids['settings_name']
				);
			}

			// add the field for the Bing Ads UET tag ID
			add_settings_field(
				'wpm_plugin_bing_uet_tag_id',
				esc_html__(
					'Microsoft Advertising UET tag ID',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'option_html_bing_uet_tag_id' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			if (Helpers::is_experiment()) {

				// add the field for the Outbrain pixel
				add_settings_field(
					'pmw_plugin_outbrain_advertiser_id',
					esc_html__(
						'Outbrain advertiser ID',
						'woocommerce-google-adwords-conversion-tracking-tag'
					) . $this->html_beta(),
					[ $this, 'option_html_outbrain_advertiser_id' ],
					'wpm_plugin_options_page',
					$section_ids['settings_name']
				);

			}


			// Add the field for the Pinterest pixel
			add_settings_field(
				'pmw_plugin_pinterest_pixel_id',
				esc_html__(
					'Pinterest pixel ID',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'option_html_pinterest_pixel_id' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			// Add the field for the Reddit Ads pixel
			add_settings_field(
				'plugin_reddit_pixel_id',
				esc_html__(
					'Reddit pixel ID',
					'woocommerce-google-adwords-conversion-tracking-tag'
				) . $this->html_beta(),
				[ $this, 'option_html_reddit_pixel_id' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			// add the field for the Snapchat pixel
			add_settings_field(
				'wpm_plugin_snapchat_pixel_id',
				esc_html__(
					'Snapchat pixel ID',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'option_html_snapchat_pixel_id' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			if (Helpers::is_experiment()) {

				// add the field for the Taboola pixel
				add_settings_field(
					'pmw_plugin_taboola_account_id',
					esc_html__(
						'Taboola account ID',
						'woocommerce-google-adwords-conversion-tracking-tag'
					) . $this->html_beta(),
					[ $this, 'option_html_taboola_account_id' ],
					'wpm_plugin_options_page',
					$section_ids['settings_name']
				);
			}


			// add the field for the TikTok pixel
			add_settings_field(
				'wpm_plugin_tiktok_pixel_id',
				esc_html__(
					'TikTok pixel ID',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'option_html_tiktok_pixel_id' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			// add the field for the Twitter pixel
			add_settings_field(
				'wpm_plugin_twitter_pixel_id',
				esc_html__(
					'Twitter pixel ID',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'option_html_twitter_pixel_id' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}
	}

	public function add_section_main_subsection_optimization( $section_ids ) {

		/**
		 * Set up the subsection
		 */

		// configuration
		$sub_section_ids = [
			'title' => esc_html__(
				'Optimization',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'slug'  => 'optimization',
		];

		// add the subsection div
		self::add_subsection_div($section_ids, $sub_section_ids);

		/**
		 * Add the settings fields
		 */

		add_settings_field(
			'wpm_plugin_google_optimize_container_id',
			esc_html__(
				'Google Optimize',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'option_html_google_optimize_container_id' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		if (Helpers::is_experiment()) {

			add_settings_field(
				'pmw_plugin_ab_tasty_account_id',
				esc_html__(
					'AB Tasty',
					'woocommerce-google-adwords-conversion-tracking-tag'
				) . $this->html_beta(),
				[ $this, 'option_html_ab_tasty_account_id' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			add_settings_field(
				'pmw_plugin_optimizely_project_id',
				esc_html__(
					'Optimizely',
					'woocommerce-google-adwords-conversion-tracking-tag'
				) . $this->html_beta(),
				[ $this, 'option_html_optimizely_project_id' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			add_settings_field(
				'pmw_plugin_vwo_account_id',
				esc_html__(
					'VWO',
					'woocommerce-google-adwords-conversion-tracking-tag'
				) . $this->html_beta(),
				[ $this, 'option_html_vwo_account_id' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}
	}

	public function add_section_advanced() {
		$section_ids = [
			'title'         => esc_html__(
				'Advanced',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'slug'          => 'advanced',
			'settings_name' => 'wpm_plugin_advanced_section',
		];

		add_settings_section(
			$section_ids['settings_name'],
			esc_html__(
				$section_ids['title'],
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'plugin_section_advanced_description' ],
			'wpm_plugin_options_page'
		);

		self::output_section_data_field($section_ids);

		$this->add_section_advanced_subsection_general($section_ids);
		if (Environment::is_woocommerce_active()) {
			$this->add_section_advanced_subsection_shop($section_ids);
		}
		$this->add_section_advanced_subsection_google($section_ids);

		if (wpm_fs()->can_use_premium_code__premium_only() || Options::pro_version_demo_active()) {
			$this->add_section_advanced_subsection_facebook($section_ids);
			if (Helpers::is_experiment()) {
				if (Environment::is_woocommerce_active()) {
					$this->add_section_advanced_subsection_linkedin($section_ids);
				}
			}
			$this->add_section_advanced_subsection_pinterest($section_ids);
			$this->add_section_advanced_subsection_reddit($section_ids);
			$this->add_section_advanced_subsection_tiktok($section_ids);
			if (Environment::is_woocommerce_active()) {
				$this->add_section_advanced_subsection_twitter($section_ids);
			}
			$this->add_section_advanced_subsection_cookie_consent_mgmt($section_ids);
		}
	}

	public function add_section_advanced_subsection_general( $section_ids ) {
		$sub_section_ids = [
			'title' => 'General',
			'slug'  => 'general',
		];

		self::add_subsection_div($section_ids, $sub_section_ids);

		if (wpm_fs()->can_use_premium_code__premium_only() || Options::pro_version_demo_active()) {

			// add checkbox for disabling tracking for user roles
			add_settings_field(
				'wpm_setting_disable_tracking_for_user_roles',
				esc_html__(
					'Disable Tracking for User Roles',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_disable_tracking_for_user_roles' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
			// Add checkbox for the scroll tracker
			add_settings_field(
				'pmw_setting_scroll_tracker_thresholds',
				esc_html__(
					'Scroll Tracker',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'info_html_scroll_tracker_thresholds' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			// Add option for lazy loading PMW
			add_settings_field(
				'pmw_setting_lazy_load_pmw',
				esc_html__(
					'Lazy Load PMW',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'html_lazy_load_pmw' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		// add checkbox for maximum compatibility mode
		add_settings_field(
			'wpm_setting_maximum_compatibility_mode',
			esc_html__(
				'Maximum Compatibility Mode',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_html_maximum_compatibility_mode' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);
	}


	public function add_section_advanced_subsection_shop( $section_ids ) {
		$sub_section_ids = [
			'title' => 'Shop',
			'slug'  => 'shop',
		];

		self::add_subsection_div($section_ids, $sub_section_ids);

		// add fields for the order total logic
		add_settings_field(
			'wpm_plugin_order_total_logic',
			esc_html__(
				'Order Total Logic',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->get_documentation_html_e(Documentation::get_link('order_total_logic')),
			[ $this, 'option_html_shop_order_total_logic' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// add checkbox for order duplication prevention
		add_settings_field(
			'wpm_setting_order_duplication_prevention',
			esc_html__(
				'Order Duplication Prevention',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_html_order_duplication_prevention' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		if (wpm_fs()->can_use_premium_code__premium_only() || Options::pro_version_demo_active()) {

			// add info for ACR
			add_settings_field(
				'wpm_setting_acr',
				esc_html__(
					'ACR',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'info_html_acr' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		// add checkbox for order list info
		add_settings_field(
			'pmw_setting_order_list_info',
			esc_html__(
				'Order List Info',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'info_html_order_list_info' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		if (wpm_fs()->can_use_premium_code__premium_only() || Options::pro_version_demo_active()) {

			// Add a subscription value multiplier
			add_settings_field(
				'pmw_setting_subscription_value_multiplier',
				esc_html__(
					'Subscription Value Multiplier',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'html_subscription_value_multiplier' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}
	}

	public function add_section_advanced_subsection_google( $section_ids ) {
		$sub_section_ids = [
			'title' => 'Google',
			'slug'  => 'google',
		];

		self::add_subsection_div($section_ids, $sub_section_ids);

		if (Environment::is_woocommerce_active()) {

			// add the field for the aw_merchant_id
			add_settings_field(
				'wpm_plugin_aw_merchant_id',
				esc_html__(
					'Conversion Cart Data',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'plugin_setting_aw_merchant_id' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);


			if (wpm_fs()->can_use_premium_code__premium_only() || Options::pro_version_demo_active()) {
				// add fields for the Google enhanced e-commerce
				add_settings_field(
					'wpm_setting_google_analytics_eec',
					esc_html__(
						'Enhanced E-Commerce',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
					[ $this, 'info_html_google_analytics_eec' ],
					'wpm_plugin_options_page',
					$section_ids['settings_name']
				);

				// add fields for the Google GA4 API secret
				add_settings_field(
					'wpm_setting_google_analytics_4_api_secret',
					esc_html__(
						'GA4 API secret',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
					[ $this, 'setting_html_google_analytics_4_api_secret' ],
					'wpm_plugin_options_page',
					$section_ids['settings_name']
				);

				// Add fields for the GA4 Data API property ID
				add_settings_field(
					'pmw_setting_ga4_property_id',
					esc_html__(
						'GA4 Property ID',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
					[ $this, 'setting_html_ga4_property_id' ],
					'wpm_plugin_options_page',
					$section_ids['settings_name']
				);

				// Add fields for the GA4 Data API credentials upload
				add_settings_field(
					'pmw_setting_ga4_data_api_credentials',
					esc_html__(
						'GA4 Data API Credentials',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
					[ $this, 'setting_html_g4_data_api_credentials' ],
					'wpm_plugin_options_page',
					$section_ids['settings_name']
				);

			}
		}

		if (wpm_fs()->can_use_premium_code__premium_only() || Options::pro_version_demo_active()) {
			// Add fields for the GA4 Page Load Time Tracking
			add_settings_field(
				'pmw_setting_ga4_page_load_time_tracking',
				esc_html__(
					'GA4 Page Load Time Tracking',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_g4_page_load_time_tracking' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

		}

		// add fields for the Google Analytics link attribution
		add_settings_field(
			'wpm_setting_google_analytics_link_attribution',
			esc_html__(
				'Enhanced Link Attribution',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_html_google_analytics_link_attribution' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		if (wpm_fs()->can_use_premium_code__premium_only() || Options::pro_version_demo_active()) {
			// add user_id for the Google
			add_settings_field(
				'wpm_setting_google_user_id',
				esc_html__(
					'Google User ID',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_google_user_id' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			if (Environment::is_woocommerce_active()) {

				// Add Google Ads Enhanced Conversions
				add_settings_field(
					'wpm_setting_google_ads_enhanced_conversions',
					esc_html__(
						'Google Ads Enhanced Conversions',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
					[ $this, 'setting_html_google_ads_enhanced_conversions' ],
					'wpm_plugin_options_page',
					$section_ids['settings_name']
				);
			}
		}

		if (wpm_fs()->can_use_premium_code__premium_only() || Options::pro_version_demo_active()) {
			// add fields for the Google Ads phone conversion number
			add_settings_field(
				'wpm_plugin_google_ads_phone_conversion_number',
				esc_html__(
					'Google Ads Phone Conversion Number',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_google_ads_phone_conversion_number' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			// add fields for the Google Ads phone conversion label
			add_settings_field(
				'wpm_plugin_google_ads_phone_conversion_label',
				esc_html__(
					'Google Ads Phone Conversion Label',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_google_ads_phone_conversion_label' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			if (Environment::is_woocommerce_active()) {

				// add fields for the Google Ads Conversion Adjustments Conversion Name
				add_settings_field(
					'pmw_plugin_google_ads_conversion_adjustments_conversion_name',
					esc_html__(
						'Google Ads Conversion Adjustments: Conversion Name',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
					[ $this, 'setting_html_google_ads_conversion_adjustments_conversion_name' ],
					'wpm_plugin_options_page',
					$section_ids['settings_name']
				);

				// add fields for the Google Ads Conversion Adjustments Feed
				add_settings_field(
					'pmw_plugin_google_ads_conversion_adjustments_feed',
					esc_html__(
						'Google Ads Conversion Adjustments: Feed',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
					[ $this, 'setting_html_google_ads_conversion_adjustments_feed' ],
					'wpm_plugin_options_page',
					$section_ids['settings_name']
				);
			}

			// Add html for the Google Optimize anti-flicker snippet
			add_settings_field(
				'pmw_plugin_google_optimize_anti_flicker_snippet',
				esc_html__(
					'Google Optimize Anti-Flicker Snippet',
					'woocommerce-google-adwords-conversion-tracking-tag'
				) . $this->html_beta(),
				[ $this, 'setting_html_google_optimize_anti_flicker_snippet' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			// Add html for the Google Optimize anti-flicker snippet timeout
			add_settings_field(
				'pmw_plugin_google_optimize_anti_flicker_snippet_timeout',
				esc_html__(
					'Google Optimize Anti-Flicker Snippet Timeout',
					'woocommerce-google-adwords-conversion-tracking-tag'
				) . $this->html_beta(),
				[ $this, 'setting_html_google_optimize_anti_flicker_snippet_timeout' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		if (wpm_fs()->can_use_premium_code__premium_only() || Options::pro_version_demo_active()) {

			// add info for automatic email link click tracking
			add_settings_field(
				'pmw_info_automatic_email_link_tracking',
				esc_html__(
					'Automatic Email Link Tracking',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'info_html_automatic_email_link_tracking' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);

			// add info for automatic phone link click tracking
			add_settings_field(
				'pmw_info_automatic_phone_link_click_tracking',
				esc_html__(
					'Automatic Phone Link Click Tracking',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'info_html_automatic_phone_link_click_tracking' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}
	}

	public function add_section_advanced_subsection_cookie_consent_mgmt( $section_ids ) {

		$sub_section_ids = [
			'title' => esc_html__(
				'Cookie Consent Management',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'slug'  => 'cookie-consent-mgmt',
		];

		self::add_subsection_div($section_ids, $sub_section_ids);

		// add fields for the Google Consent beta
		add_settings_field(
			'wpm_setting_google_consent_mode_active',
			esc_html__(
				'Google Consent Mode',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_html_google_consent_mode_active' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// add fields for the Google consent regions
		add_settings_field(
			'wpm_setting_google_consent_regions',
			esc_html__(
				'Google Consent Regions',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_html_google_consent_regions' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// add fields for explicit cookie consent mode
		add_settings_field(
			'wpm_setting_explicit_consent_mode',
			esc_html__(
				'Explicit Consent Mode',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_html_explicit_consent_mode' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		if (Environment::is_borlabs_cookie_active()) {
			// add fields for the Borlabs Cookie support
			add_settings_field(
				'wpm_setting_borlabs_support',
				esc_html__(
					'Borlabs Cookie Support',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_borlabs_support' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		if (Environment::is_cookiebot_active()) {
			// add fields for the Cookiebot support
			add_settings_field(
				'wpm_setting_cookiebot_support',
				esc_html__(
					'Cookiebot Support',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_cookiebot_support' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		if (Environment::is_complianz_active()) {
			// add fields for the Complianz GDPR support
			add_settings_field(
				'wpm_setting_complianz_support',
				esc_html__(
					'Complianz GDPR Support',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_complianz_support' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		if (Environment::is_cookie_notice_active()) {
			// add fields for the Cookie Notice by hu-manity.co support
			add_settings_field(
				'wpm_setting_cookie_notice_support',
				esc_html__(
					'Cookie Notice Support',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_cookie_notice_support' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		if (Environment::is_cookie_script_active()) {
			// add fields for the Cookie Script support
			add_settings_field(
				'wpm_setting_cookie_script_support',
				esc_html__(
					'Cookie Script Support',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_cookie_script_support' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		if (Environment::is_moove_gdpr_active()) {
			// add fields for the GDPR Cookie Compliance support
			add_settings_field(
				'wpm_setting_moove_gdpr_support',
				esc_html__(
					'GDPR Cookie Compliance Support',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_moove_gdpr_support' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		if (Environment::is_cookie_law_info_active()) {
			// add fields for the GDPR Cookie Consent support
			add_settings_field(
				'wpm_setting_cookie_law_info_support',
				esc_html__(
					'GDPR Cookie Consent Support',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_cookie_law_info_support' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		if (Environment::is_real_cookie_banner_active()) {
			// add fields for the GDPR Cookie Consent support
			add_settings_field(
				'wpm_setting_cookie_law_info_support',
				esc_html__(
					'Real Cookie Banner Support',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_real_cookie_banner_support' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}

		if (Environment::is_termly_active()) {
			// add fields for the GDPR Cookie Consent support
			add_settings_field(
				'wpm_setting_cookie_law_info_support',
				esc_html__(
					'Termly CMP Support',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_termly_support' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}
	}

	public function add_section_advanced_subsection_facebook( $section_ids ) {
		$sub_section_ids = [
			'title' => 'Meta (Facebook)',
			'slug'  => 'facebook',
		];

		self::add_subsection_div($section_ids, $sub_section_ids);

		// add field for the Facebook CAPI token
		add_settings_field(
			'wpm_setting_facebook_capi_token',
			esc_html__(
				'Meta (Facebook) CAPI: token',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_html_facebook_capi_token' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the Facebook CAPI test event code
		add_settings_field(
			'pmw_setting_facebook_capi_test_event_code',
			esc_html__(
				'Meta (Facebook) CAPI: test event code',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_html_facebook_capi_test_event_code' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// add field for the Facebook CAPI user transparency process anonymous hits
		add_settings_field(
			'wpm_setting_facebook_capi_user_transparency_process_anonymous_hits',
			esc_html__(
				'Meta (Facebook) CAPI: process anonymous hits',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_facebook_capi_user_transparency_process_anonymous_hits' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// add field for the Facebook CAPI user transparency send additional client identifiers
		add_settings_field(
			'wpm_setting_facebook_capi_user_transparency_send_additional_client_identifiers',
			esc_html__(
				'Meta (Facebook): Advanced Matching',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_facebook_advanced_matching' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// This feature is deprecated since 1.25.1
		// We keep it active for users who are currently using it.
		if ($this->options['facebook']['microdata']) {

			// add fields for Facebook microdata
			add_settings_field(
				'wpm_setting_facebook_microdata_active',
				esc_html__(
					'Meta (Facebook) Microdata Tags for Catalogues',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'setting_html_facebook_microdata' ],
				'wpm_plugin_options_page',
				$section_ids['settings_name']
			);
		}
	}

	public function add_section_advanced_subsection_pinterest( $section_ids ) {

		$sub_section_ids = [
			'title' => 'Pinterest',
			'slug'  => 'pinterest',
		];

		self::add_subsection_div($section_ids, $sub_section_ids);

		// Add field for the Pinterest ad account ID token
		add_settings_field(
			'pmw_setting_pinterest_ad_account_id',
			esc_html__(
				'Pinterest Ad Account ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_html_pinterest_ad_account_id' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the Pinterest APIC token
		add_settings_field(
			'pmw_setting_pinterest_apic_token',
			esc_html__(
				'Pinterest Events API: token',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_html_pinterest_apic_token' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// add field for the Pinterest APIC user transparency process anonymous hits
		add_settings_field(
			'pmw_setting_pinterest_apic_user_transparency_process_anonymous_hits',
			esc_html__(
				'Pinterest Events API: process anonymous hits',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_pinterest_apic_process_anonymous_hits' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// add the field for the Pinterest enhanced match
		add_settings_field(
			'wpm_plugin_pinterest_enhanced_match',
			esc_html__(
				'Pinterest Enhanced Match',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'option_html_pinterest_enhanced_match' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// add field for the Pinterest Advanced Matching
		add_settings_field(
			'pmw_setting_pinterest_user_transparency_advanced_matching',
			esc_html__(
				'Pinterest: Advanced Matching',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_pinterest_advanced_matching' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);
	}

	public function add_section_advanced_subsection_reddit( $section_ids ) {

		$sub_section_ids = [
			'title' => 'Reddit',
			'slug'  => 'reddit',
		];

		self::add_subsection_div($section_ids, $sub_section_ids);

		// Add the field for the Reddit advanced matching
		add_settings_field(
			'plugin_reddit_advanced_matching',
			esc_html__(
				'Reddit Advanced Matching',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'option_html_reddit_advanced_matching' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);
	}


	public function add_section_advanced_subsection_tiktok( $section_ids ) {
		$sub_section_ids = [
			'title' => 'TikTok',
			'slug'  => 'tiktok',
		];

		self::add_subsection_div($section_ids, $sub_section_ids);

		// Add field for the TikTok Events API token
		add_settings_field(
			'pmw_setting_tiktok_eapi_token',
			esc_html__(
				'TikTok Events API: token',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_html_tiktok_eapi_token' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the TikTok EAPI test event code
		add_settings_field(
			'pmw_setting_tiktok_eapi_test_event_code',
			esc_html__(
				'TikTok EAPI: test event code',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_html_tiktok_eapi_test_event_code' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// add field for the TikTok EAPI user transparency process anonymous hits
		add_settings_field(
			'pmw_setting_tiktok_eapi_user_transparency_process_anonymous_hits',
			esc_html__(
				'TikTok Events API: process anonymous hits',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_tiktok_eapi_process_anonymous_hits' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// add field for the TikTok Advanced Matching
		add_settings_field(
			'pmw_setting_tiktok_eapi_user_transparency_advanced_matching',
			esc_html__(
				'TikTok: Advanced Matching',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'setting_tiktok_advanced_matching' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);
	}


	/**
	 * Info:
	 * Since the Lead events can be sent using the shortcodes,
	 * we don't need to add those ase fields to the advanced settings page.
	 *
	 * @param $section_ids
	 * @return void
	 */
	public function add_section_advanced_subsection_linkedin( $section_ids ) {

		// Configuration for the LinkedIn section
		$sub_section_ids = [
			'title' => 'LinkedIn',
			'slug'  => 'linkedin',
		];

		self::add_subsection_div($section_ids, $sub_section_ids);

		// Add field for the LinkedIn search event
		add_settings_field(
			'pmw_setting_linkedin_search',
			esc_html__(
				'Search event ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_linkedin_search' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the LinkedIn view_content event
		add_settings_field(
			'pmw_setting_linkedin_view_content',
			esc_html__(
				'View Content event ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_linkedin_view_content' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the LinkedIn add_to_list event
		add_settings_field(
			'pmw_setting_linkedin_add_to_list',
			esc_html__(
				'Add To List event ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_linkedin_add_to_list' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the LinkedIn add_to_cart event
		add_settings_field(
			'pmw_setting_linkedin_add_to_cart',
			esc_html__(
				'Add-to-cart event ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_linkedin_add_to_cart' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the LinkedIn start_checkout event
		add_settings_field(
			'pmw_setting_linkedin_start_checkout',
			esc_html__(
				'Start-checkout event ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_linkedin_start_checkout' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the LinkedIn purchase event
		add_settings_field(
			'pmw_setting_linkedin_purchase',
			esc_html__(
				'Purchase event ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_linkedin_purchase' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);
	}

	public function add_section_advanced_subsection_twitter( $section_ids ) {

		// configuration for the Twitter section
		$sub_section_ids = [
			'title' => 'Twitter',
			'slug'  => 'twitter',
		];

		self::add_subsection_div($section_ids, $sub_section_ids);

		// Add field for the Twitter event add_to_cart
		add_settings_field(
			'pmw_setting_twitter_add_to_cart',
			esc_html__(
				'Add To Cart Event ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_twitter_add_to_cart' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the Twitter event add_to_wishlist
		add_settings_field(
			'pmw_setting_twitter_add_to_wishlist',
			esc_html__(
				'Add To Wishlist Event ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_twitter_add_to_wishlist' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the Twitter event view_content
		add_settings_field(
			'pmw_setting_twitter_view_content',
			esc_html__(
				'Content View Event ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_twitter_view_content' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the Twitter event search
		add_settings_field(
			'pmw_setting_twitter_search',
			esc_html__(
				'Search Event ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_twitter_search' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the Twitter event initiate_checkout
		add_settings_field(
			'pmw_setting_twitter_initiate_checkout',
			esc_html__(
				'Checkout Initiated Event ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_twitter_initiate_checkout' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);

		// Add field for the Twitter event purchase
		add_settings_field(
			'pmw_setting_twitter_purchase',
			esc_html__(
				'Purchase Event ID',
				'woocommerce-google-adwords-conversion-tracking-tag'
			) . $this->html_beta(),
			[ $this, 'setting_twitter_purchase' ],
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);
	}

	public function add_section_dynamic_remarketing() {
		$section_ids = [
			'title'         => esc_html__(
				'Dynamic Remarketing',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'slug'          => 'dynamic-remarketing',
			'settings_name' => 'wpm_plugin_beta_section',
		];

		self::output_section_data_field($section_ids);

		// add new section for cart data
		add_settings_section(
			'wpm_plugin_beta_section',
			esc_html__(
				'Dynamic Remarketing',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'plugin_section_add_cart_data_description' ],
			'wpm_plugin_options_page'
		);

		// add checkbox for dynamic remarketing
		add_settings_field(
			'wpm_plugin_option_gads_dynamic_remarketing',
			esc_html__(
				'Dynamic Remarketing',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'option_html_google_ads_dynamic_remarketing' ],
			'wpm_plugin_options_page',
			'wpm_plugin_beta_section'
		);

		// add fields for the product identifier
		add_settings_field(
			'wpm_plugin_option_product_identifier',
			esc_html__(
				'Product Identifier',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'plugin_option_product_identifier' ],
			'wpm_plugin_options_page',
			'wpm_plugin_beta_section'
		);

		// add checkbox for variations output
		add_settings_field(
			'wpm_plugin_option_variations_output',
			esc_html__(
				'Variations output',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			[ $this, 'option_html_variations_output' ],
			'wpm_plugin_options_page',
			'wpm_plugin_beta_section'
		);

		if (wpm_fs()->can_use_premium_code__premium_only() || Options::pro_version_demo_active()) {
			// google_business_vertical
			add_settings_field(
				'wpm_plugin_google_business_vertical',
				esc_html__(
					'Google Business Vertical',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				[ $this, 'plugin_option_google_business_vertical' ],
				'wpm_plugin_options_page',
				'wpm_plugin_beta_section'
			);
		}
	}

	public function add_section_diagnostics() {
		$section_ids = [
			'title'         => esc_html__('Diagnostics', 'woocommerce-google-adwords-conversion-tracking-tag'),
			'slug'          => 'diagnostics',
			'settings_name' => 'wpm_plugin_diagnostics_section',
		];

		self::output_section_data_field($section_ids);

		add_settings_section(
			'wpm_plugin_diagnostics_section',
			esc_html__('Diagnostics', 'woocommerce-google-adwords-conversion-tracking-tag'),
			[ $this, 'plugin_section_diagnostics_html' ],
			'wpm_plugin_options_page'
		);
	}

	public function add_section_opportunities() {
		$section_ids = [
			'title'         => esc_html__('Opportunities', 'woocommerce-google-adwords-conversion-tracking-tag'),
			'slug'          => 'opportunities',
			'settings_name' => 'wpm_plugin_opportunities_section',
		];

		self::output_section_data_field($section_ids);

		add_settings_section(
			'wpm_plugin_opportunities_section',
			esc_html__('Opportunities', 'woocommerce-google-adwords-conversion-tracking-tag'),
			[ $this, 'plugin_section_opportunities_html' ],
			'wpm_plugin_options_page'
		);
	}

	public function add_section_support() {
		$section_ids = [
			'title'         => esc_html__('Support', 'woocommerce-google-adwords-conversion-tracking-tag'),
			'slug'          => 'support',
			'settings_name' => 'wpm_plugin_support_section',
		];

		self::output_section_data_field($section_ids);

		add_settings_section(
			'wpm_plugin_support_section',
			esc_html__('Support', 'woocommerce-google-adwords-conversion-tracking-tag'),
			[ $this, 'plugin_section_support_description' ],
			'wpm_plugin_options_page'
		);
	}

	public function add_section_author() {
		$section_ids = [
			'title'         => esc_html__('Author', 'woocommerce-google-adwords-conversion-tracking-tag'),
			'slug'          => 'author',
			'settings_name' => 'wpm_plugin_author_section',
		];

		self::output_section_data_field($section_ids);

		add_settings_section(
			'wpm_plugin_author_section',
			esc_html__('Author', 'woocommerce-google-adwords-conversion-tracking-tag'),
			[ $this, 'plugin_section_author_description' ],
			'wpm_plugin_options_page'
		);

		// end add_section_author
	}

	private static function output_section_data_field( array $section_ids ) {
		add_settings_field(
			'wgact_plugin_section_' . $section_ids['slug'] . '_opening_div',
			'',
			function () use ( $section_ids ) {
				self::section_generic_opening_div_html($section_ids);
			},
			'wpm_plugin_options_page',
			$section_ids['settings_name']
		);
	}

	public static function section_generic_opening_div_html( $section_ids ) {
		echo '<div class="section" data-section-title="' . esc_js($section_ids['title']) . '" data-section-slug="' . esc_js($section_ids['slug']) . '"></div>';
	}

	public static function subsection_generic_opening_div_html( $section_ids, $sub_section_ids ) {
		echo '<div class="subsection" data-section-slug="' . esc_js($section_ids['slug']) . '" data-subsection-title="' . esc_js($sub_section_ids['title']) . '" data-subsection-slug="' . esc_js($sub_section_ids['slug']) . '"></div>';
	}

	// display the admin options page
	public function plugin_options_page() {

		$freemius_data = [
			'active' => Environment::is_freemius_active(),
			'tabs'   => [
				'contact' => esc_html__('Contact', 'woocommerce-google-adwords-conversion-tracking-tag'),
				'account' => esc_html__('Account', 'woocommerce-google-adwords-conversion-tracking-tag'),
			],
		];
		?>

		<script>
			const pmwFreemius = <?php echo wp_json_encode($freemius_data); ?>;
		</script>
		<?php

		if (
			wpm_fs()->is__premium_only()
			&& !defined('WP_FS__SKIP_EMAIL_ACTIVATION')
		) {
			add_action('admin_notices', function () {
				Notifications::license_expired_warning__premium_only();
			});
		}

		?>

		<div id="script-blocker-notice"
			 style="
			 font-weight: bold;
			 width:90%;
			 float: left;
			 margin: 5px 15px 2px;
			 padding: 1px 12px;
			 background: #fff;
			 border: 1px solid #c3c4c7;
			 border-left-width: 4px;
			 border-left-color: #d63638;
			 box-shadow: 0 1px 1px rgb(0 0 0 / 4%);">
			<p>
				<?php
				esc_html_e('It looks like you are using some sort of ad or script blocker in your browser which is blocking the script and CSS files of this plugin.
                    In order for the plugin to work properly you need to disable the script blocker.', 'woocommerce-google-adwords-conversion-tracking-tag');
				?>
			</p>
			<p>
				<a href="<?php echo esc_url(Documentation::get_link('script_blockers')); ?>"
				   target="_blank">
					<?php esc_html_e('Learn more', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</a>
			</p>

			<script>
				if (typeof wpm_hide_script_blocker_warning === "function") {
					wpm_hide_script_blocker_warning()
				}
			</script>

		</div>

		<div style="width:90%; margin: 5px">

			<?php
			// Only run if WooCommerce is active,
			// otherwise the settings_errors() function will be run twice.
			if (Environment::is_woocommerce_active()) {
				settings_errors();
			}
			?>

			<h2 class="nav-tab-wrapper"></h2>

			<form id="wpm_settings_form" action="options.php" method="post">

				<?php

				settings_fields('wpm_plugin_options_group');
				do_settings_sections('wpm_plugin_options_page');
				submit_button();

				$this->inject_developer_banner();
				?>

			</form>
		</div>
		<?php
	}

	private function inject_developer_banner() {
		?>

		<div class="pmw dev-banner">
			<div class="text left">
				<?php esc_html_e('Profit Driven Marketing by SweetCode', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			</div>
			<?php
			if (
				!wpm_fs()->can_use_premium_code__premium_only()
				&& !Environment::is_on_playground_wordpress_net()
			) :
				?>
				<div
						class="text center"
						id="divTooltip"
						data-tooltip="<?php esc_html_e("Enabling this will show you the pro settings in the user interface. It won't actually enable the pro features. If you want to try out the pro features head over to sweetcode.com and sign up for a trial.", 'woocommerce-google-adwords-conversion-tracking-tag'); ?>"
				>
					<div style="padding-right: 6px">
						<?php esc_html_e('Show Pro version settings:', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>

					</div>
					<div>

						<label class="switch" id="pmw-pro-version-demo">

							<input type='hidden' value='0'
								   name='wgact_plugin_options[general][pro_version_demo]'>
							<input type="checkbox" value='1'
								   name='wgact_plugin_options[general][pro_version_demo]'
								<?php checked(Options::pro_version_demo_active()); ?>
							/>

							<span class="slider round"></span>

						</label>

					</div>
				</div>

			<?php endif; ?>
			<div class="text right">
				<span style="padding-right: 6px">
				<?php esc_html_e('Visit us here:', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</span>
				<span>
				<a href="https://sweetcode.com/?utm_source=plugin&utm_medium=banner&utm_campaign=wpm"
				   target="_blank">https://sweetcode.com</a>
				</span>
			</div>
		</div>

		<?php
	}


	private function get_link_locale() {

		if (substr(get_user_locale(), 0, 2) === 'de') {
			return 'de';
		} else {
			return 'en';
		}
	}

	/*
	 * descriptions
	 */

	public function plugin_section_main_description() {
		// do nothing
	}

	public function plugin_section_advanced_description() {
		// do nothing
	}

	public function plugin_section_add_cart_data_description() {
//        echo '<div id="beta-description" style="margin-top:20px">';
//        esc_html_e('Find out more about this new feature: ', 'woocommerce-google-adwords-conversion-tracking-tag');
//        echo '<a href="https://support.google.com/google-ads/answer/9028254" target="_blank">https://support.google.com/google-ads/answer/9028254</a><br>';
//        echo '</div>';
	}

	public function plugin_section_diagnostics_html() {

		// output a text that explains that transients need to be enabled
		// then abort
		if (!Environment::is_transients_enabled()) {

			?>
			<div style="margin-top:20px">
				<h2>
					<?php esc_html_e('Diagnostics', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</h2>
				<div>
					<?php esc_html_e('Transients are disabled on your site. Transients are required for the diagnostics report.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</div>
				<div style="margin-top: 10px">
					<?php esc_html_e('Please enable transients on your site.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</div>
			</div>
			<?php

			return;
		}

		?>
		<div style="margin-top:20px">
			<h2>
				<?php esc_html_e('Payment Gateway Tracking Accuracy Report', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				<div
						class="pmw-status-icon beta"><?php esc_html_e('beta', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></div>
			</h2>

			<div style="margin-bottom: 20px">
				<?php esc_html_e("What's this? Follow this link to learn more", 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				:
				<?php self::get_documentation_html_by_key('payment_gateway_tracking_accuracy'); ?>
			</div>

			<div>
				<div>

					<b><?php esc_html_e('Available payment gateways', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
						:</b>
				</div>
				<div style="margin-left: 10px; font-family: Courier">
					<table>
						<thead style="align:left">
						<tr>
							<th style="text-align: left"><?php esc_html_e('id', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></th>
							<th style="text-align: left"><?php esc_html_e('method_title', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></th>
							<th style="text-align: left"><?php esc_html_e('class', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></th>
						</tr>
						</thead>
						<tbody>
						<?php foreach (Debug_Info::get_payment_gateways() as $gateway) : ?>
							<tr>
								<td><?php esc_html_e($gateway->id); ?></td>
								<td><?php esc_html_e($gateway->method_title); ?></td>
								<td><?php esc_html_e(get_class($gateway)); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>

			</div>
			<div style="margin-top: 10px">

				<b><?php esc_html_e('Purchase confirmation page reached per gateway (active and inactive)', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					:</b>

				<?php
				if (Debug_Info::get_gateway_analysis_array() === false) {
					?>
					<br>
					<?php
					esc_html_e(Debug_Info::tracking_accuracy_loading_message());
					$per_gateway_analysis = [];
				} else {
					$per_gateway_analysis = Debug_Info::get_gateway_analysis_array();
				}
				?>

				<div style="margin-left: 10px; font-family: Courier;">
					<table>

						<?php

						$order_count_total    = 0;
						$order_count_measured = 0;
						?>

						<tbody>
						<?php foreach ($per_gateway_analysis as $gateway_analysis) : ?>
							<?php
							$order_count_total    += $gateway_analysis['order_count_total'];
							$order_count_measured += $gateway_analysis['order_count_measured'];
							?>
							<tr>
								<td><?php esc_html_e($gateway_analysis['gateway_id']); ?></td>
								<td><?php esc_html_e($gateway_analysis['order_count_measured']); ?></td>
								<td>of</td>
								<td><?php esc_html_e($gateway_analysis['order_count_total']); ?></td>
								<td>=</td>
								<td><?php esc_html_e($gateway_analysis['percentage']); ?>%</td>
								<td><?php $this->get_gateway_accuracy_warning_status($gateway_analysis['percentage']); ?></td>
							</tr>
						<?php endforeach; ?>

						</tbody>
					</table>
				</div>

			</div>

			<div style="margin-top: 10px">

				<b><?php esc_html_e('Purchase confirmation page reached per gateway (only active), weighted by frequency', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					:</b>

				<?php
				if (Debug_Info::get_gateway_analysis_weighted_array() === false) {
					?>
					<br>
					<?php
					esc_html_e(Debug_Info::tracking_accuracy_loading_message());
					$per_gateway_analysis = [];
				} else {
					$per_gateway_analysis = Debug_Info::get_gateway_analysis_weighted_array();
				}
				?>

				<div style="margin-left: 10px; font-family: Courier;">
					<table>
						<?php

						$order_count_total    = 0;
						$order_count_measured = 0;
						?>

						<tbody>
						<?php foreach ($per_gateway_analysis as $gateway_analysis) : ?>
							<?php
							$order_count_total    += $gateway_analysis['order_count_total'];
							$order_count_measured += $gateway_analysis['order_count_measured'];
							?>
							<tr>
								<td><?php esc_html_e($gateway_analysis['gateway_id']); ?></td>
								<td><?php esc_html_e($gateway_analysis['order_count_measured']); ?></td>
								<td>of</td>
								<td><?php esc_html_e($gateway_analysis['order_count_total']); ?></td>
								<td>=</td>
								<td><?php esc_html_e($gateway_analysis['percentage']); ?>%</td>
								<td><?php $this->get_gateway_accuracy_warning_status($gateway_analysis['percentage']); ?></td>
							</tr>
						<?php endforeach; ?>
						<tr>
							<td>Total</td>
							<td><?php esc_html_e($order_count_measured); ?></td>
							<td>of</td>
							<td><?php esc_html_e($order_count_total); ?></td>
							<td>=</td>
							<td>
								<?php
								$percent = Helpers::get_percentage($order_count_measured, $order_count_total);

								if ($order_count_total > 0) {
									esc_html_e($percent . '%');
								} else {
									echo '0%';
								}
								?>
							</td>
							<td><?php $this->get_gateway_accuracy_warning_status($percent); ?></td>

						</tr>
						</tbody>
					</table>
				</div>
			</div>

		</div>

		<div style="margin-top: 10px">

			<b><?php esc_html_e('Automatic Conversion Recovery (ACR)', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				:</b>
			<?php self::get_documentation_html_by_key('acr'); ?>
			<?php

			if (wpm_fs()->can_use_premium_code__premium_only()) {

				if (Debug_Info::get_gateway_analysis_weighted_array() === false) {
					?>
					<br>
					<?php
					esc_html_e(Debug_Info::tracking_accuracy_loading_message());
					$per_gateway_analysis = [];
				} else {
					$per_gateway_analysis = Debug_Info::get_gateway_analysis_weighted_array();
				}

				?>

				<div style="margin-left: 10px; font-family: Courier;">

					<table>

						<?php

						$order_count_total    = 0;
						$order_count_measured = 0;
						$missing_conversions  = 0;
						$acr_total_recovered  = 0;

						?>

						<tbody>
						<?php foreach ($per_gateway_analysis as $gateway_analysis) : ?>
							<?php
							$order_count_total    += $gateway_analysis['order_count_total'];
							$order_count_measured += $gateway_analysis['order_count_measured_including_acr'];
							$missing_conversions  += $gateway_analysis['order_count_total'] - $gateway_analysis['order_count_measured'];
							$acr_total_recovered  += $gateway_analysis['acr_recovered'];
							?>
							<tr>
								<td><?php esc_html_e($gateway_analysis['gateway_id']); ?></td>
								<td><?php esc_html_e($gateway_analysis['order_count_measured_including_acr'] . ' (' . $gateway_analysis['acr_recovered'] . ')'); ?></td>

								<td>of</td>
								<td><?php esc_html_e($gateway_analysis['order_count_total']); ?></td>
								<td>=</td>
								<td><?php esc_html_e($gateway_analysis['percentage_including_acr_recovered']); ?>%</td>

								<td><?php $this->get_gateway_accuracy_warning_status($gateway_analysis['percentage_including_acr_recovered']); ?></td>


							</tr>
						<?php endforeach; ?>
						<tr>
							<td>Total</td>
							<td><?php esc_html_e($order_count_measured); ?> (<?php esc_html_e($acr_total_recovered); ?>)
							</td>
							<td>of</td>
							<td><?php esc_html_e($order_count_total); ?></td>
							<td>=</td>
							<td>
								<?php
								$percent = Helpers::get_percentage($order_count_measured, $order_count_total);

								if ($order_count_total > 0) {
									esc_html_e($percent . '%');
								} else {
									echo '0%';
								}
								?>
							</td>
							<td><?php $this->get_gateway_accuracy_warning_status($percent); ?></td>

						</tr>
						</tbody>
					</table>
					<p>
						<b>
							<?php
							// translators: The number and percentage of orders that were recovered by the Automatic Conversion Recovery (ACR).
							esc_html_e(
								sprintf(
								// translators: The number and percentage of orders that were recovered by the Automatic Conversion Recovery (ACR).
									__('ACR recovered %1$s (%2$s%%) out of %3$s missing conversions.', 'woocommerce-google-adwords-conversion-tracking-tag'),
									$acr_total_recovered,
									Helpers::get_percentage($acr_total_recovered, $missing_conversions),
									$missing_conversions
								)
							);
							?>
						</b>
					</p>
				</div>
				<?php
			} else {
				?>

				<div style="margin-top: 10px">

					<div style="margin-left: 10px">
						<p>
							<?php esc_html_e('This feature is only available in the pro version of the plugin. Follow the link to learn more about it:', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
							<?php self::get_documentation_html_by_key('acr'); ?></br>
							<?php esc_html_e('Get the pro version of the Pixel Manager for WooCommerce over here', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
							: <a href="//sweetcode.com/pricing"
								 target="_blank"><?php esc_html_e('Go Pro', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></a>
						</p>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	public function plugin_section_opportunities_html() {
		Opportunities::html();
	}

	private function get_gateway_accuracy_warning_status( $percent ) {
		if (0 === intval($percent)) {
			echo '';
		} elseif ($percent > 0 && $percent < 90) {
			echo '<span style="color:red">warning</span>';
		} elseif ($percent >= 90 && $percent < 95) {
			echo '<span style="color:orange">monitor</span>';
		} elseif (0 !== $percent) {
			echo '<span style="color:green">good</span>';
		} else {
			echo '';
		}
	}

	public function plugin_section_support_description() {
		?>
		<!-- Contacting Support -->
		<div style="margin-top:20px">
			<h2><?php esc_html_e('Contacting Support', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></h2>
		</div>
		<?php $this->support_info_for_freemius(); ?>
		<!-- Contacting Support -->

		<hr style="border: none;height: 1px; color: #333; background-color: #333;">

		<!-- Info for translators -->
		<?php


		$this->info_for_translators()

		// placeholder
		?>
		<!-- Info for translators -->

		<!-- Debug Info -->
		<div>
			<h2><?php esc_html_e('Debug Information', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></h2>

			<div>
				<textarea id="debug-info-textarea" class=""
						  style="display:block; margin-bottom: 10px; width: 100%;resize: none;color:dimgrey;font-family: Courier"
						  cols="100%" rows="30"
						  readonly><?php esc_html_e(Debug_Info::get_debug_info()); ?>
				</textarea>
				<button id="debug-info-button"
						type="button"><?php esc_html_e('copy to clipboard', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></button>
			</div>

		</div>
		<!-- Debug Info -->

		<hr class="pmw-hr">

		<!-- Export Settings -->
		<div>
			<h2><?php esc_html_e('Export settings', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></h2>

			<div>
				<textarea id="export-settings-json" class=""
						  style="display:block; margin-bottom: 10px; width: 100%;resize: none;color:dimgrey;"
						  cols="100%" rows="10"
						  readonly><?php echo wp_json_encode($this->options); ?>
				</textarea>
				<button
						id="debug-info-button"
						type="button"
						onclick="wpm.saveSettingsToDisk('<?php esc_html_e(PMW_CURRENT_VERSION); ?>')"
				><?php esc_html_e('Export to disk', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></button>
			</div>
		</div>
		<!-- Export Settings -->

		<hr class="pmw-hr">

		<!-- Import Settings -->
		<div>
			<h2><?php esc_html_e('Import settings', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></h2>
			<div>
				<input type="file" id="json-settings-file-input"/>
				<pre id="upload-status-success" style="display: none; white-space: pre-line;">
					<span style="color: green; font-weight: bold">
						<?php esc_html_e('Settings imported successfully!', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</span>
					<span>
						<?php esc_html_e('Reloading...(in 1 second)!', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</span>
				</pre>

				<pre id="upload-status-error" style="display: none; white-space: pre-line;">
					<span style="color: red; font-weight: bold">
						<?php esc_html_e('There was an error importing that file! Please try again.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</span>
				</pre>
			</div>
		</div>
		<!-- Import Settings -->

		<hr class="pmw-hr">

		<?php
	}

	private function info_for_translators() {
		?>

		<div style="margin-bottom: 20px">
			<h2><?php esc_html_e('Translations', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></h2>
			<?php esc_html_e('If you want to participate improving the translations of this plugin into your language, please follow this link:', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			<a href="https://translate.wordpress.org/projects/wp-plugins/woocommerce-google-adwords-conversion-tracking-tag/"
			   target="_blank">translate.wordpress.org</a>

		</div>
		<hr style="border: none;height: 1px; color: #333; background-color: #333;">
		<?php
	}

	private function support_info_for_freemius() {
		?>
		<div style="margin-bottom: 30px;">
			<ul>

				<li>
					<?php esc_html_e('Post a support request in the WordPress support forum here: ', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					<a href="https://wordpress.org/support/plugin/woocommerce-google-adwords-conversion-tracking-tag/"
					   target="_blank">
						<?php esc_html_e('Support forum', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</a>
					&nbsp;
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e('(Never post the debug or other sensitive information to the support forum. Instead send us the information by email.)', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</li>
				<li>
					<?php esc_html_e('Or send us an email to the following address: ', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					<a href="mailto:support@sweetcode.com" target="_blank">support@sweetcode.com</a>
				</li>
			</ul>
		</div>

		<?php
	}


	private function support_info_for_wc_market() {
		?>
		<div style="margin-bottom: 30px;">
			<ul>
				<li>
					<?php esc_html_e('Send us your support request through the WooCommerce.com dashboard: ', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					<a href="https://woocommerce.com/my-account/create-a-ticket/" target="_blank">WooCommerce support
						dashboard</a>
				</li>
			</ul>
		</div>

		<?php
	}

	public function plugin_section_author_description() {
		?>
		<div style="margin-top:20px;margin-bottom: 30px">
			<?php esc_html_e('More details about the developer of this plugin: ', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</div>
		<div style="margin-bottom: 30px;">
			<div><?php esc_html_e('Developer: SweetCode', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></div>
			<div>
				<?php esc_html_e('Website: ', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				<a href="https://sweetcode.com/?utm_source=plugin&utm_medium=banner&utm_campaign=wpm"
				   target="_blank">https://sweetcode.com</a>
			</div>
		</div>
		<?php
	}


	public function option_html_google_analytics_universal_property() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_analytics_ua_property_id'
			   name='wgact_plugin_options[google][analytics][universal][property_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['google']['analytics']['universal']['property_id']); ?>'
		/>
		<?php
//		self::display_status_icon($this->options['google']['analytics']['universal']['property_id'], true, true);
		$this->html_status_icon_decommissioned_and_disabled();
		self::get_documentation_html_by_key('google_analytics_universal_property');
		echo '<br><br>';
//		esc_html_e('The Google Analytics Universal property ID looks like this:', 'woocommerce-google-adwords-conversion-tracking-tag');
//		echo '&nbsp;<i>UA-12345678-1</i>';
		esc_html_e('Google Analytics Universal has been decommissioned.', 'woocommerce-google-adwords-conversion-tracking-tag');
		?>
		<a href="https://support.google.com/analytics/answer/11583528" target="_blank">Info</a>
		<?php
	}

	public function option_html_google_analytics_4_id() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_analytics_4_measurement_id'
			   name='wgact_plugin_options[google][analytics][ga4][measurement_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['google']['analytics']['ga4']['measurement_id']); ?>'
		/>
		<?php
		self::display_status_icon($this->options['google']['analytics']['ga4']['measurement_id']);
		self::get_documentation_html_by_key('google_analytics_4_id');
		self::output_advanced_section_cog_html('google');
		self::wistia_video_icon('rcc3qzb25l');
		echo '<br><br>';
		esc_html_e('The Google Analytics 4 measurement ID looks like this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>G-R912ZZ1MHH0</i>';
	}

	public function option_html_google_ads_conversion_id() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_conversion_id'
			   name='wgact_plugin_options[google][ads][conversion_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['google']['ads']['conversion_id']); ?>'
		/>
		<?php
		self::display_status_icon($this->options['google']['ads']['conversion_id']);
		self::get_documentation_html_by_key('google_ads_conversion_id');
		self::output_advanced_section_cog_html('google');
		self::wistia_video_icon('vcuj59kbm6');
		self::wistia_video_icon('and34arrgg');
		echo '<br><br>';
		esc_html_e('The conversion ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>123456789</i>';
	}

	public function option_html_google_ads_conversion_label() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_conversion_label'
			   name='wgact_plugin_options[google][ads][conversion_label]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['google']['ads']['conversion_label']); ?>'
		/>
		<?php
		self::display_status_icon($this->options['google']['ads']['conversion_label'], $this->options['google']['ads']['conversion_id']);
		self::get_documentation_html_by_key('google_ads_conversion_label');
		self::output_advanced_section_cog_html('google');
		self::wistia_video_icon('vcuj59kbm6');
		self::wistia_video_icon('and34arrgg');
		echo '<br><br>';
		esc_html_e('The purchase conversion label looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>Xt19CO3axGAX0vg6X3gM</i>';
		if ($this->options['google']['ads']['conversion_label'] && !$this->options['google']['ads']['conversion_id']) {
			echo '<p><span class="dashicons dashicons-info"></span>';
			esc_html_e('Requires an active Google Ads Conversion ID', 'woocommerce-google-adwords-conversion-tracking-tag');
		}
		echo '</p>';
	}

	public function option_html_google_optimize_container_id() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_google_optimize_container_id'
			   name='wgact_plugin_options[google][optimize][container_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['google']['optimize']['container_id']); ?>'
		/>
		<?php
		self::display_status_icon($this->options['google']['optimize']['container_id'], true, true);
//        echo self::get_documentation_html('/wgact/#/plugin-configuration?id=configure-the-plugin');
		self::get_documentation_html_by_key('google_optimize_container_id');
		echo '<br><br>';
		esc_html_e('The Google Optimize container ID looks like this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>GTM-WMAB1BM</i>&nbsp;';
		esc_html_e('or', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>OPT-WMAB1BM</i>';
	}

	public function option_html_vwo_account_id() {
		?>
		<input class="pmw mono"
			   id='pmw_plugin_vwo_account_id'
			   name='wgact_plugin_options[pixels][vwo][account_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['pixels']['vwo']['account_id']); ?>'
		/>
		<?php
		self::display_status_icon($this->options['pixels']['vwo']['account_id'], true, true);
		self::get_documentation_html_by_key('vwo_account_id');
		echo '<br><br>';
		esc_html_e('The VWO account ID looks like this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>737312</i>&nbsp;';
	}

	public function option_html_optimizely_project_id() {
		?>
		<input class="pmw mono"
			   id='pmw_plugin_optimizely_project_id'
			   name='wgact_plugin_options[pixels][optimizely][project_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['pixels']['optimizely']['project_id']); ?>'
		/>
		<?php
		self::display_status_icon($this->options['pixels']['optimizely']['project_id'], true, true);
		self::get_documentation_html_by_key('optimizely_project_id');
		echo '<br><br>';
		esc_html_e('The Optimizely project ID looks like this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>20297535627</i>&nbsp;';
	}

	public function option_html_ab_tasty_account_id() {
		?>
		<input class="pmw mono"
			   id='pmw_plugin_ab_tasty_account_id'
			   name='wgact_plugin_options[pixels][ab_tasty][account_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['pixels']['ab_tasty']['account_id']); ?>'
		/>
		<?php
		self::display_status_icon($this->options['pixels']['ab_tasty']['account_id'], true, true);
		self::get_documentation_html_by_key('ab_tasty_account_id');
		echo '<br><br>';
		esc_html_e('The AB Tasty account ID looks like this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>3d09baddc21a365b7da5ae4d0aa5cb95</i>&nbsp;';
	}

	public function option_html_facebook_pixel_id() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_facebook_pixel_id'
			   name='wgact_plugin_options[facebook][pixel_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['facebook']['pixel_id']); ?>'
		/>
		<?php
		self::display_status_icon($this->options['facebook']['pixel_id']);
		self::get_documentation_html_by_key('facebook_pixel_id');
		self::output_advanced_section_cog_html('facebook');
		echo '<br><br>';
		esc_html_e('The Meta (Facebook) pixel ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>765432112345678</i>';
	}

	public function option_html_adroll_advertiser_id() {
		?>
		<input class="pmw mono"
			   id='pmw_adroll_advertiser_id'
			   name='wgact_plugin_options[pixels][adroll][advertiser_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e(Options::get_adroll_advertiser_id()); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon(Options::is_adroll_advertiser_id_set(), Options::is_adroll_pixel_id_set());
		self::get_documentation_html_by_key('adroll_advertiser_id');
		self::wistia_video_icon('xwr0q08bk0');
		self::html_pro_feature();
		echo '<br><br>';

		if (
			Options::is_adroll_advertiser_id_set()
			&& !Options::is_adroll_pixel_id_set()
		) {
			?>
			<p style="margin: 10px 0 10px 0">
				<span class="dashicons dashicons-info" style="padding-right: 10px"></span>
				<?php
				esc_html_e(
					'Requires an active Adroll pixel ID.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				);
				?>
			</p>
			<?php
		}

		esc_html_e('The Adroll advertiser ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>ABCD1EF2GHIJKLMNOPQRS3</i>';
	}

	public function option_html_adroll_pixel_id() {
		?>
		<input class="pmw mono"
			   id='pmw_adroll_pixel_id'
			   name='wgact_plugin_options[pixels][adroll][pixel_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e(Options::get_adroll_pixel_id()); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon(Options::is_adroll_pixel_id_set(), Options::is_adroll_advertiser_id_set());
		self::get_documentation_html_by_key('adroll_pixel_id');
		self::wistia_video_icon('xwr0q08bk0');
		self::html_pro_feature();
		echo '<br><br>';

		if (
			Options::is_adroll_pixel_id_set()
			&& !Options::is_adroll_advertiser_id_set()
		) {
			?>
			<p style="margin: 10px 0 10px 0">
				<span class="dashicons dashicons-info" style="padding-right: 10px"></span>
				<?php
				esc_html_e(
					'Requires an active Adroll advertiser ID.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				);
				?>
			</p>
			<?php
		}

		esc_html_e('The Adroll pixel ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>ABCD1EFGHIJKLMN2O3PQR</i>';
	}

	public function option_html_linkedin_partner_id() {
		?>
		<input class="pmw mono"
			   id='pmw_linkedin_partner_id'
			   name='wgact_plugin_options[pixels][linkedin][partner_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e(Options::get_linkedin_partner_id()); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon(Options::is_linkedin_active());
		self::get_documentation_html_by_key('linkedin_partner_id');
		self::output_advanced_section_cog_html('linkedin');
		self::wistia_video_icon('vvyiav46ft');
		self::wistia_video_icon('zrrp8aq4g0');
		self::html_pro_feature();
		echo '<br><br>';

		esc_html_e('The LinkedIn partner ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>1234567</i>';
	}

	public function setting_linkedin_search() {

		$text_length = max(strlen($this->options['pixels']['linkedin']['conversion_ids']['search']), 14);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_linkedin_search'
				name='wgact_plugin_options[pixels][linkedin][conversion_ids][search]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['pixels']['linkedin']['conversion_ids']['search']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['pixels']['linkedin']['conversion_ids']['search'], $this->options['pixels']['linkedin']['partner_id']);
		self::get_documentation_html_by_key('linkedin_event_ids');
		self::wistia_video_icon('zrrp8aq4g0');
		self::html_pro_feature();
	}

	public function setting_linkedin_view_content() {

		$text_length = max(strlen($this->options['pixels']['linkedin']['conversion_ids']['view_content']), 14);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_linkedin_view_content'
				name='wgact_plugin_options[pixels][linkedin][conversion_ids][view_content]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['pixels']['linkedin']['conversion_ids']['view_content']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['pixels']['linkedin']['conversion_ids']['view_content'], $this->options['pixels']['linkedin']['partner_id']);
		self::get_documentation_html_by_key('linkedin_event_ids');
		self::wistia_video_icon('zrrp8aq4g0');
		self::html_pro_feature();
	}

	public function setting_linkedin_add_to_list() {

		$text_length = max(strlen($this->options['pixels']['linkedin']['conversion_ids']['add_to_list']), 14);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_linkedin_add_to_list'
				name='wgact_plugin_options[pixels][linkedin][conversion_ids][add_to_list]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['pixels']['linkedin']['conversion_ids']['add_to_list']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['pixels']['linkedin']['conversion_ids']['add_to_list'], $this->options['pixels']['linkedin']['partner_id']);
		self::get_documentation_html_by_key('linkedin_event_ids');
		self::wistia_video_icon('zrrp8aq4g0');
		self::html_pro_feature();
	}

	public function setting_linkedin_add_to_cart() {

		$text_length = max(strlen($this->options['pixels']['linkedin']['conversion_ids']['add_to_cart']), 14);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_linkedin_add_to_cart'
				name='wgact_plugin_options[pixels][linkedin][conversion_ids][add_to_cart]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['pixels']['linkedin']['conversion_ids']['add_to_cart']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['pixels']['linkedin']['conversion_ids']['add_to_cart'], $this->options['pixels']['linkedin']['partner_id']);
		self::get_documentation_html_by_key('linkedin_event_ids');
		self::wistia_video_icon('zrrp8aq4g0');
		self::html_pro_feature();
	}

	public function setting_linkedin_start_checkout() {

		$text_length = max(strlen($this->options['pixels']['linkedin']['conversion_ids']['start_checkout']), 14);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_linkedin_start_checkout'
				name='wgact_plugin_options[pixels][linkedin][conversion_ids][start_checkout]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['pixels']['linkedin']['conversion_ids']['start_checkout']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['pixels']['linkedin']['conversion_ids']['start_checkout'], $this->options['pixels']['linkedin']['partner_id']);
		self::get_documentation_html_by_key('linkedin_event_ids');
		self::wistia_video_icon('zrrp8aq4g0');
		self::html_pro_feature();
	}

	public function setting_linkedin_purchase() {

		$text_length = max(strlen($this->options['pixels']['linkedin']['conversion_ids']['purchase']), 14);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_linkedin_purchase'
				name='wgact_plugin_options[pixels][linkedin][conversion_ids][purchase]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['pixels']['linkedin']['conversion_ids']['purchase']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['pixels']['linkedin']['conversion_ids']['purchase'], $this->options['pixels']['linkedin']['partner_id']);
		self::get_documentation_html_by_key('linkedin_event_ids');
		self::wistia_video_icon('zrrp8aq4g0');
		self::html_pro_feature();
	}

	public function option_html_bing_uet_tag_id() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_bing_uet_tag_id'
			   name='wgact_plugin_options[bing][uet_tag_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['bing']['uet_tag_id']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['bing']['uet_tag_id']);
		self::get_documentation_html_by_key('bing_uet_tag_id');
		self::html_pro_feature();
		echo '<br><br>';
		esc_html_e('The Microsoft Advertising UET tag ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>12345678</i>';
	}

	public function option_html_twitter_pixel_id() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_twitter_pixel_id'
			   name='wgact_plugin_options[twitter][pixel_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['twitter']['pixel_id']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['twitter']['pixel_id']);
		self::get_documentation_html_by_key('twitter_pixel_id');
		self::output_advanced_section_cog_html('twitter');
		self::html_pro_feature();
		echo '<br><br>';
		esc_html_e('The Twitter pixel ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>a1cde</i>';
	}

	public function setting_twitter_add_to_cart() {

		$text_length = max(strlen($this->options['twitter']['event_ids']['add_to_cart']), 14);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_twitter_add_to_cart'
				name='wgact_plugin_options[twitter][event_ids][add_to_cart]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['twitter']['event_ids']['add_to_cart']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['twitter']['event_ids']['add_to_cart'], $this->options['twitter']['pixel_id']);
		self::get_documentation_html_by_key('twitter_event_ids');
		self::html_pro_feature();
	}

	public function setting_twitter_add_to_wishlist() {

		$text_length = max(strlen($this->options['twitter']['event_ids']['add_to_wishlist']), 14);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_twitter_add_to_wishlist'
				name='wgact_plugin_options[twitter][event_ids][add_to_wishlist]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['twitter']['event_ids']['add_to_wishlist']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['twitter']['event_ids']['add_to_wishlist'], $this->options['twitter']['pixel_id']);
		self::get_documentation_html_by_key('twitter_event_ids');
		self::html_pro_feature();
	}

	public function setting_twitter_view_content() {

		$text_length = max(strlen($this->options['twitter']['event_ids']['view_content']), 14);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_twitter_view_content'
				name='wgact_plugin_options[twitter][event_ids][view_content]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['twitter']['event_ids']['view_content']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['twitter']['event_ids']['view_content'], $this->options['twitter']['pixel_id']);
		self::get_documentation_html_by_key('twitter_event_ids');
		self::html_pro_feature();
	}

	public function setting_twitter_search() {

		$text_length = max(strlen($this->options['twitter']['event_ids']['search']), 14);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_twitter_search'
				name='wgact_plugin_options[twitter][event_ids][search]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['twitter']['event_ids']['search']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['twitter']['event_ids']['search'], $this->options['twitter']['pixel_id']);
		self::get_documentation_html_by_key('twitter_event_ids');
		self::html_pro_feature();
	}

	public function setting_twitter_initiate_checkout() {

		$text_length = max(strlen($this->options['twitter']['event_ids']['initiate_checkout']), 14);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_twitter_initiate_checkout'
				name='wgact_plugin_options[twitter][event_ids][initiate_checkout]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['twitter']['event_ids']['initiate_checkout']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['twitter']['event_ids']['initiate_checkout'], $this->options['twitter']['pixel_id']);
		self::get_documentation_html_by_key('twitter_event_ids');
		self::html_pro_feature();
	}

	public function setting_twitter_purchase() {

		$text_length = max(strlen($this->options['twitter']['event_ids']['purchase']), 14);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_twitter_purchase'
				name='wgact_plugin_options[twitter][event_ids][purchase]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['twitter']['event_ids']['purchase']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['twitter']['event_ids']['purchase'], $this->options['twitter']['pixel_id']);
		self::get_documentation_html_by_key('twitter_event_ids');
		self::html_pro_feature();
	}

	public function option_html_outbrain_advertiser_id() {
		?>
		<input class="pmw mono"
			   id='pmw_plugin_outbrain_advertiser_id'
			   name='wgact_plugin_options[pixels][outbrain][advertiser_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['pixels']['outbrain']['advertiser_id']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon(Options::is_outbrain_active());
		self::get_documentation_html_by_key('outbrain_advertiser_id');
		self::wistia_video_icon('56u98s3rh6');
		self::wistia_video_icon('sj8aig7b6q');
		self::html_pro_feature();
		echo '<br><br>';
		esc_html_e('The Outbrain advertiser ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');

		echo '&nbsp;<i>001a2b3cd4e5fgh6i789j01234k567890l</i>';
	}

	public function option_html_pinterest_pixel_id() {
		?>
		<input class="pmw mono"
			   id='pmw_plugin_pinterest_pixel_id'
			   name='wgact_plugin_options[pinterest][pixel_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['pinterest']['pixel_id']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['pinterest']['pixel_id']);
		self::get_documentation_html_by_key('pinterest_pixel_id');
		self::output_advanced_section_cog_html('pinterest');
		self::html_pro_feature();
		echo '<br><br>';
		esc_html_e('The Pinterest pixel ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>1234567890123</i>';
	}

	public function option_html_pinterest_enhanced_match() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[pinterest][enhanced_match]'>
			<input type='checkbox' id='wpm_plugin_pinterest_enhanced_match'
				   name='wgact_plugin_options[pinterest][enhanced_match]'
				   value='1' <?php checked($this->options['pinterest']['enhanced_match']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>

			<?php
			esc_html_e('Enable Pinterest enhanced match', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['pinterest']['enhanced_match'], Options::is_pinterest_active(), true);
		self::get_documentation_html_by_key('pinterest_enhanced_match');
		self::html_pro_feature();
		?>
		<?php
//		self::get_documentation_html_by_key('pinterest_enhanced_match');
	}

	public function option_html_snapchat_pixel_id() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_snapchat_pixel_id'
			   name='wgact_plugin_options[snapchat][pixel_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['snapchat']['pixel_id']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['snapchat']['pixel_id']);
		self::get_documentation_html_by_key('snapchat_pixel_id');
		self::html_pro_feature();
		echo '<br><br>';
		esc_html_e('The Snapchat pixel ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');

		echo '&nbsp;<i>1a2345b6-cd78-9012-e345-fg6h7890ij12</i>';
	}

	public function option_html_taboola_account_id() {
		?>
		<input class="pmw mono"
			   id='pmw_plugin_taboola_account_id'
			   name='wgact_plugin_options[pixels][taboola][account_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['pixels']['taboola']['account_id']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon(Options::is_taboola_active());
		self::get_documentation_html_by_key('taboola_account_id');
		self::wistia_video_icon('fw6rwcw1sf');
		self::wistia_video_icon('q3hhvz9mgt');
		self::html_pro_feature();
		echo '<br><br>';
		esc_html_e('The Taboola account ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');

		echo '&nbsp;<i>1234567</i>';
	}

	public function option_html_tiktok_pixel_id() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_tiktok_pixel_id'
			   name='wgact_plugin_options[tiktok][pixel_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['tiktok']['pixel_id']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['tiktok']['pixel_id']);
		self::get_documentation_html_by_key('tiktok_pixel_id');
		self::output_advanced_section_cog_html('tiktok');
		self::html_pro_feature();
		echo '<br><br>';
		esc_html_e('The TikTok pixel ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>ABCD1E2FGH3IJK45LMN6</i>';
	}

	public function option_html_hotjar_site_id() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_hotjar_site_id'
			   name='wgact_plugin_options[hotjar][site_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['hotjar']['site_id']); ?>'/>
		<?php
		self::display_status_icon($this->options['hotjar']['site_id']);
		self::get_documentation_html_by_key('hotjar_site_id');
		self::wistia_video_icon('z9tjk5eia7');
		echo '<br><br>';
		esc_html_e('The Hotjar site ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '&nbsp;<i>1234567</i>';
	}

	/**
	 * 2023.09.11: Reddit renamed the advertiser ID to pixel ID.
	 * Don't change the database key, because that would break the plugin for existing users.
	 */
	public function option_html_reddit_pixel_id() {
		?>
		<input class="pmw mono"
			   id='plugin_reddit_pixel_id'
			   name='wgact_plugin_options[pixels][reddit][advertiser_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e(Options::get_reddit_advertiser_id()); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php self::display_status_icon(Options::get_reddit_advertiser_id(), true, true); ?>
		<?php self::get_documentation_html_by_key('reddit_advertiser_id'); ?>
		<?php self::output_advanced_section_cog_html('reddit'); ?>
		<?php self::wistia_video_icon('3cr5pwksrf'); ?>
		<?php self::html_pro_feature(); ?>
		<p style="margin-top:16px">
			<?php esc_html_e('The Reddit pixel ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			<?php echo '&nbsp;<i>t2_gvnawxpb</i>'; ?>
		</p>
		<?php
	}

	public function option_html_reddit_advanced_matching() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[pixels][reddit][advanced_matching]'>
			<input type='checkbox'
				   id='plugin_reddit_advanced_matching'
				   name='wgact_plugin_options[pixels][reddit][advanced_matching]'
				   value='1'
				<?php checked(Options::is_reddit_advanced_matching_enabled()); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php esc_html_e('Enable Reddit advanced matching', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</label>
		<?php
		self::display_status_icon(Options::is_reddit_advanced_matching_enabled(), Options::is_reddit_active(), true);
		self::get_documentation_html_by_key('reddit_advanced_matching');
		self::html_pro_feature();
	}

	public function option_html_shop_order_total_logic() {
		?>
		<label>
			<input type='radio' id='wpm_plugin_order_total_logic_0'
				   name='wgact_plugin_options[shop][order_total_logic]'
				   value='0' <?php echo( checked(0, $this->options['shop']['order_total_logic'], false) ); ?> >
			<?php esc_html_e('Order Subtotal: Doesn\'t include tax, shipping, and if available, fees like PayPal or Stripe fees (default)', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			<?php self::get_documentation_html_by_key('order_subtotal'); ?>
		</label>
		<br>
		<label>
			<input type='radio' id='wpm_plugin_order_total_logic_1'
				   name='wgact_plugin_options[shop][order_total_logic]'
				   value='1' <?php echo( checked(1, $this->options['shop']['order_total_logic'], false) ); ?> >
			<?php esc_html_e('Order Total: Includes tax and shipping', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			<?php self::get_documentation_html_by_key('order_total'); ?>

		</label>
		<?php if (wpm_fs()->can_use_premium_code__premium_only() || '2' === $this->options['shop']['order_total_logic'] || Options::pro_version_demo_active()) : ?>
			<br>
			<label>
				<input type='radio' id='wpm_plugin_order_total_logic_2'
					   name='wgact_plugin_options[shop][order_total_logic]'
					   value='2' <?php echo( checked(2, $this->options['shop']['order_total_logic'], false) ); ?>
					<?php if (!Environment::is_a_cog_plugin_active() || !wpm_fs()->can_use_premium_code__premium_only()) : ?>
						disabled
					<?php endif; ?>
				>
				<?php esc_html_e('Profit Margin: Only reports the profit margin. Excludes tax, shipping, and where possible, gateway fees.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				<?php self::get_documentation_html_by_key('order_profit_margin'); ?>
				<?php self::html_pro_feature(); ?>
			</label>
		<?php endif; ?>
		<div style="margin-top: 10px">
			<div>
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e('This is the order total amount reported back to the marketing pixels (such as Google Ads, Meta (Facebook), etc.). It excludes statistics pixels.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				<?php if ( wpm_fs()->can_use_premium_code__premium_only() && !Environment::is_a_cog_plugin_active() ) : ?>
			</div>
			<div style="margin-top: 10px">
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e('To use the Profit Margin setting you will need to install one of the following two Cost of Goods plugins:', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				<a href="https://woocommerce.com/products/woocommerce-cost-of-goods/" target="_blank">WooCommerce Cost
					of Goods (SkyVerge)</a>
				<?php esc_html_e('or', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				<a href="https://wordpress.org/plugins/cost-of-goods-for-woocommerce/" target="_blank">Cost of Goods for
					WooCommerce (WPFactory)</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private static function get_documentation_html_by_key( $key = 'default' ) {
		return self::get_documentation_html(Documentation::get_link($key));
	}

	private static function get_documentation_html( $path ) {
//		$html  = '<a class="pmw-documentation-icon" href="' . $path . '" target="_blank">';
//		$html .= '<span style="vertical-align: top; margin-top: 0px" class="dashicons dashicons-info-outline tooltip"><span class="tooltiptext">';
//		$html .= esc_html__('open the documentation', 'woocommerce-google-adwords-conversion-tracking-tag');
//		$html .= '</span></span></a>';
//
//		return $html;
		?>
		<a class="pmw-documentation-icon" href="<?php echo esc_url($path); ?>" target="_blank">
			<span style="vertical-align: top; margin-top: 0" class="dashicons dashicons-info-outline pmw-tooltip">
				<span class="tooltiptext">
					<?php esc_html_e('open the documentation', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</span>
			</span>
		</a>

		<?php
	}

	private static function output_advanced_section_cog_html( $subsection ) {
		?>
		<a href="#" class="advanced-section-link pmw-tooltip"
		   data-as-section="advanced" data-as-subsection="<?php esc_html_e($subsection); ?>">
			<svg class="advanced-settings-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
				<path d="M14 21h-4l-.551-2.48a6.991 6.991 0 0 1-1.819-1.05l-2.424.763-2-3.464 1.872-1.718a7.055 7.055 0 0 1 0-2.1L3.206 9.232l2-3.464 2.424.763A6.992 6.992 0 0 1 9.45 5.48L10 3h4l.551 2.48a6.992 6.992 0 0 1 1.819 1.05l2.424-.763 2 3.464-1.872 1.718a7.05 7.05 0 0 1 0 2.1l1.872 1.718-2 3.464-2.424-.763a6.99 6.99 0 0 1-1.819 1.052L14 21z"/>
				<circle cx="12" cy="12" r="3"/>
			</svg>
			<span class="tooltiptext">
				<?php esc_html_e('advanced settings', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			</span>
		</a>
		<?php
	}

	private function get_documentation_html_e( $path ) {

		$html = '<a class="pmw-documentation-icon" href="' . $path . '" target="_blank">';
		$html .= '<span style="vertical-align: top; margin-top: 0px" class="dashicons dashicons-info-outline pmw-tooltip"><span class="tooltiptext">';
		$html .= esc_html__('open the documentation', 'woocommerce-google-adwords-conversion-tracking-tag');
		$html .= '</span></span></a>';

		return $html;
	}

	public function setting_html_google_consent_mode_active() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[google][consent_mode][active]'>
			<input type='checkbox' id='wpm_setting_google_consent_mode_active'
				   name='wgact_plugin_options[google][consent_mode][active]'
				   value='1'
				<?php checked($this->options['google']['consent_mode']['active']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php esc_html_e('Enable Google consent mode with standard settings', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</label>
		<?php
		self::display_status_icon($this->options['google']['consent_mode']['active'], true, true);
		?>
		<?php
		self::get_documentation_html_by_key('google_consent_mode');
		self::html_pro_feature();
	}

	public function setting_html_google_consent_regions() {
		// https://semantic-ui.com/modules/dropdown.html#multiple-selection
		// https://developer.woocommerce.com/2017/08/08/selectwoo-an-accessible-replacement-for-select2/
		// https://github.com/woocommerce/selectWoo
		?>
		<select id="wpm_setting_google_consent_regions" multiple="multiple"
				name="wgact_plugin_options[google][consent_mode][regions][]"
				style="width:350px;"
				data-placeholder="<?php esc_html_e('Choose countries', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>&hellip;"
				aria-label="Country"
				class="wc-enhanced-select"
			<?php esc_html_e(self::disable_if_demo()); ?>
		>
			<?php foreach (Consent_Mode_Regions::get_consent_mode_regions() as $region_code => $region_name) : ?>
				<option
						value="<?php esc_html_e($region_code); ?>"
					<?php
					// Rarely Options::get_options_obj()->google->consent_mode->regions is null
					// The reason is a mystery. It happens in very rare cases and can't be reproduced.
					// So we have to check if it is an array before using in_array()
					if (is_array(Options::get_options_obj()->google->consent_mode->regions)) {
						esc_html_e(in_array($region_code, Options::get_options_obj()->google->consent_mode->regions) ? 'selected' : '');
					}
					?>
				><?php esc_html_e($region_name); ?></option>
			<?php endforeach; ?>

		</select>
		<script>
			jQuery("#wpm_setting_google_consent_regions").select2({
				// theme: "classic"
			})
		</script>
		<?php
		self::get_documentation_html_by_key('google_consent_regions');
		self::html_pro_feature();
		?>
		<p>
			<span class="dashicons dashicons-info"></span>
			<?php
			esc_html_e('If no region is set, then the restrictions are enabled for all regions. If you specify one or more regions, then the restrictions only apply for the specified regions.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</p>
		<?php
	}

	public function info_html_google_analytics_eec() {
		esc_html_e('Google Analytics Enhanced E-Commerce is ', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(wpm_fs()->can_use_premium_code__premium_only() && $this->google->is_google_analytics_active());
		self::html_pro_feature();
//		self::get_documentation_html_by_key('eec');
	}

	public function setting_html_google_analytics_4_api_secret() {
		?>
		<input class="pmw mono"
			   id='wpm_setting_google_analytics_4_api_secret'
			   name='wgact_plugin_options[google][analytics][ga4][api_secret]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['google']['analytics']['ga4']['api_secret']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['google']['analytics']['ga4']['api_secret']);
		self::get_documentation_html_by_key('google_analytics_4_api_secret');
		self::html_pro_feature();
		echo '<br><br>';
		if (!$this->options['google']['analytics']['ga4']['measurement_id']) {
			echo '<p></p><span class="dashicons dashicons-info" style="margin-right: 10px"></span>';
			esc_html_e('Google Analytics 4 activation required', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '</p>';
		}
		esc_html_e('If enabled, purchase and refund events will be sent to Google through the measurement protocol for increased accuracy.', 'woocommerce-google-adwords-conversion-tracking-tag');
	}

	public function setting_html_ga4_property_id() {
		?>
		<input class="pmw mono"
			   id='pmw_setting_ga4_property_id'
			   name='wgact_plugin_options[google][analytics][ga4][data_api][property_id]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['google']['analytics']['ga4']['data_api']['property_id']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['google']['analytics']['ga4']['data_api']['property_id'], count($this->options['google']['analytics']['ga4']['data_api']['credentials']) > 0);
		self::get_documentation_html_by_key('ga4_data_api_property_id');
		self::html_pro_feature();
//		echo '<br><br>';
//		if (!$this->options['google']['analytics']['ga4']['measurement_id']) {
//			echo '<p></p><span class="dashicons dashicons-info" style="margin-right: 10px"></span>';
//			esc_html_e('Google Analytics 4 activation required', 'woocommerce-google-adwords-conversion-tracking-tag');
//			echo '</p>';
//		}
//		esc_html_e('If enabled, purchase and refund events will be sent to Google through the measurement protocol for increased accuracy.', 'woocommerce-google-adwords-conversion-tracking-tag');
	}

	public function setting_html_g4_data_api_credentials() {

		$client_email
			= isset($this->options['google']['analytics']['ga4']['data_api']['credentials']['client_email'])
			? $this->options['google']['analytics']['ga4']['data_api']['credentials']['client_email']
			: '';

		$text_length = max(strlen($client_email), 80);
		?>
		<div style="margin-top: 5px">

			<input class="pmw mono"
				   id='pmw_setting_ga4_data_api_client_email'
				   name='pmw_setting_ga4_data_api_client_email'
				   size='<?php esc_html_e($text_length); ?>'
				   type='text'
				   style="width:<?php esc_html_e($text_length); ?>ch"
				   value='<?php esc_html_e($client_email); ?>'
				   disabled
			/>

			<script>
				const pmwCopyGa4ClientEmailToClipboardCA = () => {
					navigator.clipboard.writeText(document.getElementById("pmw_setting_ga4_data_api_client_email").value)
					const pmwCaFeedTooltip     = document.getElementById("myPmwGa4ClientEmailTooltip")
					pmwCaFeedTooltip.innerHTML = "<?php esc_html_e('Copied the account email to the clipboard', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>"
				}

				const resetGa4ClientEmailCopyButton = () => {
					const pmwCaFeedTooltip     = document.getElementById("myPmwGa4ClientEmailTooltip")
					pmwCaFeedTooltip.innerHTML = "<?php esc_html_e('Copy to clipboard', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>"
				}
			</script>

			<div class="pmwCaTooltip">
				<a href="javascript:void(0)" class="pmw-copy-icon pmwCaTooltip"
				   onclick="pmwCopyGa4ClientEmailToClipboardCA()" onmouseout="resetGa4ClientEmailCopyButton()"></a>
				<span class="pmwCaTooltiptext"
					  id="myPmwGa4ClientEmailTooltip"><?php esc_html_e('Copy to clipboard', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></span>
			</div>


			<?php self::display_status_icon(isset($this->options['google']['analytics']['ga4']['data_api']['credentials']['client_email']), $this->options['google']['analytics']['ga4']['data_api']['property_id']); ?>
			<?php self::get_documentation_html_by_key('ga4_data_api_credentials'); ?>
			<?php self::html_pro_feature(); ?>

			<div style="margin-top: 5px">

				<!-- Import Settings -->
				<div class="button">
					<div>
						<label for="ga4-data-api-credentials-upload-button">
							<?php esc_html_e('Import credentials', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
							<input type="file" id="ga4-data-api-credentials-upload-button" style="display: none;"/>
						</label>
					</div>
				</div>
				<!-- Import Settings -->

				<!-- Delete Settings -->
				<div class="button">
					<div>
						<label for="ga4-data-api-credentials-delete-button">
							<?php esc_html_e('Delete credentials', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
							<input id="ga4-data-api-credentials-delete-button" style="display: none;"/>
						</label>
					</div>
				</div>
				<!-- Delete Settings -->
			</div>

			<div style="margin-top: 20px">
				<?php if (!$this->options['google']['analytics']['ga4']['data_api']['property_id']) : ?>
					<span class="dashicons dashicons-info" style="padding-right: 10px"></span>
					<?php esc_html_e('The GA4 property ID must be set.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				<?php endif; ?>
			</div>

			<div>
				<pre id="ga4-api-credentials-upload-status-success" style="display: none; white-space: pre-line;">
					<span style="color: green; font-weight: bold">
						<?php esc_html_e('Settings imported successfully!', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</span>
					<span>
						<?php esc_html_e('Reloading...(in 5 seconds)!', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</span>
				</pre>

				<pre id="ga4-api-credentials-upload-status-error" style="display: none; white-space: pre-line;">
					<span style="color: red; font-weight: bold">
						<?php esc_html_e('There was an error importing that file! Please try again.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</span>
					<span id="ga4-api-credentials-upload-status-error-message"></span>
				</pre>
			</div>
		</div>
		<?php
	}

	public function setting_html_g4_page_load_time_tracking() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0'
				   name='wgact_plugin_options[google][analytics][ga4][page_load_time_tracking]'>
			<input type='checkbox' id='pmw_setting_ga4_page_load_time_tracking'
				   name='wgact_plugin_options[google][analytics][ga4][page_load_time_tracking]'
				   value='1'
				<?php checked($this->options['google']['analytics']['ga4']['page_load_time_tracking']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php
			esc_html_e('GA4 page load time tracking.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['google']['analytics']['ga4']['page_load_time_tracking'], true, true);
		self::get_documentation_html_by_key('ga4_page_load_time_tracking');
		self::html_pro_feature();
	}

	public function setting_html_google_analytics_link_attribution() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[google][analytics][link_attribution]'>
			<input type='checkbox' id='wpm_setting_google_analytics_link_attribution'
				   name='wgact_plugin_options[google][analytics][link_attribution]'
				   value='1' <?php checked($this->options['google']['analytics']['link_attribution']); ?>
			/>
			<?php
			esc_html_e('Enable Google Analytics enhanced link attribution', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon(
			$this->options['google']['analytics']['link_attribution'],
			$this->options['google']['analytics']['universal']['property_id'] || $this->options['google']['analytics']['ga4']['measurement_id'],
			true
		);
		?>
		<?php
//        echo self::get_documentation_html('/wgact/?utm_source=woocommerce-plugin&utm_medium=documentation-link&utm_campaign=pixel-manager-for-woocommerce-docs&utm_content=google-consent-mode#/consent-mgmt/google-consent-mode');
		?>
		<?php
		if ($this->options['google']['analytics']['link_attribution'] && ( !$this->options['google']['analytics']['universal']['property_id'] && !$this->options['google']['analytics']['ga4']['measurement_id'] )) {
			echo '<p></p><span class="dashicons dashicons-info"></span>';
			esc_html_e('You need to activate at least Google Analytics UA or Google Analytics 4', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '</p><br>';
		}
	}

	public function setting_html_google_user_id() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[google][user_id]'>
			<input type='checkbox' id='wpm_setting_google_user_id'
				   name='wgact_plugin_options[google][user_id]'
				   value='1'
				<?php checked($this->options['google']['user_id']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php
			esc_html_e('Enable Google user ID', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['google']['user_id'], $this->options['google']['analytics']['universal']['property_id'] || $this->options['google']['analytics']['ga4']['measurement_id'] || $this->google->is_google_ads_active(), true);
		self::html_pro_feature();
//        echo self::get_documentation_html('/wgact/?utm_source=woocommerce-plugin&utm_medium=documentation-link&utm_campaign=pixel-manager-for-woocommerce-docs&utm_content=google-consent-mode#/consent-mgmt/google-consent-mode');
		?>
		<?php
		if (!$this->options['google']['analytics']['universal']['property_id'] && !$this->options['google']['analytics']['ga4']['measurement_id']) {
			echo '<p></p><span class="dashicons dashicons-info"></span>';
			esc_html_e('You need to activate at least Google Analytics UA or Google Analytics 4', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '</p><br>';
		}
	}

	public function setting_html_google_ads_enhanced_conversions() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[google][ads][enhanced_conversions]'>
			<input type='checkbox' id='wpm_setting_google_user_id'
				   name='wgact_plugin_options[google][ads][enhanced_conversions]'
				   value='1'
				<?php checked($this->options['google']['ads']['enhanced_conversions']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php
			esc_html_e('Enable Google Ads Enhanced Conversions', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['google']['ads']['enhanced_conversions'], $this->google->is_google_ads_active(), false);
		self::get_documentation_html_by_key('google_ads_enhanced_conversions');
		self::wistia_video_icon('73e5op37xg');
		self::html_pro_feature();
		if (!$this->google->is_google_ads_active()) {
			echo '<p></p><span class="dashicons dashicons-info"></span>';
			esc_html_e('You need to activate Google Ads', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '</p><br>';
		}
	}

	public function setting_html_google_ads_phone_conversion_number() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_google_ads_phone_conversion_number'
			   name='wgact_plugin_options[google][ads][phone_conversion_number]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['google']['ads']['phone_conversion_number']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['google']['ads']['phone_conversion_number'], $this->options['google']['ads']['phone_conversion_label'] && $this->options['google']['ads']['phone_conversion_number']);
		self::get_documentation_html_by_key('google_ads_phone_conversion_number');
		self::html_pro_feature();
		echo '<br><br>';
		esc_html_e('The Google Ads phone conversion number must be in the same format as on the website.', 'woocommerce-google-adwords-conversion-tracking-tag');
	}

	public function setting_html_google_ads_phone_conversion_label() {
		?>
		<input class="pmw mono"
			   id='wpm_plugin_google_ads_phone_conversion_label'
			   name='wgact_plugin_options[google][ads][phone_conversion_label]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['google']['ads']['phone_conversion_label']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['google']['ads']['phone_conversion_label'], $this->options['google']['ads']['phone_conversion_label'] && $this->options['google']['ads']['phone_conversion_number']);
		self::get_documentation_html_by_key('google_ads_phone_conversion_label');
		self::html_pro_feature();
		echo '<br><br>';
//        esc_html_e('The Google Ads phone conversion label must be in the same format as on the website.', 'woocommerce-google-adwords-conversion-tracking-tag');
	}

	public function setting_html_google_ads_conversion_adjustments_conversion_name() {
		?>
		<input class="pmw mono"
			   id='pmw_plugin_google_ads_conversion_adjustments_conversion_name'
			   name='wgact_plugin_options[google][ads][conversion_adjustments][conversion_name]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e($this->options['google']['ads']['conversion_adjustments']['conversion_name']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php self::display_status_icon($this->options['google']['ads']['conversion_adjustments']['conversion_name'], $this->google->is_google_ads_active()); ?>
		<?php self::get_documentation_html_by_key('google_ads_conversion_adjustments'); ?>
		<?php self::html_pro_feature(); ?>
		<div style="margin-top: 20px">
			<span class="dashicons dashicons-info" style="margin-right: 10px"></span>
			<?php esc_html_e('The conversion name must match the conversion name in Google Ads exactly.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</div>
		<?php

		if (!$this->options['google']['ads']['conversion_id'] || !$this->options['google']['ads']['conversion_label']) {
			?>
			<span class="dashicons dashicons-info" style="padding-right: 10px"></span>
			<?php
			esc_html_e('Requires an active Google Ads Conversion ID and Conversion Label.', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '<br>';
		}
	}

	public function setting_html_google_ads_conversion_adjustments_feed() {

		$feed_url    = get_site_url() . Pixel_Manager::get_instance()->get_google_ads_conversion_adjustments_endpoint();
		$text_length = strlen($feed_url);
		?>
		<div style="margin-top: 5px">

			<input class="pmw mono"
				   id='pmw_plugin_google_ads_conversion_adjustments_feed'
				   name='pmw_plugin_google_ads_conversion_adjustments_feed'
				   size='<?php esc_html_e($text_length); ?>'
				   type='text'
				   style="width:<?php esc_html_e($text_length); ?>ch"
				   value='<?php echo esc_url($feed_url); ?>'
				   disabled
			/>
			<script>
				const pmwCopyToClipboardCA = () => {

					// const feedUrlElement = document.getElementById("pmw_plugin_google_ads_conversion_adjustments_feed")
					// feedUrlElement.select()
					// feedUrlElement.setSelectionRange(0, 99999)
					navigator.clipboard.writeText(document.getElementById("pmw_plugin_google_ads_conversion_adjustments_feed").value)

					const pmwCaFeedTooltip     = document.getElementById("myPmwCaTooltip")
					pmwCaFeedTooltip.innerHTML = "<?php esc_html_e('Copied feed URL to clipboard', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>"
				}

				const resetCaCopyButton = () => {
					const pmwCaFeedTooltip     = document.getElementById("myPmwCaTooltip")
					pmwCaFeedTooltip.innerHTML = "<?php esc_html_e('Copy to clipboard', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>"
				}
			</script>
			<div class="pmwCaTooltip">
				<a href="javascript:void(0)" class="pmw-copy-icon pmwCaTooltip"
				   onclick="pmwCopyToClipboardCA()" onmouseout="resetCaCopyButton()"></a>
				<span class="pmwCaTooltiptext"
					  id="myPmwCaTooltip"><?php esc_html_e('Copy to clipboard', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></span>
			</div>
			<?php self::display_status_icon($this->options['google']['ads']['conversion_adjustments']['conversion_name'], $this->google->is_google_ads_active()); ?>
			<?php self::get_documentation_html_by_key('google_ads_conversion_adjustments'); ?>
			<?php self::html_pro_feature(); ?>
			<div style="margin-top: 20px">
				<?php if (!$this->options['google']['ads']['conversion_adjustments']['conversion_name']) : ?>
					<span class="dashicons dashicons-info" style="padding-right: 10px"></span>
					<?php esc_html_e('The Conversion Name must be set.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public function setting_html_google_optimize_anti_flicker_snippet() {

		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612

		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[google][optimize][anti_flicker]'>
			<input type='checkbox' id='pmw_plugin_google_optimize_anti_flicker_snippet'
				   name='wgact_plugin_options[google][optimize][anti_flicker]'
				   value='1'
				<?php checked($this->options['google']['optimize']['anti_flicker']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php
			esc_html_e('Google Optimize Anti-Flicker Snippet', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php

		self::display_status_icon(Options::get_options_obj()->google->optimize->anti_flicker, Options::is_google_optimize_active(), true);
		self::html_pro_feature();
		self::get_documentation_html_by_key('google_optimize_anti_flicker');
		?>
		<div style="margin-top: 10px">
			<?php if (!Options::is_google_optimize_active() && Options::get_options_obj()->google->optimize->anti_flicker) : ?>
				<span class="dashicons dashicons-info" style="padding-right: 10px"></span>
				<?php esc_html_e('Enabling the Google Optimize Anti-Flicker Snippet requires Google Optimize to be active.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	public function setting_html_google_optimize_anti_flicker_snippet_timeout() {
		?>
		<input class="pmw mono"
			   id='pmw_plugin_google_optimize_anti_flicker_snippet_timeout'
			   name='wgact_plugin_options[google][optimize][anti_flicker_timeout]'
			   size='4'
			   type='text'
			   value='<?php esc_html_e($this->options['google']['optimize']['anti_flicker_timeout']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['google']['optimize']['anti_flicker'], true, true);
		self::get_documentation_html_by_key('google_optimize_anti_flicker_timeout');
		self::html_pro_feature();
//        esc_html_e('The Google Ads phone conversion label must be in the same format as on the website.', 'woocommerce-google-adwords-conversion-tracking-tag');
	}

	public function info_html_automatic_email_link_tracking() {
		esc_html_e('Automatic email link click tracking is ', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(Options::is_ga4_enabled());
		self::html_pro_feature();
	}

	public function info_html_automatic_phone_link_click_tracking() {
		esc_html_e('Automatic phone link click tracking is ', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(Options::is_ga4_enabled());
		self::html_pro_feature();
	}

	public function setting_html_borlabs_support() {
		esc_html_e('Borlabs Cookie detected. Automatic support is:', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(true, true, true);
		self::html_pro_feature();
	}

	public function setting_html_cookiebot_support() {
		esc_html_e('Cookiebot detected. Automatic support is:', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(true, true, true);
		self::html_pro_feature();
	}

	public function setting_html_complianz_support() {
		esc_html_e('Complianz GDPR detected. Automatic support is:', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(true, true, true);
		self::html_pro_feature();
	}

	public function setting_html_cookie_notice_support() {
		esc_html_e('Cookie Notice (by hu-manity.co) detected. Automatic support is:', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(true, true, true);
		self::html_pro_feature();
	}

	public function setting_html_cookie_script_support() {
		esc_html_e('Cookie Script (by cookie-script.com) detected. Automatic support is:', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(true, true, true);
		self::html_pro_feature();
	}

	public function setting_html_moove_gdpr_support() {
		esc_html_e('GDPR Cookie Compliance (by Moove Agency) detected. Automatic support is:', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(true, true, true);
		self::html_pro_feature();
	}

	public function setting_html_cookie_law_info_support() {
		esc_html_e('GDPR Cookie Consent (by WebToffee) detected. Automatic support is:', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(true, true, true);
		self::html_pro_feature();
	}

	public function setting_html_real_cookie_banner_support() {
		esc_html_e('Real Cookie Banner by devowl.io detected. Automatic support is:', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(true, true, true);
		self::html_pro_feature();
	}

	public function setting_html_termly_support() {
		esc_html_e('Termly CMP detected. Automatic support is:', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(true, true, true);
		self::html_pro_feature();
	}

	public function setting_html_explicit_consent_mode() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[shop][cookie_consent_mgmt][explicit_consent]'>
			<input type='checkbox' id='wpm_setting_explicit_consent_mode'
				   name='wgact_plugin_options[shop][cookie_consent_mgmt][explicit_consent]'
				   value='1'
				<?php checked($this->options['shop']['cookie_consent_mgmt']['explicit_consent']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php
			esc_html_e('Enable Explicit Consent Mode', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['shop']['cookie_consent_mgmt']['explicit_consent'], true, true);
		self::get_documentation_html_by_key('explicit_consent_mode');
		self::html_pro_feature();

		echo '<p style="margin-top:10px">';
		esc_html_e('Only activate the Explicit Consent Mode if you are also using a Cookie Management Platform (a cookie banner) that is compatible with this plugin. Find a list of compatible plugins in the documentation.', 'woocommerce-google-adwords-conversion-tracking-tag');
		echo '</p>';
	}

	public function setting_html_facebook_capi_token() {
		?>
		<textarea class="pmw mono"
				  id='wpm_setting_facebook_capi_token'
				  name='wgact_plugin_options[facebook][capi][token]'
				  cols='60'
				  rows='5'
		<?php esc_html_e(self::disable_if_demo()); ?>
		><?php esc_html_e($this->options['facebook']['capi']['token']); ?></textarea>
		<?php
		self::display_status_icon($this->options['facebook']['capi']['token'], $this->options['facebook']['pixel_id']);
		self::get_documentation_html_by_key('facebook_capi_token');
		self::html_pro_feature();
		if (!$this->options['facebook']['pixel_id']) {
			echo '<p></p><span class="dashicons dashicons-info"></span>';
			esc_html_e('You need to activate the Meta (Facebook) pixel', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '</p><br>';
		}
//        echo self::get_documentation_html('/wgact/?utm_source=woocommerce-plugin&utm_medium=documentation-link&utm_campaign=pixel-manager-for-woocommerce-docs&utm_content=google-ads-conversion-id#/pixels/google-ads?id=configure-the-plugin');
		echo '<br><br>';
//        esc_html_e('The conversion ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
//        echo '&nbsp;<i>123456789</i>';
	}

	public function setting_html_facebook_capi_test_event_code() {

		$text_length = max(strlen($this->options['facebook']['capi']['test_event_code']), 9);
		?>
		<input class="pmw mono"
			   id='pmw_setting_facebook_capi_test_event_code'
			   name='wgact_plugin_options[facebook][capi][test_event_code]'
			   size='<?php esc_html_e($text_length); ?>'
			   type='text'
			   style="width:<?php esc_html_e($text_length); ?>ch"
			   value='<?php esc_html_e($this->options['facebook']['capi']['test_event_code']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php self::display_status_icon($this->options['facebook']['capi']['test_event_code'], $this->options['facebook']['capi']['token'] && $this->options['facebook']['capi']['test_event_code'], true); ?>
		<?php //		self::get_documentation_html_by_key('facebook_capi_test_event_code'); ?>
		<?php self::html_pro_feature(); ?>
		<div style="margin-top: 20px">
			<span class="dashicons dashicons-info" style="margin-right: 10px"></span>
			<?php esc_html_e("The test event code automatically rotates frequently within Facebook. If you don't see the server events flowing in, first make sure that you've set the latest test event code.", 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</div>
		<?php
	}

	public function setting_facebook_capi_user_transparency_process_anonymous_hits() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0'
				   name='wgact_plugin_options[facebook][capi][user_transparency][process_anonymous_hits]'>
			<input type='checkbox' id='wpm_setting_facebook_capi_user_transparency_process_anonymous_hits'
				   name='wgact_plugin_options[facebook][capi][user_transparency][process_anonymous_hits]'
				   value='1'
				<?php checked($this->options['facebook']['capi']['user_transparency']['process_anonymous_hits']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php
			esc_html_e('Send CAPI hits for anonymous visitors who likely have blocked the Meta (Facebook) pixel.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['facebook']['capi']['user_transparency']['process_anonymous_hits'], $this->options['facebook']['pixel_id'], true);
		self::get_documentation_html_by_key('facebook_capi_user_transparency_process_anonymous_hits');
		self::html_pro_feature();
		if ($this->options['facebook']['capi']['user_transparency']['process_anonymous_hits'] && !$this->options['facebook']['pixel_id']) {
			echo '<p></p><span class="dashicons dashicons-info"></span>';
			esc_html_e('You need to activate the Meta (Facebook) pixel', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '</p><br>';
		}
	}

	public function setting_facebook_advanced_matching() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0'
				   name='wgact_plugin_options[facebook][capi][user_transparency][send_additional_client_identifiers]'>
			<input type='checkbox' id='wpm_setting_facebook_capi_user_transparency_send_additional_client_identifiers'
				   name='wgact_plugin_options[facebook][capi][user_transparency][send_additional_client_identifiers]'
				   value='1'
				<?php checked($this->options['facebook']['capi']['user_transparency']['send_additional_client_identifiers']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php
			esc_html_e('Send events with additional visitor identifiers, such as email and phone number, if available.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['facebook']['capi']['user_transparency']['send_additional_client_identifiers'], $this->options['facebook']['pixel_id'], true);
		self::get_documentation_html_by_key('facebook_advanced_matching');
		self::html_pro_feature();
		if ($this->options['facebook']['capi']['user_transparency']['send_additional_client_identifiers'] && !$this->options['facebook']['pixel_id']) {
			echo '<p></p><span class="dashicons dashicons-info"></span>';
			esc_html_e('You need to activate the Meta (Facebook) pixel', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '</p><br>';
		}
	}

	public function setting_html_facebook_microdata() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[facebook][microdata]'>
			<input type='checkbox' id='wpm_setting_facebook_microdata_active'
				   name='wgact_plugin_options[facebook][microdata]'
				   value='1'
				<?php checked($this->options['facebook']['microdata']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php
			esc_html_e('Enable Meta (Facebook) product microdata output', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['facebook']['microdata'], $this->options['facebook']['pixel_id'], true);
		self::get_documentation_html_by_key('facebook_microdata');
		self::html_pro_feature();
		if ($this->options['facebook']['microdata'] && !$this->options['facebook']['pixel_id']) {
			echo '<p></p><span class="dashicons dashicons-info"></span>';
			esc_html_e('You need to activate the Meta (Facebook) pixel', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '</p><br>';
		}
	}

	public function setting_html_pinterest_ad_account_id() {

		$text_length = max(strlen($this->options['pinterest']['ad_account_id']), 40);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_pinterest_ad_account_id'
				name='wgact_plugin_options[pinterest][ad_account_id]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['pinterest']['ad_account_id']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['pinterest']['ad_account_id'], $this->options['pinterest']['pixel_id']);
		self::get_documentation_html_by_key('pinterest_ad_account_id');
		self::html_pro_feature();
	}

	public function setting_html_pinterest_apic_token() {

		?>
		<textarea class="pmw mono"
				  id='pmw_setting_pinterest_apic_token'
				  name='wgact_plugin_options[pinterest][apic][token]'
				  cols='50'
				  rows='2'
		<?php esc_html_e(self::disable_if_demo()); ?>
		><?php esc_html_e($this->options['pinterest']['apic']['token']); ?></textarea>

		<?php
		self::display_status_icon($this->options['pinterest']['apic']['token'], $this->options['pinterest']['pixel_id']);
		self::get_documentation_html_by_key('pinterest_apic_token');
		self::html_pro_feature();
		if (!$this->options['pinterest']['pixel_id']) {
			?>
			<br><span class="dashicons dashicons-info"></span>
			<?php esc_html_e('You need to activate the Pinterest pixel', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			<?php
		}
	}

	public function setting_pinterest_apic_process_anonymous_hits() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0'
				   name='wgact_plugin_options[pinterest][apic][process_anonymous_hits]'>
			<input type='checkbox' id='pmw_setting_pinterest_apic_user_transparency_process_anonymous_hits'
				   name='wgact_plugin_options[pinterest][apic][process_anonymous_hits]'
				   value='1'
				<?php checked($this->options['pinterest']['apic']['process_anonymous_hits']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php
			esc_html_e('Send Events API hits for anonymous visitors who likely have blocked the Pinterest pixel.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['pinterest']['apic']['process_anonymous_hits'], $this->options['pinterest']['pixel_id'] && $this->options['pinterest']['apic']['token'], true);
		self::get_documentation_html_by_key('pinterest_apic_process_anonymous_hits');
		self::html_pro_feature();
		if ($this->options['pinterest']['apic']['process_anonymous_hits'] && !$this->options['pinterest']['pixel_id']) {
			echo '<p></p><span class="dashicons dashicons-info"></span>';
			esc_html_e('You need to activate the Pinterest pixel', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '</p><br>';
		}
	}

	public function setting_pinterest_advanced_matching() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0'
				   name='wgact_plugin_options[pinterest][advanced_matching]'>
			<input type='checkbox' id='pmw_setting_pinterest_user_transparency_advanced_matching'
				   name='wgact_plugin_options[pinterest][advanced_matching]'
				   value='1'
				<?php checked($this->options['pinterest']['advanced_matching']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php
			esc_html_e('Send events with additional visitor identifiers, such as email and phone number, if available.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['pinterest']['advanced_matching'], $this->options['pinterest']['pixel_id'] && $this->options['pinterest']['apic']['token'], true);
		self::get_documentation_html_by_key('pinterest_advanced_matching');
		self::html_pro_feature();
		if ($this->options['pinterest']['advanced_matching'] && !$this->options['pinterest']['pixel_id']) {
			echo '<p></p><span class="dashicons dashicons-info"></span>';
			esc_html_e('You need to activate the Pinterest pixel', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '</p><br>';
		}
	}

	public function setting_html_tiktok_eapi_token() {

		$text_length = max(strlen($this->options['tiktok']['eapi']['token']), 40);

		?>
		<input
				class="pmw mono"
				id='pmw_setting_tiktok_eapi_token'
				name='wgact_plugin_options[tiktok][eapi][token]'
				size='<?php esc_html_e($text_length); ?>'
				type='text'
				value='<?php esc_html_e($this->options['tiktok']['eapi']['token']); ?>'
				style="width:<?php esc_html_e($text_length); ?>ch"
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php
		self::display_status_icon($this->options['tiktok']['eapi']['token'], $this->options['tiktok']['pixel_id']);
		self::get_documentation_html_by_key('tiktok_eapi_token');
		self::html_pro_feature();
		if (!$this->options['tiktok']['pixel_id']) {
			?>
			<br><span class="dashicons dashicons-info"></span>
			<?php esc_html_e('You need to activate the TikTok pixel', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			<?php
		}
//        echo self::get_documentation_html('/wgact/?utm_source=woocommerce-plugin&utm_medium=documentation-link&utm_campaign=pixel-manager-for-woocommerce-docs&utm_content=google-ads-conversion-id#/pixels/google-ads?id=configure-the-plugin');
//        esc_html_e('The conversion ID looks similar to this:', 'woocommerce-google-adwords-conversion-tracking-tag');
//        echo '&nbsp;<i>123456789</i>';
	}

	public function setting_html_tiktok_eapi_test_event_code() {

		$text_length = max(strlen($this->options['tiktok']['eapi']['test_event_code']), 9);

		?>
		<input class="pmw mono"
			   id='pmw_setting_tiktok_eapi_test_event_code'
			   name='wgact_plugin_options[tiktok][eapi][test_event_code]'
			   size='<?php esc_html_e($text_length); ?>'
			   type='text'
			   style="width:<?php esc_html_e($text_length); ?>ch"
			   value='<?php esc_html_e($this->options['tiktok']['eapi']['test_event_code']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php self::display_status_icon($this->options['tiktok']['eapi']['test_event_code'], $this->options['tiktok']['eapi']['token'] && $this->options['tiktok']['eapi']['test_event_code'], true); ?>
		<?php //		self::get_documentation_html_by_key('facebook_capi_test_event_code'); ?>
		<?php self::html_pro_feature(); ?>
		<div style="margin-top: 20px">
			<span class="dashicons dashicons-info" style="margin-right: 10px"></span>
			<?php esc_html_e("The test event code automatically rotates frequently within TikTok. If you don't see the server events flowing in, first make sure that you've set the latest test event code.", 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</div>
		<?php
	}

	public function setting_tiktok_eapi_process_anonymous_hits() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0'
				   name='wgact_plugin_options[tiktok][eapi][process_anonymous_hits]'>
			<input type='checkbox' id='pmw_setting_tiktok_eapi_process_anonymous_hits'
				   name='wgact_plugin_options[tiktok][eapi][process_anonymous_hits]'
				   value='1'
				<?php checked($this->options['tiktok']['eapi']['process_anonymous_hits']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php
			esc_html_e('Send Events API hits for anonymous visitors who likely have blocked the TikTok pixel.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['tiktok']['eapi']['process_anonymous_hits'], $this->options['tiktok']['pixel_id'] && $this->options['tiktok']['eapi']['token'], true);
		self::get_documentation_html_by_key('tiktok_eapi_process_anonymous_hits');
		self::html_pro_feature();
		if ($this->options['tiktok']['eapi']['process_anonymous_hits'] && !$this->options['tiktok']['pixel_id']) {
			echo '<p></p><span class="dashicons dashicons-info"></span>';
			esc_html_e('You need to activate the TikTok pixel', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '</p><br>';
		}
	}

	public function setting_tiktok_advanced_matching() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0'
				   name='wgact_plugin_options[tiktok][advanced_matching]'>
			<input type='checkbox' id='pmw_setting_tiktok_advanced_matching'
				   name='wgact_plugin_options[tiktok][advanced_matching]'
				   value='1'
				<?php checked($this->options['tiktok']['advanced_matching']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>
			<?php
			esc_html_e('Send events with additional visitor identifiers, such as email and phone number, if available.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['tiktok']['advanced_matching'], $this->options['tiktok']['pixel_id'] && $this->options['tiktok']['eapi']['token'], true);
		self::get_documentation_html_by_key('tiktok_advanced_matching');
		self::html_pro_feature();
		if ($this->options['tiktok']['advanced_matching'] && !$this->options['tiktok']['pixel_id']) {
			echo '<p></p><span class="dashicons dashicons-info"></span>';
			esc_html_e('You need to activate the TikTok pixel', 'woocommerce-google-adwords-conversion-tracking-tag');
			echo '</p><br>';
		}
	}

	public function setting_html_order_duplication_prevention() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[shop][order_deduplication]'>
			<input type='checkbox' id='wpm_setting_order_duplication_prevention'
				   name='wgact_plugin_options[shop][order_deduplication]'
				   value='1' <?php checked($this->options['shop']['order_deduplication']); ?>
			/>
			<?php
			$this->get_order_duplication_prevention_text();
			?>
		</label>
		<?php self::display_status_icon($this->options['shop']['order_deduplication']); ?>
		<?php self::get_documentation_html_by_key('duplication_prevention'); ?>

		<br>
		<p>
			<span class="dashicons dashicons-info"></span>
			<?php
			esc_html_e('Only disable order duplication prevention for testing.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</p>
		<p>
			<span class="dashicons dashicons-info"></span>
			<?php
			esc_html_e('Automatically reactivates 6 hours after disabling duplication prevention.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</p>
		<?php
	}

	public function setting_html_maximum_compatibility_mode() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[general][maximum_compatibility_mode]'>
			<input type='checkbox' id='wpm_setting_maximum_compatibility_mode'
				   name='wgact_plugin_options[general][maximum_compatibility_mode]'
				   value='1' <?php checked($this->options['general']['maximum_compatibility_mode']); ?> />
			<?php
			esc_html_e('Enable the maximum compatibility mode', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['general']['maximum_compatibility_mode'], true, true);
		self::get_documentation_html_by_key('maximum_compatibility_mode');
	}

	public function setting_html_disable_tracking_for_user_roles() {
		// https://semantic-ui.com/modules/dropdown.html#multiple-selection
		// https://developer.woocommerce.com/2017/08/08/selectwoo-an-accessible-replacement-for-select2/
		// https://github.com/woocommerce/selectWoo
		?>
		<select id="wpm_setting_disable_tracking_for_user_roles" multiple="multiple"
				name="wgact_plugin_options[shop][disable_tracking_for][]"
				style="width:350px; padding-left: 10px" data-placeholder="Choose roles&hellip;" aria-label="Roles"
				class="wc-enhanced-select"
			<?php esc_html_e(self::disable_if_demo()); ?>
		>
			<?php foreach (get_editable_roles() as $role => $details) : ?>
				<option
						value="<?php esc_html_e($role); ?>" <?php esc_html_e(in_array($role, $this->options['shop']['disable_tracking_for'], true) ? 'selected' : ''); ?>><?php esc_html_e($details['name']); ?></option>
			<?php endforeach; ?>

		</select>
		<script>
			jQuery("#wpm_setting_disable_tracking_for_user_roles").select2({
				// theme: "classic"
			})
		</script>
		<?php
//		self::get_documentation_html_by_key('google_consent_regions');
		self::html_pro_feature();
	}

	public function info_html_acr() {
		esc_html_e('Automatic Conversion Recovery (ACR) is ', 'woocommerce-google-adwords-conversion-tracking-tag');
		self::display_status_icon(wpm_fs()->can_use_premium_code__premium_only());
		self::html_pro_feature();
		self::get_documentation_html_by_key('acr');
	}

	public function info_html_order_list_info() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[shop][order_list_info]'>
			<input type='checkbox' id='pmw_setting_order_list_info'
				   name='wgact_plugin_options[shop][order_list_info]'
				   value='1' <?php checked($this->options['shop']['order_list_info']); ?> />

			<?php
			esc_html_e('Display PMW related information on the order list page', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['shop']['order_list_info']);
		?>
		<?php
		self::get_documentation_html_by_key('order_list_info');
	}

	public function info_html_scroll_tracker_thresholds() {
		?>
		<input class="pmw mono"
			   id='pmw_setting_scroll_tracker_thresholds'
			   name='wgact_plugin_options[general][scroll_tracker_thresholds]'
			   size='40'
			   type='text'
			   value='<?php esc_html_e(implode(',', $this->options['general']['scroll_tracker_thresholds'])); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php self::display_status_icon(!empty($this->options['general']['scroll_tracker_thresholds']), true, true); ?>
		<?php self::get_documentation_html_by_key('scroll_tracker_threshold'); ?>
		<?php self::html_pro_feature(); ?>
		<div style="margin-top: 10px">
			<?php esc_html_e('The Scroll Tracker thresholds. A comma separated list of scroll tracking thresholds in percent where the scroll tracker triggers its events.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</div>
		<?php
	}

	public function html_subscription_value_multiplier() {

		$field_width = max(strlen($this->options['shop']['subscription_value_multiplier']), 3);

		?>
		<input class="pmw mono"
			   id='pmw_setting_subscription_value_multiplier'
			   name='wgact_plugin_options[shop][subscription_value_multiplier]'
			   size='<?php esc_html_e($field_width); ?>'
			   type='text'
			   style="width:<?php esc_html_e($field_width); ?>ch"
			   value='<?php esc_html_e($this->options['shop']['subscription_value_multiplier']); ?>'
			<?php esc_html_e(self::disable_if_demo()); ?>
		/>
		<?php self::get_documentation_html_by_key('subscription_value_multiplier'); ?>
		<?php self::html_pro_feature(); ?>
		<div style="margin-top: 10px">
			<?php esc_html_e('The multiplier multiplies the conversion value output for initial subscriptions to match the CLV of a subscription more closely.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</div>
		<?php
	}

	public function html_lazy_load_pmw() {

		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[general][lazy_load_pmw]'>
			<input type='checkbox' id='pmw_setting_lazy_load_pmw'
				   name='wgact_plugin_options[general][lazy_load_pmw]'
				   value='1' <?php checked($this->options['general']['lazy_load_pmw']); ?>
				<?php esc_html_e(self::disable_if_demo()); ?>
			/>

			<?php
			esc_html_e('Lazy load the Pixel Manager', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['general']['lazy_load_pmw'], Options::lazy_load_requirements(), true);
		?>
		<?php
		self::get_documentation_html_by_key('lazy_load_pmw');
		self::html_pro_feature();
		?>

		<?php if ($this->options['general']['lazy_load_pmw'] && !Options::lazy_load_requirements()) : ?>
			<p>
				<span class="dashicons dashicons-info"></span>
				<?php
				esc_html_e('Google Optimize is active, but the Google Optimize anti flicker snippet is not. You need to activate the Google Optimize anti flicker snippet, or deactivate Google Optimize.', 'woocommerce-google-adwords-conversion-tracking-tag');
				?>
			</p>
		<?php endif; ?>

		<p>
			<span class="dashicons dashicons-info"></span>
			<?php
			esc_html_e('Enabling this feature will give you better page speed scores. Please read the documentation to learn more about the full implications while using this feature.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</p>


		<?php
	}

	private function get_order_duplication_prevention_text() {
		if (wpm_fs()->can_use_premium_code__premium_only()) {
			esc_html_e('Advanced order duplication prevention is ', 'woocommerce-google-adwords-conversion-tracking-tag');
		} else {
			esc_html_e('Basic order duplication prevention is ', 'woocommerce-google-adwords-conversion-tracking-tag');
		}
	}

	private function add_to_cart_requirements_fulfilled() {
		if ($this->options['google']['ads']['conversion_id'] && $this->options['google']['ads']['conversion_label'] && $this->options['google']['ads']['aw_merchant_id']) {
			return true;
		} else {
			return false;
		}
	}

	public function option_html_google_ads_dynamic_remarketing() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[google][ads][dynamic_remarketing]'>
			<input type='checkbox' id='wpm_plugin_option_gads_dynamic_remarketing'
				   name='wgact_plugin_options[google][ads][dynamic_remarketing]'
				   value='1' <?php checked($this->options['google']['ads']['dynamic_remarketing']); ?> />

			<?php
			esc_html_e('Enable dynamic remarketing audience collection', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['google']['ads']['dynamic_remarketing']);
		?>
		<?php
		self::get_documentation_html_by_key('google_ads_dynamic_remarketing');
		self::wistia_video_icon('7fhtv2s94t');
		?>
		<p>
			<span class="dashicons dashicons-info"></span>
			<?php
			esc_html_e('You need to choose the correct product identifier setting in order to match the product identifiers in the product feeds.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</p>
		<?php
	}

	public function option_html_variations_output() {
		// adding the hidden input is a hack to make WordPress save the option with the value zero,
		// instead of not saving it and remove that array key entirely
		// https://stackoverflow.com/a/1992745/4688612
		?>
		<label>
			<input type='hidden' value='0' name='wgact_plugin_options[general][variations_output]'>
			<input type='checkbox' id='wpm_plugin_option_variations_output'
				   name='wgact_plugin_options[general][variations_output]'
				   value='1' <?php checked($this->options['general']['variations_output']); ?>
			/>
			<?php
			esc_html_e('Enable variations output', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</label>
		<?php
		self::display_status_icon($this->options['general']['variations_output'], $this->options['google']['ads']['dynamic_remarketing'], true);
		?>
		<?php
		self::get_documentation_html_by_key('variations_output');
		?>
		<p><span class="dashicons dashicons-info"></span>
			<?php
			esc_html_e('In order for this to work you need to upload your product feed including product variations and the item_group_id. Disable it, if you choose only to upload the parent product for variable products.', 'woocommerce-google-adwords-conversion-tracking-tag');
			?>
		</p>
		<?php
	}

	public function plugin_option_google_business_vertical() {
		?>
		<div style="display: inline-block;">

			<label>
				<input type='radio' id='wpm_plugin_google_business_vertical_0'
					   name='wgact_plugin_options[google][ads][google_business_vertical]'
					   value='0'
					<?php echo( checked(0, $this->options['google']['ads']['google_business_vertical'], false) ); ?>
					<?php esc_html_e(self::disable_if_demo()); ?>
				/>
				<?php esc_html_e('Retail', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			</label>
			<br>
			<label>
				<input type='radio' id='wpm_plugin_google_business_vertical_1'
					   name='wgact_plugin_options[google][ads][google_business_vertical]'
					   value='1'
					<?php echo( checked(1, $this->options['google']['ads']['google_business_vertical'], false) ); ?>
					<?php esc_html_e(self::disable_if_demo()); ?>
				/>
				<?php esc_html_e('Education', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			</label>
			<br>
			<label>
				<input type='radio' id='wpm_plugin_google_business_vertical_3'
					   name='wgact_plugin_options[google][ads][google_business_vertical]'
					   value='3'
					<?php echo( checked(3, $this->options['google']['ads']['google_business_vertical'], false) ); ?>
					<?php esc_html_e(self::disable_if_demo()); ?>
				/>
				<?php esc_html_e('Hotels and rentals', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			</label>
			<br>
			<label>
				<input type='radio' id='wpm_plugin_google_business_vertical_4'
					   name='wgact_plugin_options[google][ads][google_business_vertical]'
					   value='4'
					<?php echo( checked(4, $this->options['google']['ads']['google_business_vertical'], false) ); ?>
					<?php esc_html_e(self::disable_if_demo()); ?>
				/>
				<?php esc_html_e('Jobs', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			</label>
			<br>
			<label>
				<input type='radio' id='wpm_plugin_google_business_vertical_5'
					   name='wgact_plugin_options[google][ads][google_business_vertical]'
					   value='5'
					<?php echo( checked(5, $this->options['google']['ads']['google_business_vertical'], false) ); ?>
					<?php esc_html_e(self::disable_if_demo()); ?>
				/>
				<?php esc_html_e('Local deals', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			</label>
			<br>
			<label>
				<input type='radio' id='wpm_plugin_google_business_vertical_6'
					   name='wgact_plugin_options[google][ads][google_business_vertical]'
					   value='6'
					<?php echo( checked(6, $this->options['google']['ads']['google_business_vertical'], false) ); ?>
					<?php esc_html_e(self::disable_if_demo()); ?>
				/>
				<?php esc_html_e('Real estate', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			</label>
			<br>
			<label>
				<input type='radio' id='wpm_plugin_google_business_vertical_8'
					   name='wgact_plugin_options[google][ads][google_business_vertical]'
					   value='8'
					<?php echo( checked(8, $this->options['google']['ads']['google_business_vertical'], false) ); ?>
					<?php esc_html_e(self::disable_if_demo()); ?>
				/>
				<?php esc_html_e('Custom', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			</label>
		</div>

		<div style="display: inline-block;vertical-align: top;">
			<?php self::html_pro_feature(); ?>
		</div>

		<?php
	}

	public function plugin_setting_aw_merchant_id() {
		?>
		<input class="pmw mono"
			   type="text"
			   id="wpm_plugin_aw_merchant_id"
			   name="wgact_plugin_options[google][ads][aw_merchant_id]"
			   size="40"
			   value="<?php esc_html_e($this->options['google']['ads']['aw_merchant_id']); ?>"
		/>
		<?php self::display_status_icon($this->options['google']['ads']['aw_merchant_id']); ?>
		<?php self::get_documentation_html_by_key('aw_merchant_id'); ?>
		<br><br>
		<?php
		esc_html_e('ID of your Google Merchant Center account. It looks like this: 12345678', 'woocommerce-google-adwords-conversion-tracking-tag');
	}

	public function plugin_option_product_identifier() {
		?>
		<label>
			<input type='radio' id='wpm_plugin_option_product_identifier_0'
				   name='wgact_plugin_options[google][ads][product_identifier]'
				   value='0' <?php echo( checked(0, $this->options['google']['ads']['product_identifier'], false) ); ?>/>
			<?php esc_html_e('post ID (default)', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></label>
		<br>
		<label>
			<input type='radio' id='wpm_plugin_option_product_identifier_2'
				   name='wgact_plugin_options[google][ads][product_identifier]'
				   value='2' <?php echo( checked(2, $this->options['google']['ads']['product_identifier'], false) ); ?>/>
			<?php esc_html_e('SKU', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</label>
		<br>
		<label>
			<input type='radio' id='wpm_plugin_option_product_identifier_1'
				   name='wgact_plugin_options[google][ads][product_identifier]'
				   value='1' <?php echo( checked(1, $this->options['google']['ads']['product_identifier'], false) ); ?>/>
			<?php esc_html_e('ID for the WooCommerce Google Product Feed. Outputs the post ID with woocommerce_gpf_ prefix *', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</label>
		<br>
		<label>
			<input type='radio' id='wpm_plugin_option_product_identifier_3'
				   name='wgact_plugin_options[google][ads][product_identifier]'
				   value='3' <?php echo( checked(3, $this->options['google']['ads']['product_identifier'], false) ); ?>/>
			<?php esc_html_e('ID for the WooCommerce Google Listings & Ads Plugin. Outputs the post ID with gla_ prefix **', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</label>
		<br>
		<p style="margin-top:10px">
			<?php esc_html_e('Choose a product identifier.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</p>
		<br>
		<?php esc_html_e('* This is for users of the WooCommerce Google Product Feed Plugin', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		<a href="https://woocommerce.com/products/google-product-feed/" target="_blank">WooCommerce Google Product Feed
			Plugin</a>
		<br>
		<?php esc_html_e('** This is for users of the WooCommerce Google Listings & Ads Plugin', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		<a href="https://woocommerce.com/products/google-listings-and-ads/" target="_blank">WooCommerce Google Listings
			& Ads Plugin
			Plugin</a>

		<?php
	}

	private function html_beta() {
		return '<div class="pmw-status-icon beta">' . esc_html__('beta', 'woocommerce-google-adwords-conversion-tracking-tag') . '</div>';
	}

	private function html_beta_e( $margin_top = '1px' ) {
		?>
		<div class="pmw-status-icon beta"
			 style="margin-top: <?php esc_html_e($margin_top); ?>"
		>
			<?php esc_html_e('beta', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
		</div>
		<?php
	}

	private static function html_status_icon_active() {
		?>
		<div
				class="pmw-status-icon active"><?php esc_html_e('active', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></div>
		<?php
	}

	private static function html_status_icon_inactive() {
		?>
		<div
				class="pmw-status-icon inactive"><?php esc_html_e('inactive', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></div>
		<?php
	}

	private static function html_status_icon_partially_active() {
		?>
		<div
				class="pmw-status-icon partially-active"><?php esc_html_e('partially active', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></div>
		<?php
	}

	private function html_status_icon_decommissioned_and_disabled() {
		?>
		<div
				class="pmw-status-icon partially-active"><?php esc_html_e('decommissioned and disabled', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></div>
		<?php
	}

	private static function html_pro_feature() {
		if (!wpm_fs()->can_use_premium_code__premium_only() && Options::pro_version_demo_active()) {
//            if (1===1) {

//			return '<div class="pmw-pro-feature">' . esc_html__('Pro Feature', 'woocommerce-google-adwords-conversion-tracking-tag') . '</div>';

			?>
			<span class="pmw-pro-feature">
				<?php esc_html_e('Pro Feature', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
			</span>

			<a href="https://sweetcode.com/plugins/pmw/?open-checkout=&trial=&billing-cycle=annual&utm_source=plugin&utm_medium=start-free-trial-button&utm_campaign=show-pro-version-settings#pricing-section"
			   target="_blank" style="box-shadow: none;">
				<div id="proVersionButton" class="button">
					<?php esc_html_e('Start a free trial', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</div>
			</a>

			<?php
		}
	}

	private static function display_status_icon( $status, $requirements = true, $inactive_silent = false ) {
		if ($status && $requirements) {
			self::html_status_icon_active();
		} elseif ($status && !$requirements) {
			self::html_status_icon_partially_active();
		} elseif (!$inactive_silent) {
			self::html_status_icon_inactive();
		}
	}

	private static function disable_if_demo() {
		if (!wpm_fs()->can_use_premium_code__premium_only() && Options::pro_version_demo_active()) {
			return 'disabled';
		} else {
			return '';
		}
	}

	public function deduper_enable() {
		$this->options['shop']['order_deduplication'] = true;
		update_option('wgact_plugin_options', $this->options);
	}

	private static function wistia_video_icon( $wistia_id, $tooltip_text = '' ) {
		?>

		<script>
			var script   = document.createElement("script")
			script.async = true
			script.src   = 'https://fast.wistia.com/embed/medias/<?php esc_html_e($wistia_id); ?>.jsonp'
			document.getElementsByTagName("head")[0].appendChild(script)
		</script>

		<div class="pmw wistia_embed wistia_async_<?php esc_html_e($wistia_id); ?> popover=true popoverContent=link videoFoam=false"
			 style="display:inline-block;position:relative;text-decoration: none; vertical-align: top;">
			<span class="dashicons dashicons-video-alt3"></span>
		</div>
		<?php
	}
}
