<?php

class MonsterInsights_Enhanced_eCommerce_Integration {

	/**
	 * Queued events and impression JavaScript *
	 *
	 * @var array
	 */
	protected $queued_js = array();

	/**
	 * Funnel steps.
	 *
	 * @var array
	 */
	protected $funnel_steps = array();

	/**
	 * MonsterInsights_Enhanced_eCommerce_Integration constructor.
	 */
	public function __construct() {
		$this->generic_hooks();
	}

	/**
	 * Get the tracking mode, if the core plugin was not yet updated, default to analytics.
	 *
	 * @return string
	 * @deprecated Since 8.3 with the removal of ga compatibility
	 */
	protected function get_tracking_mode() {
		$tracking = 'analytics';

		if ( method_exists( MonsterInsights(), 'get_tracking_mode' ) ) {
			$tracking = MonsterInsights()->get_tracking_mode();
		}

		return $tracking;
	}

	/**
	 * Hooks to run for all integrations.
	 */
	protected function generic_hooks() {
		// If ec.js isn't already requested, add it now.
		add_filter( 'monsterinsights_frontend_tracking_options_analytics_before_scripts', array(
			$this,
			'require_ec'
		), 10, 1 );

		// Dual Tracking helpers
		add_filter( 'monsterinsights_tracking_after_gtag', array( $this, 'print_dual_tracking_js' ), 11, 1 );

		// If we have queued JS to print, print it now.
		// Impression JS.
		add_filter( 'wp_footer', array( $this, 'print_impressions_js' ), 11, 1 );

		// Event JS.
		add_filter( 'wp_footer', array( $this, 'print_events_js' ), 11, 1 );
	}

	/**
	 * Require eCommerce.
	 *
	 * @param array $options The options for the frontend tracking script.
	 *
	 * @return array
	 */
	public function require_ec( $options ) {
		if ( empty( $options['ec'] ) ) {
			$options['ec'] = "'require', 'ec'";
		}

		return $options;
	}

	public function print_dual_tracking_js() {
		$v4_id = monsterinsights_get_v4_id_to_output();  // phpcs:ignore

		if ( ! $v4_id ) {
			return;
		}

		$attr_string = function_exists( 'monsterinsights_get_frontend_analytics_script_atts' )
			? monsterinsights_get_frontend_analytics_script_atts()
			: ' type="text/javascript" data-cfasync="false"';
		?>
		<script<?php echo $attr_string; // phpcs:ignore ?>>
			window.MonsterInsightsDualTracker.helpers.mapProductItem = function (uaItem) {
				var prefixIndex, prefixKey, mapIndex;

				var toBePrefixed = ['id', 'name', 'list_name', 'brand', 'category', 'variant'];

				var item = {};

				var fieldMap = {
					'price': 'price',
					'list_position': 'index',
					'quantity': 'quantity',
					'position': 'index',
				};

				for (mapIndex in fieldMap) {
					if (uaItem.hasOwnProperty(mapIndex)) {
						item[fieldMap[mapIndex]] = uaItem[mapIndex];
					}
				}

				for (prefixIndex = 0; prefixIndex < toBePrefixed.length; prefixIndex++) {
					prefixKey = toBePrefixed[prefixIndex];
					if (typeof uaItem[prefixKey] !== 'undefined') {
						item['item_' + prefixKey] = uaItem[prefixKey];
					}
				}

				return item;
			};

			MonsterInsightsDualTracker.trackers['view_item_list'] = function (parameters) {
				var items = parameters.items;
				var listName, itemIndex, item, itemListName;
				var lists = {
					'_': {items: [], 'send_to': monsterinsights_frontend.v4_id},
				};

				for (itemIndex = 0; itemIndex < items.length; itemIndex++) {
					item = MonsterInsightsDualTracker.helpers.mapProductItem(items[itemIndex]);

					if (typeof item['item_list_name'] === 'undefined') {
						lists['_'].items.push(item);
					} else {
						itemListName = item['item_list_name'];
						if (typeof lists[itemListName] === 'undefined') {
							lists[itemListName] = {
								'items': [],
								'item_list_name': itemListName,
								'send_to': monsterinsights_frontend.v4_id,
							};
						}

						lists[itemListName].items.push(item);
					}
				}

				for (listName in lists) {
					__gtagDataLayer('event', 'view_item_list', lists[listName]);
				}
			};

			MonsterInsightsDualTracker.trackers['select_content'] = function (parameters) {
				const items = parameters.items.map(MonsterInsightsDualTracker.helpers.mapProductItem);
				__gtagDataLayer('event', 'select_item', {items: items, send_to: parameters.send_to});
			};

			MonsterInsightsDualTracker.trackers['view_item'] = function (parameters) {
				const items = parameters.items.map(MonsterInsightsDualTracker.helpers.mapProductItem);
				__gtagDataLayer('event', 'view_item', {items: items, send_to: parameters.send_to});
			};
		</script>
		<?php
	}

	private function print_impressions_gtag( $attr ) {
		?>
		<script<?php echo $attr; // phpcs:ignore ?>>__gtagTracker('event', 'view_item_list', {items: <?php echo wp_json_encode( $this->queued_js['impression'] ); ?> })</script>
		<?php
	}

	/**
	 * If any impressions JS is needed on the page, print it as a script.
	 *
	 * @param $options
	 */
	public function print_impressions_js( $options ) {
		// If tracking for user is disabled, so will the JS. So don't output.
		if ( ! monsterinsights_track_user() ) {
			return $options;
		}

		if ( empty( $this->queued_js['impression'] ) ) {
			return;
		}

		$attr_string = function_exists( 'monsterinsights_get_frontend_analytics_script_atts' ) ? monsterinsights_get_frontend_analytics_script_atts() : ' type="text/javascript" data-cfasync="false"';

		?>
		<!-- MonsterInsights Enhanced eCommerce Impression JS -->
		<?php
		$this->print_impressions_gtag( $attr_string );
		?>
		<!-- / MonsterInsights Enhanced eCommerce Impression JS -->
		<?php
	}

	/**
	 * If any events JS is needed on the page print it as a script.
	 *
	 * @param array $options
	 */
	public function print_events_js( $options ) {
		// If tracking for user is disabled, so will the JS. So don't output.
		if ( ! monsterinsights_track_user() ) {
			return $options;
		}

		if ( empty( $this->queued_js['event'] ) ) {
			return;
		}

		$attr_string = function_exists( "monsterinsights_get_frontend_analytics_script_atts" ) ? monsterinsights_get_frontend_analytics_script_atts() : ' type="text/javascript" data-cfasync="false"';

		ob_start(); ?>
		<!-- MonsterInsights Enhanced eCommerce Event JS -->
		<script<?php echo $attr_string; // phpcs:ignore ?>>
			<?php
			foreach ( $this->queued_js['event'] as $code ) {
				echo $code . "\n"; // phpcs:ignore
			}
			?>
		</script>
		<!-- / MonsterInsights Enhanced eCommerce Event JS -->
		<?php
		echo ob_get_clean(); // phpcs:ignore
	}

	/**
	 * @param $type
	 * @param $javascript
	 */
	public function enqueue_js( $type, $javascript ) {

		if ( ! isset( $this->queued_js[ $type ] ) ) {
			$this->queued_js[ $type ] = array();
		}
		$this->queued_js[ $type ][] = $javascript;
	}

	/**
	 * @param       $event_name
	 * @param array $args
	 *
	 * @deprecated Since 8.3 with the removal of ga compatibility
	 */
	protected function js_record_event( $event_name, $args = array() ) {
		if ( ! is_array( $args ) ) {
			return;
		}

		$args = array(
			'hitType'        => isset( $args['hitType'] ) ? $args['hitType'] : 'event',     // Required
			'eventCategory'  => isset( $args['eventCategory'] ) ? $args['eventCategory'] : 'page',      // Required
			'eventAction'    => isset( $args['eventAction'] ) ? $args['eventAction'] : $event_name, // Required
			'eventLabel'     => isset( $args['eventLabel'] ) ? $args['eventLabel'] : null,
			'eventValue'     => isset( $args['eventValue'] ) ? $args['eventValue'] : null,
			'nonInteraction' => isset( $args['nonInteraction'] ) ? $args['nonInteraction'] : false,
		);

		// Remove blank args.
		unset( $args[''] );

		foreach ( $args as $key => $value ) {
			if ( empty( $value ) ) {
				unset( $args[ $key ] );
			}
		}

		$this->enqueue_js( 'event', $this->get_event_js( $args ) );
	}

	/**
	 * Get the correct event code based on the type of tracking.
	 *
	 * @param $args
	 *
	 * @deprecated Since 8.3 with the removal of ga compatibility
	 */
	protected function get_event_js( $args ) {
		return false;
	}

	/**
	 * @param       $event_key
	 * @param array $args
	 *
	 * @return string
	 * @deprecated Since 8.3 with the removal of ga compatibility
	 *
	 */
	protected function get_funnel_js( $event_key, $args = array() ) {
		return '';
	}

	/**
	 * @param $event_key
	 *
	 * @return mixed|string
	 */
	protected function get_funnel_step( $event_key ) {
		$step = '';
		if ( isset( $this->funnel_steps[ $event_key ] ) && isset( $this->funnel_steps[ $event_key ]['step'] ) ) {
			$step = $this->funnel_steps[ $event_key ]['step'];
		}

		return $step;
	}

	/**
	 * @param $event_key
	 *
	 * @return mixed|string
	 */
	protected function get_funnel_action( $event_key ) {
		$action = '';
		if ( isset( $this->funnel_steps[ $event_key ] ) && isset( $this->funnel_steps[ $event_key ]['action'] ) ) {
			$action = $this->funnel_steps[ $event_key ]['action'];
		}

		return $action;
	}

	/**
	 * Add impression js from a single place so we can use different tracking options.
	 *
	 * @param array $data The data for the impression.
	 */
	protected function add_impression( $data ) {
		$to_replace = array(
			'list'     => 'list_name',
			'position' => 'list_position',
		);
		foreach ( $to_replace as $analytics => $gtag ) {
			if ( isset( $data[ $analytics ] ) ) {
				$data[ $gtag ] = $data[ $analytics ];
				unset( $data[ $analytics ] );
			}
		}

		$js = $data;

		$this->enqueue_js( 'impression', $js );
	}

	/**
	 * @param $data
	 *
	 * @retun string
	 */
	protected function get_add_product_js( $data ) {
		return sprintf( "__gtagTracker( 'event', 'select_content', { content_type : 'product', items: [ %s ] } );", wp_json_encode( $data ) );
	}

    protected function save_user_session_id( $payment_id, $measurement_id ) {
        if ( function_exists( 'monsterinsights_get_browser_session_id' ) ) {
            $session_id = monsterinsights_get_browser_session_id($measurement_id);
            update_post_meta($payment_id, '_monsterinsights_ga_session_id', $session_id);
        }
    }
}
