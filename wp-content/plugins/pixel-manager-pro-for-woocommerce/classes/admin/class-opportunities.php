<?php

/**
 * Class Opportunities
 *
 * Show opportunities in a PMW tab
 *
 * @package PMW
 * @since   1.27.11
 *
 * Available opportunities
 *          pro
 *            Meta CAPI
 *            Google Ads Enhanced Conversions
 *            Google Ads Conversion Adjustments
 *            Pinterest Enhanced Match
 *            Subscription Multiplier
 *
 *          free
 *            Dynamic Remarketing
 *            Dynamic Remarketing Variations Output
 *            Google Ads Conversion Cart Data
 *
 *  TODO: TikTok EAPI
 *  TODO: Newsletter subscription
 *  TODO: Upgrade to Premium version
 *  TODO: Gateway accuracy warning
 *  TODO: Detect WooCommerce GA Integration (rule, only if one, GA3 or GA4 are enabled)
 *  TODO: Detect MonsterInsights
 *  TODO: Detect Tatvic
 *  TODO: Detect WooCommerce Conversion Tracking
 *  TODO: Opportunity to use the SweetCode Google Automated Discounts plugin
 *
 */

namespace WCPM\Classes\Admin;

use WCPM\Classes\Options;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Class Opportunities
 *
 * Manages the opportunities tab.
 * Contains HTML templates.
 *
 * @package WCPM\Classes\Admin
 * @since   1.28.0
 */
class Opportunities {

	public static $pmw_opportunities_option = 'pmw_opportunities';

	public static function html() {
		?>
		<div>
			<div>
				<p>
					<?php esc_html_e('Opportunities show how you could tweak the plugin settings to get more out of the Pixel Manager.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</p>
			</div>
			<div>
				<h2>
					<?php esc_html_e('Available Opportunities', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></h2>
			</div>

			<!-- Opportunities -->

			<?php self::opportunities_not_dismissed(); ?>

			<div>
				<h2>
					<?php esc_html_e('Dismissed Opportunities', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></h2>
			</div>
			<div id="pmw-dismissed-opportunities">
				<?php self::opportunities_dismissed(); ?>
			</div>
		</div>
		<?php
	}

	private static function opportunities_not_dismissed() {
		foreach (self::get_opportunities() as $opportunity) {
			if ($opportunity::is_not_dismissed()) {
				$opportunity::output_card();
			}
		}
	}

	private static function opportunities_dismissed() {
		foreach (self::get_opportunities() as $opportunity) {
			if ($opportunity::is_dismissed()) {
				$opportunity::output_card();
			}
		}
	}

	public static function card_html( $card_data, $custom_middle_html = null ) {

		$main_card_classes = [
			'pmw',
			'opportunity-card',
		];

		if ($card_data['dismissed']) {
			$main_card_classes[] = 'dismissed';
		}

		?>
		<div id="pmw-opportunity-<?php esc_html_e($card_data['id']); ?>"
			 class="<?php esc_html_e(implode(' ', $main_card_classes)); ?>"
		>
			<!-- top -->
			<div class="pmw opportunity-card-top">
				<div><b><?php esc_html_e($card_data['title']); ?></b></div>
				<div class="pmw opportunity-card-top-right">
					<div class="pmw opportunity-card-top-impact">
						<?php esc_html_e('Impact', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>:
					</div>
					<div class="pmw opportunity-card-top-impact-level">
						<?php esc_html_e($card_data['impact']); ?>
					</div>
				</div>
			</div>

			<hr class="pmw opportunity-card-hr">

			<!-- middle -->
			<div class="pmw opportunity-card-middle">

				<?php if (!empty($custom_middle_html)) : ?>
					<?php esc_html_e($custom_middle_html); ?>
				<?php else : ?>
					<?php foreach ($card_data['description'] as $description) : ?>
						<p class="pmw opportunity-card-description">
							<?php esc_html_e($description); ?>
						</p>
					<?php endforeach; ?>
				<?php endif; ?>

			</div>

			<hr class="pmw opportunity-card-hr">

			<!-- bottom -->
			<div class="pmw opportunity-card-bottom">

				<?php if (isset($card_data['setup_video'])) : ?>
					<!-- Video Link-->
					<div>
						<script>
							var script   = document.createElement("script")
							script.async = true
							script.src   = 'https://fast.wistia.com/embed/medias/<?php esc_html_e($card_data['setup_video']); ?>.jsonp'
							document.getElementsByTagName("head")[0].appendChild(script)
						</script>

						<div class="pmw opportunities wistia_embed wistia_async_<?php esc_html_e($card_data['setup_video']); ?> popover=true popoverContent=link videoFoam=false"
							 style="display:inline-block;height:123;position:relative;width:150;text-decoration: none; vertical-align: top;">
							<span class="dashicons dashicons-video-alt3" style="font-size: 36px"></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if (isset($card_data['setup_link'])) : ?>
					<!-- Setup Link-->
					<a class="pmw opportunity-card-button-link"
					   href="<?php esc_html_e($card_data['setup_link']); ?>"
					   target="_blank"
					>
						<div class="pmw opportunity-card-bottom-button">
							<?php esc_html_e('Setup', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
						</div>
					</a>
				<?php endif; ?>


				<?php if (isset($card_data['learn_more_link'])) : ?>
					<!-- Learn More Link-->
					<a class="pmw opportunity-card-button-link"
					   href="<?php esc_html_e($card_data['learn_more_link']); ?>"
					   target="_blank"
					>
						<div class="pmw opportunity-card-bottom-button">
							<?php esc_html_e('Learn more', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
						</div>
					</a>
				<?php endif; ?>

				<?php if (empty($card_data['dismissed'])) : ?>
					<!-- Dismiss Link-->
					<a class="pmw opportunity-card-button-link"
					   href="#"
					>
						<div class="pmw opportunity-dismiss opportunity-card-bottom-button"
							 data-opportunity-id="<?php esc_html_e($card_data['id']); ?>">
							<?php esc_html_e('Dismiss', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
						</div>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private static function get_opportunities() {
		$classes       = get_declared_classes();
		$opportunities = [];

		foreach ($classes as $class) {
			if (is_subclass_of($class, 'WCPM\Classes\Admin\Opportunity')) {
				$opportunities[] = $class;
			}
		}

		return $opportunities;
	}

	public static function active_opportunities_available() {

		// get pmw_opportunities option
		$option = get_option(self::$pmw_opportunities_option);

		foreach (self::get_opportunities() as $opportunity) {
			if (class_exists($opportunity)) {
				if (
					$opportunity::available()
					&& $opportunity::is_not_dismissed()
					&& $opportunity::is_newer_than_dismissed_dashboard_time($option)
				) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Dismisses the dashboard notification.
	 *
	 * @return void
	 * @since 1.28.0
	 */
	public static function dismiss_dashboard_notification() {

		$option = get_option(self::$pmw_opportunities_option);

		if (empty($option)) {
			$option = [];
		}

		$option['dashboard_notification_dismissed'] = time();

		update_option(self::$pmw_opportunities_option, $option);

		wp_send_json_success();
	}

	public static function dismiss_opportunity( $opportunity_id ) {

		$option = get_option(self::$pmw_opportunities_option);

		if (empty($option)) {
			$option = [];
		}

		$option[$opportunity_id]['dismissed'] = time();

		update_option(self::$pmw_opportunities_option, $option);

		wp_send_json_success();
	}
}

/**
 * Abstract class Opportunity
 *
 * @since 1.28.0
 */
abstract class Opportunity {

	/**
	 * Check if the opportunity is available.
	 *
	 * @return bool
	 * @since 1.28.0
	 */
	abstract public static function available();

	public static function not_available() {
		return !static::available();
	}

	abstract public static function card_data();

	public static function custom_middle_cart_html() {
		return null;
	}

	public static function output_card() {

		if (static::not_available()) {
			return;
		}

		$card_data              = static::card_data();
		$card_data['dismissed'] = static::is_dismissed();

		Opportunities::card_html($card_data, static::custom_middle_cart_html());
	}

	public static function is_dismissed() {

		$option = get_option(Opportunities::$pmw_opportunities_option);

		if (empty($option)) {
			return false;
		}

		if (isset($option[static::card_data()['id']]['dismissed'])) {
			return true;
		}

		return false;
	}

	public static function is_not_dismissed() {
		return !static::is_dismissed();
	}


	public static function is_newer_than_dismissed_dashboard_time( $option ) {

		if (empty($option)) {
			return true;
		}

		if (!isset($option['dashboard_notification_dismissed'])) {
			return true;
		}

		if (static::card_data()['since'] > $option['dashboard_notification_dismissed']) {
			return true;
		}

		return false;
	}
}

/**
 * Opportunities that are only available in the pro version
 */
if (wpm_fs()->can_use_premium_code__premium_only()) {

	/**
	 * Opportunity: Google Ads Enhanced Conversions
	 *
	 * @since 1.28.0
	 */
	class Google_Ads_Enhanced_Conversions extends Opportunity {

		public static function available() {

			// Google Ads purchase conversion must be enabled
			if (!Options::is_google_ads_purchase_conversion_enabled()) {
				return false;
			}

			// Enhanced conversions must be disabled
			if (Options::is_google_ads_enhanced_conversions_active()) {
				return false;
			}

			return true;
		}

		public static function card_data() {

			return [
				'id'              => 'google-ads-enhanced-conversions',
				'title'           => esc_html__(
					'Google Ads Enhanced Conversions',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				'description'     => [
					esc_html__(
						'The Pixel Manager detected that Google Ads purchase conversion is enabled, but Google Ads Enhanced Conversions has yet to be enabled.',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
					esc_html__(
						'Enabling Google Ads Enhanced Conversions will help you track more conversions that otherwise would get lost, such as cross-device conversions.',
						'woocommerce-google-adwords-conversion-tracking-tag	'
					),
				],
				'impact'          => esc_html__(
					'high',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				'setup_link'      => Documentation::get_link('google_ads_enhanced_conversions'),
				'learn_more_link' => Documentation::get_link('opportunity_google_ads_enhanced_conversions'),
				'since'           => 1672895375, // timestamp
			];
		}
	}

	/**
	 * Opportunity: Google Ads Conversion Adjustments
	 *
	 * @since 1.28.0
	 */
	class Google_Ads_Conversion_Adjustments extends Opportunity {

		public static function available() {

			// Google Ads purchase conversion must be enabled
			if (!Options::is_google_ads_purchase_conversion_enabled()) {
				return false;
			}

			// Conversion Adjustments conversions must be disabled
			if (Options::is_google_ads_conversion_adjustments_active()) {
				return false;
			}

			return true;
		}

		public static function card_data() {

			return [
				'id'              => 'google-ads-conversion-adjustments',
				'title'           => esc_html__(
					'Google Ads Conversion Adjustments',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				'description'     => [
					esc_html__(
						'The Pixel Manager detected that Google Ads purchase conversion is enabled, but Google Ads Conversion Adjustments has yet to be enabled.',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
					esc_html__(
						'Enabling Google Ads Conversion Adjustments will improve conversion value accuracy by adjusting existing conversion values after processing refunds and cancellations.',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
				],
				'impact'          => esc_html__(
					'high',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				'setup_link'      => Documentation::get_link('google_ads_conversion_adjustments'),
				'learn_more_link' => Documentation::get_link('opportunity_google_ads_conversion_adjustments'),
				'since'           => 1672895375, // timestamp
			];
		}
	}

	/**
	 * Opportunity: Pinterest Enhanced Match
	 *
	 * @since 1.28.1
	 */
	class Pinterest_Enhanced_Match extends Opportunity {

		public static function available() {

			// Google Ads purchase conversion must be enabled
			if (!Options::is_pinterest_active()) {
				return false;
			}

			// Conversion Adjustments conversions must be disabled
			if (Options::is_pinterest_enhanced_match_enabled()) {
				return false;
			}

			return true;
		}

		public static function card_data() {

			return [
				'id'          => 'pinterest-enhanced-match',
				'title'       => esc_html__(
					'Pinterest Enhanced Match',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				'description' => [
					esc_html__(
						'The Pixel Manager detected that Pinterest is enabled, but Pinterest Enhanced Match has yet to be enabled.',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
					esc_html__(
						'Enabling Pinterest Enhanced Match will improve conversion tracking accuracy when no Pinterest cookie is present and with cross-device checkouts.',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
				],
				'impact'      => esc_html__(
					'medium',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				'setup_link'  => Documentation::get_link('pinterest_enhanced_match'),
				//				'learn_more_link' => '#',
				'since'       => 1672895375, // timestamp
			];
		}
	}

	/**
	 * Opportunity: Subscription Multiplier
	 *
	 * Checks for the WooCommerce Subscriptions plugin.
	 * Doesn't check for YITH WooCommerce Subscription plugin, because that plugin doesn't register a proper subscription product type.
	 * Doesn't check for WP Swings WooCommerce Subscription plugin, because that plugin doesn't register a proper subscription product type.
	 *
	 * @since 1.28.1
	 */
	class Subscription_Multiplier extends Opportunity {

		public static function available() {

			// WooCommerce Subscriptions must be active
			if (!Environment::is_woocommerce_subscriptions_active()) {
				return false;
			}

			// Subscription Multiplier must be 1
			if (Options::get_subscription_multiplier() != 1) {
				return false;
			}

			return true;
		}

		public static function card_data() {

			return [
				'id'          => 'subscription-multiplier',
				'title'       => esc_html__(
					'Subscription Multiplier',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				'description' => [
					esc_html__(
						'The Pixel Manager detected that a WooCommerce Subscriptions plugin is enabled, but the Subscription Multiplier is still set to 1.00.',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
					esc_html__(
						'Setting a value in the Subscription Multiplier field will multiply the conversion value of subscription products by the specified value to better match the lifetime value of the subscription. This will improve campaign optimization.',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
				],
				'impact'      => esc_html__(
					'high',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				'setup_link'  => Documentation::get_link('subscription_value_multiplier'),
				//				'learn_more_link' => '#',
				'since'       => 1672895375, // timestamp
			];
		}
	}

	/**
	 * Opportunity: Meta CAPI
	 *
	 * @since 1.29.2
	 */
	class Meta_CAPI extends Opportunity {

		public static function available() {

			// Facebook Pixel must be enabled
			if (!Options::is_facebook_active()) {
				return false;
			}

			// Facebook CAPI must be disabled
			if (Options::is_facebook_capi_enabled()) {
				return false;
			}

			return true;
		}

		public static function card_data() {

			return [
				'id'          => 'meta-capi',
				'title'       => esc_html__(
					'Meta (Facebook) CAPI',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				'description' => [
					esc_html__(
						'The Pixel Manager detected that the Meta (Facebook) Pixel is enabled, but Meta (Facebook) CAPI has yet to be enabled.',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
					esc_html__(
						'Enabling Meta (Facebook) CAPI will improve conversion tracking accuracy overall.',
						'woocommerce-google-adwords-conversion-tracking-tag'
					),
				],
				'impact'      => esc_html__(
					'high',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				'setup_link'  => Documentation::get_link('facebook_capi_token'),
				//				'learn_more_link' => '#',
				'since'       => 1673553471, // timestamp
			];
		}
	}
} // end if (wpm_fs()->can_use_premium_code__premium_only())

/**
 * Opportunity: Google Ads Conversion Cart Data
 *
 * @since 1.28.0
 */
class Google_Ads_Conversion_Cart_Data extends Opportunity {

	public static function available() {

		// Google Ads purchase conversion must be enabled
		if (!Options::is_google_ads_purchase_conversion_enabled()) {
			return false;
		}

		// Conversion Cart Data must be disabled
		if (Options::is_google_ads_conversion_cart_data_enabled()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'          => 'google-ads-conversion-cart-data',
			'title'       => esc_html__(
				'Google Ads Conversion Cart Data',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description' => [
				esc_html__(
					'The Pixel Manager detected that Google Ads purchase conversion is enabled, but Google Ads Conversion Cart Data has yet to be enabled.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enabling Google Ads Conversion Cart Data will improve reporting by including cart item data in your Google Ads conversion reports.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			],
			'impact'      => esc_html__(
				'medium',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'  => Documentation::get_link('aw_merchant_id'),
			//			'learn_more_link' => '#',
			'since'       => 1672895375, // timestamp
		];
	}
}

/**
 * Opportunity: Dynamic Remarketing
 *
 * @since 1.28.0
 */
class Dynamic_Remarketing extends Opportunity {

	public static function available() {

		// At least one paid ads pixel must be enabled
		if (!Options::is_at_least_one_marketing_pixel_active()) {
			return false;
		}

		// Dynamic Remarketing must be disabled
		if (Options::is_dynamic_remarketing_enabled()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'          => 'dynamic-remarketing',
			'title'       => esc_html__(
				'Dynamic Remarketing',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description' => [
				esc_html__(
					'The Pixel Manager detected that at least one paid ads pixel is enabled, but Dynamic Remarketing has yet to be enabled.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enabling Dynamic Remarketing output will allow you to collect dynamic audiences (such as general visitors, product viewers, cart abandoners, and buyers) and create dynamic remarketing campaigns.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			],
			'impact'      => esc_html__(
				'medium',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'  => Documentation::get_link('google_ads_dynamic_remarketing'),
			'setup_video' => '7fhtv2s94t',
			//			'learn_more_link' => '#',
			'since'       => 1672895375, // timestamp
		];
	}
}

/**
 * Opportunity: Dynamic Remarketing Variations Output
 *
 * @since 1.28.0
 */
class Dynamic_Remarketing_Variations_Output extends Opportunity {

	public static function available() {

		// At least one paid ads pixel must be enabled
		if (!Options::is_at_least_one_marketing_pixel_active()) {
			return false;
		}

		// Dynamic Remarketing must be disabled
		if (!Options::is_dynamic_remarketing_enabled()) {
			return false;
		}

		// Dynamic Remarketing Variations Output must be disabled
		if (Options::is_dynamic_remarketing_variations_output_enabled()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'          => 'dynamic-remarketing-variations-output',
			'title'       => esc_html__(
				'Dynamic Remarketing Variations Output',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description' => [
				esc_html__(
					'The Pixel Manager detected that at least one paid ads pixel is enabled, Dynamic Remarketing is enabled, but Variations Output has yet to be enabled.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enabling Dynamic Remarketing Variations Output will allow you to collect more fine-grained, dynamic audiences down to the product variation level.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'When enabling this setting, you also need to upload product variations to your catalogs.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			],
			'impact'      => esc_html__(
				'low',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'  => Documentation::get_link('variations_output'),
			//			'learn_more_link' => '#',
			'since'       => 1672895375, // timestamp
		];
	}
}
