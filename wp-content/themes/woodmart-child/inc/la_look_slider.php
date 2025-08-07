<?php
/**
 * Look Slider functionality
 * Відображає товари з образу на сторінці товару
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function la_collect_look_ids() {
	if ( ! is_product() ) { return []; }
	global $product;

	$bundle_ids = maybe_unserialize(
		get_post_meta( $product->get_id(), 'woodmart_fbt_bundles_id', true )
	);
	if ( ! $bundle_ids ) { return []; }

	$ids = [];
	foreach ( (array) $bundle_ids as $bid ) {
		$items = maybe_unserialize( get_post_meta( $bid, '_woodmart_fbt_products', true ) );
		foreach ( (array) $items as $it ) {
			if ( empty( $it['id'] ) ) { continue; }
			$obj = wc_get_product( (int) $it['id'] );
			if ( ! $obj ) { continue; }
			$ids[] = $obj->is_type( 'variation' ) ? $obj->get_parent_id() : $obj->get_id();
		}
	}
	return array_unique( $ids );
}

add_shortcode( 'look_slider', function () {

	$ids = la_collect_look_ids();
	if ( ! $ids ) { return ''; }

	if ( is_product() ) { $ids = array_diff( $ids, [ get_the_ID() ] ); }
	if ( ! $ids ) { return ''; }

	$q = new WP_Query( [
		'post_type'        => 'product',
		'post__in'         => $ids,
		'orderby'          => 'post__in',
		'posts_per_page'   => -1,
		'post_status'      => 'publish',
		'suppress_filters' => true,
	] );
	if ( ! $q->have_posts() ) { return ''; }

	$slider_id = 'la-look-slider-' . uniqid();

	ob_start(); ?>
	<h3 class="la-look-title wd-el-title title element-title">ЗБЕРІТЬ ВЕСЬ ОБРАЗ</h3>

	<div id="<?php echo esc_attr( $slider_id ); ?>"
	     class="la-look-slider wd-carousel-container wd-products-element wd-products products wd-stretch-cont-lg"
	     data-lg="3" data-md="2" data-sm="2"
	     data-loop="false" data-scroll-per-page="false">
		<div class="wd-carousel-inner">
			<div class="wd-carousel wd-grid"
			     style="--wd-col-lg:2.5;--wd-col-md:2;--wd-col-sm:2;
			            --wd-gap-lg:20px;--wd-gap-sm:10px;">
				<div class="wd-carousel-wrap">
					<?php while ( $q->have_posts() ) { $q->the_post(); ?>
						<div class="wd-carousel-item la-look-slider-item">
							<?php wc_get_template_part( 'content', 'product' ); ?>
						</div>
					<?php } wp_reset_postdata(); ?>
				</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
} );

add_action( 'wp_footer', function () {
	if ( ! is_product() ) { return; } ?>
	<script>
	(function ($) {
		function init($el) {
			if ($el.length && !$el.hasClass('wd-initialized')
			    && window.woodmartThemeModule?.carousels) {
				woodmartThemeModule.carousels($el);
			}
		}
		$(function () { $('.la-look-slider').each(function () { init($(this)); }); });
		$('body').on('wdProductsLoaded wdUpdateCarousel', function () {
			$('.la-look-slider').each(function () { init($(this)); });
		});
	})(jQuery);
	</script>
<?php } );
