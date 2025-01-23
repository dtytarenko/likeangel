<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MonsterInsights_eCommerce_Charitable_Integration extends MonsterInsights_Enhanced_eCommerce_Integration {

	// Holds instance of eCommerce object to ensure no double instantiation of hooks
	private static $instance;

	/**
	 * Meta key name for storing GA client_id.
	 *
	 * @var string
	 */
	private $ga_client_id_meta_key = '_monsterinsights_ga_client_id';

	/**
	 * Create singleton instance.
	 *
	 * @return MonsterInsights_eCommerce_Charitable_Integration
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Add required hooks.
	 *
	 * @return void
	 */
	private function hooks() {
		// Save session for purchase event.
		add_action( 'charitable_after_save_donation', array( $this, 'after_save_donation' ), 10, 1 );

		// Trigger purchase event.
		add_action( 'charitable_donation_status_charitable-completed', array( $this, 'trigger_purchase_event' ), 10, 1 );

		// Trigger refund event.
		add_action( 'charitable_donation_status_charitable-refunded', array( $this, 'trigger_refund_event' ), 10, 1 );

		// Trigger view_item event.
		add_action( 'charitable_campaign_summary_before', array( $this, 'trigger_view_item_event' ), 10, 1 );

		// Trigger begin_checkout event.
		add_action( 'charitable_donation_form_before', array( $this, 'donation_form_before' ), 10, 1 );
	}

	/**
	 * After the donation has been saved in the database.
	 * Store necessary ID as meta to use later in purchase.
	 *
	 * @param int $donation_id
	 *
	 * @return void
	 */
	public function after_save_donation( $donation_id ) {
		// Save client_id to donation meta.
		if ( $client_id = monsterinsights_get_client_id() ) {
			update_post_meta( $donation_id, $this->ga_client_id_meta_key, $client_id );
		}

		// Save GA session_id to donation meta.
		if ( $measurement_id = monsterinsights_get_v4_id_to_output() ) {
			$this->save_user_session_id( $donation_id, $measurement_id );
		}
	}

	/**
	 * Charitable status changes.
	 *
	 * @param Charitable_Donation $donation
	 *
	 * @return void
	 */
	public function trigger_purchase_event( $donation ) {
		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::charitable_test_mode() ) {
			return;
		}

		$is_in_ga = get_post_meta( $donation->ID, '_monsterinsights_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_do_transaction_skip_ga', false, $donation->ID );

		// If it's already in GA or filtered to skip, then skip adding
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		monsterinsights_mp_collect_v4( $this->prepare_mp_donation_data( $donation, 'purchase' ) );

		update_post_meta( $donation->ID, '_monsterinsights_is_in_ga', 'yes' );
	}

	/**
	 * Trigger view_item event through frontend.
	 *
 	 * @param Charitable_Campaign $campaign The Campaign instance.
	 *
	 * @return void
	 */
	public function trigger_view_item_event( $campaign ) {
		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::charitable_test_mode() ) {
			return;
		}

		// If page reload, then return.
		if ( MonsterInsights_eCommerce_Helper::is_page_reload() ) {
			return;
		}

		$event_js = sprintf(
			"__gtagTracker( 'event', 'view_item', { items: [%s] });",
			json_encode( $this->get_item_data( $campaign ) )
		);

		$this->enqueue_js( 'event', $event_js );
	}

	/**
	 * Trigger on checkout page/form.
	 * Send begin_checkout event to GA.
	 *
	 * @param  Charitable_Donation_Form $form The donation form object.
	 */
	public function donation_form_before( $form ) {
		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::charitable_test_mode() ) {
			return;
		}

		// If page reload, then return.
		if ( MonsterInsights_eCommerce_Helper::is_page_reload() ) {
			return;
		}

		$event_js = sprintf(
			"__gtagTracker( 'event', 'begin_checkout', { items: [%s] });",
			json_encode( $this->get_item_data( $form->get_campaign() ) )
		);

		$this->enqueue_js( 'event', $event_js );
	}

	/**
	 * Find default or minimum price for campaign.
	 *
 	 * @param Charitable_Campaign $campaign The Campaign instance.
	 * @return int|float
	 */
	private function get_campaign_price( $campaign ) {
		$default = charitable_sanitize_amount( $campaign->get_suggested_donations_default() );

		if ( empty( $default ) ) {
			$min = $campaign->get_meta( '_campaign_minimum_donation_amount' );
			return empty( $min ) ? 0 : charitable_sanitize_amount( $min );
		}

		return $default;
	}

	/**
	 * Prepare data for item to send to GA.
	 *
 	 * @param Charitable_Campaign $campaign The Campaign instance.
	 * @return array
	 */
	private function get_item_data( $campaign ) {
		return array(
			'item_id'   => (string) $campaign->ID,
			'item_name' => $campaign->post_title,
			'price'     => $this->get_campaign_price( $campaign ),
			'quantity'  => 1,
		);
	}

	/**
	 * Get donation items.
	 *
	 * @param Charitable_Donation $donation
	 */
	private function get_donation_items( $donation ) {
		$items = array();

		foreach ( $donation->get_campaign_donations() as $campaign_donation ) {
			$items[] = array(
				'item_id'   => (string) $campaign_donation->campaign_id,
				'item_name' => $campaign_donation->campaign_name,
				'price'     => $campaign_donation->amount,
				'quantity'  => 1,
			);
		}

		return $items;
	}

	/**
	 * Send refund to GA.
	 *
	 * @param Charitable_Donation $donation
	 */
	public function trigger_refund_event( $donation ) {
		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::charitable_test_mode() ) {
			return;
		}

		$is_in_ga = get_post_meta( $donation->ID, '_monsterinsights_refund_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_undo_transaction_skip_ga', false, $donation->ID );

		// If it's already in GA or filtered to skip, then skip adding
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		monsterinsights_mp_collect_v4( $this->prepare_mp_donation_data( $donation, 'refund' ) );

		update_post_meta( $donation->ID, '_monsterinsights_refund_is_in_ga', 'yes' );
	}

	/**
	 * Prepare donation data to send to GA.
	 *
	 * @param Charitable_Donation $donation
	 * @param string $event_name
	 */
	public function prepare_mp_donation_data( $donation, $event_name ) {
		return array(
			'client_id' => get_post_meta( $donation->ID, $this->ga_client_id_meta_key, true ),
			'events'    => array(
				array(
					'name'   => $event_name,
					'params' => array(
						'transaction_id' => $donation->ID,
						'value'          => $donation->get_total(),
						'items'          => $this->get_donation_items( $donation ),
						'session_id'     => get_post_meta( $donation->ID, '_monsterinsights_ga_session_id', true )
					),
				)
			),
		);
	}
}
