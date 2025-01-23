<?php
/**
 * Functions specific to the Klarna WooCommerce Gateway.
 *
 * @package ga-ecommerce
 */

/**
 * Run the after checkout form trigger without printing anything but allowing MI to make the GA call.
 */
function monsterinsights_klarna_custom_triggers() {
	ob_start();
	do_action( 'woocommerce_after_checkout_form' );
	ob_end_clean();
}

add_action( 'kco_wc_after_checkout_form', 'monsterinsights_klarna_custom_triggers' );
add_action( 'klarna_after_kco_checkout', 'monsterinsights_klarna_custom_triggers' );

/**
 * Force the gateway to finalize the order in the thank you page for tracking purposes.
 */
add_filter( 'klarna_finalize_order_in_thank_you_page', '__return_true' );

/**
 * Check the Klarna Gateway version and add an admin notice if the version is too old.
 */
function monsterinsights_maybe_add_klarna_notice() {
	if ( defined( 'WC_KLARNA_VER' ) && version_compare( WC_KLARNA_VER, '2.5.7' ) < 0 ) {
		add_action( 'admin_notices', 'monsterinsights_klarna_version_notice' );
	}
}

add_action( 'admin_init', 'monsterinsights_maybe_add_klarna_notice' );

/**
 * The Klarna version admin notice.
 */
function monsterinsights_klarna_version_notice() {
	?>
	<div class="error">
		<p><?php echo esc_html__( 'Please install WooCommerce Klarna Gateway version 2.5.7 or newer to use the MonsterInsights eCommerce addon.', 'monsterinsights-ecommerce' ); ?></p>
	</div>
	<?php
}
