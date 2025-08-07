<?php
/**
 * Look Slider functionality
 * Відображає товари з образу на сторінці товару
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------
 * 1. Збираємо ID товарів образу
 * ------------------------------------------------- */
function la_collect_look_ids() {
	if ( ! is_product() ) return [];
	global $product;

	$bundle_ids = maybe_unserialize(
		get_post_meta( $product->get_id(), 'woodmart_fbt_bundles_id', true )
	);
	if ( ! $bundle_ids ) return [];

	$ids = [];
	foreach ( (array) $bundle_ids as $bid ) {
		$items = maybe_unserialize( get_post_meta( $bid, '_woodmart_fbt_products', true ) );
		foreach ( (array) $items as $it ) {
			if ( empty( $it['id'] ) ) continue;
			$obj = wc_get_product( (int) $it['id'] );
			if ( ! $obj ) continue;
			$ids[] = $obj->is_type( 'variation' ) ? $obj->get_parent_id() : $obj->get_id();
		}
	}
	return array_unique( $ids );
}

/* -------------------------------------------------
 * 2. Шорткод  [look_slider]
 * ------------------------------------------------- */
add_shortcode( 'look_slider', function () {

	$ids = la_collect_look_ids();
	if ( ! $ids ) return '';

	$q = new WP_Query( [
		'post_type'        => 'product',
		'post__in'         => $ids,
		'orderby'          => 'post__in',
		'posts_per_page'   => -1,            // змініть, якщо треба обмежити
		'post_status'      => 'publish',
		'suppress_filters' => true,          // показати even hidden
	] );
	if ( ! $q->have_posts() ) return '';

	$carousel_id = 'la-look-slider-' . uniqid();
	
	ob_start(); ?>
	<div id="<?php echo esc_attr( $carousel_id ); ?>"
	     class="wd-carousel-container wd-products-element wd-products products">

		<div class="wd-carousel-inner">
			<div class="wd-carousel wd-grid" 
			     data-carousel-id="<?php echo esc_attr( $carousel_id ); ?>"
			     data-desktop-columns="3" 
			     data-tablet-columns="2" 
			     data-mobile-columns="2"
			     data-speed="800"
			     data-autoplay="false"
			     data-loop="false"
			     data-scroll-per-page="false">
				
				<div class="wd-carousel-wrap">
					<?php
					while ( $q->have_posts() ) {
						$q->the_post();
						echo '<div class="wd-carousel-item">';
						wc_get_template_part( 'content', 'product' );
						echo '</div>';
					}
					wp_reset_postdata();
					?>
				</div><!-- /.wd-carousel-wrap -->
			</div><!-- /.wd-carousel -->
		</div><!-- /.wd-carousel-inner -->
	</div><!-- /#<?php echo esc_attr( $carousel_id ); ?> -->
	<?php
	return ob_get_clean();
} );

/* -------------------------------------------------
 * 3. Додаємо  wd-carousel-item  карткам
 * ------------------------------------------------- */
add_filter( 'woocommerce_post_class', function ( $classes ) {
	if ( is_product() && in_the_loop() && ! in_array( 'wd-carousel-item', $classes, true ) ) {
		$classes[] = 'wd-carousel-item';
	}
	return $classes;
}, 20 );

/* -------------------------------------------------
 * 4. Точкова ініціалізація слайдера після завантаження
 * ------------------------------------------------- */
add_action( 'wp_footer', function () {
	if ( ! is_product() ) return; ?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		const $slider = jQuery('#la-look-slider');
		if (!$slider.length) return;

		/* рідна функція Woodmart */
		if (window.woodmartThemeModule?.carousels) {
			woodmartThemeModule.carousels($slider);
		}
	});

	/* якщо Elementor / AJAX підгрузить контент повторно */
	jQuery(function ($) {
		$('body').on('wdProductsLoaded wdUpdateCarousel', function () {
			const $slider = $('#la-look-slider');
			if ($slider.length && !$slider.hasClass('wd-initialized') &&
				window.woodmartThemeModule?.carousels) {
				woodmartThemeModule.carousels($slider);
			}
		});
	});
	</script>
<?php } );