<?php

/**
 * Plugin Name:       MonsterInsights - eCommerce Addon
 * Plugin URI:        https://www.monsterinsights.com
 * Description:       Adds eCommerce tracking options to MonsterInsights
 * Author:            MonsterInsights Team
 * Author URI:        https://www.monsterinsights.com
 * Version:           8.5.4
 * Requires at least: 5.6.0
 * Requires PHP:      7.2
 * Text Domain:       monsterinsights-ecommerce
 * Domain Path:       languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 *
 * @since 6.0.0
 *
 * @package MonsterInsights_eCommerce
 * @author  Chris Christoff
 */
class MonsterInsights_eCommerce {

	/**
	 * Holds the class object.
	 *
	 * @since 6.0.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since 6.0.0
	 *
	 * @var string
	 */
	public $version = '8.5.4';

	/**
	 * The name of the plugin.
	 *
	 * @since 6.0.0
	 *
	 * @var string
	 */
	public $plugin_name = 'MonsterInsights eCommerce';

	/**
	 * Unique plugin slug identifier.
	 *
	 * @since 6.0.0
	 *
	 * @var string
	 */
	public $plugin_slug = 'monsterinsights-ecommerce';

	/**
	 * Plugin file.
	 *
	 * @since 6.0.0
	 *
	 * @var string
	 */
	public $file;

	/**
	 * Primary class constructor.
	 *
	 * @since 6.0.0
	 */
	public function __construct() {
		$this->file = __FILE__;

		if ( ! $this->check_compatibility() ) {
			return;
		}

		// Load the plugin textdomain.
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Load the updater
		add_action( 'monsterinsights_updater', array( $this, 'updater' ) );

		// Load the plugin.
		add_action( 'monsterinsights_load_plugins', array( $this, 'init' ), 99 );

		if ( ! defined( 'MONSTERINSIGHTS_PRO_VERSION' ) ) {
			// Make sure plugin is listed in Auto-update Disabled view
			add_filter( 'auto_update_plugin', array( $this, 'disable_auto_update' ), 10, 2 );

			// Display call-to-action to get Pro in order to enable auto-update
			add_filter( 'plugin_auto_update_setting_html', array( $this, 'modify_autoupdater_setting_html' ), 11, 2 );
		}

		register_activation_hook( $this->file, array( $this, 'deactivate_plugins' ) );
	}

	/**
	 * Check compatibility with PHP and WP, and display notices if necessary
	 *
	 * @return bool
	 * @since 8.0.0
	 */
	private function check_compatibility() {
		if ( defined( 'MONSTERINSIGHTS_FORCE_ACTIVATION' ) && MONSTERINSIGHTS_FORCE_ACTIVATION ) {
			return true;
		}

		require_once plugin_dir_path( __FILE__ ) . 'includes/compatibility-check.php';
		$compatibility = MonsterInsights_Ecommerce_Compatibility_Check::get_instance();
		$compatibility->maybe_display_notice();

		return $compatibility->is_php_compatible() && $compatibility->is_wp_compatible();
	}

	/**
	 * Loads the plugin textdomain for translation.
	 *
	 * @since 6.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( $this->plugin_slug, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Loads the plugin into WordPress.
	 *
	 * @since 6.0.0
	 */
	public function init() {

		if ( ! defined( 'MONSTERINSIGHTS_VERSION' ) || ! defined( 'MONSTERINSIGHTS_PRO_VERSION' ) || version_compare( MONSTERINSIGHTS_VERSION, '8.8', '<' ) ) {
			// admin notice, MI not installed
			add_action( 'admin_notices', array( self::$instance, 'requires_monsterinsights' ) );

			return;
		}

		// Load frontend components.
		$this->require_frontend();
	}

	/**
	 * Initializes the addon updater.
	 *
	 * @param string $key The user license key.
	 *
	 * @since 6.0.0
	 *
	 */
	function updater( $key ) {
		$args = array(
			'plugin_name' => $this->plugin_name,
			'plugin_slug' => $this->plugin_slug,
			'plugin_path' => plugin_basename( __FILE__ ),
			'plugin_url'  => trailingslashit( WP_PLUGIN_URL ) . $this->plugin_slug,
			'remote_url'  => 'https://www.monsterinsights.com/',
			'version'     => $this->version,
			'key'         => $key,
		);

		$updater = new MonsterInsights_Updater( $args );
	}

	/**
	 * Display MonsterInsights Pro CTA on Plugins -> autoupdater setting column
	 *
	 * @param string $html
	 * @param string $plugin_file
	 *
	 * @return string
	 */
	public function modify_autoupdater_setting_html( $html, $plugin_file ) {
		if (
			plugin_basename( __FILE__ ) === $plugin_file &&
			// If main plugin (free) happens to be enabled and already takes care of this, then bail
			! apply_filters( 'monsterinsights_is_autoupdate_setting_html_filtered_' . $plugin_file, false )
		) {
			$html = sprintf(
				'<a href="%s">%s</a>',
				'https://www.monsterinsights.com/docs/go-lite-pro/?utm_source=liteplugin&utm_medium=plugins-autoupdate&utm_campaign=upgrade-to-autoupdate&utm_content=monsterinsights-ecommerce',
				__( 'Enable the MonsterInsights PRO plugin to manage auto-updates', 'monsterinsights-ecommerce' )
			);
		}

		return $html;
	}

	/**
	 * Disable auto-update.
	 *
	 * @param $update
	 * @param $item
	 *
	 * @return bool
	 */
	public function disable_auto_update( $update, $item ) {
		// If this is multisite and is not on the main site, return early.
		if ( is_multisite() && ! is_main_site() ) {
			return $update;
		}

		if ( isset( $item->id ) && plugin_basename( __FILE__ ) === $item->id ) {
			return false;
		}

		return $update;
	}

	/**
	 * Loads all frontend files into scope.
	 *
	 * @since 6.0.0
	 */
	public function require_frontend() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-monsterinsights-ecommerce-helper.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/providers/ecommerce-integration.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/providers/edd.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/providers/woocommerce.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/providers/memberpress.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/providers/lifterlms.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/providers/restrict-content-pro.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/providers/givewp.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/providers/easy-affiliate.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/providers/membermouse.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/providers/wishlistmember.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/providers/charitable.php';

		$tracking_code_v4  = function_exists( 'monsterinsights_get_v4_id' ) && monsterinsights_get_v4_id();
		$has_tracking      = ! empty( $tracking_code_v4 );
		$enhanced_commerce = version_compare( MONSTERINSIGHTS_VERSION, '7.0.0', '>' );

		if ( $has_tracking && $enhanced_commerce ) {

			if ( class_exists( 'Easy_Digital_Downloads' ) && apply_filters( 'monsterinsights_track_edd', true ) ) {
				MonsterInsights_eCommerce_EDD_Integration::get_instance();
			}

			if ( class_exists( 'WooCommerce' ) && apply_filters( 'monsterinsights_track_woocommerce', true ) ) {
				MonsterInsights_eCommerce_WooCommerce_Integration::get_instance();

				if ( class_exists( 'WC_Gateway_Klarna' ) ) {
					require_once plugin_dir_path( __FILE__ ) . 'includes/providers/woocommerce-klarna.php';
				}
			}

			if ( defined( 'MEPR_VERSION' ) && version_compare( MEPR_VERSION, '1.3.43', '>' ) && apply_filters( 'monsterinsights_track_memberpress', true ) ) {
				MonsterInsights_eCommerce_MemberPress_Integration::get_instance();
			}

			if ( function_exists( 'llms' ) && version_compare( llms()->version, '3.32.0', '>=' ) && apply_filters( 'monsterinsights_track_lifterlms', true ) ) {
				MonsterInsights_eCommerce_LifterLMS_Integration::get_instance();
			}
			if ( function_exists( 'Give' ) && apply_filters( 'monsterinsights_track_givewp', true ) ) {
				MonsterInsights_eCommerce_GiveWP_Integration::get_instance();
			}

			if ( class_exists( 'Restrict_Content_Pro' ) && version_compare( RCP_PLUGIN_VERSION, '3.5.4', '>=' ) && apply_filters( 'monsterinsights_track_rcp', true ) ) {
				MonsterInsights_eCommerce_RCP_Integration::get_instance();
			}

			if ( class_exists( 'MemberMouse' ) && apply_filters( 'monsterinsights_track_mm', true ) ) {
				MonsterInsights_eCommerce_MemberMouse_Integration::get_instance();
			}

			if ( function_exists( 'wishlistmember_instance' ) ) {
				MonsterInsights_eCommerce_WishListMember_Integration::get_instance();
			}

			if ( class_exists( 'Charitable' ) && apply_filters( 'monsterinsights_track_charitable', true ) ) {
				MonsterInsights_eCommerce_Charitable_Integration::get_instance();
			}

			//          if (class_exists('Easy_Digital_Downloads') && apply_filters('monsterinsights_track_edd', true)) {
			//              new MonsterInsights_GA_EDD_eCommerce_Tracking();
			//          }
			//
			//          if (class_exists('WooCommerce') && apply_filters('monsterinsights_track_woocommerce', true)) {
			//              new MonsterInsights_GA_Woo_eCommerce_Tracking();
			//          }
			//
			//          if (defined('MEPR_VERSION') && version_compare(MEPR_VERSION, '1.3.43', '>') && apply_filters('monsterinsights_track_memberpress', true)) {
			//              new MonsterInsights_GA_MemberPress_eCommerce_Tracking();
			//          }
			//
			//          if (function_exists('LLMS') && version_compare(LLMS()->version, '3.32.0', '>=') && apply_filters('monsterinsights_track_lifterlms', true)) {
			//              new MonsterInsights_GA_LifterLMS_eCommerce_Tracking();
			//          }
			//          if (function_exists('Give') && apply_filters('monsterinsights_track_givewp', true)) {
			//              new MonsterInsights_GA_GiveWP_Tracking();
			//          }
			//
			//          if (class_exists('Restrict_Content_Pro') && version_compare(RCP_PLUGIN_VERSION, '3.5.4', '>=') && apply_filters('monsterinsights_track_rcp', true)) {
			//              new MonsterInsights_GA_RCP_eCommerce_Tracking();
			//          }
		}
	}

	/**
	 * Output a nag notice if the user does not have MI installed
	 *
	 * @access public
	 * @return    void
	 * @since 6.0.0
	 *
	 */
	public function requires_monsterinsights() {
		?>
		<div class="error">
			<p><?php echo esc_html__( 'Please install MonsterInsights Pro version 8.8 or newer to use the MonsterInsights eCommerce addon.', 'monsterinsights-ecommerce' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The MonsterInsights_eCommerce object.
	 * @since 6.0.0
	 *
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof MonsterInsights_eCommerce ) ) {
			self::$instance = new MonsterInsights_eCommerce();
		}

		return self::$instance;
	}

	/**
	 * Check if authed and disable other WooCommerce GA integrations to prevent skewing the data permanently.
	 */
	public function deactivate_plugins() {
		if ( function_exists( 'MonsterInsights' ) ) {
			if ( MonsterInsights()->auth->get_v4_id() ) {
				deactivate_plugins(
					array(
						'woocommerce-google-analytics-integration/woocommerce-google-analytics-integration.php',
						'enhanced-e-commerce-for-woocommerce-store/enhanced-ecommerce-google-analytics.php',
					)
				);
			}
		}
	}
}

// Load the main plugin class.
$monsterinsights_ecommerce = MonsterInsights_eCommerce::get_instance();
